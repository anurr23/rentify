<?php
declare(strict_types=1);

/**
 * Bootstrap semua endpoint AJAX. Semua respons berupa JSON.
 */
require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

ob_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    verify_csrf();
}

set_exception_handler(static function (Throwable $exception): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    error_log('API error: ' . $exception->getMessage() . ' @ ' . $exception->getFile() . ':' . $exception->getLine());
    json_response([
        'success' => false,
        'message' => APP_ENV === 'development'
            ? 'Terjadi kesalahan: ' . $exception->getMessage()
            : 'Terjadi kesalahan pada server. Coba lagi.',
        'errors' => [],
    ], 500);
});

/**
 * Pastikan request memakai method tertentu.
 */
function api_method(array $allowed): string
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($method, $allowed, true)) {
        json_error('Metode request tidak diizinkan.', 405);
    }
    return $method;
}
