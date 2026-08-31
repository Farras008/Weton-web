<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../lib/PaymentService.php';
require_once __DIR__ . '/../lib/DokuCheckoutService.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success' => false, 'message' => 'Method Not Allowed']); exit; }
$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;
if (!hash_equals($_SESSION['payment_csrf'] ?? '', (string) ($input['csrf'] ?? '')) || ($input['product'] ?? '') !== 'weton_full') {
    http_response_code(422); echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid. Silakan hitung ulang weton Anda.']); exit;
}
$email = trim((string) ($input['email'] ?? '')); $birthDate = trim((string) ($input['birth_date'] ?? '')); $birthTime = trim((string) ($input['birth_time'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) { http_response_code(422); echo json_encode(['success' => false, 'message' => 'Masukkan alamat email yang valid.']); exit; }
$payment = null;
try {
    $payment = PaymentService::create($email, $birthDate, $birthTime);
    $response = (new DokuCheckoutService())->createPayment($payment);
    $dokuPayment = $response['payment'];
    PaymentService::markInvoiceCreated($payment['merchant_order_id'], [
        'reference' => (string) ($response['uuid'] ?? $dokuPayment['token_id'] ?? $payment['merchant_order_id']),
        'statusMessage' => 'DOKU Checkout dibuat',
    ]);
    $_SESSION['doku_orders'][$payment['merchant_order_id']] = true;
    DokuCheckoutService::recordFrontendResponse($payment['merchant_order_id'], (int) $payment['amount']);
    echo json_encode(['success' => true, 'invoice' => $payment['merchant_order_id'], 'paymentUrl' => $dokuPayment['url']]);
} catch (Throwable $e) {
    $invoiceForLog = is_array($payment) ? (string) ($payment['merchant_order_id'] ?? '-') : '-';
    error_log('DOKU create payment failed: invoice=' . $invoiceForLog . ' error_type=' . get_class($e));
    // Preserve a diagnostic already written by the DOKU HTTP layer, but never
    // let an older application trace hide this request's database metadata.
    $lastTrace = DokuCheckoutService::lastCreateDiagnostic();
    $hasCurrentDokuTrace = is_array($lastTrace)
        && ($lastTrace['stage'] ?? null) === 'doku_http_response'
        && ($lastTrace['invoice'] ?? null) === $invoiceForLog;
    if (!$hasCurrentDokuTrace || $e instanceof PaymentDatabaseException) {
        DokuCheckoutService::recordApplicationFailure($invoiceForLog, is_array($payment) ? (int) ($payment['amount'] ?? 0) : PaymentService::AMOUNT, $e);
    }
    if (is_array($payment) && isset($payment['merchant_order_id'])) {
        try {
            database()->prepare("UPDATE payments SET status='FAILED', payment_message=? WHERE merchant_order_id=? AND status='PENDING'")
                ->execute(['Pembayaran belum dapat dibuat', $payment['merchant_order_id']]);
        } catch (Throwable $updateError) {
            error_log('DOKU create failure status update failed: invoice=' . $invoiceForLog . ' error_type=' . get_class($updateError));
        }
    }
    http_response_code(502); echo json_encode(['success' => false, 'message' => 'Pembayaran belum dapat dibuat. Silakan coba lagi.']);
}
