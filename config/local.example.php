<?php

// Copy this file to config/local.php, then replace every YOUR_* value.
// Do not commit config/local.php.
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'u468044357_weton_db',
        'user' => 'u468044357_weton',
        'password' => '@Sendiriku230602',
    ],
    'louvin' => [
        'base_url' => 'https://api.louvin.dev',
        'api_key' => 'lv_e7e5ac7459f94854ab161ab207bdf055',
        'slug' => 'weton-online',
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
        // Set true untuk mengaktifkan kembali checkout pembayaran.
        'payment_enabled' => true,
        // Aktifkan hanya pada config/local.php di hosting production.
        'visitor_counter_enabled' => true,
    ],
];
