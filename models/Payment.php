<?php
declare(strict_types=1);

class Payment extends Model
{
    public static function find(int $id): ?array
    {
        $payment = self::fetchOne(
            'SELECT p.*, b.code AS booking_code, b.user_id, b.total AS booking_total, u.name AS user_name
             FROM payments p JOIN bookings b ON b.id = p.booking_id JOIN users u ON u.id = b.user_id
             WHERE p.id = ? LIMIT 1',
            [$id]
        );
        return $payment ?: null;
    }

    public static function pending(int $limit = 8): array
    {
        return self::fetchAll(
            "SELECT p.*, b.code AS booking_code, b.total AS booking_total, u.name AS user_name, u.phone AS user_phone
             FROM payments p JOIN bookings b ON b.id = p.booking_id JOIN users u ON u.id = b.user_id
             WHERE p.status = 'pending' ORDER BY p.created_at ASC LIMIT ?",
            [$limit]
        );
    }

    public static function verify(int $paymentId, int $adminId, bool $approve, string $note = ''): array
    {
        $payment = self::find($paymentId);
        if (!$payment) {
            return ['success' => false, 'message' => 'Pembayaran tidak ditemukan.'];
        }
        if ($payment['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Pembayaran sudah diproses.'];
        }
        $db = self::pdo();
        $db->beginTransaction();
        try {
            $db->prepare(
                "UPDATE payments SET status = ?, verified_by = ?, note = ?, updated_at = NOW() WHERE id = ?"
            )->execute([$approve ? 'verified' : 'rejected', $adminId, $note ?: null, $paymentId]);

            if ($approve) {
                $db->prepare(
                    "UPDATE bookings SET payment_status = 'paid',
                        status = CASE WHEN status = 'pending' THEN 'confirmed' ELSE status END
                     WHERE id = ?"
                )->execute([$payment['booking_id']]);
                $db->prepare(
                    "INSERT INTO booking_status_logs (booking_id, status, note, changed_by) VALUES (?, 'confirmed', ?, ?)"
                )->execute([$payment['booking_id'], 'Pembayaran diverifikasi admin.', $adminId]);
            } else {
                $db->prepare("UPDATE bookings SET payment_status = 'unpaid' WHERE id = ?")
                    ->execute([$payment['booking_id']]);
            }
            $db->commit();
        } catch (Throwable $exception) {
            $db->rollBack();
            error_log('Payment verify failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Gagal memproses pembayaran.'];
        }

        notify(
            (int) $payment['user_id'],
            'payment',
            $approve ? 'Pembayaran ' . $payment['booking_code'] . ' diverifikasi' : 'Pembayaran ' . $payment['booking_code'] . ' ditolak',
            $approve ? 'Pesanan Anda sudah lunas dan sedang diproses.' : ('Alasan: ' . ($note ?: 'Bukti pembayaran tidak valid.')),
            'pesanan_detail.php?id=' . $payment['booking_id']
        );
        log_audit('payment.' . ($approve ? 'verify' : 'reject'), 'payment', $paymentId, ['booking_id' => $payment['booking_id']]);
        return ['success' => true, 'message' => $approve ? 'Pembayaran diverifikasi, pesanan dikonfirmasi.' : 'Pembayaran ditolak.'];
    }

    public static function revenueThisMonth(): float
    {
        return (float) self::fetchValue(
            "SELECT COALESCE(SUM(amount), 0) FROM payments
             WHERE status = 'verified' AND MONTH(paid_at) = MONTH(CURDATE()) AND YEAR(paid_at) = YEAR(CURDATE())"
        );
    }
}
