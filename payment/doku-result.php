<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../lib/PaymentService.php';
$invoice = trim((string) ($_GET['invoice'] ?? ''));
if ($invoice === '' || empty($_SESSION['doku_orders'][$invoice])) { http_response_code(403); exit('Hasil lengkap belum tersedia. Selesaikan pembayaran terlebih dahulu.'); }
$payment = PaymentService::findByOrderId($invoice);
if (!$payment || $payment['status'] !== 'PAID') { http_response_code(403); exit('Hasil lengkap belum tersedia.'); }
$_SESSION['doku_paid_orders'][$invoice] = true;
$reportForTemplate = FullWetonReport::build($payment['birth_date'], $payment['birth_time']);
require __DIR__ . '/../templates/email/weton-full.php';
