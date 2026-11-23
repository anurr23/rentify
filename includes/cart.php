<?php
declare(strict_types=1);

function cart_raw(): array
{
    return is_array($_SESSION['cart'] ?? null) ? $_SESSION['cart'] : [];
}

function cart_key(int $itemId, ?int $variantId): string
{
    return $itemId . ':' . ($variantId ?: 0);
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

function cart_count(): int
{
    $count = 0;
    foreach (cart_raw() as $item) {
        $count += (int) ($item['qty'] ?? 0);
    }
    return $count;
}

function item_stock(PDO $db, int $itemId, ?int $variantId): int
{
    if ($variantId) {
        $stmt = $db->prepare('SELECT stock_total FROM item_variants WHERE id = ? AND item_id = ? AND is_active = 1');
        $stmt->execute([$variantId, $itemId]);
        $stock = $stmt->fetchColumn();
        return $stock === false ? 0 : (int) $stock;
    }
    $stmt = $db->prepare('SELECT stock_total FROM items WHERE id = ? AND is_active = 1');
    $stmt->execute([$itemId]);
    $stock = $stmt->fetchColumn();
    return $stock === false ? 0 : (int) $stock;
}

function item_booked_qty(PDO $db, int $itemId, ?int $variantId, string $start, string $end, ?int $excludeBookingId = null): int
{
    $sql = "SELECT COALESCE(SUM(bi.qty), 0)
            FROM booking_items bi
            JOIN bookings b ON b.id = bi.booking_id
            WHERE bi.item_id = ?
              AND (bi.variant_id <=> ?)
              AND b.status IN ('pending','confirmed','preparing','ready','ongoing')
              AND (b.status <> 'pending' OR b.hold_expires_at IS NULL OR b.hold_expires_at > NOW())
              AND bi.start_date <= ?
              AND bi.end_date >= ?";
    $params = [$itemId, $variantId, $end, $start];
    if ($excludeBookingId) {
        $sql .= ' AND b.id <> ?';
        $params[] = $excludeBookingId;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function item_available_qty(PDO $db, int $itemId, ?int $variantId, ?string $start = null, ?string $end = null, ?int $excludeBookingId = null): int
{
    $stock = item_stock($db, $itemId, $variantId);
    if (!$start || !$end) {
        return $stock;
    }
    return max(0, $stock - item_booked_qty($db, $itemId, $variantId, $start, $end, $excludeBookingId));
}

function cart_add(int $itemId, ?int $variantId, int $qty = 1, ?string $start = null, ?string $end = null): array
{
    $db = Database::conn();
    $qty = max(1, $qty);
    $stmt = $db->prepare('SELECT * FROM items WHERE id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) {
        return ['success' => false, 'message' => 'Alat tidak ditemukan atau sudah tidak aktif.'];
    }

    $variant = null;
    $hasVariants = (int) $db->query('SELECT COUNT(*) FROM item_variants WHERE item_id = ' . (int) $itemId . ' AND is_active = 1')->fetchColumn() > 0;
    if ($hasVariants) {
        if (!$variantId) {
            return ['success' => false, 'message' => 'Pilih varian/ukuran terlebih dahulu.'];
        }
        $stmt = $db->prepare('SELECT * FROM item_variants WHERE id = ? AND item_id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$variantId, $itemId]);
        $variant = $stmt->fetch();
        if (!$variant) {
            return ['success' => false, 'message' => 'Varian tidak ditemukan.'];
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

    $key = cart_key($itemId, $variantId);
    $existing = (int) ($_SESSION['cart'][$key]['qty'] ?? 0);
    $newQty = $existing + $qty;
    $available = item_available_qty($db, $itemId, $variantId, $start, $end);
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
        'variant_id' => $variantId,
        'qty' => $newQty,
        'added_at' => time(),
    ];
    $_SESSION['cart_last_change'] = time();

    return [
        'success' => true,
        'message' => $item['name'] . ' ditambahkan ke keranjang.',
        'cart_count' => cart_count(),
        'item' => ['item_id' => $itemId, 'variant_id' => $variantId, 'qty' => $newQty],
    ];
}

function cart_update(string $key, int $qty): array
{
    if (!isset($_SESSION['cart'][$key])) {
        return ['success' => false, 'message' => 'Item tidak ada di keranjang.'];
    }
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
        return ['success' => true, 'message' => 'Item dihapus dari keranjang.', 'cart_count' => cart_count()];
    }
    $entry = $_SESSION['cart'][$key];
    $dates = cart_dates();
    $available = item_available_qty(
        Database::conn(),
        (int) $entry['item_id'],
        $entry['variant_id'] ? (int) $entry['variant_id'] : null,
        $dates['start'] ?: null,
        $dates['end'] ?: null
    );
    if ($qty > $available) {
        return ['success' => false, 'message' => 'Stok tersedia hanya ' . $available . ' unit.'];
    }
    $_SESSION['cart'][$key]['qty'] = $qty;
    return ['success' => true, 'message' => 'Jumlah diperbarui.', 'cart_count' => cart_count()];
}

function cart_remove(string $key): array
{
    if (!isset($_SESSION['cart'][$key])) {
        return ['success' => false, 'message' => 'Item tidak ditemukan.'];
    }
    unset($_SESSION['cart'][$key]);
    return ['success' => true, 'message' => 'Item dihapus dari keranjang.', 'cart_count' => cart_count()];
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
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

    if (!$cart) {
        return [
            'items' => [],
            'dates' => $dates,
            'duration' => $duration,
            'subtotal' => 0,
            'deposit' => 0,
            'count' => 0,
            'errors' => [],
        ];
    }

    $db = Database::conn();
    $ids = array_values(array_unique(array_map(static fn ($entry) => (int) $entry['item_id'], $cart)));
    $variantIds = array_values(array_filter(array_unique(array_map(static fn ($entry) => (int) ($entry['variant_id'] ?? 0), $cart))));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare(
        "SELECT i.*, c.name AS category_name, c.slug AS category_slug
         FROM items i JOIN categories c ON c.id = i.category_id
         WHERE i.id IN ($placeholders) AND i.is_active = 1"
    );
    $stmt->execute($ids);
    $itemsById = [];
    foreach ($stmt->fetchAll() as $row) {
        $itemsById[(int) $row['id']] = $row;
    }

    $variantsById = [];
    if ($variantIds) {
        $vPlaceholders = implode(',', array_fill(0, count($variantIds), '?'));
        $vStmt = $db->prepare("SELECT * FROM item_variants WHERE id IN ($vPlaceholders)");
        $vStmt->execute($variantIds);
        foreach ($vStmt->fetchAll() as $variant) {
            $variantsById[(int) $variant['id']] = $variant;
        }
    }

    foreach ($cart as $key => $entry) {
        $itemId = (int) $entry['item_id'];
        $variantId = $entry['variant_id'] ? (int) $entry['variant_id'] : null;
        $qty = max(1, (int) $entry['qty']);
        $item = $itemsById[$itemId] ?? null;
        if (!$item) {
            unset($_SESSION['cart'][$key]);
            $errors[] = 'Salah satu item sudah tidak tersedia dan dihapus dari keranjang.';
            continue;
        }
        $variant = $variantId ? ($variantsById[$variantId] ?? null) : null;
        if ($variantId && !$variant) {
            unset($_SESSION['cart'][$key]);
            $errors[] = e($item['name']) . ' variannya tidak tersedia lagi.';
            continue;
        }
        $unitPrice = (float) ($variant['price_per_day'] ?? $item['price_per_day']);
        $days = $duration > 0 ? $duration : 1;
        $lineSubtotal = $unitPrice * $qty * $days;
        $lineDeposit = (float) $item['deposit'] * $qty;
        $available = item_available_qty($db, $itemId, $variantId, $dates['start'] ?: null, $dates['end'] ?: null);
        if ($dates['start'] && $dates['end'] && $qty > $available) {
            $errors[] = e($item['name']) . ' hanya tersedia ' . $available . ' unit untuk tanggal ini.';
        }
        $subtotal += $lineSubtotal;
        $depositTotal += $lineDeposit;
        $items[] = [
            'key' => $key,
            'item_id' => $itemId,
            'variant_id' => $variantId,
            'name' => $item['name'],
            'slug' => $item['slug'],
            'brand' => $item['brand'],
            'category_name' => $item['category_name'],
            'image' => $item['primary_image'],
            'features' => safe_json_decode($item['features'], []),
            'qty' => $qty,
            'stock' => item_stock($db, $itemId, $variantId),
            'available' => $available,
            'price_per_day' => $unitPrice,
            'duration_days' => $days,
            'subtotal' => $lineSubtotal,
            'deposit' => $lineDeposit,
            'variant_label' => $variant['label'] ?? null,
        ];
    }

    return [
        'items' => $items,
        'dates' => $dates,
        'duration' => $duration,
        'subtotal' => $subtotal,
        'deposit' => $depositTotal,
        'count' => cart_count(),
        'errors' => $errors,
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

function cart_add_multiple(array $items, ?string $start = null, ?string $end = null): array
{
    $added = 0;
    $errors = [];
    foreach ($items as $entry) {
        $result = cart_add(
            (int) ($entry['item_id'] ?? 0),
            !empty($entry['variant_id']) ? (int) $entry['variant_id'] : null,
            max(1, (int) ($entry['qty'] ?? 1)),
            $start,
            $end
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
