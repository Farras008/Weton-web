<?php

// Copy this file to config/local.php, then replace every YOUR_* value.
// Do not commit config/local.php.
return [
    'db' => [
        'host' => 'srv1981.hstgr.io',
        'port' => 3306,
        'name' => 'u468044357_weton_db',
        'user' => 'u468044357_weton',
        'password' => 'YOUR_DATABASE_PASSWORD',
    ],
    'duitku' => [
        'environment' => 'sandbox',
        'merchant_code' => 'YOUR_DUITKU_MERCHANT_CODE',
        'api_key' => 'YOUR_DUITKU_API_KEY',
    ],
    'mail' => [
        'host' => 'YOUR_SMTP_HOST',
        'port' => 587,
        'username' => 'YOUR_SMTP_USERNAME',
        'password' => 'YOUR_SMTP_PASSWORD',
        'encryption' => 'tls',
        'from_address' => 'YOUR_EMAIL',
        'from_name' => 'Weton Online',
        // Buat token acak panjang khusus untuk membuka endpoint test email.
        'test_token' => 'YOUR_RANDOM_TEST_EMAIL_TOKEN',
    ],
    'app' => [
        'url' => 'https://weton.online',
        // Aktifkan hanya pada config/local.php di hosting production.
        'visitor_counter_enabled' => true,
    ],
];
