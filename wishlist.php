<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/item_card.php';

require_login();
$currentUser = auth_user();
$items = Wishlist::items((int) $currentUser['id']);

$pageTitle = 'Wishlist — Rentify Outdoor Karawang';
$metaDescription = 'Gear favorit yang kamu simpan untuk petualangan berikutnya.';
$activeNav = 'profil';
$bodyClass = 'pb-16 lg:pb-0';
$bodyDataPage = 'wishlist';
$pageScripts = [];
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';
?>
<main id="main" class="pb-16 lg:pb-24">
    <section class="app-hero !pb-14">
        <div class="absolute inset-0 topo-bg opacity-15"></div>
        <div class="container-app relative pt-3">
            <div class="flex items-center justify-between">
                <a href="<?= e(url('profil.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Kembali"><?= icon('arrow-left', 'h-[1.1rem] w-[1.1rem]') ?></a>
                <div class="text-center">
                    <p class="text-[0.55rem] font-extrabold uppercase tracking-[0.2em] text-mint-300/70">Simpanan kamu</p>
                    <h1 class="text-base font-extrabold tracking-tight">Wishlist</h1>
                </div>
                <a href="<?= e(url('katalog.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Katalog"><?= icon('grid', 'h-[1.1rem] w-[1.1rem]') ?></a>
            </div>
            <div class="mt-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-extrabold tracking-tight"><?= count($items) ?> gear disimpan</h2>
                    <p class="mt-1 text-xs text-white/55">Ketuk ikon hati untuk menghapus dari wishlist.</p>
                </div>
                <a href="<?= e(url('katalog.php')) ?>" class="btn btn-accent btn-sm">Tambah gear <?= icon('plus', 'h-3.5 w-3.5') ?></a>
            </div>
        </div>
        <div class="absolute inset-x-0 bottom-0 h-8 rounded-t-[1.7rem] bg-cream-50"></div>
    </section>

    <div class="container-app -mt-2">
        <?php if (!$items): ?>
            <div class="rounded-[1.5rem] border border-dashed border-line bg-white px-6 py-16 text-center">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-cream-100 text-muted"><?= icon('heart', 'h-7 w-7') ?></span>
                <h2 class="mt-4 text-lg font-extrabold text-forest-900">Wishlist masih kosong</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-muted">Simpan gear favoritmu agar mudah ditemukan saat merencanakan perjalanan.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    <a href="<?= e(url('katalog.php')) ?>" class="btn btn-primary btn-sm">Jelajahi katalog</a>
                    <a href="<?= e(url('rekomendasi.php')) ?>" class="btn btn-outline btn-sm"><?= icon('sparkles', 'h-3.5 w-3.5') ?> AI Planner</a>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 gap-3.5 sm:gap-5 lg:grid-cols-4">
                <?php foreach ($items as $item): ?>
                    <div data-wishlist-row><?= item_card($item) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
