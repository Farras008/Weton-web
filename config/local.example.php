<?php

// Copy this file to config/local.php, then replace every YOUR_* value.
// Do not commit config/local.php.
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'YOUR_DATABASE_NAME',
        'user' => 'YOUR_DATABASE_USER',
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
    ],
    'app' => [
        'url' => 'https://weton.online',
    ],
];
