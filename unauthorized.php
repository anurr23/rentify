<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

http_response_code(403);
$currentUser = auth_user();
$pageTitle = 'Akses ditolak — Rentify Outdoor Karawang';
$metaDescription = 'Halaman ini hanya dapat diakses oleh admin Rentify.';
$bodyClass = 'pb-16 lg:pb-0';
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';
?>
<main id="main" class="container-app py-20 text-center">
    <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-red-50 text-danger"><?= icon('shield', 'h-8 w-8') ?></span>
    <p class="mt-5 text-[0.65rem] font-extrabold uppercase tracking-[0.18em] text-forest-500">Error 403</p>
    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-forest-900">Akses ditolak</h1>
    <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-muted">Halaman ini khusus untuk admin Rentify. Jika kamu merasa seharusnya punya akses, hubungi pemilik toko.</p>
    <div class="mt-6 flex flex-wrap justify-center gap-2">
        <a href="<?= e(url($currentUser ? 'beranda.php' : 'index.php')) ?>" class="btn btn-primary">Kembali ke beranda</a>
        <a href="<?= e(url('katalog.php')) ?>" class="btn btn-outline">Lihat katalog</a>
    </div>
</main>
<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
