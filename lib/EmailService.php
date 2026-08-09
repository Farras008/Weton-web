<?php
require_once __DIR__ . '/../config/config.php';

final class EmailService
{
    public function sendFullReport(string $recipient, array $report): void
    {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($autoload)) throw new RuntimeException('Dependensi email belum dipasang. Jalankan composer install saat deploy.');
        require_once $autoload;
        $template = __DIR__ . '/../templates/email/weton-full.php';
        ob_start(); $reportForTemplate = $report; require $template; $html = ob_get_clean();
        $mail = $this->mailer((string) app_config('MAIL_FROM_ADDRESS'), (string) app_config('MAIL_FROM_NAME', 'Weton Online'));
        $mail->addAddress($recipient); $mail->isHTML(true); $mail->Subject = 'Pembacaan Weton Lengkap — Weton Online';
        $mail->Body = $html; $mail->AltBody = $this->plainText($report); $mail->send();
    }
    /** Sends a minimal SMTP test without involving payment or Weton data. */
    public function sendSmtpTest(string $recipient): void
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Alamat email tujuan tidak valid.');
        $mail = $this->mailer('admin@weton.online', 'Weton Online');
        $mail->addAddress($recipient); $mail->isHTML(true); $mail->Subject = 'Test Email - Weton Online';
        $mail->Body = '<!doctype html><html lang="id"><body style="margin:0;padding:24px;background:#f5efe2;color:#173d31;font-family:Arial,sans-serif"><table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#fffdf7;border:1px solid #d9bd7a;border-radius:12px"><tr><td style="padding:28px;background:#173d31;color:#f2c66d;text-align:center;font-family:Georgia,serif;font-size:25px;font-weight:bold">Weton Online</td></tr><tr><td style="padding:28px"><h1 style="margin:0 0 12px;color:#173d31;font-family:Georgia,serif;font-size:24px">SMTP berhasil bekerja</h1><p style="margin:0;line-height:1.6">Email test ini membuktikan konfigurasi SMTP Weton Online dapat mengirim email melalui server.</p></td></tr></table></td></tr></table></body></html>';
        $mail->AltBody = "WETON ONLINE\n\nSMTP Weton Online berhasil bekerja."; $mail->send();
    }
    private function mailer(string $fromAddress, string $fromName): PHPMailer\PHPMailer\PHPMailer
    {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($autoload)) throw new RuntimeException('Dependensi PHPMailer belum dipasang. Jalankan composer install saat deploy.');
        require_once $autoload;
        if ($fromAddress === '') throw new RuntimeException('Alamat pengirim email belum dikonfigurasi.');
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP(); $mail->Host = (string) app_config('MAIL_HOST', ''); $mail->Port = (int) app_config('MAIL_PORT', '587');
        $mail->SMTPAuth = true; $mail->Username = (string) app_config('MAIL_USERNAME', ''); $mail->Password = (string) app_config('MAIL_PASSWORD', '');
        $mail->SMTPSecure = app_config('MAIL_ENCRYPTION', 'tls') === 'ssl' ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8'; $mail->setFrom($fromAddress, $fromName);
        return $mail;
    }
    private function plainText(array $r): string
    {
        $n = $r['neptu'];
        $lines = [
            'WETON ONLINE', 'Pembacaan Weton Lengkap', '',
            'DATA KELAHIRAN', 'Tanggal lahir: ' . $r['birthDate'], 'Waktu lahir: ' . $r['birthTime'],
            'Hari: ' . $r['hari'], 'Pasaran: ' . $r['pasaran'], 'Weton: ' . $n['weton'],
            'Neptu: ' . $n['neptuHari'] . ' + ' . $n['neptuPasaran'] . ' = ' . $n['totalNeptu'], '',
            'WATAK HARI', $r['watakHari']['makna'] ?? '', '',
            'WATAK PASARAN', $r['watakPasaran']['makna'] ?? '', '',
            'WATAK KELAHIRAN', $r['watakKelahiran']['makna'] ?? '', '',
            'ARAH KEJAYAAN', $r['arahKejayaan']['display'] ?? '', '',
            'PERBINTANGAN', ($r['perbintangan']['bintang'] ?? '') . ' — ' . ($r['perbintangan']['makna'] ?? ''), '',
            'PAL SRIGATI', 'Nilai periode aktif: ' . ($r['palSrigati']['value'] ?? ''), '',
            'Weton Online', 'https://weton.online', 'Warisan kalender Jawa, hadir dalam bentuk digital.', '',
            'Pembacaan ini merupakan bagian dari tradisi Primbon Jawa dan digunakan sebagai bahan refleksi budaya; bukan kepastian mengenai masa depan, kecocokan, kesehatan, atau hubungan.',
        ];
        return implode("\n", $lines);
    }
}
