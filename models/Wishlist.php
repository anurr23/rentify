<?php
declare(strict_types=1);

class Wishlist extends Model
{
    public static function toggle(int $userId, int $itemId): array
    {
        $exists = (int) self::fetchValue(
            'SELECT COUNT(*) FROM wishlists WHERE user_id = ? AND item_id = ?',
            [$userId, $itemId]
        );
        if ($exists) {
            self::pdo()->prepare('DELETE FROM wishlists WHERE user_id = ? AND item_id = ?')->execute([$userId, $itemId]);
            return ['success' => true, 'message' => 'Dihapus dari wishlist.', 'active' => false];
        }
        $item = (int) self::fetchValue('SELECT COUNT(*) FROM items WHERE id = ? AND is_active = 1', [$itemId]);
        if (!$item) {
            return ['success' => false, 'message' => 'Item tidak ditemukan.'];
        }
        self::pdo()->prepare('INSERT IGNORE INTO wishlists (user_id, item_id, created_at) VALUES (?, ?, NOW())')
            ->execute([$userId, $itemId]);
        return ['success' => true, 'message' => 'Ditambahkan ke wishlist.', 'active' => true];
    }

    public static function ids(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        $rows = self::fetchAll('SELECT item_id FROM wishlists WHERE user_id = ?', [$userId]);
        return array_map(static fn ($row) => (int) $row['item_id'], $rows);
    }

    public static function items(int $userId): array
    {
        $rows = self::fetchAll(
            'SELECT i.*, c.name AS category_name, c.slug AS category_slug, w.created_at AS saved_at
             FROM wishlists w JOIN items i ON i.id = w.item_id JOIN categories c ON c.id = i.category_id
             WHERE w.user_id = ? AND i.is_active = 1 ORDER BY w.created_at DESC',
            [$userId]
        );
        foreach ($rows as &$row) {
            $row['features'] = safe_json_decode($row['features'], []);
        }
        return $rows;
    }

    public static function count(int $userId): int
    {
        return (int) self::fetchValue('SELECT COUNT(*) FROM wishlists WHERE user_id = ?', [$userId]);
    }
}
