<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$method = api_method(['GET', 'POST']);
$user = api_require_login();

if ($method === 'GET') {
    $items = Wishlist::items((int) $user['id']);
    json_success([
        'items' => array_map(static fn (array $item): array => [
            'id' => (int) $item['id'],
            'name' => $item['name'],
            'slug' => $item['slug'],
            'price_per_day' => (float) $item['price_per_day'],
            'primary_image' => $item['primary_image'],
            'category_name' => $item['category_name'],
        ], $items),
        'count' => Wishlist::count((int) $user['id']),
    ]);
}

$itemId = input_int('item_id');
if ($itemId <= 0) {
    json_error('Item tidak valid.');
}

$result = Wishlist::toggle((int) $user['id'], $itemId);
if (!$result['success']) {
    json_error($result['message']);
}

json_success([
    'message' => $result['message'],
    'active' => (bool) $result['active'],
    'wishlist_count' => Wishlist::count((int) $user['id']),
]);
