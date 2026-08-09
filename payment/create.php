<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../lib/PaymentService.php';
require_once __DIR__ . '/../lib/DuitkuService.php';

function fail_create(string $message): never { http_response_code(422); exit('<!doctype html><meta charset="utf-8"><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p><p><a href="../index.php">Kembali ke Weton Online</a></p>'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }
if (!hash_equals($_SESSION['payment_csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) fail_create('Permintaan tidak valid. Silakan hitung ulang weton Anda.');
$email = trim((string) ($_POST['email'] ?? '')); $date = trim((string) ($_POST['birth_date'] ?? '')); $time = trim((string) ($_POST['birth_time'] ?? ''));
if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) fail_create('Masukkan alamat email yang valid.');
try {
    $payment = PaymentService::create($email, $date, $time);
    try {
        $invoice = (new DuitkuService())->createInvoice($payment);
        if (($invoice['statusCode'] ?? '') !== '00' || empty($invoice['paymentUrl']) || empty($invoice['reference'])) throw new RuntimeException('Invoice tidak dapat dibuat.');
        PaymentService::markInvoiceCreated($payment['merchant_order_id'], $invoice);
        header('Location: ' . $invoice['paymentUrl'], true, 303); exit;
    } catch (Throwable $e) {
        error_log('Weton create invoice error: ' . $e->getMessage());
        database()->prepare("UPDATE payments SET status='FAILED', payment_message=? WHERE merchant_order_id=?")->execute(['Gagal membuat invoice', $payment['merchant_order_id']]);
        throw new RuntimeException('Pembayaran belum dapat dibuat. Silakan coba lagi beberapa saat.');
    }
} catch (Throwable $e) { fail_create($e->getMessage()); }
