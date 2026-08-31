<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

final class DokuCheckoutService
{
    private const SANDBOX_API = 'https://api-sandbox.doku.com';
    private const PRODUCTION_API = 'https://api.doku.com';
    private string $clientId;
    private string $secretKey;
    private string $apiBase;
    private const DIAGNOSTIC_FILE = __DIR__ . '/../data/doku-create-diagnostic.json';

    public function __construct()
    {
        $environment = strtolower((string) app_config('DOKU_ENV', 'sandbox'));
        if (!in_array($environment, ['sandbox', 'production'], true)) throw new RuntimeException('DOKU_ENV harus bernilai sandbox atau production.');
        $this->clientId = (string) app_config('DOKU_CLIENT_ID', '');
        $this->secretKey = (string) app_config('DOKU_SECRET_KEY', '');
        if ($this->clientId === '' || $this->secretKey === '') throw new RuntimeException('Konfigurasi DOKU belum lengkap.');
        $this->apiBase = $environment === 'production' ? self::PRODUCTION_API : self::SANDBOX_API;
    }

    public static function checkoutScriptUrl(): string
    {
        return strtolower((string) app_config('DOKU_ENV', 'sandbox')) === 'production'
            ? 'https://jokul.doku.com/jokul-checkout-js/v1/jokul-checkout-1.0.0.js'
            : 'https://sandbox.doku.com/jokul-checkout-js/v1/jokul-checkout-1.0.0.js';
    }

    /** Request target must match the public merchant webhook path used by DOKU. */
    public static function notificationRequestTarget(): string
    {
        $path = rtrim((string) parse_url((string) app_config('APP_URL', ''), PHP_URL_PATH), '/');
        return ($path === '' ? '' : $path) . '/api/doku/notification';
    }

    public function createPayment(array $payment): array
    {
        $payload = [
            'order' => [
                'amount' => (int) $payment['amount'],
                'invoice_number' => $payment['merchant_order_id'],
                'currency' => 'IDR',
                'callback_url' => app_url('payment/doku-result.php?invoice=' . rawurlencode($payment['merchant_order_id'])),
                // Keep the Checkout result inside DOKU's modal; payment truth still comes from the webhook.
                'auto_redirect' => false,
            ],
            'payment' => [
                'payment_due_date' => 60,
                'payment_method_types' => ['QRIS'],
            ],
            'customer' => ['email' => $payment['email'], 'name' => 'Pelanggan Weton'],
        ];
        return $this->request('/checkout/v1/payment', $payload);
    }

    public function verifyNotification(string $rawBody, array $headers, string $requestTarget): bool
    {
        $clientId = $headers['client-id'] ?? '';
        $requestId = $headers['request-id'] ?? '';
        $timestamp = $headers['request-timestamp'] ?? '';
        $signature = $headers['signature'] ?? '';
        if ($clientId === '' || $requestId === '' || $timestamp === '' || $signature === '' || !hash_equals($this->clientId, $clientId)) return false;
        $expected = $this->signatureFor($requestId, $timestamp, $requestTarget, $rawBody);
        return hash_equals($expected, $signature);
    }

    private function request(string $target, array $payload): array
    {
        if (!function_exists('curl_init')) throw new RuntimeException('Ekstensi PHP cURL diperlukan untuk DOKU Checkout.');
        $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $requestId = $this->uuid();
        $timestamp = gmdate('Y-m-d\\TH:i:s\\Z');
        $curl = curl_init($this->apiBase . $target);
        curl_setopt_array($curl, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json', 'Accept: application/json',
                'Client-Id: ' . $this->clientId, 'Request-Id: ' . $requestId,
                'Request-Timestamp: ' . $timestamp, 'Signature: ' . $this->signatureFor($requestId, $timestamp, $target, $body),
            ],
        ]);
        $response = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE); $error = curl_error($curl); curl_close($curl);
        if ($response === false || $status < 200 || $status >= 300) {
            $this->logCreateFailure($payload, $status, $error, is_string($response) ? $response : '');
            throw new RuntimeException('DOKU belum dapat membuat pembayaran. Silakan coba lagi.');
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded) || !is_string($decoded['payment']['url'] ?? null) || $decoded['payment']['url'] === '') {
            $this->logCreateFailure($payload, $status, 'missing_payment_url', is_string($response) ? $response : '');
            throw new RuntimeException('Respons DOKU tidak lengkap.');
        }
        return $decoded;
    }

    /** Builds the documented Non-SNAP DOKU signature; used for requests and verification. */
    public function signatureFor(string $requestId, string $timestamp, string $target, string $body): string
    {
        $digest = base64_encode(hash('sha256', $body, true));
        $component = 'Client-Id:' . $this->clientId . "\nRequest-Id:" . $requestId . "\nRequest-Timestamp:" . $timestamp . "\nRequest-Target:" . $target . "\nDigest:" . $digest;
        return 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $component, $this->secretKey, true));
    }

    /** Returns a credential-free last failure trace for the temporary diagnostic tool. */
    public static function lastCreateDiagnostic(): ?array
    {
        $raw = @file_get_contents(self::DIAGNOSTIC_FILE);
        $trace = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($trace) ? $trace : null;
    }

    /** Records a credential-free failure that happened before DOKU returned HTTP. */
    public static function recordApplicationFailure(?string $invoice, int $amount, Throwable $error): void
    {
        self::writeDiagnosticTrace([
            'recorded_at_utc' => gmdate('c'),
            'stage' => 'application_before_doku_response',
            'invoice' => $invoice ?: '-',
            'amount' => $amount,
            'http_status' => null,
            'curl_error' => '-',
            'doku_response' => '-',
            'error_type' => get_class($error),
        ]);
    }

    private function uuid(): string
    {
        $bytes = bin2hex(random_bytes(16));
        return substr($bytes, 0, 8) . '-' . substr($bytes, 8, 4) . '-4' . substr($bytes, 13, 3) . '-8' . substr($bytes, 17, 3) . '-' . substr($bytes, 20);
    }

    /** Logs diagnostics without credentials, signatures, customer data, or request headers. */
    private function logCreateFailure(array $payload, int $httpStatus, string $curlError, string $response): void
    {
        $invoice = (string) ($payload['order']['invoice_number'] ?? '-');
        $amount = (int) ($payload['order']['amount'] ?? 0);
        $error = substr(str_replace(["\r", "\n"], ' ', $curlError), 0, 500);
        error_log(sprintf(
            'DOKU create request failed: invoice=%s amount=%d http_status=%d curl_error=%s response=%s',
            $invoice,
            $amount,
            $httpStatus,
            $error !== '' ? $error : '-',
            $this->safeResponseForLog($response)
        ));
        self::writeDiagnosticTrace([
            'recorded_at_utc' => gmdate('c'),
            'stage' => 'doku_http_response',
            'invoice' => $invoice,
            'amount' => $amount,
            'http_status' => $httpStatus,
            'curl_error' => $error !== '' ? $error : '-',
            'doku_response' => $this->safeResponseForLog($response),
        ]);
    }

    private static function writeDiagnosticTrace(array $trace): void
    {
        $directory = dirname(self::DIAGNOSTIC_FILE);
        if (is_dir($directory) || @mkdir($directory, 0750, true)) {
            @file_put_contents(self::DIAGNOSTIC_FILE, json_encode($trace, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
            @chmod(self::DIAGNOSTIC_FILE, 0600);
        }
    }

    private function safeResponseForLog(string $response): string
    {
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) return 'non_json_response_sha256=' . hash('sha256', $response);
        $redact = static function (mixed $value) use (&$redact): mixed {
            if (!is_array($value)) return $value;
            foreach ($value as $key => $item) {
                if (preg_match('/secret|signature|authorization|api.?key|client.?id|email|phone/i', (string) $key)) $value[$key] = '[redacted]';
                else $value[$key] = $redact($item);
            }
            return $value;
        };
        $json = json_encode($redact($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $safe = $json === false ? 'unserializable_json_response' : $json;
        $safe = str_replace([$this->clientId, $this->secretKey], ['[redacted]', '[redacted]'], $safe);
        return substr($safe, 0, 2000);
    }
}
