<?php
require_once __DIR__ . '/../config/config.php';

final class LouvinService
{
    private string $baseUrl;
    private string $apiKey;
    private string $slug;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) app_config('LOUVIN_BASE_URL', 'https://api.louvin.dev'), '/');
        $this->apiKey = (string) app_config('LOUVIN_API_KEY', '');
        $this->slug = (string) app_config('LOUVIN_SLUG', 'weton-online');
        if ($this->apiKey === '' || $this->slug === '') throw new RuntimeException('Konfigurasi Louvin belum lengkap.');
    }

    public function createTransaction(array $payment): array
    {
        $payload = [
            'slug' => $this->slug,
            'amount' => (int) $payment['amount'],
            'external_id' => $payment['merchant_order_id'],
            'customer_name' => 'Pelanggan Weton',
            'customer_email' => $payment['email'],
            'callback_url' => app_url('payment/callback.php'),
            'return_url' => app_url('payment/return.php?merchantOrderId=' . rawurlencode($payment['merchant_order_id'])),
            'metadata' => ['merchant_order_id' => $payment['merchant_order_id']],
        ];

        return $this->request('/create-transaction', $payload);
    }

    private function request(string $path, array $payload): array
    {
        if (!function_exists('curl_init')) throw new RuntimeException('Ekstensi PHP cURL diperlukan untuk integrasi Louvin.');
        $curl = curl_init($this->baseUrl . $path);
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $this->apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($body === false || $status < 200 || $status >= 300) throw new RuntimeException('Louvin tidak dapat memproses permintaan' . ($error ? ': ' . $error : '.'));
        $result = json_decode($body, true);
        if (!is_array($result)) throw new RuntimeException('Respons Louvin tidak valid.');
        return $result;
    }
}
