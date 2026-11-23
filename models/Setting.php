<?php
declare(strict_types=1);

class Setting extends Model
{
    public static function allGrouped(): array
    {
        $rows = settings_all();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['setting_group']][] = $row;
        }
        return $grouped;
    }

    public static function updateMany(array $values): void
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );
        foreach ($values as $key => $value) {
            $stmt->execute([$key, (string) $value]);
        }
    }

    public static function pickupPoints(?int $storeId = null): array
    {
        $where = 'WHERE p.is_active = 1' . ($storeId !== null ? ' AND p.store_id = ' . (int) $storeId : '');
        return self::fetchAll(
            "SELECT p.*, s.name AS store_name FROM pickup_points p
             LEFT JOIN stores s ON s.id = p.store_id
             $where ORDER BY p.sort_order ASC, p.id ASC"
        );
    }

    public static function allPickupPoints(): array
    {
        return self::fetchAll(
            'SELECT p.*, s.name AS store_name FROM pickup_points p
             LEFT JOIN stores s ON s.id = p.store_id
             ORDER BY p.sort_order ASC, p.id ASC'
        );
    }

    public static function savePickupPoint(?int $id, array $data): void
    {
        $values = [
            $data['store_id'] ?? null,
            $data['name'], $data['address'], $data['hours'] ?: null, $data['maps_url'] ?: null,
            $data['latitude'] !== '' ? (float) $data['latitude'] : null,
            $data['longitude'] !== '' ? (float) $data['longitude'] : null,
            $data['is_active'], $data['sort_order'],
        ];
        if ($id) {
            self::pdo()->prepare(
                'UPDATE pickup_points SET store_id = ?, name = ?, address = ?, hours = ?, maps_url = ?, latitude = ?, longitude = ?,
                    is_active = ?, sort_order = ? WHERE id = ?'
            )->execute([...$values, $id]);
            return;
        }
        self::pdo()->prepare(
            'INSERT INTO pickup_points (store_id, name, address, hours, maps_url, latitude, longitude, is_active, sort_order, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute($values);
    }
}
