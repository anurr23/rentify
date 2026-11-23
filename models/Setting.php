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

    public static function pickupPoints(): array
    {
        return self::fetchAll('SELECT * FROM pickup_points WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
    }

    public static function allPickupPoints(): array
    {
        return self::fetchAll('SELECT * FROM pickup_points ORDER BY sort_order ASC, id ASC');
    }

    public static function savePickupPoint(?int $id, array $data): void
    {
        $values = [
            $data['name'], $data['address'], $data['hours'] ?: null, $data['maps_url'] ?: null,
            $data['latitude'] !== '' ? (float) $data['latitude'] : null,
            $data['longitude'] !== '' ? (float) $data['longitude'] : null,
            $data['is_active'], $data['sort_order'],
        ];
        if ($id) {
            self::pdo()->prepare(
                'UPDATE pickup_points SET name = ?, address = ?, hours = ?, maps_url = ?, latitude = ?, longitude = ?,
                    is_active = ?, sort_order = ? WHERE id = ?'
            )->execute([...$values, $id]);
            return;
        }
        self::pdo()->prepare(
            'INSERT INTO pickup_points (name, address, hours, maps_url, latitude, longitude, is_active, sort_order, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute($values);
    }
}
