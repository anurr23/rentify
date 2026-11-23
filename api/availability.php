<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

api_method(['GET']);

$itemId = input_int('item_id');
$storeId = input_int('store_id');
$storeItemId = input_int('store_item_id');
$variantId = input_int('variant_id');
$qty = max(1, input_int('qty', 1));
$start = (string) input('start_date', '');
$end = (string) input('end_date', '');

if ($storeItemId > 0) {
    $offer = StoreItem::find($storeItemId);
    if (!$offer) {
        json_error('Inventaris cabang tidak ditemukan.', 404);
    }
    $itemId = (int) $offer['item_id'];
    $storeId = (int) $offer['store_id'];
}
if ($itemId <= 0) {
    json_error('Item tidak valid.');
}
if ($storeId <= 0) {
    $storeId = default_store_id();
}
$offer = store_item_for($storeId, $itemId);
if (!$offer) {
    json_error('Barang tidak tersedia di cabang ini.');
}
if (($start !== '' && $end === '') || ($start === '' && $end !== '')) {
    json_error('Lengkapi tanggal mulai dan tanggal kembali.');
}
if ($start !== '' && $end !== '') {
    if ($start < today()) {
        json_error('Tanggal mulai tidak boleh di masa lalu.');
    }
    if ($end < $start) {
        json_error('Tanggal kembali harus setelah tanggal mulai.');
    }
    if (duration_days($start, $end) > 30) {
        json_error('Durasi sewa maksimal 30 hari.');
    }
}

$variant = null;
if ($variantId) {
    $variant = StoreItem::variants((int) $offer['id']);
    $variant = array_values(array_filter($variant, static fn (array $row): bool => (int) $row['id'] === $variantId))[0] ?? null;
    if (!$variant) {
        json_error('Varian tidak tersedia di cabang ini.');
    }
}
$available = StoreItem::availableQty(
    (int) $offer['id'],
    $variantId ?: null,
    $start !== '' ? $start : null,
    $end !== '' ? $end : null
);
$stock = StoreItem::stock((int) $offer['id'], $variantId ?: null);
$duration = ($start !== '' && $end !== '') ? duration_days($start, $end) : 1;
$unitPrice = (float) ($variant['price_per_day'] ?? $offer['price_per_day']);
$store = Store::find($storeId);

json_success([
    'item_id' => $itemId,
    'store_id' => $storeId,
    'store_item_id' => (int) $offer['id'],
    'store_name' => $store['name'] ?? null,
    'variant_id' => $variantId ?: null,
    'available' => $available,
    'stock' => $stock,
    'duration_days' => $duration,
    'qty' => $qty,
    'price_per_day' => $unitPrice,
    'deposit' => (float) $offer['deposit'] * $qty,
    'subtotal' => $unitPrice * $qty * $duration,
    'total' => ($unitPrice * $qty * $duration) + ((float) $offer['deposit'] * $qty),
    'message' => item_availability_text($available),
]);
