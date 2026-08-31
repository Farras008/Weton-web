<?php
declare(strict_types=1);

putenv('APP_URL=https://local.example.test');
putenv('DOKU_ENV=sandbox');
require_once __DIR__ . '/../config/config.php';

if (app_config('DOKU_ENV') !== 'sandbox') throw new RuntimeException('DOKU_ENV must come from environment variables.');
if (app_url('/api/doku/notification') !== 'https://local.example.test/api/doku/notification') throw new RuntimeException('APP_URL was not read from environment variables.');
echo "Configuration environment tests passed\n";
