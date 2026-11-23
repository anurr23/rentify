<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once ROOT_PATH . '/includes/cart_render.php';

$method = api_method(['GET', 'POST']);

/**
 * Snapshot keranjang untuk respons AJAX.
 */
function cart_api_state(): array
{
    $cart = cart_enrich();
    $fee = 0.0;
    $total = (float) $cart['subtotal'] + (float) $cart['deposit'] + $fee;
    return [
        'html' => render_cart_lines($cart),
        'summary' => render_cart_summary($cart, $fee),
        'summary_text' => render_cart_summary($cart, $fee),
        'count' => (int) $cart['count'],
        'subtotal' => (float) $cart['subtotal'],
        'deposit' => (float) $cart['deposit'],
        'total' => $total,
        'duration' => (int) $cart['duration'],
        'dates' => $cart['dates'],
        'errors' => $cart['errors'],
        'items' => array_map(static fn (array $line): array => [
            'key' => $line['key'],
            'item_id' => $line['item_id'],
            'variant_id' => $line['variant_id'],
            'name' => $line['name'],
            'qty' => $line['qty'],
            'available' => $line['available'],
            'price_per_day' => $line['price_per_day'],
            'subtotal' => $line['subtotal'],
            'deposit' => $line['deposit'],
        ], $cart['items']),
    ];
}

if ($method === 'GET') {
    $state = cart_api_state();
    json_success($state);
}

$action = (string) input('action', 'state');

switch ($action) {
    case 'add':
        $itemId = input_int('item_id');
        $variantId = input_int('variant_id');
        $qty = max(1, input_int('quantity', input_int('qty', 1)));
        $start = (string) input('start_date', '');
        $end = (string) input('end_date', '');
        if ($itemId <= 0) {
            json_error('Item tidak valid.');
        }
        $result = cart_add($itemId, $variantId ?: null, $qty, $start ?: null, $end ?: null);
        if (!$result['success']) {
            json_error($result['message']);
        }
        $state = cart_api_state();
        json_success(array_merge($state, [
            'message' => $result['message'],
            'cart_count' => cart_count(),
        ]));
        break;

    case 'add_many':
        $rawItems = input('items', []);
        if (is_string($rawItems)) {
            $rawItems = json_decode($rawItems, true) ?: [];
        }
        if (!is_array($rawItems) || !$rawItems) {
            json_error('Tidak ada item yang dipilih.');
        }
        $normalized = [];
        foreach ($rawItems as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $itemId = (int) ($entry['item_id'] ?? $entry['id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }
            $normalized[] = [
                'item_id' => $itemId,
                'variant_id' => (int) ($entry['variant_id'] ?? 0) ?: null,
                'qty' => max(1, (int) ($entry['qty'] ?? 1)),
            ];
        }
        if (!$normalized) {
            json_error('Tidak ada item yang valid.');
        }
        $start = (string) input('start_date', '');
        $end = (string) input('end_date', '');
        $result = cart_add_multiple($normalized, $start ?: null, $end ?: null);
        if (!$result['success']) {
            json_error($result['message'], 422, $result['errors'] ?? []);
        }
        $state = cart_api_state();
        json_success(array_merge($state, [
            'message' => $result['message'],
            'added' => $result['added'] ?? 0,
            'add_errors' => $result['errors'] ?? [],
            'cart_count' => cart_count(),
        ]));
        break;

    case 'update':
        $key = (string) input('key', '');
        $qty = input_int('qty');
        $result = cart_update($key, $qty);
        if (!$result['success']) {
            json_error($result['message']);
        }
        $state = cart_api_state();
        json_success(array_merge($state, [
            'message' => $result['message'],
            'cart_count' => cart_count(),
        ]));
        break;

    case 'remove':
        $key = (string) input('key', '');
        $result = cart_remove($key);
        if (!$result['success']) {
            json_error($result['message']);
        }
        $state = cart_api_state();
        json_success(array_merge($state, [
            'message' => $result['message'],
            'cart_count' => cart_count(),
        ]));
        break;

    case 'clear':
        cart_clear();
        cart_set_dates(null, null);
        $state = cart_api_state();
        json_success(array_merge($state, [
            'message' => 'Keranjang dikosongkan.',
            'cart_count' => 0,
        ]));
        break;

    case 'dates':
        $start = (string) input('start_date', '');
        $end = (string) input('end_date', '');
        $result = cart_set_dates($start ?: null, $end ?: null);
        if (!$result['success']) {
            json_error($result['message']);
        }
        $state = cart_api_state();
        json_success(array_merge($state, [
            'message' => $result['message'],
            'cart_count' => cart_count(),
            'duration' => (int) $state['duration'],
            'dates' => $state['dates'],
        ]));
        break;

    case 'state':
    default:
        $state = cart_api_state();
        json_success($state);
}
