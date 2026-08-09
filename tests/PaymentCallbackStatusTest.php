<?php
define('PAYMENT_CALLBACK_TEST_MODE', true);
require_once __DIR__ . '/../payment/callback.php';

function expect_action(string $expected, string $status, string $result, ?string $verified = null, string $message = ''): void
{
    $actual = duitku_callback_action($status, $result, $verified, $message);
    if ($actual !== $expected) throw new RuntimeException("Expected $expected, got $actual");
}

expect_action('success', 'PENDING', '00', '00');
expect_action('pending', 'PENDING', '01');
expect_action('failed', 'PENDING', '02');
expect_action('expired', 'PENDING', '02', null, 'Transaction expired');
expect_action('keep_success', 'SUCCESS', '01');
expect_action('keep_success', 'SUCCESS', '02');
expect_action('keep_success', 'SUCCESS', '00', '00');
expect_action('pending', 'PENDING', '00', '01');
expect_action('failed', 'PENDING', '00', '02');
echo "Payment callback status tests passed\n";
