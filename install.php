<?php
declare(strict_types=1);

/**
 * Installer & health check Rentify.
 * Halaman ini tidak membutuhkan database untuk tampil, sehingga aman dibuka
 * saat pertama kali menyiapkan proyek.
 */

$root = __DIR__;
if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/includes/icons.php';
$localFile = $root . '/config/database.local.php';
$local = is_file($localFile) ? (require $localFile) : [];
$inContainer = is_file('/.dockerenv');
$envPass = env('DB_PASSWORD');
$config = [
    'host' => $inContainer
        ? (env_os('DB_HOST') ?? env_non_empty('DB_DOCKER_HOST') ?? env_non_empty('DB_HOST') ?? (string) ($local['docker_host'] ?? 'mysql-server'))
        : (env_non_empty('DB_HOST') ?? (string) ($local['host'] ?? '127.0.0.1')),
    'port' => env_non_empty('DB_PORT') ?? (string) ($local['port'] ?? '3306'),
    'name' => env_non_empty('DB_NAME') ?? (string) ($local['name'] ?? 'rentify'),
    'user' => env_non_empty('DB_USER') ?? (string) ($local['username'] ?? 'root'),
    'pass' => $envPass !== null ? $envPass : (string) ($local['password'] ?? ''),
];
$state = [
    'server' => false,
    'database' => false,
    'schema' => false,
    'seeded' => false,
    'error' => null,
    'message' => null,
    'users' => 0,
    'items' => 0,
];

if (session_status() === PHP_SESSION_NONE) {
    session_name('rentify_install');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
if (empty($_SESSION['install_token'])) {
    $_SESSION['install_token'] = bin2hex(random_bytes(24));
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function install_pdo(array $config, bool $withDatabase): PDO
{
    $dsn = 'mysql:host=' . $config['host'] . ';port=' . ($config['port'] ?? '3306') . ';charset=utf8mb4';
    if ($withDatabase) {
        $dsn = 'mysql:host=' . $config['host'] . ';port=' . ($config['port'] ?? '3306') . ';dbname=' . $config['name'] . ';charset=utf8mb4';
    }
    return new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ]);
}

function install_run_file(PDO $pdo, string $file): int
{
    if (!is_file($file)) {
        throw new RuntimeException('File SQL tidak ditemukan: ' . basename($file));
    }
    $sql = (string) file_get_contents($file);
    $statements = preg_split('/;\s*\n/', $sql) ?: [];
    $count = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $withoutComments = preg_replace('/^\s*--.*$/m', '', $statement);
        if (trim((string) $withoutComments) === '') {
            continue;
        }
        $pdo->exec($statement);
        $count++;
    }
    return $count;
}

$dbReady = false;
try {
    $server = install_pdo($config, false);
    $state['server'] = true;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'install') {
        if (!hash_equals($_SESSION['install_token'], (string) ($_POST['_token'] ?? ''))) {
            throw new RuntimeException('Token keamanan tidak valid. Muat ulang halaman.');
        }
        $databaseName = preg_replace('/[^a-zA-Z0-9_]/', '', $config['name']) ?: 'rentify';
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $state['database'] = true;

        $pdo = install_pdo($config, true);
        $schemaCount = install_run_file($pdo, $root . '/database/rentify.sql');
        $state['schema'] = true;

        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $seedCount = 0;
        if ($userCount === 0) {
            $seedCount = install_run_file($pdo, $root . '/database/seed.sql');
            $state['seeded'] = true;
        }
        $state['message'] = 'Database berhasil disiapkan. ' . $schemaCount . ' pernyataan schema dan ' . $seedCount . ' blok seed dijalankan.';
        $state['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $state['seeded'] = $state['users'] > 0;
        $itemsTable = $pdo->query("SHOW TABLES LIKE 'items'")->fetchAll();
        if ($itemsTable) {
            $state['items'] = (int) $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
        }
        $dbReady = true;
    } else {
        $pdo = install_pdo($config, true);
        $state['database'] = true;
        $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
        $state['schema'] = (bool) $tables;
        if ($state['schema']) {
            $state['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $items = $pdo->query("SHOW TABLES LIKE 'items'")->fetchAll();
            if ($items) {
                $state['items'] = (int) $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
            }
            $state['seeded'] = $state['users'] > 0;
            $dbReady = true;
        }
    }
} catch (Throwable $exception) {
    $state['error'] = $exception->getMessage();
} finally {
    $state['server'] = $state['server'] || $state['database'];
}

$checks = [
    ['PHP >= 8.1', version_compare(PHP_VERSION, '8.1.0', '>='), 'Versi saat ini ' . PHP_VERSION],
    ['Ekstensi PDO MySQL', extension_loaded('pdo_mysql'), 'Wajib untuk koneksi database'],
    ['Ekstensi cURL', extension_loaded('curl'), 'Dipakai AI Gear Planner'],
    ['Ekstensi fileinfo', extension_loaded('fileinfo'), 'Validasi upload gambar'],
    ['Ekstensi mbstring', extension_loaded('mbstring'), 'Teks & encoding UTF-8'],
    ['Folder uploads', is_dir($root . '/uploads') && is_writable($root . '/uploads'), 'Foto gear, bukti bayar, avatar'],
    ['Folder storage', is_dir($root . '/storage/logs') && is_writable($root . '/storage/logs'), 'Log aplikasi & server'],
    ['Koneksi MySQL', $state['server'], $config['user'] . '@' . $config['host']],
    ['Database ' . $config['name'], $state['database'], $state['database'] ? 'Terhubung' : 'Belum dibuat'],
    ['Tabel utama', $state['schema'], $state['schema'] ? 'users, items, bookings, dll.' : 'Import schema diperlukan'],
    ['Data demo', $state['seeded'], $state['seeded'] ? $state['users'] . ' users · ' . $state['items'] . ' items' : 'Seed belum dijalankan'],
];
$allPassed = !in_array(false, array_column($checks, 1), true);
$hasCss = is_file($root . '/assets/css/tailwind.css');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install & Health Check — Rentify</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <?php if ($hasCss): ?>
        <link rel="stylesheet" href="assets/css/tailwind.css">
    <?php else: ?>
        <style>
            body { font-family: system-ui, sans-serif; background: #f5f7f4; color: #0e2117; margin: 0; padding: 2rem 1rem; }
            .box { max-width: 56rem; margin: 0 auto; background: #fff; border-radius: 1.5rem; padding: 2rem; box-shadow: 0 18px 50px -30px rgba(0,0,0,.3); }
            h1 { color: #032517; margin: 0 0 .25rem; } p { color: #5e7066; }
            ul { list-style: none; padding: 0; } li { padding: .6rem 0; border-bottom: 1px solid #e6ece5; display: flex; justify-content: space-between; gap: 1rem; }
            .ok { color: #16803c; font-weight: 700; } .bad { color: #b42318; font-weight: 700; }
            button { background: #032517; color: #fff; border: 0; border-radius: 999px; padding: .8rem 1.4rem; font-weight: 700; cursor: pointer; }
            code { background: #f3f4f0; padding: .1rem .35rem; border-radius: .3rem; }
        </style>
    <?php endif; ?>
</head>
<body class="bg-cream-100 text-ink">
<main class="container-app py-8 sm:py-12">
    <div class="mx-auto max-w-4xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="index.php" class="flex items-center gap-2.5 text-forest-900">
                <img src="assets/img/logo-mark.svg" alt="" class="h-9 w-9">
                <span class="text-lg font-extrabold tracking-tight">Rentify</span>
            </a>
            <a href="index.php" class="btn btn-outline btn-sm">Buka aplikasi <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
        </div>

        <div class="mt-6 overflow-hidden rounded-[1.6rem] bg-forest-900 p-6 text-white sm:p-8">
            <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.18em] text-mint-300">Setup & diagnosa</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Install Rentify</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-white/55">Halaman ini membuat database <code class="rounded bg-white/10 px-1.5 py-0.5 text-mint-200"><?= h($config['name']) ?></code>, mengimpor schema, dan mengisi data demo. Aman dijalankan berulang — data yang sudah ada tidak dihapus.</p>
        </div>

        <?php if ($state['message']): ?>
            <div class="mt-4 rounded-2xl border border-mint-200 bg-mint-100 px-4 py-3 text-sm font-semibold text-forest-900"><?= h($state['message']) ?></div>
        <?php endif; ?>
        <?php if ($state['error']): ?>
            <div class="mt-4 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-extrabold">Terjadi kesalahan</p>
                <p class="mt-1 font-mono text-xs leading-relaxed"><?= h($state['error']) ?></p>
                <p class="mt-2 text-xs">Pastikan MySQL/MariaDB XAMPP sudah berjalan. Default: host <code>127.0.0.1</code>, user <code>root</code>, password kosong.</p>
            </div>
        <?php endif; ?>

        <div class="mt-6 grid gap-4 lg:grid-cols-[1.2fr_.8fr] lg:items-start">
            <section class="card p-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="section-kicker">Status lingkungan</p>
                        <h2 class="mt-1 text-lg font-extrabold text-forest-900">Checklist instalasi</h2>
                    </div>
                    <span class="badge <?= $allPassed ? 'badge-success' : 'badge-warning' ?>"><?= $allPassed ? 'Siap dipakai' : 'Perlu tindakan' ?></span>
                </div>
                <ul class="mt-4 divide-y divide-line">
                    <?php foreach ($checks as $check): ?>
                        <li class="flex items-start justify-between gap-4 py-2.5">
                            <div>
                                <p class="text-xs font-extrabold text-forest-900"><?= h($check[0]) ?></p>
                                <p class="mt-0.5 text-[0.62rem] text-muted"><?= h($check[2]) ?></p>
                            </div>
                            <span class="mt-0.5 inline-flex shrink-0 items-center gap-1 text-[0.65rem] font-extrabold <?= $check[1] ? 'text-success' : 'text-danger' ?>">
                                <?= icon($check[1] ? 'check-circle' : 'x-circle', 'h-3.5 w-3.5') ?> <?= $check[1] ? 'OK' : 'Gagal' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="mt-4 flex flex-wrap gap-2">
                    <?php if ($dbReady && $state['seeded']): ?>
                        <a href="index.php" class="btn btn-primary">Buka landing page</a>
                        <a href="login.php" class="btn btn-outline">Masuk admin</a>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="action" value="install">
                            <input type="hidden" name="_token" value="<?= h($_SESSION['install_token']) ?>">
                            <button type="submit" class="btn btn-primary"><?= icon('zap', 'h-4 w-4') ?> <?= $state['schema'] ? 'Import data demo' : 'Buat database & import data' ?></button>
                        </form>
                    <?php endif; ?>
                    <a href="install.php" class="btn btn-outline">Muat ulang status</a>
                </div>
            </section>

            <aside class="space-y-4">
                <div class="card p-5">
                    <h2 class="text-sm font-extrabold text-forest-900">Akun demo</h2>
                    <div class="mt-3 space-y-2 text-xs">
                        <div class="rounded-xl bg-cream-50 p-3">
                            <p class="font-extrabold text-forest-900">Admin / Owner</p>
                            <p class="mt-0.5 text-muted">admin@rentify.id</p>
                            <p class="text-muted">password</p>
                        </div>
                        <div class="rounded-xl bg-cream-50 p-3">
                            <p class="font-extrabold text-forest-900">Customer</p>
                            <p class="mt-0.5 text-muted">galih@petualang.id</p>
                            <p class="text-muted">password</p>
                        </div>
                    </div>
                </div>
                <div class="card p-5">
                    <h2 class="text-sm font-extrabold text-forest-900">Konfigurasi</h2>
                    <dl class="mt-3 space-y-2 text-[0.68rem]">
                        <div class="flex justify-between gap-2"><dt class="text-muted">Host</dt><dd class="font-bold text-forest-900"><?= h($config['host']) ?></dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-muted">Database</dt><dd class="font-bold text-forest-900"><?= h($config['name']) ?></dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-muted">User</dt><dd class="font-bold text-forest-900"><?= h($config['user']) ?></dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-muted">Schema</dt><dd class="font-bold text-forest-900"><?= $state['schema'] ? 'Terpasang' : 'Belum' ?></dd></div>
                    </dl>
                    <p class="mt-3 text-[0.62rem] leading-relaxed text-muted">Ubah kredensial lewat file <code>.env</code> atau environment variable <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_NAME</code>, <code>DB_USER</code>, dan <code>DB_PASSWORD</code>.</p>
                </div>
            </aside>
        </div>

        <p class="mt-6 text-center text-[0.62rem] text-muted">Setelah instalasi selesai, hapus atau lindungi <code>install.php</code> di server produksi.</p>
    </div>
</main>
</body>
</html>
