<?php
declare(strict_types=1);

function cart_raw(): array
{
    return is_array($_SESSION['cart'] ?? null) ? $_SESSION['cart'] : [];
}

function cart_key(int $storeItemId, ?int $variantId): string
{
    return $storeItemId . ':' . ($variantId ?: 0);
}

function cart_dates(): array
{
    $dates = $_SESSION['cart_dates'] ?? [];
    return [
        'start' => $dates['start'] ?? '',
        'end' => $dates['end'] ?? '',
    ];
}

function cart_set_dates(?string $start, ?string $end): array
{
    if (!$start || !$end) {
        $_SESSION['cart_dates'] = ['start' => '', 'end' => ''];
        return ['success' => true, 'message' => 'Tanggal sewa dikosongkan.'];
    }
    try {
        $s = new DateTimeImmutable($start);
        $e = new DateTimeImmutable($end);
    } catch (Throwable) {
        return ['success' => false, 'message' => 'Format tanggal tidak valid.'];
    }
    $today = new DateTimeImmutable(today());
    if ($s < $today) {
        return ['success' => false, 'message' => 'Tanggal mulai tidak boleh di masa lalu.'];
    }
    if ($e < $s) {
        return ['success' => false, 'message' => 'Tanggal kembali harus setelah tanggal mulai.'];
    }
    if ((int) $s->diff($e)->days > 30) {
        return ['success' => false, 'message' => 'Durasi sewa maksimal 30 hari.'];
    }
    $_SESSION['cart_dates'] = ['start' => $s->format('Y-m-d'), 'end' => $e->format('Y-m-d')];
    return ['success' => true, 'message' => 'Tanggal sewa disimpan.'];
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

function cart_count(): int
{
    $count = 0;
    foreach (cart_raw() as $item) {
        $count += (int) ($item['qty'] ?? 0);
    }
    return $count;
}

function store_item_for(int $storeId, int $itemId): ?array
{
    $stmt = Database::conn()->prepare(
        'SELECT * FROM store_items WHERE store_id = ? AND item_id = ? LIMIT 1'
    );
    $stmt->execute([$storeId, $itemId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function default_store_id(): int
{
    $store = Store::defaultStore();
    return $store ? (int) $store['id'] : 0;
}

function cart_store_id(): int
{
    foreach (cart_raw() as $entry) {
        if (!empty($entry['store_id'])) {
            return (int) $entry['store_id'];
        }
    }
    return 0;
}

function item_stock(PDO $db, int $itemId, ?int $variantId, ?int $storeId = null): int
{
    $storeId = $storeId ?: default_store_id();
    $offer = store_item_for($storeId, $itemId);
    if (!$offer) {
        return 0;
    }
    if ($variantId) {
        $stmt = $db->prepare(
            'SELECT siv.stock_total FROM store_item_variants siv
             JOIN item_variants v ON v.id = siv.variant_id
             WHERE siv.store_item_id = ? AND siv.variant_id = ? AND siv.is_active = 1 AND v.is_active = 1'
        );
        $stmt->execute([(int) $offer['id'], $variantId]);
        $stock = $stmt->fetchColumn();
        return $stock === false ? 0 : (int) $stock;
    }
    return (int) $offer['stock_total'];
}

function item_booked_qty(
    PDO $db,
    int $itemId,
    ?int $variantId,
    string $start,
    string $end,
    ?int $excludeBookingId = null,
    ?int $storeId = null
): int {
    $storeId = $storeId ?: default_store_id();
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
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function item_available_qty(
    PDO $db,
    int $itemId,
    ?int $variantId,
    ?string $start = null,
    ?string $end = null,
    ?int $excludeBookingId = null,
    ?int $storeId = null
): int {
    $storeId = $storeId ?: default_store_id();
    $stock = item_stock($db, $itemId, $variantId, $storeId);
    if (!$start || !$end) {
        return $stock;
    }
    return max(0, $stock - item_booked_qty($db, $itemId, $variantId, $start, $end, $excludeBookingId, $storeId));
}

function cart_add(
    int $itemId,
    ?int $variantId,
    int $qty = 1,
    ?string $start = null,
    ?string $end = null,
    ?int $storeId = null
): array {
    $db = Database::conn();
    $qty = max(1, $qty);
    $requestedStoreId = $storeId ?: cart_store_id() ?: default_store_id();
    $existingStoreId = cart_store_id();

    if ($existingStoreId > 0 && $requestedStoreId > 0 && $existingStoreId !== $requestedStoreId) {
        $existingStore = Store::find($existingStoreId);
        $newStore = Store::find($requestedStoreId);
        return [
            'success' => false,
            'message' => 'Keranjang berisi gear dari ' . ($existingStore['name'] ?? 'cabang lain')
                . '. Kosongkan keranjang dulu untuk beralih ke ' . ($newStore['name'] ?? 'cabang ini') . '.',
            'conflict' => true,
            'current_store' => $existingStore ? [
                'id' => (int) $existingStore['id'],
                'name' => $existingStore['name'],
            ] : null,
            'new_store' => $newStore ? [
                'id' => (int) $newStore['id'],
                'name' => $newStore['name'],
            ] : null,
        ];
    }

    $store = Store::find($requestedStoreId);
    if (!$store || (int) $store['is_active'] !== 1) {
        return ['success' => false, 'message' => 'Toko cabang tidak tersedia.'];
    }

    $stmt = $db->prepare('SELECT * FROM items WHERE id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) {
        return ['success' => false, 'message' => 'Alat tidak ditemukan atau sudah tidak aktif.'];
    }

    $offer = store_item_for($requestedStoreId, $itemId);
    if (!$offer || (int) $offer['is_active'] !== 1) {
        return ['success' => false, 'message' => $item['name'] . ' tidak tersedia di ' . $store['name'] . '.'];
    }

    $variant = null;
    $hasVariants = (int) $db->query(
        'SELECT COUNT(*) FROM item_variants WHERE item_id = ' . (int) $itemId . ' AND is_active = 1'
    )->fetchColumn() > 0;
    if ($hasVariants) {
        if (!$variantId) {
            return ['success' => false, 'message' => 'Pilih varian/ukuran terlebih dahulu.'];
        }
        $stmt = $db->prepare(
            'SELECT v.*, siv.stock_total AS store_stock, siv.price_per_day AS store_price, siv.is_active AS store_active
             FROM item_variants v
             LEFT JOIN store_item_variants siv ON siv.variant_id = v.id AND siv.store_item_id = ?
             WHERE v.id = ? AND v.item_id = ? AND v.is_active = 1 LIMIT 1'
        );
        $stmt->execute([(int) $offer['id'], $variantId, $itemId]);
        $variant = $stmt->fetch();
        if (!$variant || empty($variant['store_active'])) {
            return ['success' => false, 'message' => 'Varian tidak tersedia di cabang ini.'];
        }
    } else {
        $variantId = null;
    }

    if ($start && $end) {
        $set = cart_set_dates($start, $end);
        if (!$set['success']) {
            return $set;
        }
    } else {
        $dates = cart_dates();
        $start = $dates['start'] ?: null;
        $end = $dates['end'] ?: null;
    }

    $key = cart_key((int) $offer['id'], $variantId);
    $existing = (int) ($_SESSION['cart'][$key]['qty'] ?? 0);
    $newQty = $existing + $qty;
    $available = item_available_qty($db, $itemId, $variantId, $start, $end, null, $requestedStoreId);
    if ($newQty > $available) {
        return [
            'success' => false,
            'message' => $available > 0
                ? 'Stok tidak cukup. Tersedia ' . $available . ' unit untuk tanggal ini.'
                : 'Stok habis untuk tanggal yang dipilih.',
        ];
    }

    $_SESSION['cart'][$key] = [
        'item_id' => $itemId,
        'store_id' => $requestedStoreId,
        'store_item_id' => (int) $offer['id'],
        'variant_id' => $variantId,
        'qty' => $newQty,
        'added_at' => time(),
    ];
    $_SESSION['cart_last_change'] = time();

    $unitPrice = (float) ($variant['store_price'] ?? $variant['price_per_day'] ?? $offer['price_per_day']);
    return [
        'success' => true,
        'message' => $item['name'] . ' ditambahkan ke keranjang dari ' . $store['name'] . '.',
        'cart_count' => cart_count(),
        'item' => ['item_id' => $itemId, 'variant_id' => $variantId, 'qty' => $newQty, 'price_per_day' => $unitPrice],
        'store' => ['id' => (int) $store['id'], 'name' => $store['name'], 'slug' => $store['slug']],
    ];
}

function cart_update(string $key, int $qty): array
{
    $qty = max(1, $qty);
    $entry = $_SESSION['cart'][$key] ?? null;
    if (!$entry) {
        return ['success' => false, 'message' => 'Item tidak ada di keranjang.'];
    }
    $dates = cart_dates();
    $available = item_available_qty(
        Database::conn(),
        (int) $entry['item_id'],
        !empty($entry['variant_id']) ? (int) $entry['variant_id'] : null,
        $dates['start'] ?: null,
        $dates['end'] ?: null,
        null,
        (int) ($entry['store_id'] ?? 0)
    );
    if ($qty > $available) {
        return ['success' => false, 'message' => 'Stok tersedia hanya ' . $available . ' unit.'];
    }
    $_SESSION['cart'][$key]['qty'] = $qty;
    $_SESSION['cart_last_change'] = time();
    return ['success' => true, 'message' => 'Jumlah diperbarui.', 'qty' => $qty];
}

function cart_remove(string $key): array
{
    if (!isset($_SESSION['cart'][$key])) {
        return ['success' => false, 'message' => 'Item tidak ditemukan.'];
    }
    unset($_SESSION['cart'][$key]);
    $_SESSION['cart_last_change'] = time();
    return ['success' => true, 'message' => 'Item dihapus dari keranjang.'];
}

function cart_enrich(): array
{
    $cart = cart_raw();
    $dates = cart_dates();
    $duration = ($dates['start'] && $dates['end']) ? duration_days($dates['start'], $dates['end']) : 0;
    $items = [];
    $subtotal = 0.0;
    $depositTotal = 0.0;
    $errors = [];
    $store = null;

    if (!$cart) {
        return [
            'items' => [],
            'dates' => $dates,
            'duration' => $duration,
            'subtotal' => 0,
            'deposit' => 0,
            'count' => 0,
            'errors' => [],
            'store' => null,
        ];
    }

    $db = Database::conn();
    $storeItemIds = [];
    foreach ($cart as $key => $entry) {
        if (!empty($entry['store_item_id'])) {
            $storeItemIds[] = (int) $entry['store_item_id'];
            continue;
        }
        $fallbackStoreId = (int) ($entry['store_id'] ?? 0) ?: default_store_id();
        $offer = store_item_for($fallbackStoreId, (int) $entry['item_id']);
        if ($offer) {
            $_SESSION['cart'][$key]['store_id'] = $fallbackStoreId;
            $_SESSION['cart'][$key]['store_item_id'] = (int) $offer['id'];
            $storeItemIds[] = (int) $offer['id'];
        }
    }
    $storeItemIds = array_values(array_unique($storeItemIds));
    if (!$storeItemIds) {
        cart_clear();
        return [
            'items' => [], 'dates' => $dates, 'duration' => $duration, 'subtotal' => 0,
            'deposit' => 0, 'count' => 0,
            'errors' => ['Item di keranjang tidak lagi tersedia di cabangnya.'], 'store' => null,
        ];
    }

    $placeholders = implode(',', array_fill(0, count($storeItemIds), '?'));
    $stmt = $db->prepare(
        "SELECT si.*, i.name, i.slug, i.brand, i.primary_image, i.features, i.category_id,
            c.name AS category_name, c.slug AS category_slug,
            s.name AS store_name, s.slug AS store_slug, s.city AS store_city,
            s.phone AS store_phone, s.whatsapp AS store_whatsapp, s.email AS store_email,
            s.address AS store_address, s.opening_hours AS store_hours
         FROM store_items si
         JOIN items i ON i.id = si.item_id
         JOIN categories c ON c.id = i.category_id
         JOIN stores s ON s.id = si.store_id
         WHERE si.id IN ($placeholders) AND si.is_active = 1 AND i.is_active = 1 AND c.is_active = 1 AND s.is_active = 1"
    );
    $stmt->execute($storeItemIds);
    $offersById = [];
    foreach ($stmt->fetchAll() as $row) {
        $offersById[(int) $row['id']] = $row;
    }

    $variantRows = [];
    $vStmt = $db->prepare(
        "SELECT siv.*, v.label, v.sku
         FROM store_item_variants siv
         JOIN item_variants v ON v.id = siv.variant_id
         WHERE siv.store_item_id IN ($placeholders)"
    );
    $vStmt->execute($storeItemIds);
    foreach ($vStmt->fetchAll() as $variant) {
        $variantRows[(int) $variant['store_item_id'] . ':' . (int) $variant['variant_id']] = $variant;
    }

    $storeIds = [];
    foreach ($cart as $key => $entry) {
        $storeItemId = (int) ($entry['store_item_id'] ?? 0);
        $offer = $offersById[$storeItemId] ?? null;
        if (!$offer) {
            unset($_SESSION['cart'][$key]);
            $errors[] = 'Salah satu item sudah tidak tersedia di cabangnya dan dihapus dari keranjang.';
            continue;
        }
        $storeIds[(int) $offer['store_id']] = true;
        $itemId = (int) $offer['item_id'];
        $variantId = !empty($entry['variant_id']) ? (int) $entry['variant_id'] : null;
        $qty = max(1, (int) $entry['qty']);
        $variant = $variantId ? ($variantRows[$storeItemId . ':' . $variantId] ?? null) : null;
        if ($variantId && !$variant) {
            unset($_SESSION['cart'][$key]);
            $errors[] = e($offer['name']) . ' variannya tidak tersedia di cabang ini.';
            continue;
        }
        $unitPrice = (float) ($variant['price_per_day'] ?? $offer['price_per_day']);
        $days = $duration > 0 ? $duration : 1;
        $lineSubtotal = $unitPrice * $qty * $days;
        $lineDeposit = (float) $offer['deposit'] * $qty;
        $available = StoreItem::availableQty(
            $storeItemId,
            $variantId,
            $dates['start'] ?: null,
            $dates['end'] ?: null
        );
        if ($dates['start'] && $dates['end'] && $qty > $available) {
            $errors[] = e($offer['name']) . ' hanya tersedia ' . $available . ' unit di ' . e($offer['store_name']) . ' untuk tanggal ini.';
        }
        $subtotal += $lineSubtotal;
        $depositTotal += $lineDeposit;
        $items[] = [
            'key' => $key,
            'item_id' => $itemId,
            'store_id' => (int) $offer['store_id'],
            'store_item_id' => $storeItemId,
            'store_name' => $offer['store_name'],
            'store_slug' => $offer['store_slug'],
            'store_city' => $offer['store_city'],
            'variant_id' => $variantId,
            'name' => $offer['name'],
            'slug' => $offer['slug'],
            'brand' => $offer['brand'],
            'category_name' => $offer['category_name'],
            'image' => $offer['primary_image'],
            'features' => safe_json_decode($offer['features'], []),
            'qty' => $qty,
            'stock' => StoreItem::stock($storeItemId, $variantId),
            'available' => $available,
            'price_per_day' => $unitPrice,
            'duration_days' => $days,
            'subtotal' => $lineSubtotal,
            'deposit' => $lineDeposit,
            'variant_label' => $variant['label'] ?? null,
        ];
    }

    if (count($storeIds) > 1) {
        $errors[] = 'Keranjang hanya boleh berisi gear dari satu cabang. Kosongkan keranjang untuk pindah cabang.';
    }
    if ($storeIds) {
        $firstStoreId = (int) array_key_first($storeIds);
        $store = Store::find($firstStoreId);
    }

    return [
        'items' => $items,
        'dates' => $dates,
        'duration' => $duration,
        'subtotal' => $subtotal,
        'deposit' => $depositTotal,
        'count' => cart_count(),
        'errors' => $errors,
        'store' => $store,
    ];
}

function cart_validate(): array
{
    $cart = cart_enrich();
    $errors = $cart['errors'];
    if (!$cart['items']) {
        $errors[] = 'Keranjang masih kosong.';
    }
    if (!$cart['dates']['start'] || !$cart['dates']['end']) {
        $errors[] = 'Pilih tanggal sewa terlebih dahulu.';
    } elseif ($cart['dates']['start'] < today()) {
        $errors[] = 'Tanggal sewa sudah lewat. Pilih tanggal baru.';
    } elseif (duration_days($cart['dates']['start'], $cart['dates']['end']) > 30) {
        $errors[] = 'Durasi sewa maksimal 30 hari.';
    }
    return ['valid' => $errors === [], 'errors' => $errors, 'cart' => $cart];
}

function cart_add_multiple(array $items, ?string $start = null, ?string $end = null, ?int $storeId = null): array
{
    $added = 0;
    $errors = [];
    foreach ($items as $entry) {
        $entryStoreId = (int) ($entry['store_id'] ?? 0) ?: $storeId;
        $entryItemId = (int) ($entry['item_id'] ?? 0);
        if (!empty($entry['store_item_id'])) {
            $offer = StoreItem::find((int) $entry['store_item_id']);
            if ($offer) {
                $entryStoreId = (int) $offer['store_id'];
                $entryItemId = (int) $offer['item_id'];
            }
        }
        $result = cart_add(
            $entryItemId,
            !empty($entry['variant_id']) ? (int) $entry['variant_id'] : null,
            max(1, (int) ($entry['qty'] ?? 1)),
            $start,
            $end,
            $entryStoreId ?: null
        );
        if ($result['success']) {
            $added++;
        } else {
            $errors[] = $result['message'];
        }
    }
    return [
        'success' => $added > 0,
        'message' => $added > 0 ? $added . ' item ditambahkan ke keranjang.' : 'Tidak ada item yang bisa ditambahkan.',
        'added' => $added,
        'errors' => $errors,
        'cart_count' => cart_count(),
    ];
}
