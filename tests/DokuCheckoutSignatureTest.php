<?php
declare(strict_types=1);
putenv('DOKU_ENV=sandbox'); putenv('DOKU_CLIENT_ID=MCH-TEST'); putenv('DOKU_SECRET_KEY=secret-test');
require_once __DIR__ . '/../lib/DokuCheckoutService.php';

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
echo "DOKU Checkout signature tests passed\n";
