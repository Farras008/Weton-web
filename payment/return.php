<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/PaymentService.php';
$order = trim((string) ($_GET['merchantOrderId'] ?? '')); $payment = null;
try { if ($order !== '') $payment = PaymentService::findByOrderId($order); } catch (Throwable $e) { error_log('Weton return error: ' . $e->getMessage()); }
$message = 'Pembayaran sedang diproses.';
if ($payment && $payment['status'] === 'SUCCESS') $message = $payment['email_sent_at'] ? 'Pembayaran berhasil. Pembacaan lengkap telah dikirim ke email Anda.' : 'Pembayaran berhasil, tetapi email belum dapat dikirim. Silakan hubungi Weton Online.';
elseif ($payment && in_array($payment['status'], ['FAILED','EXPIRED'], true)) $message = 'Pembayaran belum berhasil.';
?><!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Status Pembayaran — Weton Online</title><body style="font-family:Arial;background:#f5efe2;color:#173d31;padding:48px;text-align:center"><h1>Weton Online</h1><p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><p><a href="../index.php">Kembali ke halaman utama</a></p></body></html>
