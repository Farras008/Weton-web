<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../lib/PaymentService.php';
header('Content-Type: application/json; charset=utf-8');
$invoice = trim((string) ($_GET['invoice'] ?? ''));
if ($invoice === '' || empty($_SESSION['doku_orders'][$invoice])) { http_response_code(404); echo json_encode(['paid' => false]); exit; }
try {
    $payment = PaymentService::findByOrderId($invoice);
    if (!$payment) { http_response_code(404); echo json_encode(['paid' => false]); exit; }
    $paid = $payment['status'] === 'PAID';
    if ($paid) $_SESSION['doku_paid_orders'][$invoice] = true;
    echo json_encode(['paid' => $paid, 'status' => $payment['status']]);
} catch (Throwable $e) { error_log('DOKU status error: ' . $e->getMessage()); http_response_code(500); echo json_encode(['paid' => false]); }
