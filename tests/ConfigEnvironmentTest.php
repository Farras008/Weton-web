<?php
declare(strict_types=1);

putenv('APP_URL=https://local.example.test');
putenv('DOKU_ENV=sandbox');
require_once __DIR__ . '/../config/config.php';

if (app_config('DOKU_ENV') !== 'sandbox') throw new RuntimeException('DOKU_ENV must come from environment variables.');
if (app_url('/api/doku/notification') !== 'https://local.example.test/api/doku/notification') throw new RuntimeException('APP_URL was not read from environment variables.');
$_ENV['WETON_TEST_ENV_SOURCE'] = 'from-env-array';
$_SERVER['WETON_TEST_SERVER_SOURCE'] = 'from-server-array';
if (app_config('WETON_TEST_ENV_SOURCE') !== 'from-env-array') throw new RuntimeException('$_ENV configuration was not read.');
if (app_config('WETON_TEST_SERVER_SOURCE') !== 'from-server-array') throw new RuntimeException('$_SERVER configuration was not read.');
unset($_ENV['WETON_TEST_ENV_SOURCE'], $_SERVER['WETON_TEST_SERVER_SOURCE']);
echo "Configuration environment tests passed\n";
