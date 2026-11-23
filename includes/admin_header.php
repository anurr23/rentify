<?php
declare(strict_types=1);

require_once ROOT_PATH . '/includes/icons.php';

$currentUser = auth_user();
$adminTitle = $adminTitle ?? 'Dashboard';
$adminPage = $adminPage ?? 'dashboard';
$cartCount = cart_count();
$adminStore = admin_current_store();
$adminScopeStoreId = admin_store_id();
$isSuperadmin = is_superadmin();
$pendingPaymentCount = 0;
$pendingBookingCount = 0;
$unreadNotifications = 0;
$navNotifications = [];
try {
    if ($adminScopeStoreId === null) {
        $pendingPaymentCount = (int) Database::conn()->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
        $pendingBookingCount = (int) Database::conn()->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
    } else {
        $stmt = Database::conn()->prepare(
            "SELECT COUNT(*) FROM payments p JOIN bookings b ON b.id = p.booking_id
             WHERE p.status = 'pending' AND b.store_id = ?"
        );
        $stmt->execute([$adminScopeStoreId]);
        $pendingPaymentCount = (int) $stmt->fetchColumn();
        $stmt = Database::conn()->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'pending' AND store_id = ?");
        $stmt->execute([$adminScopeStoreId]);
        $pendingBookingCount = (int) $stmt->fetchColumn();
    }
    if ($currentUser) {
        $stmt = Database::conn()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6');
        $stmt->execute([(int) $currentUser['id']]);
        $navNotifications = $stmt->fetchAll();
        $stmt = Database::conn()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([(int) $currentUser['id']]);
        $unreadNotifications = (int) $stmt->fetchColumn();
    }
} catch (Throwable) {
    // Biarkan dashboard tetap tampil.
}
$flashes = get_flashes();
$adminInitials = $currentUser ? initials($currentUser['name']) : 'A';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#032517">
    <title><?= e($adminTitle) ?> — Admin Rentify</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/img/favicon.svg')) ?>">
    <link rel="preload" href="<?= e(url('assets/fonts/plus-jakarta-sans.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/tailwind.css')) ?>">
    <?php foreach (($pageStyles ?? []) as $style): ?>
        <link rel="stylesheet" href="<?= e(asset($style)) ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= e(asset('vendor/flatpickr/flatpickr.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/tom-select/tom-select.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/inputs.css')) ?>">
</head>
<body class="bg-cream-100 text-ink antialiased">
<div id="global-loader" class="fixed inset-x-0 top-0 z-[130] hidden h-0.5 overflow-hidden bg-mint-100">
    <div class="h-full w-1/3 animate-[loading_1.1s_ease-in-out_infinite] bg-forest-700"></div>
</div>
<script>
window.RENTIFY = <?= json_encode([
    'base' => BASE_URL,
    'api' => url('api/'),
    'asset' => ASSET_URL,
    'csrf' => csrf_token(),
    'userId' => $currentUser ? (int) $currentUser['id'] : 0,
    'role' => $currentUser['role'] ?? null,
    'storeId' => $currentUser['store_id'] ?? null,
    'cartCount' => $cartCount,
    'today' => today(),
    'home' => url('admin/dashboard.php'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<div class="toast-stack" id="toast-stack" aria-live="polite"></div>
<?php if ($flashes): ?>
<div class="hidden" id="server-flashes">
    <?php foreach ($flashes as $flash): ?>
        <span data-flash-type="<?= e($flash['type']) ?>" data-flash-message="<?= e($flash['message']) ?>"></span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="min-h-screen lg:flex">
    <div class="fixed inset-0 z-[60] hidden bg-forest-950/60 backdrop-blur-sm lg:hidden" data-admin-overlay></div>
    <?php require ROOT_PATH . '/includes/admin_sidebar.php'; ?>

    <div class="min-w-0 flex-1 lg:ml-[17rem]">
        <header class="sticky top-0 z-40 border-b border-line bg-cream-50/90 backdrop-blur-xl">
            <div class="flex h-[4.2rem] items-center gap-3 px-4 lg:px-8">
                <button type="button" class="icon-btn h-10 w-10 lg:hidden" data-admin-toggle aria-label="Buka menu admin">
                    <?= icon('menu', 'h-5 w-5') ?>
                </button>
                <div class="min-w-0">
                    <p class="hidden text-[0.62rem] font-bold uppercase tracking-[0.16em] text-muted sm:block">Rentify Admin</p>
                    <h1 class="truncate text-lg font-extrabold tracking-tight text-forest-900"><?= e($adminTitle) ?></h1>
                </div>
                <div class="ml-auto flex items-center gap-1.5">
                    <a href="<?= e(url($adminStore ? 'toko.php?store=' . rawurlencode($adminStore['slug']) : 'beranda.php')) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm hidden sm:inline-flex" title="Lihat toko"><?= icon('external', 'h-3.5 w-3.5') ?> Lihat toko</a>
                    <details class="relative">
                        <summary class="icon-btn relative h-10 w-10 list-none" aria-label="Notifikasi">
                            <?= icon('bell', 'h-[1.15rem] w-[1.15rem]') ?>
                            <span class="nav-badge <?= $unreadNotifications ? '' : 'hidden' ?>" data-notif-count><?= $unreadNotifications ?></span>
                        </summary>
                        <div class="absolute right-0 mt-2 w-80 overflow-hidden rounded-2xl border border-line bg-white shadow-float">
                            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                                <p class="text-sm font-extrabold text-forest-900">Notifikasi</p>
                                <button type="button" class="text-xs font-bold text-forest-500 hover:text-forest-900" data-notif-read-all>Baca semua</button>
                            </div>
                            <div class="max-h-80 overflow-y-auto" data-notif-list>
                                <?php if (!$navNotifications): ?>
                                    <p class="px-4 py-8 text-center text-sm text-muted">Belum ada notifikasi.</p>
                                <?php endif; ?>
                                <?php foreach ($navNotifications as $notification): ?>
                                    <a href="<?= e(url($notification['link'] ?: 'admin/dashboard.php')) ?>" class="block border-b border-line/70 px-4 py-3 transition hover:bg-cream-50 <?= (int) $notification['is_read'] ? 'opacity-65' : '' ?>">
                                        <p class="text-xs font-extrabold text-forest-900"><?= e($notification['title']) ?></p>
                                        <p class="mt-0.5 line-clamp-2 text-xs text-muted"><?= e($notification['message']) ?></p>
                                        <p class="mt-1 text-[0.62rem] text-muted"><?= e(time_ago($notification['created_at'])) ?></p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </details>
                    <details class="relative ml-1">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full border border-line bg-white py-1 pl-1 pr-3">
                            <?php if (!empty($currentUser['avatar'])): ?>
                                <img src="<?= e(upload_url($currentUser['avatar'])) ?>" alt="" class="h-8 w-8 rounded-full object-cover">
                            <?php else: ?>
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-forest-900 text-xs font-extrabold text-white"><?= e($adminInitials) ?></span>
                            <?php endif; ?>
                            <span class="hidden max-w-[8rem] truncate text-xs font-bold sm:block"><?= e($currentUser['name'] ?? 'Admin') ?></span>
                            <?= icon('chevron-down', 'h-3.5 w-3.5 text-muted') ?>
                        </summary>
                        <div class="absolute right-0 mt-2 w-52 rounded-2xl border border-line bg-white p-2 shadow-float">
                            <?php if (is_superadmin()): ?>
                                <a href="<?= e(url('admin/pengaturan.php')) ?>" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold hover:bg-cream-100"><?= icon('settings', 'h-4 w-4') ?> Pengaturan</a>
                            <?php elseif ($adminStore): ?>
                                <a href="<?= e(url('toko.php?store=' . rawurlencode($adminStore['slug']))) ?>" target="_blank" rel="noopener" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold hover:bg-cream-100"><?= icon('map-pin', 'h-4 w-4') ?> Halaman toko</a>
                            <?php endif; ?>
                            <a href="<?= e(url('logout.php')) ?>" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-danger hover:bg-red-50"><?= icon('logout', 'h-4 w-4') ?> Keluar</a>
                        </div>
                    </details>
                </div>
            </div>
        </header>

        <main id="main" class="px-4 py-5 lg:px-8 lg:py-7">
