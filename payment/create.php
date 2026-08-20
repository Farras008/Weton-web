<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../lib/PaymentService.php';
require_once __DIR__ . '/../lib/LouvinService.php';

function fail_create(string $message): never { http_response_code(422); exit('<!doctype html><meta charset="utf-8"><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p><p><a href="../index.php">Kembali ke Weton Online</a></p>'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!hash_equals($_SESSION['payment_csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) fail_create('Permintaan tidak valid. Silakan hitung ulang weton Anda.');
$email = trim((string) ($_POST['email'] ?? '')); $date = trim((string) ($_POST['birth_date'] ?? '')); $time = trim((string) ($_POST['birth_time'] ?? ''));
if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) fail_create('Masukkan alamat email yang valid.');
try {
    $payment = PaymentService::create($email, $date, $time);
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
