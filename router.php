<?php
declare(strict_types=1);

/**
 * Router untuk PHP built-in server:
 *   php -S localhost:8000 router.php
 *
 * File statis dilayani langsung, request lain masuk ke aplikasi PHP.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$file = __DIR__ . $path;

$blockedPrefixes = ['/config/', '/models/', '/includes/', '/database/', '/storage/', '/_legacy_pos/', '/node_modules/', '/scripts/'];
if (preg_match('#/\.#', $path)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return true;
}
foreach ($blockedPrefixes as $prefix) {
    if (str_starts_with($path, $prefix)) {
        http_response_code(404);
        require __DIR__ . '/404.php';
        return true;
    }
}

if ($path !== '/' && is_file($file)) {
    return false;
}

if (is_dir($file) && is_file(rtrim($file, '/') . '/index.php')) {
    require rtrim($file, '/') . '/index.php';
    return true;
}

if ($path === '/' || $path === '') {
    require __DIR__ . '/index.php';
    return true;
}

http_response_code(404);
require __DIR__ . '/404.php';
