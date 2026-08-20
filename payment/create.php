<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../lib/PaymentService.php';
require_once __DIR__ . '/../lib/LouvinService.php';

function fail_create(string $message): never
{
    http_response_code(422);
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    exit('<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Status Pembayaran — Weton Online</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><main class="payment-status-shell"><a class="brand" href="../index.php"><span class="brand-mark"><span>W</span></span><span>Weton Jawa</span></a><section class="payment-status-card" aria-labelledby="payment-status-title"><p class="eyebrow">Pembayaran</p><div class="payment-status-icon" aria-hidden="true">!</div><h1 id="payment-status-title">Pembayaran belum dapat dibuat.</h1><p>' . $safeMessage . '</p><a class="payment-status-link" href="../index.php">Kembali ke Weton Online <span aria-hidden="true">→</span></a></section></main></body></html>');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!hash_equals($_SESSION['payment_csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) fail_create('Permintaan tidak valid. Silakan hitung ulang weton Anda.');
$email = trim((string) ($_POST['email'] ?? '')); $date = trim((string) ($_POST['birth_date'] ?? '')); $time = trim((string) ($_POST['birth_time'] ?? ''));
if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) fail_create('Masukkan alamat email yang valid.');
try {
    $payment = PaymentService::create($email, $date, $time);
    // Checkout stays bypassed unless payment is explicitly enabled in production config.
    $paymentEnabled = filter_var(app_config('PAYMENT_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);
    if (!$paymentEnabled) {
        PaymentService::markSuccessAndSend($payment['merchant_order_id'], [
            'amount' => $payment['amount'],
            'reference' => 'TEST-' . $payment['merchant_order_id'],
            'statusMessage' => 'Pembayaran dilewati untuk pengujian email',
        ], 'TEST');
        header('Location: return.php?merchantOrderId=' . rawurlencode($payment['merchant_order_id']), true, 303);
        exit;
    }
    try {
        $transaction = (new LouvinService())->createTransaction($payment);
        $data = is_array($transaction['data'] ?? null) ? $transaction['data'] : $transaction;
        $paymentUrl = $data['payment_url'] ?? $data['paymentUrl'] ?? $data['checkout_url'] ?? $data['checkoutUrl'] ?? $data['url'] ?? null;
        $reference = $data['reference'] ?? $data['transaction_id'] ?? $data['transactionId'] ?? $data['id'] ?? null;
        if (!is_string($paymentUrl) || $paymentUrl === '' || !is_scalar($reference) || (string) $reference === '') throw new RuntimeException('Respons transaksi Louvin tidak lengkap.');
        PaymentService::markInvoiceCreated($payment['merchant_order_id'], ['reference' => (string) $reference, 'statusMessage' => $data['message'] ?? 'Transaksi Louvin dibuat']);
        header('Location: ' . $paymentUrl, true, 303); exit;
    } catch (Throwable $e) {
        error_log('Weton create invoice error: ' . $e->getMessage());
        database()->prepare("UPDATE payments SET status='FAILED', payment_message=? WHERE merchant_order_id=?")->execute(['Gagal membuat invoice', $payment['merchant_order_id']]);
        throw new RuntimeException('Pembayaran belum dapat dibuat. Silakan coba lagi beberapa saat.');
    }
} catch (Throwable $e) { fail_create($e->getMessage()); }
