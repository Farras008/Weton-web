<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/EmailService.php';

header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-store, private', true);
$message = '';
$token = (string) app_config('MAIL_TEST_TOKEN', '');
if ($token === '') { http_response_code(503); exit('Endpoint test email belum dikonfigurasi.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['token'] ?? '');
    $recipient = trim((string) ($_POST['recipient'] ?? ''));
    if (!hash_equals($token, $submittedToken)) { http_response_code(403); $message = 'Akses test email ditolak.'; }
    elseif (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) { http_response_code(422); $message = 'Masukkan alamat email tujuan yang valid.'; }
    else {
        try { (new EmailService())->sendSmtpTest($recipient); $message = 'Email test berhasil dikirim.'; }
        catch (Throwable $e) { error_log('Weton SMTP test failed'); http_response_code(500); $message = 'Pengiriman gagal. Periksa konfigurasi SMTP dan log error server.'; }
    }
}
?><!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Test Email — Weton Online</title><body style="margin:0;padding:32px;background:#f5efe2;color:#173d31;font-family:Arial,sans-serif"><main style="max-width:520px;margin:auto;padding:28px;background:#fffdf7;border:1px solid #d9bd7a;border-radius:12px"><h1 style="margin-top:0;font-family:Georgia,serif">Test Email Weton Online</h1><?php if ($message !== ''): ?><p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?><form method="post"><label for="recipient">Email tujuan</label><input id="recipient" name="recipient" type="email" required maxlength="254" style="display:block;width:100%;box-sizing:border-box;margin:8px 0 16px;padding:12px"><label for="token">Token test</label><input id="token" name="token" type="password" required style="display:block;width:100%;box-sizing:border-box;margin:8px 0 16px;padding:12px"><button type="submit" style="padding:12px 18px;color:#fff;background:#173d31;border:0;border-radius:6px">Kirim test email</button></form></main></body></html>
