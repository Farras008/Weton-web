<?php

/**
 * Reads environment variables first, then config/local.php.
 * local.php supports the nested shared-hosting structure in local.example.php;
 * legacy flat keys remain supported for an existing deployment.
 */
function app_config(string $key, ?string $default = null): ?string
{
    static $local = null;
    if ($local === null) {
        $file = __DIR__ . '/local.php';
        $local = is_file($file) ? require $file : [];
        if (!is_array($local)) throw new RuntimeException('config/local.php harus mengembalikan array konfigurasi.');
    }
    $value = getenv($key);
    if ($value !== false && $value !== '') return $value;
    if (isset($local[$key]) && $local[$key] !== '') return (string) $local[$key];

    $paths = [
        'DB_HOST' => ['db', 'host'], 'DB_PORT' => ['db', 'port'], 'DB_NAME' => ['db', 'name'],
        'DB_USER' => ['db', 'user'], 'DB_PASSWORD' => ['db', 'password'],
        'DUITKU_ENV' => ['duitku', 'environment'], 'DUITKU_MERCHANT_CODE' => ['duitku', 'merchant_code'], 'DUITKU_API_KEY' => ['duitku', 'api_key'],
        'MAIL_HOST' => ['mail', 'host'], 'MAIL_PORT' => ['mail', 'port'], 'MAIL_USERNAME' => ['mail', 'username'], 'MAIL_PASSWORD' => ['mail', 'password'],
        'MAIL_ENCRYPTION' => ['mail', 'encryption'], 'MAIL_FROM_ADDRESS' => ['mail', 'from_address'], 'MAIL_FROM_NAME' => ['mail', 'from_name'], 'MAIL_TEST_TOKEN' => ['mail', 'test_token'],
        'APP_URL' => ['app', 'url'],
    ];
    $path = $paths[$key] ?? null;
    if ($path !== null && isset($local[$path[0]]) && is_array($local[$path[0]]) && array_key_exists($path[1], $local[$path[0]]) && $local[$path[0]][$path[1]] !== '') {
        return (string) $local[$path[0]][$path[1]];
    }
    return $default;
}

function app_url(string $path): string
{
    $base = rtrim((string) app_config('APP_URL', ''), '/');
    if ($base === '') throw new RuntimeException('APP_URL belum dikonfigurasi. Set URL HTTPS publik aplikasi.');
    return $base . '/' . ltrim($path, '/');
}
