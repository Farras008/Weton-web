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
        self::writeLifecycleTrace('doku_http_request', $payload);
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
        if (!is_array($decoded)) {
            $this->logCreateFailure($payload, $status, 'missing_payment_url', is_string($response) ? $response : '');
            throw new RuntimeException('Respons DOKU tidak lengkap.');
        }
        try {
            $checkout = $this->extractCheckoutResponse($decoded);
            self::writeLifecycleTrace('doku_http_response', $payload, $status);
            return $checkout;
        } catch (InvalidArgumentException $e) {
            $this->logCreateFailure($payload, $status, 'missing_payment_url', is_string($response) ? $response : '');
            throw new RuntimeException('Respons DOKU tidak lengkap.');
        }
    }

    /** Extracts DOKU Checkout's documented response object and validates its modal URL. */
    public function extractCheckoutResponse(array $decoded): array
    {
        $checkout = $decoded['response'] ?? null;
        $url = is_array($checkout) ? ($checkout['payment']['url'] ?? null) : null;
        $parts = is_string($url) ? parse_url($url) : false;
        if (!is_array($checkout) || !is_string($url) || $url === '' || !is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
            throw new InvalidArgumentException('DOKU Checkout payment URL tidak valid.');
        }
        return $checkout;
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
        $databaseError = self::findPaymentDatabaseException($error);
        $operation = $databaseError?->operation;
        $stage = in_array($operation, ['mark_invoice_created', 'payments_update_after_doku'], true)
            ? 'application_after_doku_response' : 'application_before_doku_response';
        $trace = [
            'recorded_at_utc' => gmdate('c'),
            'stage' => $stage,
            'invoice' => $invoice ?: '-',
            'amount' => $amount,
            'http_status' => null,
            'curl_error' => '-',
            'doku_response' => '-',
            'error_type' => get_class($error),
        ];
        if ($databaseError instanceof PaymentDatabaseException) {
            $trace['error_type'] = 'PDOException';
            $trace['database_operation'] = $databaseError->operation;
            $trace['sqlstate'] = $databaseError->sqlState;
            $trace['pdo_driver_code'] = $databaseError->driverCode;
            $trace['pdo_message'] = self::safePdoMessage($databaseError->getPrevious());
        } elseif ($error instanceof PDOException) {
            $info = is_array($error->errorInfo ?? null) ? $error->errorInfo : [];
            $trace['database_operation'] = 'unknown_before_doku_request';
            $trace['sqlstate'] = (string) ($info[0] ?? $error->getCode() ?? 'unknown');
            $trace['pdo_driver_code'] = isset($info[1]) && is_numeric($info[1]) ? (int) $info[1] : null;
            $trace['pdo_message'] = self::safePdoMessage($error);
        }
        self::writeDiagnosticTrace($trace);
    }

    /** Records only lifecycle state and non-sensitive order facts. */
    private static function writeLifecycleTrace(string $stage, array $payload, ?int $httpStatus = null): void
    {
        $order = is_array($payload['order'] ?? null) ? $payload['order'] : [];
        self::writeDiagnosticTrace([
            'recorded_at_utc' => gmdate('c'),
            'stage' => $stage,
            'invoice' => (string) ($order['invoice_number'] ?? '-'),
            'amount' => (int) ($order['amount'] ?? 0),
            'http_status' => $httpStatus,
            'curl_error' => '-',
            'doku_response' => $stage === 'doku_http_response' ? 'received' : '-',
        ]);
    }

    public static function recordFrontendResponse(string $invoice, int $amount): void
    {
        self::writeDiagnosticTrace([
            'recorded_at_utc' => gmdate('c'), 'stage' => 'response_to_frontend',
            'invoice' => $invoice, 'amount' => $amount, 'http_status' => 200,
            'curl_error' => '-', 'doku_response' => 'checkout_url_returned',
        ]);
    }

    /** Finds operation-labelled database failures even when another layer wraps them. */
    private static function findPaymentDatabaseException(Throwable $error): ?PaymentDatabaseException
    {
        $current = $error;
        $seen = [];
        while ($current !== null && !isset($seen[spl_object_id($current)])) {
            $seen[spl_object_id($current)] = true;
            if ($current instanceof PaymentDatabaseException) return $current;
            $current = $current->getPrevious();
        }
        return null;
    }

    /**
     * Retains only schema-safe PDO details. Values, credentials, SQL, and customer
     * data are deliberately never persisted to the temporary diagnostic trace.
     */
    private static function safePdoMessage(?Throwable $error): string
    {
        if (!$error instanceof PDOException) return 'PDO error message unavailable.';
        $message = preg_replace('/\s+/', ' ', $error->getMessage()) ?? '';
        // Preserve only the identifier from MySQL's schema errors; never retain SQL or values.
        if (preg_match("/Unknown column\\s+['`\"]?([A-Za-z0-9_.]+)['`\"]?\\s+in\\s+(?:['`\"]?(?:field|where|order|group) list['`\"]?)/i", $message, $match)) {
            return "Unknown column '" . $match[1] . "' in field list";
        }
        if (preg_match("/Table ['`]?([A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)?)['`]? doesn't exist/i", $message, $match)) {
            return "Table '" . $match[1] . "' doesn't exist";
        }
        if (str_contains(strtolower($message), 'duplicate entry')) return 'Duplicate entry for a unique key.';
        return 'PDO error message suppressed for safety.';
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
