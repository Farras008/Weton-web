<?php
require_once __DIR__ . '/config.php';

function database(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    foreach (['DB_HOST', 'DB_NAME', 'DB_USER'] as $key) {
        if (app_config($key, '') === '') throw new RuntimeException('Database belum dikonfigurasi (' . $key . '). Set environment variable terkait.');
    }
    $dsn = 'mysql:host=' . app_config('DB_HOST') . ';port=' . app_config('DB_PORT', '3306') . ';dbname=' . app_config('DB_NAME') . ';charset=utf8mb4';
    $pdo = new PDO($dsn, (string) app_config('DB_USER'), (string) app_config('DB_PASSWORD', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
