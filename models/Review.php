<?php
declare(strict_types=1);

class Review extends Model
{
    public static function create(int $bookingId, int $userId, int $itemId, int $rating, string $comment): array
    {
        $rating = min(5, max(1, $rating));
        $booking = Booking::find($bookingId);
        if (!$booking || (int) $booking['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Pesanan tidak ditemukan.'];
        }
        if (!in_array($booking['status'], ['returned', 'completed'], true)) {
            return ['success' => false, 'message' => 'Review bisa dibuat setelah pesanan selesai.'];
        }
        $owns = (int) self::fetchValue(
            'SELECT COUNT(*) FROM booking_items WHERE booking_id = ? AND item_id = ?',
            [$bookingId, $itemId]
        );
        if (!$owns) {
            return ['success' => false, 'message' => 'Item bukan bagian dari pesanan ini.'];
        }
        $exists = (int) self::fetchValue(
            'SELECT COUNT(*) FROM reviews WHERE booking_id = ? AND item_id = ?',
            [$bookingId, $itemId]
        );
        if ($exists) {
            return ['success' => false, 'message' => 'Anda sudah mereview item ini.'];
        }

        self::pdo()->prepare(
            'INSERT INTO reviews (booking_id, user_id, item_id, rating, comment, is_published, created_at)
             VALUES (?, ?, ?, ?, ?, 1, NOW())'
        )->execute([$bookingId, $userId, $itemId, $rating, trim($comment) ?: null]);

        self::refreshItemRating($itemId);
        return ['success' => true, 'message' => 'Terima kasih, review Anda sudah terbit.'];
    }

    public static function refreshItemRating(int $itemId): void
    {
        self::pdo()->prepare(
            'UPDATE items i SET
                i.rating_avg = COALESCE((SELECT ROUND(AVG(rating), 2) FROM reviews WHERE item_id = ? AND is_published = 1), 0),
                i.rating_count = (SELECT COUNT(*) FROM reviews WHERE item_id = ? AND is_published = 1)
             WHERE i.id = ?'
        )->execute([$itemId, $itemId, $itemId]);
    }

    public static function forItem(int $itemId, int $limit = 10): array
    {
        return Item::reviews($itemId, $limit);
    }

    public static function pendingCount(): int
    {
        return (int) self::fetchValue('SELECT COUNT(*) FROM reviews WHERE is_published = 1');
    }

    public static function recent(int $limit = 5): array
    {
        return self::fetchAll(
            'SELECT r.*, u.name AS user_name, i.name AS item_name, i.slug AS item_slug
             FROM reviews r JOIN users u ON u.id = r.user_id JOIN items i ON i.id = r.item_id
             WHERE r.is_published = 1 ORDER BY r.created_at DESC LIMIT ?',
            [$limit]
        );
    }
}
