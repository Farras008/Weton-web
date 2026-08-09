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
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP(); $mail->Host = (string) app_config('MAIL_HOST', ''); $mail->Port = (int) app_config('MAIL_PORT', '587');
        $mail->SMTPAuth = true; $mail->Username = (string) app_config('MAIL_USERNAME', ''); $mail->Password = (string) app_config('MAIL_PASSWORD', '');
        $mail->SMTPSecure = app_config('MAIL_ENCRYPTION', 'tls') === 'ssl' ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = 'UTF-8'; $mail->setFrom((string) app_config('MAIL_FROM_ADDRESS'), (string) app_config('MAIL_FROM_NAME', 'Weton Online'));
        $mail->addAddress($recipient); $mail->isHTML(true); $mail->Subject = 'Pembacaan Lengkap Wetonmu — Weton Online';
        $mail->Body = $html; $mail->AltBody = $this->plainText($report); $mail->send();
    }
    private function plainText(array $r): string
    {
        $n = $r['neptu'];
        return "Weton Online\n\nPembacaan Lengkap Wetonmu\n\nWeton: {$n['weton']}\nTanggal lahir: {$r['birthDate']}\nWaktu lahir: {$r['birthTime']}\nNeptu: {$n['neptuHari']} + {$n['neptuPasaran']} = {$n['totalNeptu']}\n\nPembacaan ini merupakan bagian dari tradisi Primbon Jawa dan digunakan sebagai bahan refleksi budaya; bukan kepastian mengenai masa depan, kecocokan, kesehatan, atau hubungan.";
    }
}
