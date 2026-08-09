<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/FullWetonReport.php';
require_once __DIR__ . '/EmailService.php';

final class PaymentService
{
    public const AMOUNT = 1000;
    public static function create(string $email, string $birthDate, string $birthTime): array
    {
        $report = FullWetonReport::build($birthDate, $birthTime); $n = $report['neptu'];
        $orderId = 'WETON-' . gmdate('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $data = ['merchant_order_id' => $orderId, 'email' => $email, 'birth_date' => $birthDate, 'birth_time' => $birthTime,
            'weton' => $n['weton'], 'neptu_hari' => $n['neptuHari'], 'neptu_pasaran' => $n['neptuPasaran'], 'total_neptu' => $n['totalNeptu'], 'amount' => self::AMOUNT];
        $sql = 'INSERT INTO payments (merchant_order_id,email,birth_date,birth_time,weton,neptu_hari,neptu_pasaran,total_neptu,amount,status) VALUES (:merchant_order_id,:email,:birth_date,:birth_time,:weton,:neptu_hari,:neptu_pasaran,:total_neptu,:amount,\'PENDING\')';
        database()->prepare($sql)->execute($data); return $data;
    }
    public static function findByOrderId(string $orderId, bool $lock = false): ?array
    {
        $sql = 'SELECT * FROM payments WHERE merchant_order_id = ?' . ($lock ? ' FOR UPDATE' : ''); $s = database()->prepare($sql); $s->execute([$orderId]); return $s->fetch() ?: null;
    }
    public static function markInvoiceCreated(string $orderId, array $invoice): void
    { database()->prepare('UPDATE payments SET reference=?, duitku_reference=?, payment_message=? WHERE merchant_order_id=?')->execute([$invoice['reference'], $invoice['reference'], $invoice['statusMessage'] ?? null, $orderId]); }
    public static function markSuccessAndSend(string $orderId, array $verified, string $method = ''): void
    {
        $pdo = database(); $pdo->beginTransaction();
        try {
            $payment = self::findByOrderId($orderId, true); if (!$payment) throw new RuntimeException('Transaksi tidak ditemukan.');
            if ((int) $payment['amount'] !== self::AMOUNT || (int) ($verified['amount'] ?? 0) !== (int) $payment['amount']) throw new RuntimeException('Nominal transaksi tidak sesuai.');
            if ($payment['status'] !== 'SUCCESS') {
                $pdo->prepare("UPDATE payments SET status='SUCCESS', reference=?, duitku_reference=?, payment_method=?, payment_message=?, paid_at=COALESCE(paid_at, NOW()) WHERE id=?")
                    ->execute([$verified['reference'] ?? null, $verified['reference'] ?? null, $method ?: null, $verified['statusMessage'] ?? 'SUCCESS', $payment['id']]);
            }
            $pdo->commit();
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
        // SMTP is intentionally outside the database transaction: it may be slow or unavailable.
        self::sendPendingEmail($orderId);
    }

    /** Atomically claims a pending email so duplicate callbacks cannot send it twice. */
    public static function sendPendingEmail(string $orderId): void
    {
        $pdo = database();
        $claim = $pdo->prepare("UPDATE payments SET email_sending_at=NOW() WHERE merchant_order_id=? AND status='SUCCESS' AND email_sent_at IS NULL AND (email_sending_at IS NULL OR email_sending_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE))");
        $claim->execute([$orderId]);
        if ($claim->rowCount() !== 1) return;

        $payment = self::findByOrderId($orderId);
        if (!$payment) return;
        try {
            (new EmailService())->sendFullReport($payment['email'], FullWetonReport::build($payment['birth_date'], $payment['birth_time']));
            $pdo->prepare('UPDATE payments SET email_sent_at=NOW(), email_sending_at=NULL, email_error=NULL WHERE id=? AND email_sent_at IS NULL')->execute([$payment['id']]);
        } catch (Throwable $e) {
            // Keep payment successful; avoid retaining or logging SMTP/API secrets.
            error_log('Weton email send failed for payment id ' . $payment['id']);
            $pdo->prepare('UPDATE payments SET email_sending_at=NULL, email_error=? WHERE id=? AND email_sent_at IS NULL')->execute(['Email belum dapat dikirim. Silakan coba ulang.', $payment['id']]);
        }
    }
}
