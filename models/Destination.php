<?php
declare(strict_types=1);

class Destination extends Model
{
    public static function allActive(): array
    {
        return self::fetchAll('SELECT * FROM destinations WHERE is_active = 1 ORDER BY name ASC');
    }

    public static function find(int $id): ?array
    {
        $row = self::fetchOne('SELECT * FROM destinations WHERE id = ? LIMIT 1', [$id]);
        return $row ?: null;
    }

    public static function save(?int $id, array $data): void
    {
        $slug = slugify($data['slug'] ?: $data['name']);
        $values = [
            $data['name'], $slug, $data['region'], $data['elevation_m'] !== '' ? (int) $data['elevation_m'] : null,
            $data['terrain'], $data['notes'], $data['is_active'],
        ];
        if ($id) {
            self::pdo()->prepare(
                'UPDATE destinations SET name = ?, slug = ?, region = ?, elevation_m = ?, terrain = ?, notes = ?, is_active = ? WHERE id = ?'
            )->execute([...$values, $id]);
            return;
        }
        self::pdo()->prepare(
            'INSERT INTO destinations (name, slug, region, elevation_m, terrain, notes, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute($values);
    }
}
