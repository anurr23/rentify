<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

http_response_code(404);
$currentUser = auth_user();
$pageTitle = 'Halaman tidak ditemukan — Rentify Outdoor Karawang';
$metaDescription = 'Halaman yang Anda cari tidak ditemukan.';
$bodyClass = 'pb-16 lg:pb-0';
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';
?>
<main id="main" class="container-app py-20 text-center">
    <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-cream-100 text-forest-600"><?= icon('compass', 'h-9 w-9') ?></span>
    <p class="mt-5 text-[0.65rem] font-extrabold uppercase tracking-[0.18em] text-forest-500">Error 404</p>
    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-forest-900">Tersesat di jalur?</h1>
    <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-muted">Halaman yang kamu cari tidak ada atau sudah dipindahkan. Mari kembali ke jalur utama.</p>
    <div class="mt-6 flex flex-wrap justify-center gap-2">
        <a href="<?= e(url($currentUser ? 'beranda.php' : 'index.php')) ?>" class="btn btn-primary">Kembali ke beranda</a>
        <a href="<?= e(url('katalog.php')) ?>" class="btn btn-outline">Jelajahi katalog</a>
    </div>
</main>
<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
