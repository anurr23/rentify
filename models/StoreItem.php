<?php
declare(strict_types=1);

/**
 * Inventaris barang per cabang. Harga, deposit, stok, dan status di sini
 * adalah sumber utama untuk katalog, keranjang, booking, dan laporan cabang.
 */
class StoreItem extends Model
{
    public static function find(int $id): ?array
    {
        $row = self::fetchOne(
            'SELECT si.*, i.name, i.slug, i.brand, i.category_id, i.short_description, i.description,
                i.capacity_person, i.weight_gram, i.specs, i.features, i.primary_image, i.rating_avg,
                i.rating_count, i.rented_count, i.search_keywords, i.owner_store_id,
                c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon,
                s.name AS store_name, s.slug AS store_slug, s.city AS store_city, s.province AS store_province,
                s.address AS store_address, s.phone AS store_phone, s.whatsapp AS store_whatsapp,
                s.email AS store_email, s.opening_hours AS store_hours, s.latitude AS store_latitude,
                s.longitude AS store_longitude
             FROM store_items si
             JOIN items i ON i.id = si.item_id
             JOIN categories c ON c.id = i.category_id
             JOIN stores s ON s.id = si.store_id
             WHERE si.id = ? LIMIT 1',
            [$id]
        );
        if (!$row) {
            return null;
        }
        $row['features'] = safe_json_decode($row['features'], []);
        $row['specs'] = safe_json_decode($row['specs'], []);
        return $row;
    }

    public static function findForStore(int $storeId, int $itemId): ?array
    {
        $id = (int) self::fetchValue(
            'SELECT id FROM store_items WHERE store_id = ? AND item_id = ? LIMIT 1',
            [$storeId, $itemId]
        );
        return $id ? self::find($id) : null;
    }

    public static function findByStoreItemId(int $storeItemId): ?array
    {
        return self::find($storeItemId);
    }

    public static function forItem(int $itemId, ?float $latitude = null, ?float $longitude = null): array
    {
        $rows = self::fetchAll(
            'SELECT si.*, s.name AS store_name, s.slug AS store_slug, s.city AS store_city,
                s.province AS store_province, s.address AS store_address, s.phone AS store_phone,
                s.whatsapp AS store_whatsapp, s.email AS store_email, s.opening_hours AS store_hours,
                s.latitude AS store_latitude, s.longitude AS store_longitude
             FROM store_items si
             JOIN stores s ON s.id = si.store_id
             WHERE si.item_id = ? AND si.is_active = 1 AND s.is_active = 1
             ORDER BY s.sort_order ASC, s.id ASC',
            [$itemId]
        );
        foreach ($rows as &$row) {
            $row['distance_km'] = null;
            $row['distance_label'] = null;
            if ($latitude !== null && $longitude !== null && $row['store_latitude'] !== null && $row['store_longitude'] !== null) {
                $row['distance_km'] = Store::distanceKm(
                    $latitude,
                    $longitude,
                    (float) $row['store_latitude'],
                    (float) $row['store_longitude']
                );
                $row['distance_label'] = Store::distanceLabel((float) $row['distance_km']);
            }
        }
        unset($row);
        usort($rows, static function (array $a, array $b): int {
            if ($a['distance_km'] === null) {
                return 1;
            }
            if ($b['distance_km'] === null) {
                return -1;
            }
            return $a['distance_km'] <=> $b['distance_km'];
        });
        return $rows;
    }

    public static function forStore(int $storeId, int $limit = 12, int $offset = 0): array
    {
        return self::fetchAll(
            'SELECT si.*, i.name, i.slug, i.brand, i.primary_image, i.rating_avg, i.rating_count,
                c.name AS category_name
             FROM store_items si
             JOIN items i ON i.id = si.item_id
             JOIN categories c ON c.id = i.category_id
             WHERE si.store_id = ? AND si.is_active = 1 AND i.is_active = 1 AND c.is_active = 1
             ORDER BY si.is_featured DESC, si.sort_order ASC, si.id ASC
             LIMIT ? OFFSET ?',
            [$storeId, $limit, $offset]
        );
    }

    public static function searchInventory(int $storeId, array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $where = [];
        $params = [];
        if ($storeId > 0) {
            $where[] = 'si.store_id = ?';
            $params[] = $storeId;
        }
        if (!$where) {
            $where[] = '1=1';
        }
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(i.name LIKE ? OR i.brand LIKE ? OR i.slug LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where[] = 'i.category_id = ?';
            $params[] = $categoryId;
        }
        $status = (string) ($filters['status'] ?? 'semua');
        if ($status === 'aktif') {
            $where[] = 'si.is_active = 1';
        } elseif ($status === 'nonaktif') {
            $where[] = 'si.is_active = 0';
        } elseif ($status === 'habis') {
            $where[] = 'si.stock_total <= 0';
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) self::fetchValue(
            "SELECT COUNT(*) FROM store_items si
             JOIN items i ON i.id = si.item_id
             JOIN stores s ON s.id = si.store_id
             WHERE $whereSql",
            $params
        );
        $pagination = paginate($total, $perPage, $page);
        $rows = self::fetchAll(
            "SELECT si.*, i.name, i.slug, i.brand, i.primary_image, i.rating_avg, i.rating_count,
                i.category_id, i.owner_store_id, c.name AS category_name, c.slug AS category_slug,
                s.name AS store_name, s.slug AS store_slug, s.city AS store_city,
                (SELECT COUNT(*) FROM item_variants v WHERE v.item_id = i.id AND v.is_active = 1) AS variant_count
             FROM store_items si
             JOIN items i ON i.id = si.item_id
             JOIN categories c ON c.id = i.category_id
             JOIN stores s ON s.id = si.store_id
             WHERE $whereSql
             ORDER BY s.sort_order ASC, si.is_active DESC, i.created_at DESC, si.id DESC
             LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
            $params
        );
        return ['items' => $rows, 'pagination' => $pagination, 'total' => $total];
    }

    public static function countsByStore(): array
    {
        $rows = self::fetchAll(
            'SELECT store_id, COUNT(*) AS items, COALESCE(SUM(stock_total), 0) AS stock
             FROM store_items WHERE is_active = 1 GROUP BY store_id'
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['store_id']] = [
                'items' => (int) $row['items'],
                'stock' => (int) $row['stock'],
            ];
        }
        return $out;
    }

    public static function stock(int $storeItemId, ?int $variantId = null): int
    {
        if ($variantId) {
            $stock = self::fetchValue(
                'SELECT siv.stock_total FROM store_item_variants siv
                 JOIN item_variants v ON v.id = siv.variant_id
                 WHERE siv.store_item_id = ? AND siv.variant_id = ? AND siv.is_active = 1 AND v.is_active = 1',
                [$storeItemId, $variantId]
            );
            return $stock === false ? 0 : (int) $stock;
        }
        $stock = self::fetchValue('SELECT stock_total FROM store_items WHERE id = ? AND is_active = 1', [$storeItemId]);
        return $stock === false ? 0 : (int) $stock;
    }

    public static function bookedQty(
        int $storeId,
        int $itemId,
        ?int $variantId,
        string $start,
        string $end,
        ?int $excludeBookingId = null
    ): int {
        $sql = "SELECT COALESCE(SUM(bi.qty), 0)
                FROM booking_items bi
                JOIN bookings b ON b.id = bi.booking_id
                WHERE b.store_id = ?
                  AND bi.item_id = ?
                  AND (bi.variant_id <=> ?)
                  AND b.status IN ('pending','confirmed','preparing','ready','ongoing')
                  AND (b.status <> 'pending' OR b.hold_expires_at IS NULL OR b.hold_expires_at > NOW())
                  AND bi.start_date <= ?
                  AND bi.end_date >= ?";
        $params = [$storeId, $itemId, $variantId, $end, $start];
        if ($excludeBookingId) {
            $sql .= ' AND b.id <> ?';
            $params[] = $excludeBookingId;
        }
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function availableQty(
        int $storeItemId,
        ?int $variantId = null,
        ?string $start = null,
        ?string $end = null,
        ?int $excludeBookingId = null
    ): int {
        $offer = self::find($storeItemId);
        if (!$offer) {
            return 0;
        }
        $stock = self::stock($storeItemId, $variantId);
        if (!$start || !$end) {
            return $stock;
        }
        return max(0, $stock - self::bookedQty(
            (int) $offer['store_id'],
            (int) $offer['item_id'],
            $variantId,
            $start,
            $end,
            $excludeBookingId
        ));
    }

    public static function variants(int $storeItemId): array
    {
        return self::fetchAll(
            'SELECT v.id, v.sku, v.label, v.attributes, v.sort_order,
                COALESCE(siv.stock_total, 0) AS stock_total,
                COALESCE(siv.price_per_day, si.price_per_day) AS price_per_day,
                COALESCE(siv.is_active, v.is_active) AS is_active
             FROM item_variants v
             JOIN store_items si ON si.id = ?
             LEFT JOIN store_item_variants siv ON siv.store_item_id = si.id AND siv.variant_id = v.id
             WHERE v.item_id = si.item_id AND v.is_active = 1
             ORDER BY v.sort_order ASC, v.id ASC',
            [$storeItemId]
        );
    }

    public static function save(int $storeId, int $itemId, array $data, ?int $userId = null): array
    {
        $db = self::pdo();
        try {
            $db->prepare(
                'INSERT INTO store_items (store_id, item_id, price_per_day, deposit, replacement_value, stock_total,
                    min_duration, max_duration, is_active, is_featured, sort_order, created_by, updated_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE price_per_day = VALUES(price_per_day), deposit = VALUES(deposit),
                    replacement_value = VALUES(replacement_value), stock_total = VALUES(stock_total),
                    min_duration = VALUES(min_duration), max_duration = VALUES(max_duration),
                    is_active = VALUES(is_active), is_featured = VALUES(is_featured), sort_order = VALUES(sort_order),
                    updated_by = VALUES(updated_by), updated_at = NOW()'
            )->execute([
                $storeId,
                $itemId,
                max(0, (float) ($data['price_per_day'] ?? 0)),
                max(0, (float) ($data['deposit'] ?? 0)),
                ($data['replacement_value'] ?? '') !== '' ? (float) $data['replacement_value'] : null,
                max(0, (int) ($data['stock_total'] ?? 0)),
                max(1, (int) ($data['min_duration'] ?? 1)),
                max(1, (int) ($data['max_duration'] ?? 30)),
                !empty($data['is_active']) ? 1 : 0,
                !empty($data['is_featured']) ? 1 : 0,
                (int) ($data['sort_order'] ?? 0),
                $userId,
                $userId,
            ]);
            $storeItem = self::findForStore($storeId, $itemId);
            if (!$storeItem) {
                return ['success' => false, 'message' => 'Gagal menyimpan inventaris cabang.'];
            }
            self::syncVariants((int) $storeItem['id'], $data['variants'] ?? []);
            return ['success' => true, 'id' => (int) $storeItem['id'], 'message' => 'Inventaris cabang disimpan.'];
        } catch (Throwable $exception) {
            error_log('Store item save failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Gagal menyimpan inventaris cabang.'];
        }
    }

    public static function syncVariants(int $storeItemId, array $variants): void
    {
        if (!$variants) {
            return;
        }
        $offer = self::find($storeItemId);
        if (!$offer) {
            return;
        }
        $stmt = self::pdo()->prepare(
            'INSERT INTO store_item_variants (store_item_id, variant_id, stock_total, price_per_day, is_active)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE stock_total = VALUES(stock_total), price_per_day = VALUES(price_per_day),
                is_active = VALUES(is_active)'
        );
        $masterVariants = self::fetchAll(
            'SELECT id FROM item_variants WHERE item_id = ? AND is_active = 1',
            [(int) $offer['item_id']]
        );
        $masterIds = array_map(static fn (array $row): int => (int) $row['id'], $masterVariants);
        foreach ($masterIds as $variantId) {
            $variant = $variants[$variantId] ?? [];
            if (!$variant) {
                continue;
            }
            $stmt->execute([
                $storeItemId,
                $variantId,
                max(0, (int) ($variant['stock_total'] ?? 0)),
                ($variant['price_per_day'] ?? '') !== '' ? (float) $variant['price_per_day'] : null,
                array_key_exists('is_active', $variant) ? (!empty($variant['is_active']) ? 1 : 0) : 1,
            ]);
        }
    }

    public static function delete(int $id): array
    {
        $offer = self::find($id);
        if (!$offer) {
            return ['success' => false, 'message' => 'Inventaris tidak ditemukan.'];
        }
        $used = (int) self::fetchValue('SELECT COUNT(*) FROM booking_items WHERE store_item_id = ?', [$id]);
        if ($used > 0) {
            self::pdo()->prepare('UPDATE store_items SET is_active = 0 WHERE id = ?')->execute([$id]);
            return ['success' => true, 'message' => 'Barang pernah disewa, jadi dinonaktifkan agar riwayat aman.'];
        }
        self::pdo()->prepare('DELETE FROM store_items WHERE id = ?')->execute([$id]);
        return ['success' => true, 'message' => 'Inventaris cabang dihapus.'];
    }

    public static function lowStock(?int $storeId = null, int $threshold = 3, int $limit = 6): array
    {
        $where = ['si.is_active = 1', 'si.stock_total <= ?'];
        $params = [$threshold];
        if ($storeId !== null) {
            $where[] = 'si.store_id = ?';
            $params[] = $storeId;
        }
        $params[] = $limit;
        return self::fetchAll(
            'SELECT si.*, i.name, i.primary_image, c.name AS category_name, s.name AS store_name
             FROM store_items si
             JOIN items i ON i.id = si.item_id
             JOIN categories c ON c.id = i.category_id
             JOIN stores s ON s.id = si.store_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY si.stock_total ASC, i.name ASC LIMIT ?',
            $params
        );
    }

    public static function countForStore(int $storeId): int
    {
        return (int) self::fetchValue(
            'SELECT COUNT(*) FROM store_items WHERE store_id = ? AND is_active = 1',
            [$storeId]
        );
    }
}
