<?php
declare(strict_types=1);

/**
 * Single configuration source: environment variables.
 * A local, Git-ignored .env file may populate missing variables for development;
 * deployment uses the same keys supplied by the hosting environment.
 */
function load_local_env(): array
{
    static $variables = null;
    if (is_array($variables)) return $variables;
    $variables = [];

    $file = dirname(__DIR__) . '/.env';
    if (!is_file($file) || !is_readable($file)) return $variables;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return $variables;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key) || getenv($key) !== false) continue;
        $value = trim($value);
        if (strlen($value) >= 2 && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) $value = substr($value, 1, -1);
        if (function_exists('putenv')) putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $variables[$key] = $value;
    }
    return $variables;
}

function app_config(string $key, ?string $default = null): ?string
{
    $local = load_local_env();
    $value = getenv($key);
    if ($value !== false && $value !== '') return $value;
    return $local[$key] ?? $default;
}

function app_url(string $path): string
{
    $base = rtrim((string) app_config('APP_URL', ''), '/');
    if ($base === '') throw new RuntimeException('APP_URL belum dikonfigurasi. Set URL HTTPS publik aplikasi.');
    return $base . '/' . ltrim($path, '/');
}
