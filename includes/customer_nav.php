<?php
declare(strict_types=1);

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$navItems = [
    ['label' => 'Beranda', 'url' => 'beranda.php', 'key' => 'beranda'],
    ['label' => 'Katalog', 'url' => 'katalog.php', 'key' => 'katalog'],
    ['label' => 'Toko', 'url' => 'toko.php', 'key' => 'toko'],
    ['label' => 'AI Planner', 'url' => 'rekomendasi.php', 'key' => 'ai'],
    ['label' => 'Pesanan', 'url' => 'pesanan.php', 'key' => 'pesanan'],
];
?>
<header class="sticky top-0 z-50 hidden border-b border-line/80 bg-cream-50/90 backdrop-blur-xl lg:block no-print">
    <div class="container-app flex h-[4.6rem] items-center gap-6">
        <?= brand(false) ?>

        <nav class="ml-2 flex items-center gap-1 text-sm font-semibold text-forest-700">
            <?php foreach ($navItems as $navItem): ?>
                <a href="<?= e(url($navItem['url'])) ?>"
                   class="rounded-full px-3.5 py-2 transition hover:bg-mint-100 <?= ($activeNav ?? '') === $navItem['key'] ? 'bg-mint-100 text-forest-900' : '' ?>">
                    <?= e($navItem['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <form action="<?= e(url('katalog.php')) ?>" method="get" class="relative ml-auto hidden w-64 xl:block">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted"><?= icon('search', 'h-4 w-4') ?></span>
            <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Cari gear..." class="input input-icon h-10 !rounded-full !bg-white !py-0 text-sm" aria-label="Cari gear">
        </form>

        <div class="ml-auto flex items-center gap-1.5 xl:ml-0">
            <details class="relative">
                <summary class="icon-btn relative h-10 w-10 cursor-pointer list-none" aria-label="Notifikasi">
                    <?= icon('bell', 'h-[1.15rem] w-[1.15rem]') ?>
                    <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-success ring-2 ring-cream-50 <?= $unreadNotifications ? '' : 'hidden' ?>" data-notif-dot></span>
                </summary>
                <?php $panelVariant = 'header'; require ROOT_PATH . '/includes/notification_panel.php'; ?>
            </details>
            <a href="<?= e(url('wishlist.php')) ?>" class="icon-btn relative h-10 w-10" aria-label="Wishlist">
                <?= icon('heart', 'h-[1.15rem] w-[1.15rem]') ?>
                <span class="nav-badge <?= $wishlistCount ? '' : 'hidden' ?>" data-wishlist-count><?= $wishlistCount ?></span>
            </a>
            <a href="<?= e(url('keranjang.php')) ?>" class="icon-btn relative h-10 w-10" aria-label="Keranjang">
                <?= icon('cart', 'h-[1.15rem] w-[1.15rem]') ?>
                <span class="nav-badge <?= $cartCount ? '' : 'hidden' ?>" data-cart-count><?= $cartCount ?></span>
            </a>

            <?php if ($currentUser): ?>
                <details class="relative">
                    <summary class="ml-1 flex cursor-pointer list-none items-center gap-2 rounded-full border border-line bg-white py-1 pl-1 pr-3 text-sm font-bold text-forest-900">
                        <?php if (!empty($currentUser['avatar'])): ?>
                            <img src="<?= e(upload_url($currentUser['avatar'])) ?>" alt="" class="h-8 w-8 rounded-full object-cover">
                        <?php else: ?>
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-mint-300 text-xs font-extrabold"><?= e($userInitials) ?></span>
                        <?php endif; ?>
                        <span class="max-w-[7rem] truncate"><?= e(explode(' ', $currentUser['name'])[0]) ?></span>
                        <?= icon('chevron-down', 'h-3.5 w-3.5 text-muted') ?>
                    </summary>
                    <div class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-line bg-white p-2 shadow-float">
                        <div class="border-b border-line px-3 pb-3 pt-2">
                            <p class="truncate text-sm font-bold text-forest-900"><?= e($currentUser['name']) ?></p>
                            <p class="truncate text-xs text-muted"><?= e($currentUser['email']) ?></p>
                        </div>
                        <a href="<?= e(url('profil.php')) ?>" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-forest-700 hover:bg-cream-100"><?= icon('user', 'h-4 w-4') ?> Profil saya</a>
                        <a href="<?= e(url('pesanan.php')) ?>" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-forest-700 hover:bg-cream-100"><?= icon('clipboard', 'h-4 w-4') ?> Pesanan saya</a>
                        <?php if (is_staff()): ?>
                            <a href="<?= e(url('admin/dashboard.php')) ?>" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-forest-700 hover:bg-cream-100"><?= icon('chart', 'h-4 w-4') ?> Dashboard admin</a>
                        <?php endif; ?>
                        <a href="<?= e(url('logout.php')) ?>" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-danger hover:bg-red-50"><?= icon('logout', 'h-4 w-4') ?> Keluar</a>
                    </div>
                </details>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>" class="btn btn-primary btn-sm ml-1">Masuk</a>
            <?php endif; ?>
        </div>
    </div>
</header>
