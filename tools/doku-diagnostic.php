<?php
declare(strict_types=1);

/**
 * Temporary Hostinger diagnostic. Delete this file and DOKU_DIAGNOSTIC_TOKEN
 * from the server as soon as diagnosis is complete.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/DokuCheckoutService.php';

$token = (string) app_config('DOKU_DIAGNOSTIC_TOKEN', '');
$provided = (string) ($_GET['token'] ?? '');
if ($token === '' || $provided === '' || !hash_equals($token, $provided)) {
    http_response_code(403);
    exit('Diagnostic unavailable. Set a temporary DOKU_DIAGNOSTIC_TOKEN on the server, then provide it as ?token=...');
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, private');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
$host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
$requestPath = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/tools/doku-diagnostic.php'), '?') ?: '/tools/doku-diagnostic.php';
$currentBase = $host === '' ? '' : ($isHttps ? 'https' : 'http') . '://' . $host;
$appUrl = rtrim((string) app_config('APP_URL', ''), '/');
$appUrlMatches = $appUrl !== '' && $currentBase !== '' && strcasecmp($appUrl, $currentBase) === 0;

$routes = [
    '^api/doku/create-payment/?$' => false,
    '^api/doku/notification/?$' => false,
    '^api/doku/payment-status/?$' => false,
];
$htaccess = dirname(__DIR__) . '/.htaccess';
if (is_readable($htaccess)) {
    $rules = (string) file_get_contents($htaccess);
    foreach ($routes as $route => $_) $routes[$route] = str_contains($rules, $route);
}

$databaseStatus = 'NO';
$paymentsSchemaStatus = 'NOT CHECKED';
$paymentsSchema = [];
try {
    $pdo = database();
    $databaseStatus = 'YES';
} catch (Throwable) {
    $databaseStatus = 'NO';
}

if ($databaseStatus === 'YES') {
    try {
    $columns = $pdo->query('SHOW COLUMNS FROM payments')->fetchAll(PDO::FETCH_ASSOC);
    $columnMap = [];
    foreach ($columns as $column) $columnMap[(string) ($column['Field'] ?? '')] = (string) ($column['Type'] ?? '');
    foreach (['merchant_order_id', 'email', 'birth_date', 'birth_time', 'weton', 'neptu_hari', 'neptu_pasaran', 'total_neptu', 'amount', 'status', 'reference', 'doku_transaction_id', 'payment_message'] as $column) {
        $paymentsSchema['payments.' . $column . ' exists'] = isset($columnMap[$column]) ? 'YES' : 'NO';
    }
    $statusType = strtoupper($columnMap['status'] ?? '');
    $paymentsSchema['payments.status supports PENDING'] = str_contains($statusType, "'PENDING'") ? 'YES' : 'NO';
    $paymentsSchema['payments.status supports PAID'] = isset($columnMap['status']) && str_contains(strtoupper($columnMap['status']), "'PAID'") ? 'YES' : 'NO';
    $indexes = $pdo->query('SHOW INDEX FROM payments')->fetchAll(PDO::FETCH_ASSOC);
    $indexNames = [];
    foreach ($indexes as $index) $indexNames[(string) ($index['Key_name'] ?? '')] = true;
    $paymentsSchema['payments unique invoice index exists'] = isset($indexNames['uq_payments_merchant_order_id']) ? 'YES' : 'NO';
        $paymentsSchemaStatus = 'YES';
    } catch (Throwable) {
        $paymentsSchemaStatus = 'NO';
    }
}

$checks = [
    'PHP version' => PHP_VERSION,
    'putenv available' => function_exists('putenv') ? 'YES' : 'NO',
    'cURL extension' => function_exists('curl_init') ? 'YES' : 'NO',
    'OpenSSL extension' => extension_loaded('openssl') ? 'YES' : 'NO',
    'HTTPS active for this request' => $isHttps ? 'YES' : 'NO',
    'APP_URL configured' => $appUrl !== '' ? 'YES' : 'NO',
    'APP_URL matches current host' => $appUrlMatches ? 'YES' : 'NO',
    'DB connection' => $databaseStatus,
    'payments schema inspection' => $paymentsSchemaStatus,
    'DOKU_ENV configured' => app_config('DOKU_ENV', '') !== '' ? 'YES' : 'NO',
    'DOKU_ENV is sandbox' => strtolower((string) app_config('DOKU_ENV', '')) === 'sandbox' ? 'YES' : 'NO',
    'DOKU_CLIENT_ID configured' => app_config('DOKU_CLIENT_ID', '') !== '' ? 'YES' : 'NO',
    'DOKU_SECRET_KEY configured' => app_config('DOKU_SECRET_KEY', '') !== '' ? 'YES' : 'NO',
    'DB_HOST configured' => app_config('DB_HOST', '') !== '' ? 'YES' : 'NO',
    'DB_NAME configured' => app_config('DB_NAME', '') !== '' ? 'YES' : 'NO',
    'DB_USER configured' => app_config('DB_USER', '') !== '' ? 'YES' : 'NO',
];
$sourceKeys = ['DOKU_DIAGNOSTIC_TOKEN', 'DOKU_ENV', 'DOKU_CLIENT_ID', 'DOKU_SECRET_KEY', 'APP_URL', 'DB_HOST', 'DB_NAME', 'DB_USER'];
$sourceChecks = [];
foreach ($sourceKeys as $key) {
    foreach (app_config_sources($key) as $source => $present) $sourceChecks["$key via $source"] = $present ? 'YES' : 'NO';
}
$lastDokuFailure = DokuCheckoutService::lastCreateDiagnostic();
?><!doctype html>
<html lang="en"><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title>DOKU diagnostic</title>
<body style="font-family:system-ui,sans-serif;max-width:760px;margin:40px auto;padding:0 20px;line-height:1.5">
<h1>DOKU diagnostic</h1>
<p>This page does not create a payment and never displays credential values.</p>
<table style="border-collapse:collapse;width:100%"><tbody>
<?php foreach ($checks as $label => $value): ?><tr><th style="text-align:left;border-bottom:1px solid #ddd;padding:8px"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th><td style="border-bottom:1px solid #ddd;padding:8px"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
<?php foreach ($sourceChecks as $label => $value): ?><tr><th style="text-align:left;border-bottom:1px solid #ddd;padding:8px"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th><td style="border-bottom:1px solid #ddd;padding:8px"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
<?php foreach ($paymentsSchema as $label => $value): ?><tr><th style="text-align:left;border-bottom:1px solid #ddd;padding:8px"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th><td style="border-bottom:1px solid #ddd;padding:8px"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
<?php foreach ($routes as $route => $found): ?><tr><th style="text-align:left;border-bottom:1px solid #ddd;padding:8px">.htaccess route <?= htmlspecialchars($route, ENT_QUOTES, 'UTF-8') ?></th><td style="border-bottom:1px solid #ddd;padding:8px"><?= $found ? 'YES' : 'NO' ?></td></tr><?php endforeach; ?>
</tbody></table>
<h2>Last DOKU create failure</h2>
<?php if ($lastDokuFailure === null): ?><p>No captured failure yet. Trigger the payment once, then reload this page.</p>
<?php else: ?><pre style="padding:12px;overflow:auto;background:#f5f5f5"><?= htmlspecialchars((string) json_encode($lastDokuFailure, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre><?php endif; ?>
<p><strong>Delete this file and remove DOKU_DIAGNOSTIC_TOKEN from the server immediately after diagnosis.</strong></p>
</body></html>
