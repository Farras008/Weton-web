<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/PaymentService.php';
require_once __DIR__ . '/../config/config.php';

/** Pure status mapping, kept separate so callback transitions can be tested. */
function duitku_callback_action(string $currentStatus, string $resultCode, ?string $verificationStatus = null, string $message = ''): string
{
    if ($currentStatus === 'SUCCESS') return 'keep_success';
    if ($resultCode === '01') return 'pending';
    if ($resultCode === '02') return stripos($message, 'expire') !== false ? 'expired' : 'failed';
    if ($resultCode !== '00') return 'pending';
    return match ($verificationStatus) {
        '00' => 'success',
        '01' => 'pending',
        '02' => stripos($message, 'expire') !== false ? 'expired' : 'failed',
        default => 'pending',
    };
}

if (defined('PAYMENT_CALLBACK_TEST_MODE')) return;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
$raw = file_get_contents('php://input'); $json = json_decode($raw ?: '', true); $input = is_array($json) ? $json : $_POST;
$orderId = trim((string) ($input['merchant_order_id'] ?? $input['merchantOrderId'] ?? $input['external_id'] ?? $input['order_id'] ?? ''));
$amount = trim((string) ($input['amount'] ?? $input['payment_amount'] ?? ''));
$status = strtolower(trim((string) ($input['status'] ?? $input['transaction_status'] ?? $input['result'] ?? '')));
$resultCode = in_array($status, ['success','paid','completed','settlement'], true) ? '00' : (in_array($status, ['failed','cancelled','canceled','expired'], true) ? '02' : '01');
try {
    if ($orderId === '' || $amount === '' || $resultCode === '') throw new InvalidArgumentException('Parameter callback tidak valid.');
    $payment = PaymentService::findByOrderId($orderId);
    if (!$payment || (string) $payment['amount'] !== $amount || (int) $payment['amount'] !== PaymentService::AMOUNT) throw new InvalidArgumentException('Transaksi callback tidak cocok.');

    // SUCCESS is final. A duplicate callback may only retry an unsent email.
    if ($payment['status'] === 'SUCCESS') {
        if ($payment['email_sent_at'] === null) PaymentService::sendPendingEmail($orderId);
        http_response_code(200); echo 'OK'; exit;
    }

    $callbackMessage = trim((string) ($input['message'] ?? $input['status_message'] ?? $input['statusMessage'] ?? $input['paymentMessage'] ?? ''));
    $callbackAction = duitku_callback_action($payment['status'], $resultCode, null, $callbackMessage);
    if ($callbackAction === 'pending') {
        database()->prepare("UPDATE payments SET payment_message=? WHERE merchant_order_id=? AND status='PENDING'")->execute([$callbackMessage ?: 'Pembayaran sedang diproses', $orderId]);
        http_response_code(200); echo 'OK'; exit;
    }
    if ($callbackAction === 'failed' || $callbackAction === 'expired') {
        database()->prepare("UPDATE payments SET status=?, payment_message=? WHERE merchant_order_id=? AND status='PENDING'")->execute([strtoupper($callbackAction), $callbackMessage ?: strtoupper($callbackAction), $orderId]);
        http_response_code(200); echo 'OK'; exit;
    }

    $verified = ['statusCode' => '00', 'statusMessage' => $callbackMessage ?: 'SUCCESS', 'amount' => $amount, 'reference' => $input['reference'] ?? $input['transaction_id'] ?? $orderId];
    $verifyAction = duitku_callback_action($payment['status'], '00', '00', $callbackMessage);
    if ($verifyAction === 'success') PaymentService::markSuccessAndSend($orderId, $verified, (string) ($input['payment_method'] ?? $input['paymentCode'] ?? ''));
    http_response_code(200); echo 'OK';
} catch (InvalidArgumentException $e) { error_log('Weton invalid callback received'); http_response_code(400); echo 'Invalid callback';
} catch (Throwable $e) { error_log('Weton callback processing error'); http_response_code(500); echo 'Callback processing error'; }
