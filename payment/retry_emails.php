<?php
// Run only from CLI: php payment/retry_emails.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Forbidden'); }
require_once __DIR__ . '/../lib/PaymentService.php';
$rows = database()->query("SELECT merchant_order_id FROM payments WHERE status='PAID' AND email_sent_at IS NULL ORDER BY id ASC LIMIT 50")->fetchAll();
foreach ($rows as $p) {
    PaymentService::sendPendingEmail($p['merchant_order_id']);
    echo "Processed {$p['merchant_order_id']}\n";
}
