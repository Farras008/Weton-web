<?php
require_once __DIR__ . '/../config/config.php';

final class DuitkuService
{
    private string $merchantCode;
    private string $apiKey;
    private bool $sandbox;

    public function __construct()
    {
        $this->merchantCode = (string) app_config('DUITKU_MERCHANT_CODE', '');
        $this->apiKey = (string) app_config('DUITKU_API_KEY', '');
        $this->sandbox = app_config('DUITKU_ENV', 'sandbox') !== 'production';
        if ($this->merchantCode === '' || $this->apiKey === '') throw new RuntimeException('Konfigurasi Duitku belum lengkap.');
    }
    public function merchantCode(): string { return $this->merchantCode; }
    public function validCallbackSignature(string $amount, string $orderId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $this->merchantCode . $amount . $orderId, $this->apiKey);
        return hash_equals($expected, $signature);
    }
    public function createInvoice(array $payment): array
    {
        $timestamp = (string) round(microtime(true) * 1000);
        $payload = ['paymentAmount' => (int) $payment['amount'], 'merchantOrderId' => $payment['merchant_order_id'],
            'productDetails' => 'Pembacaan Lengkap Weton', 'additionalParam' => '', 'merchantUserInfo' => $payment['email'],
            'customerVaName' => 'Pelanggan Weton', 'email' => $payment['email'], 'phoneNumber' => '',
            'itemDetails' => [['name' => 'Pembacaan Lengkap Weton', 'price' => (int) $payment['amount'], 'quantity' => 1]],
            'callbackUrl' => app_url('payment/callback.php'), 'returnUrl' => app_url('payment/return.php'), 'expiryPeriod' => 60];
        $headers = ['Content-Type: application/json', 'Accept: application/json', 'x-duitku-timestamp: ' . $timestamp,
            'x-duitku-merchantcode: ' . $this->merchantCode,
            'x-duitku-signature: ' . hash('sha256', $this->merchantCode . $timestamp . $this->apiKey)];
        return $this->request($this->sandbox ? 'https://api-sandbox.duitku.com/api/merchant/createInvoice' : 'https://api-prod.duitku.com/api/merchant/createInvoice', $payload, $headers);
    }
    public function checkTransaction(string $orderId): array
    {
        $payload = ['merchantCode' => $this->merchantCode, 'merchantOrderId' => $orderId,
            'signature' => hash_hmac('sha256', $this->merchantCode . $orderId, $this->apiKey)];
        return $this->request($this->sandbox ? 'https://sandbox.duitku.com/webapi/api/merchant/transactionStatus' : 'https://passport.duitku.com/webapi/api/merchant/transactionStatus', $payload, ['Content-Type: application/json', 'Accept: application/json']);
    }
    private function request(string $url, array $payload, array $headers): array
    {
        if (!function_exists('curl_init')) throw new RuntimeException('Ekstensi PHP cURL diperlukan untuk integrasi Duitku.');
        $curl = curl_init($url); $json = json_encode($payload, JSON_THROW_ON_ERROR);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $json, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 10]);
        $body = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE); $error = curl_error($curl); curl_close($curl);
        if ($body === false || $status < 200 || $status >= 300) throw new RuntimeException('Duitku tidak dapat memproses permintaan' . ($error ? ': ' . $error : '.'));
        $result = json_decode($body, true);
        if (!is_array($result)) throw new RuntimeException('Respons Duitku tidak valid.');
        return $result;
    }
}
