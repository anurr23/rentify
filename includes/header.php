<?php
declare(strict_types=1);

require_once ROOT_PATH . '/includes/icons.php';

$currentUser = auth_user();
$pageTitle = $pageTitle ?? APP_NAME;
$metaDescription = $metaDescription ?? 'Sewa perlengkapan camping, hiking, dan pendakian berkualitas di Karawang. Gear bersih, siap pakai, dan bisa dipesan online.';
$bodyClass = $bodyClass ?? '';
$activeNav = $activeNav ?? '';
$cartCount = cart_count();
$wishlistCount = $currentUser ? Wishlist::count((int) $currentUser['id']) : 0;
$unreadNotifications = 0;
$navNotifications = [];
if ($currentUser) {
    try {
        $stmt = Database::conn()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 8');
        $stmt->execute([(int) $currentUser['id']]);
        $navNotifications = $stmt->fetchAll();
        $stmt = Database::conn()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([(int) $currentUser['id']]);
        $unreadNotifications = (int) $stmt->fetchColumn();
    } catch (Throwable) {
        $unreadNotifications = 0;
    }
}
$flashes = get_flashes();
$userInitials = $currentUser ? initials($currentUser['name']) : '';
$isAdminArea = defined('ADMIN_AREA') && ADMIN_AREA;
$homeLink = $isAdminArea ? 'admin/dashboard.php' : ($currentUser ? 'beranda.php' : 'index.php');
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#032517">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/img/favicon.svg')) ?>">
    <link rel="preload" href="<?= e(url('assets/fonts/plus-jakarta-sans.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/tailwind.css')) ?>">
</head>
<body class="bg-cream-50 text-ink antialiased <?= e($bodyClass) ?>" data-page="<?= e($bodyDataPage ?? '') ?>">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[120] focus:rounded-full focus:bg-forest-900 focus:px-5 focus:py-3 focus:text-sm focus:font-bold focus:text-white">Lewati ke konten utama</a>

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
    'userName' => $currentUser['name'] ?? null,
    'role' => $currentUser['role'] ?? null,
    'cartCount' => $cartCount,
    'wishlistCount' => $wishlistCount,
    'today' => today(),
    'home' => url($homeLink),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<div class="toast-stack" id="toast-stack" aria-live="polite" aria-atomic="true"></div>

<?php if ($flashes): ?>
<div class="hidden" id="server-flashes">
    <?php foreach ($flashes as $flash): ?>
        <span data-flash-type="<?= e($flash['type']) ?>" data-flash-message="<?= e($flash['message']) ?>"></span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<button type="button" class="scroll-top no-print" id="scroll-top" aria-label="Kembali ke atas">
    <?= icon('arrow-up', 'h-5 w-5') ?>
</button>
