<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once ROOT_PATH . '/includes/catalog_render.php';

api_method(['GET']);

$categorySlug = trim((string) ($_GET['category'] ?? ''));
$category = null;
if ($categorySlug !== '') {
    $category = Category::findBySlug($categorySlug);
}

$categoryFilters = $category ? Category::filters((int) $category['id']) : [];
$allowed = [];
foreach ($categoryFilters as $filter) {
    $allowed[$filter['filter_key']] = $filter['options'];
}
$activeFilters = [];
$rawFilters = $_GET['f'] ?? [];
if (is_array($rawFilters)) {
    foreach ($rawFilters as $key => $value) {
        if (!is_string($key) || !isset($allowed[$key]) || !is_string($value)) {
            continue;
        }
        if (in_array($value, $allowed[$key], true)) {
            $activeFilters[$key] = $value;
        }
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'nearest');
if (!in_array($sort, ['nearest', 'popular', 'rating', 'price_asc', 'price_desc', 'newest'], true)) {
    $sort = 'nearest';
}
$storeSlug = trim((string) ($_GET['store'] ?? ''));
$storeFilter = $storeSlug !== '' ? Store::findBySlug($storeSlug) : null;
if ($storeFilter && (int) $storeFilter['is_active'] !== 1) {
    $storeFilter = null;
}
$currentUser = auth_user();
$preferredStore = current_store($currentUser);
$latitude = isset($_GET['lat']) && is_numeric($_GET['lat']) ? (float) $_GET['lat'] : null;
$longitude = isset($_GET['lng']) && is_numeric($_GET['lng']) ? (float) $_GET['lng'] : null;
if ($latitude === null && $currentUser && $currentUser['last_latitude'] !== null) {
    $latitude = (float) $currentUser['last_latitude'];
}
if ($longitude === null && $currentUser && $currentUser['last_longitude'] !== null) {
    $longitude = (float) $currentUser['last_longitude'];
}
$view = ($_GET['view'] ?? 'grid') === 'list' ? 'list' : 'grid';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = $category ? 9 : 12;

$result = Item::search([
    'category_id' => $category['id'] ?? 0,
    'q' => $q,
    'filters' => $activeFilters,
    'sort' => $sort,
    'page' => $page,
    'per_page' => $perPage,
    'store_id' => $storeFilter ? (int) $storeFilter['id'] : 0,
    'preferred_store_id' => $preferredStore ? (int) $preferredStore['id'] : 0,
    'lat' => $latitude,
    'lng' => $longitude,
]);

json_success([
    'html' => render_catalog_results($result, $category, $view),
    'total' => (int) $result['total'],
    'shown' => count($result['items']),
    'page' => (int) $result['pagination']['page'],
    'pages' => (int) $result['pagination']['pages'],
    'has_next' => !empty($result['pagination']['has_next']),
    'filters' => $activeFilters,
    'sort' => $sort,
    'view' => $view,
    'store' => $storeFilter['slug'] ?? '',
    'store_id' => $storeFilter ? (int) $storeFilter['id'] : 0,
]);
