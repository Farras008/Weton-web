<?php
declare(strict_types=1);
putenv('DOKU_ENV=sandbox'); putenv('DOKU_CLIENT_ID=MCH-TEST'); putenv('DOKU_SECRET_KEY=secret-test');
require_once __DIR__ . '/../lib/DokuCheckoutService.php';
require_once __DIR__ . '/../lib/PaymentService.php';

$body = '{"order":{"amount":5000,"invoice_number":"WETON-20260831-ABC123"}}';
$service = new DokuCheckoutService();
$requestId = 'request-123';
$timestamp = '2026-08-31T01:00:00Z';
$digest = base64_encode(hash('sha256', $body, true));
$createTarget = '/checkout/v1/payment';
$createSignature = $service->signatureFor($requestId, $timestamp, $createTarget, $body);
$createComponent = "Client-Id:MCH-TEST\nRequest-Id:$requestId\nRequest-Timestamp:$timestamp\nRequest-Target:$createTarget\nDigest:" . $digest;
$createExpected = 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $createComponent, 'secret-test', true));
if (!hash_equals($createExpected, $createSignature)) throw new RuntimeException('DOKU create-payment signature mismatch.');

$notificationTarget = '/api/doku/notification';
$notificationSignature = $service->signatureFor($requestId, $timestamp, $notificationTarget, $body);
if (!$service->verifyNotification($body, ['client-id' => 'MCH-TEST', 'request-id' => $requestId, 'request-timestamp' => $timestamp, 'signature' => $notificationSignature], $notificationTarget)) throw new RuntimeException('DOKU notification signature rejected.');
if ($service->verifyNotification($body, ['client-id' => 'MCH-TEST', 'request-id' => $requestId, 'request-timestamp' => $timestamp, 'signature' => $createSignature], $notificationTarget)) throw new RuntimeException('Notification accepted a signature for the create-payment target.');

$liveResponse = json_decode('{"message":["SUCCESS"],"response":{"order":{"amount":"5000","invoice_number":"WETON-20260831-C098E999","currency":"IDR","session_id":"16c8cc38114e4d55ac89d0a097b16f43","callback_url":"https://weton.online/payment/doku-result.php?invoice=WETON-20260831-C098E999","auto_redirect":false},"payment":{"payment_method_types":["QRIS"],"payment_due_date":60,"token_id":"16c8cc38114e4d55ac89d0a097b16f4320262231182203856","url":"https://staging.doku.com/checkout-link-v2/16c8cc38114e4d55ac89d0a097b16f4320262231182203856","expired_date":"20260831192203","expired_datetime":"2026-08-31T12:22:03Z"}}}', true, 512, JSON_THROW_ON_ERROR);
$checkout = $service->extractCheckoutResponse($liveResponse);
if (($checkout['payment']['url'] ?? null) !== 'https://staging.doku.com/checkout-link-v2/16c8cc38114e4d55ac89d0a097b16f4320262231182203856') throw new RuntimeException('DOKU nested response.payment.url was not extracted.');
try {
    $service->extractCheckoutResponse(['response' => ['payment' => ['url' => 'http://example.test/checkout']]]);
    throw new RuntimeException('Non-HTTPS checkout URL was accepted.');
} catch (InvalidArgumentException) {
    // Expected: checkout URLs must be HTTPS.
}

$pdoException = new PDOException('Table missing');
$pdoException->errorInfo = ['42S02', 1146, 'Table missing'];
$databaseException = new PaymentDatabaseException('payments_insert_pending', $pdoException);
if ($databaseException->operation !== 'payments_insert_pending' || $databaseException->sqlState !== '42S02' || $databaseException->driverCode !== 1146) {
    throw new RuntimeException('PDO diagnostic metadata was not preserved.');
}

$traceMethod = new ReflectionMethod(DokuCheckoutService::class, 'safePdoMessage');
$unknownColumn = new PDOException("SQLSTATE[42S22]: Column not found: 1054 Unknown column 'legacy_provider_reference' in 'field list'");
if ($traceMethod->invoke(null, $unknownColumn) !== "Unknown column 'legacy_provider_reference' in field list") {
    throw new RuntimeException('PDO unknown-column diagnostic message was not safely preserved.');
}
$sensitive = new PDOException("Access denied for user 'customer@example.com' using password: secret-key phone=081234567890");
if ($traceMethod->invoke(null, $sensitive) !== 'PDO error message suppressed for safety.') {
    throw new RuntimeException('Sensitive PDO details were not suppressed.');
}
echo "DOKU Checkout signature tests passed\n";
