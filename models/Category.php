<?php
declare(strict_types=1);

class Category extends Model
{
    public static function allActive(): array
    {
        return self::fetchAll(
            "SELECT c.*,
                (SELECT COUNT(*) FROM items i WHERE i.category_id = c.id AND i.is_active = 1) AS item_count,
                (SELECT MIN(i.price_per_day) FROM items i WHERE i.category_id = c.id AND i.is_active = 1) AS min_price,
                (SELECT COALESCE(SUM(i.stock_total), 0) FROM items i WHERE i.category_id = c.id AND i.is_active = 1) AS stock_total
             FROM categories c
             WHERE c.is_active = 1
             ORDER BY c.sort_order ASC, c.name ASC"
        );
    }

    public static function all(): array
    {
        return self::fetchAll(
            "SELECT c.*,
                (SELECT COUNT(*) FROM items i WHERE i.category_id = c.id) AS item_count
             FROM categories c ORDER BY c.sort_order ASC, c.name ASC"
        );
    }

    public static function find(int $id): ?array
    {
        $category = self::fetchOne('SELECT * FROM categories WHERE id = ? LIMIT 1', [$id]);
        return $category ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $category = self::fetchOne(
            "SELECT c.*,
                (SELECT COUNT(*) FROM items i WHERE i.category_id = c.id AND i.is_active = 1) AS item_count
             FROM categories c WHERE c.slug = ? AND c.is_active = 1 LIMIT 1",
            [$slug]
        );
        return $category ?: null;
    }

    public static function filters(int $categoryId): array
    {
        $filters = self::fetchAll(
            'SELECT * FROM category_filters WHERE category_id = ? ORDER BY sort_order ASC, id ASC',
            [$categoryId]
        );
        foreach ($filters as &$filter) {
            $filter['options'] = safe_json_decode($filter['options'], []);
        }
        return $filters;
    }

    public static function popularItems(int $limit = 5): array
    {
        return self::fetchAll(
            "SELECT i.*, c.name AS category_name, c.slug AS category_slug
             FROM items i JOIN categories c ON c.id = i.category_id
             WHERE i.is_active = 1
             ORDER BY i.rented_count DESC, i.rating_avg DESC
             LIMIT ?",
            [$limit]
        );
    }

    public static function stats(): array
    {
        return [
            'categories' => (int) self::fetchValue('SELECT COUNT(*) FROM categories WHERE is_active = 1'),
            'items' => (int) self::fetchValue('SELECT COUNT(*) FROM items WHERE is_active = 1'),
            'customers' => (int) self::fetchValue("SELECT COUNT(*) FROM users WHERE role = 'customer'"),
            'bookings' => (int) self::fetchValue('SELECT COUNT(*) FROM bookings'),
        ];
    }

    public static function save(?int $id, array $data): int
    {
        $slug = slugify($data['slug'] ?: $data['name']);
        $exists = self::fetchValue('SELECT id FROM categories WHERE slug = ? AND id <> ? LIMIT 1', [$slug, $id ?? 0]);
        if ($exists) {
            $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }
        if ($id) {
            self::pdo()->prepare(
                'UPDATE categories SET name = ?, slug = ?, description = ?, icon = ?, accent_color = ?,
                    sort_order = ?, is_active = ? WHERE id = ?'
            )->execute([
                $data['name'], $slug, $data['description'], $data['icon'],
                $data['accent_color'], $data['sort_order'], $data['is_active'], $id,
            ]);
            return $id;
        }
        self::pdo()->prepare(
            'INSERT INTO categories (name, slug, description, icon, accent_color, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $data['name'], $slug, $data['description'], $data['icon'],
            $data['accent_color'], $data['sort_order'], $data['is_active'],
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    public static function delete(int $id): array
    {
        $count = (int) self::fetchValue('SELECT COUNT(*) FROM items WHERE category_id = ?', [$id]);
        if ($count > 0) {
            return ['success' => false, 'message' => 'Kategori masih memiliki ' . $count . ' item. Pindahkan item terlebih dahulu.'];
        }
        self::pdo()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        return ['success' => true, 'message' => 'Kategori dihapus.'];
    }
}
