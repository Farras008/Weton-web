<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/PaymentService.php';
require_once __DIR__ . '/../lib/DokuCheckoutService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
$raw = (string) file_get_contents('php://input');
$headers = array_change_key_case(function_exists('getallheaders') ? getallheaders() : [], CASE_LOWER);
if ($headers === []) foreach ($_SERVER as $name => $value) if (str_starts_with($name, 'HTTP_')) $headers[strtolower(str_replace('_', '-', substr($name, 5)))] = $value;
try {
    if (!(new DokuCheckoutService())->verifyNotification($raw, $headers, DokuCheckoutService::notificationRequestTarget())) throw new InvalidArgumentException('Invalid notification signature.');
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    $invoice = trim((string) ($data['order']['invoice_number'] ?? ''));
    $amount = (int) ($data['order']['amount'] ?? 0);
    $status = strtoupper(trim((string) ($data['transaction']['status'] ?? '')));
    if ($invoice === '' || $amount !== PaymentService::AMOUNT) throw new InvalidArgumentException('Invalid notification payload.');
    $payment = PaymentService::findByOrderId($invoice);
    if (!$payment || (int) $payment['amount'] !== $amount) throw new InvalidArgumentException('Payment does not match.');

    // Checkout may notify FAILED while the customer can retry a method; only SUCCESS is final.
    // QRIS notifications identify the payment with emoney_payment.approval_code;
    // other Checkout channels may provide transaction.original_request_id instead.
    $transactionId = trim((string) ($data['transaction']['original_request_id'] ?? $data['emoney_payment']['approval_code'] ?? ''));
    if ($status !== 'SUCCESS' || $transactionId === '') throw new InvalidArgumentException('Invalid DOKU transaction status.');
    PaymentService::markPaid($invoice, [
        'amount' => $amount,
        'reference' => $transactionId,
        'statusMessage' => 'PAID',
    ], (string) ($data['channel']['id'] ?? 'QRIS'));
    http_response_code(200); echo 'OK';
} catch (InvalidArgumentException|JsonException $e) {
    error_log('Invalid DOKU notification received'); http_response_code(400); echo 'Invalid notification';
} catch (Throwable $e) {
    error_log('DOKU notification error: ' . $e->getMessage()); http_response_code(500); echo 'Notification processing error';
}
