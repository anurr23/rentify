<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

api_method(['GET']);

$itemId = input_int('item_id');
$variantId = input_int('variant_id');
$qty = max(1, input_int('qty', 1));
$start = (string) input('start_date', '');
$end = (string) input('end_date', '');

if ($itemId <= 0) {
    json_error('Item tidak valid.');
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

$result = Item::availability($itemId, $variantId ?: null, $start, $end);
if (!$result['success']) {
    json_error($result['message']);
}

$item = Item::find($itemId);
$variant = null;
if ($variantId) {
    foreach (($item['variants'] ?? []) as $candidate) {
        if ((int) $candidate['id'] === $variantId) {
            $variant = $candidate;
            break;
        }
    }
}
$unitPrice = (float) ($variant['price_per_day'] ?? $item['price_per_day']);
$duration = max(1, (int) $result['duration_days']);

json_success([
    'item_id' => $itemId,
    'variant_id' => $variantId ?: null,
    'available' => (int) $result['available'],
    'stock' => (int) $result['stock'],
    'duration_days' => $duration,
    'qty' => $qty,
    'price_per_day' => $unitPrice,
    'deposit' => (float) $item['deposit'] * $qty,
    'subtotal' => $unitPrice * $qty * $duration,
    'total' => ($unitPrice * $qty * $duration) + ((float) $item['deposit'] * $qty),
    'message' => $result['message'],
]);
