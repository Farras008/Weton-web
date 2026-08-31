<?php
declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../payment/doku-create.php');
if (!is_string($source)) throw new RuntimeException('Endpoint DOKU create tidak dapat dibaca.');

if (!preg_match('/createPayment\(\$payment\).*?try\s*\{\s*PaymentService::markInvoiceCreated/s', $source)) {
    throw new RuntimeException('Metadata invoice harus disimpan setelah DOKU membuat Checkout.');
}
if (!str_contains($source, 'catch (PaymentDatabaseException $e)')) {
    throw new RuntimeException('Kegagalan penyimpanan metadata setelah DOKU harus ditangani terpisah.');
}
if (!preg_match('/catch \(PaymentDatabaseException \$e\).*?\$_SESSION\[\'doku_orders\'\].*?paymentUrl/s', $source)) {
    throw new RuntimeException('Checkout URL harus tetap dikembalikan setelah kegagalan metadata database.');
}

echo "DOKU create resilience test passed\n";
