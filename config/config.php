<?php
declare(strict_types=1);

/**
 * Konfigurasi utama Rentify.
 * Aplikasi tetap memakai PHP native + PDO, tanpa framework.
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/env.php';

define('APP_VERSION', '1.0.0');
define('APP_ENV', (string) env('APP_ENV'));

date_default_timezone_set('Asia/Jakarta');
mb_internal_encoding('UTF-8');

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/storage/logs/app.log');

/**
 * Deteksi base path otomatis, baik saat dijalankan dengan PHP built-in server,
 * Apache/XAMPP, maupun di belakang reverse proxy/tunnel (ngrok, Cloudflare,
 * localtunnel, dan sejenisnya).
 */
$forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
$forwardedHeader = (string) ($_SERVER['HTTP_FORWARDED'] ?? '');
if ($forwardedProto === '' && preg_match('/proto=(https?)/i', $forwardedHeader, $forwardedMatch)) {
    $forwardedProto = strtolower($forwardedMatch[1]);
}
$forwardedPort = trim((string) ($_SERVER['HTTP_X_FORWARDED_PORT'] ?? ''));
$forwardedSsl = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
$forwardedScheme = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_SCHEME'] ?? '')));
$urlScheme = strtolower(trim((string) ($_SERVER['HTTP_X_URL_SCHEME'] ?? '')));
$cfVisitor = (string) ($_SERVER['HTTP_CF_VISITOR'] ?? '');
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
    || $forwardedProto === 'https'
    || $forwardedSsl === 'on'
    || $forwardedPort === '443'
    || $forwardedScheme === 'https'
    || $urlScheme === 'https'
    || str_contains(strtolower($cfVisitor), '"scheme":"https"');
$scheme = $isHttps ? 'https' : 'http';

$forwardedHost = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''))[0] ?? '');
if ($forwardedHost === '' && preg_match('/host="?([^";,]+)/i', $forwardedHeader, $forwardedHostMatch)) {
    $forwardedHost = trim($forwardedHostMatch[1]);
}
$httpHost = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
$host = $forwardedHost !== '' ? $forwardedHost : ($httpHost !== '' ? $httpHost : 'localhost');
$host = preg_replace('#^https?://#i', '', $host) ?: 'localhost';

$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/')) : '';
$appRoot = str_replace('\\', '/', ROOT_PATH);
$basePath = '/';

if ($documentRoot !== '' && str_starts_with($appRoot, $documentRoot)) {
    $basePath = substr($appRoot, strlen($documentRoot));
    $basePath = $basePath === '' ? '/' : $basePath . '/';
}

$forwardedPrefix = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PREFIX'] ?? ''))[0] ?? '');
if ($forwardedPrefix !== '' && $forwardedPrefix !== '/') {
    $basePath = '/' . trim($forwardedPrefix, '/') . '/';
}

if (php_sapi_name() === 'cli' && !isset($_SERVER['HTTP_HOST'])) {
    $host = 'localhost';
}

define('BASE_PATH', $basePath);
define('BASE_URL', rtrim($scheme . '://' . $host, '/') . BASE_PATH);
define('ASSET_URL', BASE_URL . 'assets/');
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');
define('STORAGE_PATH', ROOT_PATH . '/storage/');

$cookieSecure = $isHttps;
if (session_status() === PHP_SESSION_NONE) {
    session_name('rentify_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $cookieSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

spl_autoload_register(function (string $className): void {
    $directories = [
        ROOT_PATH . '/models/',
        ROOT_PATH . '/classes/',
        ROOT_PATH . '/controllers/',
    ];
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/helpers.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/cart.php';

define('APP_NAME', setting('app_name', 'Rentify Outdoor Karawang'));
define('APP_TAGLINE', setting('app_tagline', 'Gear Up, Go Further'));
