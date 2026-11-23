<?php
declare(strict_types=1);

class Item extends Model
{
    public static function find(int $id): ?array
    {
        $item = self::fetchOne(
            'SELECT i.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
             FROM items i JOIN categories c ON c.id = i.category_id
             WHERE i.id = ? LIMIT 1',
            [$id]
        );
        if (!$item) {
            return null;
        }
        $item = self::decodeJsonFields($item, ['specs', 'features']);
        $item['images'] = self::images($id);
        $item['variants'] = self::variants($id);
        return $item;
    }

    public static function findBySlug(string $slug): ?array
    {
        $id = (int) self::fetchValue('SELECT id FROM items WHERE slug = ? AND is_active = 1 LIMIT 1', [$slug]);
        return $id ? self::find($id) : null;
    }

    public static function images(int $itemId): array
    {
        return self::fetchAll(
            'SELECT * FROM item_images WHERE item_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC',
            [$itemId]
        );
    }

    public static function variants(int $itemId): array
    {
        $rows = self::fetchAll(
            'SELECT * FROM item_variants WHERE item_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC',
            [$itemId]
        );
        foreach ($rows as &$row) {
            $row['attributes'] = safe_json_decode($row['attributes'], []);
        }
        return $rows;
    }

    public static function filterValues(int $itemId): array
    {
        $rows = self::fetchAll(
            'SELECT cf.filter_key, cf.label, ifv.value
             FROM item_filter_values ifv
             JOIN category_filters cf ON cf.id = ifv.filter_id
             WHERE ifv.item_id = ?',
            [$itemId]
        );
        $out = [];
        foreach ($rows as $row) {
            $out[$row['filter_key']][] = $row['value'];
        }
        return $out;
    }

    public static function reviews(int $itemId, int $limit = 6): array
    {
        return self::fetchAll(
            "SELECT r.*, u.name AS user_name, u.avatar AS user_avatar
             FROM reviews r JOIN users u ON u.id = r.user_id
             WHERE r.item_id = ? AND r.is_published = 1
             ORDER BY r.created_at DESC LIMIT ?",
            [$itemId, $limit]
        );
    }

    public static function ratingBreakdown(int $itemId): array
    {
        $rows = self::fetchAll(
            'SELECT rating, COUNT(*) AS total FROM reviews WHERE item_id = ? AND is_published = 1 GROUP BY rating',
            [$itemId]
        );
        $out = array_fill(1, 5, 0);
        foreach ($rows as $row) {
            $out[(int) $row['rating']] = (int) $row['total'];
        }
        return $out;
    }

    /**
     * Pencarian katalog dengan filter dinamis dari item_filter_values.
     */
    public static function search(array $options = []): array
    {
        $q = trim((string) ($options['q'] ?? ''));
        $categoryId = (int) ($options['category_id'] ?? 0);
        $categorySlug = (string) ($options['category'] ?? '');
        $minPrice = $options['min_price'] ?? null;
        $maxPrice = $options['max_price'] ?? null;
        $capacity = (int) ($options['capacity'] ?? 0);
        $rating = (float) ($options['rating'] ?? 0);
        $sort = (string) ($options['sort'] ?? 'popular');
        $filters = is_array($options['filters'] ?? null) ? $options['filters'] : [];
        $featured = !empty($options['featured']);
        $start = $options['start'] ?? null;
        $end = $options['end'] ?? null;
        $page = max(1, (int) ($options['page'] ?? 1));
        $perPage = min(48, max(1, (int) ($options['per_page'] ?? 12)));
        $storeId = (int) ($options['store_id'] ?? 0);
        $preferredStoreId = (int) ($options['preferred_store_id'] ?? 0);
        $latitude = isset($options['lat']) && is_numeric($options['lat']) ? (float) $options['lat'] : null;
        $longitude = isset($options['lng']) && is_numeric($options['lng']) ? (float) $options['lng'] : null;
        $excludeItemId = (int) ($options['exclude_item_id'] ?? 0);

        $where = ['si.is_active = 1', 's.is_active = 1', 'i.is_active = 1', 'c.is_active = 1'];
        $params = [];

        if ($storeId > 0) {
            $where[] = 'si.store_id = ?';
            $params[] = $storeId;
        }
        if ($excludeItemId > 0) {
            $where[] = 'i.id <> ?';
            $params[] = $excludeItemId;
        }
        if ($categoryId > 0) {
            $where[] = 'i.category_id = ?';
            $params[] = $categoryId;
        } elseif ($categorySlug !== '') {
            $where[] = 'c.slug = ?';
            $params[] = $categorySlug;
        }
        if ($q !== '') {
            $where[] = '(i.name LIKE ? OR i.brand LIKE ? OR i.short_description LIKE ? OR i.search_keywords LIKE ? OR c.name LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        if ($minPrice !== null && $minPrice !== '' && (float) $minPrice > 0) {
            $where[] = 'si.price_per_day >= ?';
            $params[] = (float) $minPrice;
        }
        if ($maxPrice !== null && $maxPrice !== '' && (float) $maxPrice > 0) {
            $where[] = 'si.price_per_day <= ?';
            $params[] = (float) $maxPrice;
        }
        if ($capacity > 0) {
            $where[] = 'i.capacity_person >= ?';
            $params[] = $capacity;
        }
        if ($rating > 0) {
            $where[] = 'i.rating_avg >= ?';
            $params[] = $rating;
        }
        if ($featured) {
            $where[] = 'si.is_featured = 1';
        }
        foreach ($filters as $filterKey => $values) {
            $filterKey = preg_replace('/[^a-z0-9_]+/', '', strtolower((string) $filterKey));
            if ($filterKey === '') {
                continue;
            }
            $values = is_array($values) ? $values : [$values];
            $values = array_values(array_filter(array_map('strval', $values), static fn ($v) => $v !== ''));
            if (!$values) {
                continue;
            }
            $placeholders = implode(',', array_fill(0, count($values), '?'));
            $where[] = "EXISTS (
                SELECT 1 FROM item_filter_values ifv
                JOIN category_filters cf ON cf.id = ifv.filter_id
                WHERE ifv.item_id = i.id AND cf.filter_key = ? AND ifv.value IN ($placeholders)
            )";
            $params[] = $filterKey;
            foreach ($values as $value) {
                $params[] = $value;
            }
        }

        $whereSql = implode(' AND ', $where);
        $rows = self::fetchAll(
            "SELECT si.id AS store_item_id, si.store_id,
                si.price_per_day AS store_price, si.deposit AS store_deposit,
                si.replacement_value AS store_replacement, si.stock_total AS store_stock,
                si.min_duration AS store_min_duration, si.max_duration AS store_max_duration,
                si.is_featured AS store_featured, si.sort_order AS store_item_sort,
                i.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon,
                s.name AS store_name, s.slug AS store_slug, s.city AS store_city, s.province AS store_province,
                s.address AS store_address, s.phone AS store_phone, s.whatsapp AS store_whatsapp,
                s.email AS store_email, s.opening_hours AS store_hours,
                s.latitude AS store_latitude, s.longitude AS store_longitude,
                s.is_default AS store_default, s.sort_order AS store_sort_order
             FROM store_items si
             JOIN items i ON i.id = si.item_id
             JOIN categories c ON c.id = i.category_id
             JOIN stores s ON s.id = si.store_id
             WHERE $whereSql",
            $params
        );

        $grouped = [];
        foreach ($rows as $row) {
            $itemId = (int) $row['id'];
            $row['distance_km'] = null;
            $row['distance_label'] = null;
            if ($latitude !== null && $longitude !== null
                && $row['store_latitude'] !== null && $row['store_longitude'] !== null) {
                $row['distance_km'] = Store::distanceKm(
                    $latitude,
                    $longitude,
                    (float) $row['store_latitude'],
                    (float) $row['store_longitude']
                );
                $row['distance_label'] = Store::distanceLabel((float) $row['distance_km']);
            }
            $grouped[$itemId][] = $row;
        }

        $items = [];
        foreach ($grouped as $offers) {
            usort($offers, static function (array $a, array $b): int {
                $aDistance = $a['distance_km'] ?? INF;
                $bDistance = $b['distance_km'] ?? INF;
                if ($aDistance !== $bDistance) {
                    return $aDistance <=> $bDistance;
                }
                if ((float) $a['store_price'] !== (float) $b['store_price']) {
                    return (float) $a['store_price'] <=> (float) $b['store_price'];
                }
                return (int) $a['store_sort_order'] <=> (int) $b['store_sort_order'];
            });

            $chosen = $offers[0];
            if ($storeId > 0) {
                foreach ($offers as $offer) {
                    if ((int) $offer['store_id'] === $storeId) {
                        $chosen = $offer;
                        break;
                    }
                }
            } elseif ($preferredStoreId > 0) {
                foreach ($offers as $offer) {
                    if ((int) $offer['store_id'] === $preferredStoreId) {
                        $chosen = $offer;
                        break;
                    }
                }
            } else {
                foreach ($offers as $offer) {
                    if ((int) $offer['store_default'] === 1) {
                        $chosen = $offer;
                        break;
                    }
                }
            }

            $item = $chosen;
            $item['price_per_day'] = $chosen['store_price'];
            $item['deposit'] = $chosen['store_deposit'];
            $item['replacement_value'] = $chosen['store_replacement'];
            $item['stock_total'] = $chosen['store_stock'];
            $item['min_duration'] = $chosen['store_min_duration'];
            $item['max_duration'] = $chosen['store_max_duration'];
            $item['is_featured'] = $chosen['store_featured'];
            $item['store_count'] = count($grouped[(int) $chosen['id']] ?? []);
            $item['features'] = safe_json_decode($item['features'], []);
            $item['specs'] = safe_json_decode($item['specs'], []);
            $item['filter_values'] = self::filterValues((int) $item['id']);
            $item['available_now'] = StoreItem::availableQty(
                (int) $item['store_item_id'],
                null,
                $start ?: null,
                $end ?: null
            );
            $items[] = $item;
        }

        $sortMode = $sort;
        if ($sortMode === 'nearest' && ($latitude === null || $longitude === null)) {
            $sortMode = 'popular';
        }
        usort($items, static function (array $a, array $b) use ($sortMode): int {
            switch ($sortMode) {
                case 'nearest':
                    $aDistance = $a['distance_km'] ?? INF;
                    $bDistance = $b['distance_km'] ?? INF;
                    if ($aDistance !== $bDistance) {
                        return $aDistance <=> $bDistance;
                    }
                    break;
                case 'price_asc':
                    return (float) $a['price_per_day'] <=> (float) $b['price_per_day'];
                case 'price_desc':
                    return (float) $b['price_per_day'] <=> (float) $a['price_per_day'];
                case 'rating':
                    return (float) $b['rating_avg'] <=> (float) $a['rating_avg'];
                case 'newest':
                    return strcmp((string) $b['created_at'], (string) $a['created_at']);
                case 'name':
                    return strcasecmp((string) $a['name'], (string) $b['name']);
                case 'stock':
                    return (int) $b['stock_total'] <=> (int) $a['stock_total'];
            }
            return (int) $b['store_count'] <=> (int) $a['store_count']
                ?: (int) $b['rented_count'] <=> (int) $a['rented_count']
                ?: (float) $b['rating_avg'] <=> (float) $a['rating_avg'];
        });

        $total = count($items);
        $pagination = paginate($total, $perPage, $page);
        $items = array_slice($items, $pagination['offset'], $pagination['per_page']);

        return ['items' => $items, 'pagination' => $pagination, 'total' => $total];
    }

    public static function featured(int $limit = 8, ?int $storeId = null): array
    {
        return self::search(['featured' => true, 'per_page' => $limit, 'sort' => 'popular', 'store_id' => $storeId ?: 0])['items'];
    }

    public static function trending(int $limit = 8, ?int $storeId = null): array
    {
        return self::search(['per_page' => $limit, 'sort' => 'popular', 'store_id' => $storeId ?: 0])['items'];
    }

    public static function newest(int $limit = 4, ?int $storeId = null): array
    {
        return self::search(['per_page' => $limit, 'sort' => 'newest', 'store_id' => $storeId ?: 0])['items'];
    }

    public static function related(int $itemId, int $categoryId, int $limit = 4, ?int $storeId = null): array
    {
        return self::search([
            'category_id' => $categoryId,
            'exclude_item_id' => $itemId,
            'store_id' => $storeId ?: 0,
            'sort' => 'popular',
            'per_page' => $limit,
        ])['items'];
    }

    public static function availability(int $itemId, ?int $variantId, string $start, string $end, ?int $storeId = null): array
    {
        $item = self::find($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Alat tidak ditemukan.'];
        }
        $storeId = $storeId ?: default_store_id();
        $offer = store_item_for($storeId, $itemId);
        if (!$offer) {
            return ['success' => false, 'message' => 'Barang tidak tersedia di cabang ini.'];
        }
        $stock = StoreItem::stock((int) $offer['id'], $variantId);
        $available = StoreItem::availableQty((int) $offer['id'], $variantId, $start, $end);
        return [
            'success' => true,
            'stock' => $stock,
            'available' => $available,
            'start' => $start,
            'end' => $end,
            'duration_days' => duration_days($start, $end),
            'message' => item_availability_text($available),
        ];
    }

    public static function save(?int $id, array $data, array $filters = [], array $variants = []): array
    {
        $slug = slugify($data['slug'] ?: $data['name']);
        $duplicate = self::fetchValue('SELECT id FROM items WHERE slug = ? AND id <> ? LIMIT 1', [$slug, $id ?? 0]);
        if ($duplicate) {
            $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }
        $specs = is_array($data['specs'] ?? null) ? $data['specs'] : self::parseKeyValue((string) ($data['specs'] ?? ''));
        $features = is_array($data['features'] ?? null)
            ? array_values(array_filter($data['features']))
            : array_values(array_filter(array_map('trim', explode(',', (string) ($data['features'] ?? '')))));

        $ownerStoreId = array_key_exists('owner_store_id', $data) && $data['owner_store_id'] !== null
            ? (int) $data['owner_store_id']
            : null;

        $payload = [
            $data['category_id'],
            $ownerStoreId,
            $data['name'],
            $slug,
            $data['brand'] ?: null,
            $data['short_description'] ?: null,
            $data['description'] ?: null,
            (float) $data['price_per_day'],
            (float) ($data['deposit'] ?? 0),
            ($data['replacement_value'] ?? '') !== '' ? (float) $data['replacement_value'] : null,
            max(0, (int) $data['stock_total']),
            max(1, (int) ($data['min_duration'] ?? 1)),
            max(1, (int) ($data['max_duration'] ?? 30)),
            ($data['capacity_person'] ?? '') !== '' ? (int) $data['capacity_person'] : null,
            ($data['weight_gram'] ?? '') !== '' ? (int) $data['weight_gram'] : null,
            json_encode($specs, JSON_UNESCAPED_UNICODE),
            json_encode($features, JSON_UNESCAPED_UNICODE),
            $data['primary_image'] ?: null,
            !empty($data['is_featured']) ? 1 : 0,
            !empty($data['is_active']) ? 1 : 0,
            trim(implode(' ', [$data['name'], $data['brand'] ?? '', $data['short_description'] ?? '', implode(' ', $features)])),
        ];

        self::pdo()->beginTransaction();
        try {
            if ($id) {
                $stmt = self::pdo()->prepare(
                    'UPDATE items SET category_id = ?, owner_store_id = ?, name = ?, slug = ?, brand = ?, short_description = ?,
                        description = ?, price_per_day = ?, deposit = ?, replacement_value = ?, stock_total = ?,
                        min_duration = ?, max_duration = ?, capacity_person = ?, weight_gram = ?, specs = ?,
                        features = ?, primary_image = ?, is_featured = ?, is_active = ?, search_keywords = ?
                     WHERE id = ?'
                );
                $stmt->execute([...$payload, $id]);
                $itemId = $id;
            } else {
                $stmt = self::pdo()->prepare(
                    'INSERT INTO items (category_id, owner_store_id, name, slug, brand, short_description, description,
                        price_per_day, deposit, replacement_value, stock_total, min_duration, max_duration,
                        capacity_person, weight_gram, specs, features, primary_image, is_featured, is_active, search_keywords)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute($payload);
                $itemId = (int) self::pdo()->lastInsertId();
            }

            if ($data['primary_image'] ?? null) {
                self::pdo()->prepare('DELETE FROM item_images WHERE item_id = ? AND is_primary = 1')->execute([$itemId]);
                self::pdo()->prepare(
                    'INSERT INTO item_images (item_id, path, alt, is_primary, sort_order) VALUES (?, ?, ?, 1, 0)'
                )->execute([$itemId, $data['primary_image'], $data['name']]);
            }

            self::pdo()->prepare('DELETE FROM item_filter_values WHERE item_id = ?')->execute([$itemId]);
            foreach ($filters as $filterId => $values) {
                $values = is_array($values) ? $values : [$values];
                foreach (array_unique(array_filter(array_map('strval', $values))) as $value) {
                    self::pdo()->prepare(
                        'INSERT IGNORE INTO item_filter_values (item_id, filter_id, value) VALUES (?, ?, ?)'
                    )->execute([$itemId, (int) $filterId, $value]);
                }
            }

            $existingVariants = self::fetchAll(
                'SELECT id, label FROM item_variants WHERE item_id = ? ORDER BY id ASC',
                [$itemId]
            );
            $variantById = [];
            $variantByLabel = [];
            foreach ($existingVariants as $existingVariant) {
                $variantById[(int) $existingVariant['id']] = true;
                $variantByLabel[mb_strtolower((string) $existingVariant['label'])] = (int) $existingVariant['id'];
            }

            $keepVariantIds = [];
            $sort = 0;
            foreach ($variants as $variant) {
                $label = trim((string) ($variant['label'] ?? ''));
                if ($label === '') {
                    continue;
                }
                $sort++;
                $variantId = (int) ($variant['id'] ?? 0);
                if ($variantId <= 0 || !isset($variantById[$variantId])) {
                    $variantId = $variantByLabel[mb_strtolower($label)] ?? 0;
                }
                $values = [
                    $variant['sku'] ?: null,
                    $label,
                    json_encode(['ukuran' => $label], JSON_UNESCAPED_UNICODE),
                    max(0, (int) ($variant['stock_total'] ?? 0)),
                    ($variant['price_per_day'] ?? '') !== '' ? (float) $variant['price_per_day'] : null,
                    $sort,
                ];
                if ($variantId > 0) {
                    self::pdo()->prepare(
                        'UPDATE item_variants SET sku = ?, label = ?, attributes = ?, stock_total = ?, price_per_day = ?,
                            is_active = 1, sort_order = ? WHERE id = ? AND item_id = ?'
                    )->execute([...$values, $variantId, $itemId]);
                    $keepVariantIds[] = $variantId;
                    continue;
                }
                self::pdo()->prepare(
                    'INSERT INTO item_variants (item_id, sku, label, attributes, stock_total, price_per_day, is_active, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?, 1, ?)'
                )->execute([
                    $itemId,
                    $values[0],
                    $values[1],
                    $values[2],
                    $values[3],
                    $values[4],
                    $sort,
                ]);
                $keepVariantIds[] = (int) self::pdo()->lastInsertId();
            }

            if ($keepVariantIds) {
                $placeholders = implode(',', array_fill(0, count($keepVariantIds), '?'));
                self::pdo()->prepare(
                    "UPDATE item_variants SET is_active = 0 WHERE item_id = ? AND id NOT IN ($placeholders)"
                )->execute([$itemId, ...$keepVariantIds]);
            } else {
                self::pdo()->prepare('UPDATE item_variants SET is_active = 0 WHERE item_id = ?')->execute([$itemId]);
            }

            self::pdo()->commit();
            return ['success' => true, 'message' => 'Item berhasil disimpan.', 'id' => $itemId];
        } catch (Throwable $exception) {
            self::pdo()->rollBack();
            error_log('Item save failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Gagal menyimpan item: ' . $exception->getMessage()];
        }
    }

    public static function delete(int $id): array
    {
        $used = (int) self::fetchValue('SELECT COUNT(*) FROM booking_items WHERE item_id = ?', [$id]);
        if ($used > 0) {
            self::pdo()->prepare('UPDATE items SET is_active = 0 WHERE id = ?')->execute([$id]);
            return ['success' => true, 'message' => 'Item pernah disewa, jadi dinonaktifkan (tidak dihapus) agar riwayat tetap aman.'];
        }
        self::pdo()->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);
        return ['success' => true, 'message' => 'Item dihapus.'];
    }

    private static function parseKeyValue(string $text): array
    {
        $out = [];
        foreach (preg_split('/\r?\n/', $text) ?: [] as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode(':', $line, 2));
            if ($key !== '') {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    public static function countActive(): int
    {
        return (int) self::fetchValue('SELECT COUNT(*) FROM items WHERE is_active = 1');
    }

    public static function totalStock(): int
    {
        return (int) self::fetchValue('SELECT COALESCE(SUM(stock_total), 0) FROM items WHERE is_active = 1');
    }

    public static function lowStock(int $threshold = 3, int $limit = 6): array
    {
        return self::fetchAll(
            'SELECT i.*, c.name AS category_name FROM items i JOIN categories c ON c.id = i.category_id
             WHERE i.is_active = 1 AND i.stock_total <= ? ORDER BY i.stock_total ASC, i.rented_count DESC LIMIT ?',
            [$threshold, $limit]
        );
    }
}
