<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/PaymentService.php';
require_once __DIR__ . '/../lib/DuitkuService.php';

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
$orderId = trim((string) ($_POST['merchantOrderId'] ?? '')); $amount = trim((string) ($_POST['amount'] ?? '')); $code = trim((string) ($_POST['merchantCode'] ?? '')); $signature = trim((string) ($_POST['signature'] ?? ''));
$resultCode = trim((string) ($_POST['resultCode'] ?? ''));
try {
    $duitku = new DuitkuService();
    if ($orderId === '' || $amount === '' || $signature === '' || $resultCode === '' || !hash_equals($duitku->merchantCode(), $code)) throw new InvalidArgumentException('Parameter callback tidak valid.');
    if (!$duitku->validCallbackSignature($amount, $orderId, $signature)) { error_log('Weton invalid Duitku callback signature for ' . $orderId); throw new InvalidArgumentException('Signature tidak valid.'); }
    $payment = PaymentService::findByOrderId($orderId);
    if (!$payment || (string) $payment['amount'] !== $amount || (int) $payment['amount'] !== PaymentService::AMOUNT) throw new InvalidArgumentException('Transaksi callback tidak cocok.');

    // SUCCESS is final. A duplicate callback may only retry an unsent email.
    if ($payment['status'] === 'SUCCESS') {
        if ($payment['email_sent_at'] === null) PaymentService::sendPendingEmail($orderId);
        http_response_code(200); echo 'OK'; exit;
    }

    $callbackMessage = trim((string) ($_POST['statusMessage'] ?? $_POST['paymentMessage'] ?? ''));
    $callbackAction = duitku_callback_action($payment['status'], $resultCode, null, $callbackMessage);
    if ($callbackAction === 'pending') {
        database()->prepare("UPDATE payments SET payment_message=? WHERE merchant_order_id=? AND status='PENDING'")->execute([$callbackMessage ?: 'Pembayaran sedang diproses', $orderId]);
        http_response_code(200); echo 'OK'; exit;
    }
    if ($callbackAction === 'failed' || $callbackAction === 'expired') {
        database()->prepare("UPDATE payments SET status=?, payment_message=? WHERE merchant_order_id=? AND status='PENDING'")->execute([strtoupper($callbackAction), $callbackMessage ?: strtoupper($callbackAction), $orderId]);
        http_response_code(200); echo 'OK'; exit;
    }

    $verified = $duitku->checkTransaction($orderId);
    $verifyAction = duitku_callback_action($payment['status'], '00', (string) ($verified['statusCode'] ?? ''), (string) ($verified['statusMessage'] ?? ''));
    if ($verifyAction === 'success') PaymentService::markSuccessAndSend($orderId, $verified, (string) ($_POST['paymentCode'] ?? ''));
    elseif ($verifyAction === 'failed' || $verifyAction === 'expired') database()->prepare("UPDATE payments SET status=?, payment_message=? WHERE merchant_order_id=? AND status='PENDING'")->execute([strtoupper($verifyAction), $verified['statusMessage'] ?? strtoupper($verifyAction), $orderId]);
    elseif ($verifyAction === 'pending') database()->prepare("UPDATE payments SET payment_message=? WHERE merchant_order_id=? AND status='PENDING'")->execute([$verified['statusMessage'] ?? 'Pembayaran sedang diproses', $orderId]);
    http_response_code(200); echo 'OK';
} catch (InvalidArgumentException $e) { error_log('Weton invalid callback received'); http_response_code(400); echo 'Invalid callback';
} catch (Throwable $e) { error_log('Weton callback processing error'); http_response_code(500); echo 'Callback processing error'; }
