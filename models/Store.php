<?php
declare(strict_types=1);

class Store extends Model
{
    public static function all(bool $onlyActive = false): array
    {
        $where = $onlyActive ? 'WHERE s.is_active = 1' : '';
        return self::fetchAll(
            "SELECT s.*,
                (SELECT COUNT(*) FROM users u WHERE u.store_id = s.id AND u.role = 'admin' AND u.is_active = 1) AS admin_count,
                (SELECT COUNT(*) FROM bookings b WHERE b.store_id = s.id) AS booking_count
             FROM stores s
             $where
             ORDER BY s.is_default DESC, s.sort_order ASC, s.name ASC"
        );
    }

    public static function active(): array
    {
        return self::all(true);
    }

    public static function find(int $id): ?array
    {
        $store = self::fetchOne('SELECT * FROM stores WHERE id = ? LIMIT 1', [$id]);
        return $store ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $store = self::fetchOne('SELECT * FROM stores WHERE code = ? LIMIT 1', [$code]);
        return $store ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $store = self::fetchOne('SELECT * FROM stores WHERE slug = ? LIMIT 1', [$slug]);
        return $store ?: null;
    }

    public static function defaultStore(): ?array
    {
        $store = self::fetchOne(
            'SELECT * FROM stores WHERE is_active = 1 ORDER BY is_default DESC, sort_order ASC, id ASC LIMIT 1'
        );
        return $store ?: null;
    }

    public static function forStaff(?array $user): ?array
    {
        if (!$user || empty($user['store_id'])) {
            return null;
        }
        $store = self::find((int) $user['store_id']);
        return $store && (int) $store['is_active'] === 1 ? $store : null;
    }

    public static function nearest(?float $latitude, ?float $longitude, int $limit = 5): array
    {
        $stores = self::active();
        if ($latitude === null || $longitude === null) {
            return array_slice($stores, 0, $limit);
        }
        foreach ($stores as &$store) {
            $distance = null;
            if ($store['latitude'] !== null && $store['longitude'] !== null) {
                $distance = self::distanceKm(
                    $latitude,
                    $longitude,
                    (float) $store['latitude'],
                    (float) $store['longitude']
                );
            }
            $store['distance_km'] = $distance;
            $store['distance_label'] = $distance === null ? null : self::distanceLabel($distance);
        }
        unset($store);
        usort($stores, static function (array $a, array $b): int {
            if ($a['distance_km'] === null) {
                return 1;
            }
            if ($b['distance_km'] === null) {
                return -1;
            }
            return $a['distance_km'] <=> $b['distance_km'];
        });
        return array_slice($stores, 0, $limit);
    }

    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }

    public static function distanceLabel(float $distanceKm): string
    {
        if ($distanceKm < 1) {
            return number_format($distanceKm * 1000, 0, ',', '.') . ' m';
        }
        if ($distanceKm < 100) {
            return number_format($distanceKm, 1, ',', '.') . ' km';
        }
        return number_format($distanceKm, 0, ',', '.') . ' km';
    }

    public static function pickupPoint(int $storeId): ?array
    {
        $point = self::fetchOne(
            'SELECT * FROM pickup_points WHERE store_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 1',
            [$storeId]
        );
        return $point ?: null;
    }

    public static function pickupPoints(int $storeId): array
    {
        return self::fetchAll(
            'SELECT * FROM pickup_points WHERE store_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC',
            [$storeId]
        );
    }

    public static function admins(int $storeId): array
    {
        return self::fetchAll(
            "SELECT id, name, email, phone, is_active, last_login_at FROM users
             WHERE store_id = ? AND role = 'admin' ORDER BY name ASC",
            [$storeId]
        );
    }

    public static function countActive(): int
    {
        return (int) self::fetchValue('SELECT COUNT(*) FROM stores WHERE is_active = 1');
    }

    public static function save(?int $id, array $data): array
    {
        $db = self::pdo();
        $db->beginTransaction();
        try {
            $values = [
                'code' => $data['code'],
                'name' => $data['name'],
                'slug' => $data['slug'],
                'address' => $data['address'],
                'district' => $data['district'] ?: null,
                'city' => $data['city'],
                'province' => $data['province'],
                'postal_code' => $data['postal_code'] ?: null,
                'phone' => $data['phone'] ?: null,
                'whatsapp' => $data['whatsapp'] ?: null,
                'email' => $data['email'] ?: null,
                'latitude' => $data['latitude'] !== null ? (float) $data['latitude'] : null,
                'longitude' => $data['longitude'] !== null ? (float) $data['longitude'] : null,
                'maps_url' => $data['maps_url'] ?: null,
                'opening_hours' => $data['opening_hours'] ?: null,
                'description' => $data['description'] ?: null,
                'image' => $data['image'] ?: null,
                'delivery_fee' => (float) $data['delivery_fee'],
                'is_active' => (int) $data['is_active'],
                'is_default' => (int) $data['is_default'],
                'sort_order' => (int) $data['sort_order'],
            ];

            if (!$values['maps_url'] && $values['latitude'] !== null && $values['longitude'] !== null) {
                $values['maps_url'] = 'https://maps.google.com/?q=' . $values['latitude'] . ',' . $values['longitude'];
            }

            if ($id) {
                $sets = implode(', ', array_map(static fn (string $key): string => $key . ' = ?', array_keys($values)));
                $db->prepare("UPDATE stores SET $sets WHERE id = ?")
                    ->execute([...array_values($values), $id]);
                $storeId = $id;
            } else {
                $columns = implode(', ', array_keys($values));
                $placeholders = implode(', ', array_fill(0, count($values), '?'));
                $db->prepare("INSERT INTO stores ($columns, created_at, updated_at) VALUES ($placeholders, NOW(), NOW())")
                    ->execute(array_values($values));
                $storeId = (int) $db->lastInsertId();
            }

            if ($values['is_default'] === 1) {
                $db->prepare('UPDATE stores SET is_default = 0 WHERE id <> ?')->execute([$storeId]);
            } else {
                $hasDefault = (int) $db->query('SELECT COUNT(*) FROM stores WHERE is_default = 1')->fetchColumn();
                if ($hasDefault === 0) {
                    $db->prepare('UPDATE stores SET is_default = 1 WHERE id = ?')->execute([$storeId]);
                }
            }

            $store = self::find($storeId);
            if ($store) {
                self::syncPickupPoint($store);
            }
            $db->commit();
            return ['success' => true, 'id' => $storeId, 'message' => $id ? 'Toko cabang diperbarui.' : 'Toko cabang ditambahkan.'];
        } catch (Throwable $exception) {
            $db->rollBack();
            error_log('Store save failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Gagal menyimpan toko cabang.'];
        }
    }

    public static function syncPickupPoint(array $store): void
    {
        $existing = self::fetchOne(
            'SELECT id FROM pickup_points WHERE store_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1',
            [(int) $store['id']]
        );
        $name = $store['name'];
        $address = $store['address'];
        $hours = $store['opening_hours'];
        $mapsUrl = $store['maps_url'];
        $latitude = $store['latitude'] !== null ? (float) $store['latitude'] : null;
        $longitude = $store['longitude'] !== null ? (float) $store['longitude'] : null;
        $active = (int) $store['is_active'];
        $sort = (int) ($store['sort_order'] ?? 0);

        if ($existing) {
            self::pdo()->prepare(
                'UPDATE pickup_points SET name = ?, address = ?, hours = ?, maps_url = ?, latitude = ?, longitude = ?,
                    is_active = ?, sort_order = ? WHERE id = ?'
            )->execute([$name, $address, $hours, $mapsUrl, $latitude, $longitude, $active, $sort, (int) $existing['id']]);
            return;
        }

        self::pdo()->prepare(
            'INSERT INTO pickup_points (store_id, name, address, hours, maps_url, latitude, longitude, is_active, sort_order, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute([(int) $store['id'], $name, $address, $hours, $mapsUrl, $latitude, $longitude, $active, $sort]);
    }

    public static function delete(int $id): array
    {
        $store = self::find($id);
        if (!$store) {
            return ['success' => false, 'message' => 'Toko cabang tidak ditemukan.'];
        }
        $bookings = (int) self::fetchValue('SELECT COUNT(*) FROM bookings WHERE store_id = ?', [$id]);
        if ($bookings > 0) {
            return ['success' => false, 'message' => 'Toko tidak bisa dihapus karena sudah memiliki ' . $bookings . ' pesanan. Nonaktifkan saja tokonya.'];
        }
        $admins = (int) self::fetchValue("SELECT COUNT(*) FROM users WHERE store_id = ? AND role = 'admin'", [$id]);
        if ($admins > 0) {
            return ['success' => false, 'message' => 'Pindahkan admin cabang ke toko lain sebelum menghapus toko ini.'];
        }
        if ((int) $store['is_active'] === 1) {
            $active = (int) self::fetchValue('SELECT COUNT(*) FROM stores WHERE is_active = 1');
            if ($active <= 1) {
                return ['success' => false, 'message' => 'Minimal satu toko cabang harus aktif.'];
            }
        }

        $db = self::pdo();
        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM pickup_points WHERE store_id = ?')->execute([$id]);
            $db->prepare('DELETE FROM stores WHERE id = ?')->execute([$id]);
            if ((int) $store['is_default'] === 1) {
                $next = (int) $db->query('SELECT id FROM stores WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 1')->fetchColumn();
                if ($next) {
                    $db->prepare('UPDATE stores SET is_default = 1 WHERE id = ?')->execute([$next]);
                }
            }
            $db->commit();
            return ['success' => true, 'message' => 'Toko cabang dihapus.'];
        } catch (Throwable $exception) {
            $db->rollBack();
            error_log('Store delete failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Gagal menghapus toko cabang.'];
        }
    }
}
