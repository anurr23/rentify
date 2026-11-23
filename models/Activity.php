<?php
declare(strict_types=1);

class Activity extends Model
{
    public static function allActive(): array
    {
        $rows = self::fetchAll('SELECT * FROM activities WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
        foreach ($rows as &$row) {
            $row['default_category_ids'] = safe_json_decode($row['default_category_ids'], []);
        }
        return $rows;
    }

    public static function find(int $id): ?array
    {
        $row = self::fetchOne('SELECT * FROM activities WHERE id = ? LIMIT 1', [$id]);
        if ($row) {
            $row['default_category_ids'] = safe_json_decode($row['default_category_ids'], []);
        }
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $row = self::fetchOne('SELECT * FROM activities WHERE slug = ? AND is_active = 1 LIMIT 1', [$slug]);
        if ($row) {
            $row['default_category_ids'] = safe_json_decode($row['default_category_ids'], []);
        }
        return $row ?: null;
    }

    public static function save(?int $id, array $data): void
    {
        $slug = slugify($data['slug'] ?: $data['name']);
        if ($id) {
            self::pdo()->prepare(
                'UPDATE activities SET name = ?, slug = ?, icon = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?'
            )->execute([$data['name'], $slug, $data['icon'], $data['description'], $data['sort_order'], $data['is_active'], $id]);
            return;
        }
        self::pdo()->prepare(
            'INSERT INTO activities (name, slug, icon, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$data['name'], $slug, $data['icon'], $data['description'], $data['sort_order'], $data['is_active']]);
    }
}
