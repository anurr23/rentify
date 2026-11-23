<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$method = api_method(['GET', 'POST']);
$user = auth_user();

function location_public_store(array $store): array
{
    return [
        'id' => (int) $store['id'],
        'code' => $store['code'],
        'name' => $store['name'],
        'slug' => $store['slug'],
        'address' => $store['address'],
        'district' => $store['district'],
        'city' => $store['city'],
        'province' => $store['province'],
        'phone' => $store['phone'],
        'whatsapp' => $store['whatsapp'],
        'latitude' => $store['latitude'] !== null ? (float) $store['latitude'] : null,
        'longitude' => $store['longitude'] !== null ? (float) $store['longitude'] : null,
        'opening_hours' => $store['opening_hours'],
        'image' => $store['image'] ? upload_url($store['image']) : null,
        'maps_url' => store_maps_link($store),
        'distance_km' => $store['distance_km'] ?? null,
        'distance_label' => $store['distance_label'] ?? null,
    ];
}

if ($method === 'GET') {
    $latitude = input('lat');
    $longitude = input('lng');
    $hasCoords = $latitude !== null && $longitude !== null && is_numeric($latitude) && is_numeric($longitude);
    $lat = $hasCoords ? (float) $latitude : null;
    $lng = $hasCoords ? (float) $longitude : null;
    if ($hasCoords && ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180)) {
        json_error('Koordinat tidak valid.', 422);
    }
    $stores = Store::nearest($lat, $lng, 12);
    json_success([
        'stores' => array_map('location_public_store', $stores),
        'nearest' => $stores ? location_public_store($stores[0]) : null,
        'has_location' => $hasCoords,
        'latitude' => $lat,
        'longitude' => $lng,
    ]);
}

$action = (string) input('action', 'save');

if ($action === 'preferred') {
    $user = api_require_login();
    $storeId = input_int('store_id');
    $store = $storeId > 0 ? Store::find($storeId) : null;
    if (!$store || (int) $store['is_active'] !== 1) {
        json_error('Toko cabang tidak tersedia.', 422);
    }
    Database::conn()->prepare('UPDATE users SET preferred_store_id = ? WHERE id = ?')
        ->execute([$storeId, (int) $user['id']]);
    if (!empty($user['last_latitude']) && !empty($user['last_longitude'])) {
        $distance = Store::distanceKm((float) $user['last_latitude'], (float) $user['last_longitude'], (float) $store['latitude'], (float) $store['longitude']);
    }
    json_success([
        'message' => 'Toko utama diperbarui menjadi ' . $store['name'] . '.',
        'store' => location_public_store($store + ['distance_km' => $distance ?? null, 'distance_label' => isset($distance) ? Store::distanceLabel($distance) : null]),
    ]);
}

$latitude = input('lat');
$longitude = input('lng');
if ($latitude === null || $longitude === null || !is_numeric($latitude) || !is_numeric($longitude)) {
    json_error('Koordinat lokasi wajib dikirim.', 422);
}
$lat = (float) $latitude;
$lng = (float) $longitude;
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    json_error('Koordinat tidak valid.', 422);
}

$nearest = Store::nearest($lat, $lng, 12);

if ($user) {
    $nearestStoreId = $nearest ? (int) $nearest[0]['id'] : null;
    Database::conn()->prepare(
        'UPDATE users SET last_latitude = ?, last_longitude = ?, last_location_at = NOW(),
            preferred_store_id = COALESCE(?, preferred_store_id) WHERE id = ?'
    )->execute([$lat, $lng, $nearestStoreId, (int) $user['id']]);
}

json_success([
    'message' => $nearest
        ? 'Lokasi tersimpan. Cabang terdekat: ' . $nearest[0]['name'] . ' (' . $nearest[0]['distance_label'] . ').'
        : 'Lokasi tersimpan.',
    'stores' => array_map('location_public_store', $nearest),
    'nearest' => $nearest ? location_public_store($nearest[0]) : null,
    'saved' => (bool) $user,
    'latitude' => $lat,
    'longitude' => $lng,
]);
