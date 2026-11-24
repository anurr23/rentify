<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

require_login();
$currentUser = auth_user();
$status = (string) ($_GET['status'] ?? 'semua');
$allowedStatuses = ['semua', 'pending', 'aktif', 'selesai', 'dibatalkan', 'confirmed', 'preparing', 'ready', 'ongoing'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'semua';
}
$page = page_number();
$result = Booking::forUser((int) $currentUser['id'], $status, $page, 8);
$bookings = $result['items'];
$pagination = $result['pagination'];
$counts = [];
$stmt = Database::conn()->prepare('SELECT status, COUNT(*) AS total FROM bookings WHERE user_id = ? GROUP BY status');
$stmt->execute([(int) $currentUser['id']]);
foreach ($stmt->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['total'];
}
$totalAll = array_sum($counts);
$activeCount = ($counts['confirmed'] ?? 0) + ($counts['preparing'] ?? 0) + ($counts['ready'] ?? 0) + ($counts['ongoing'] ?? 0);
$doneCount = ($counts['returned'] ?? 0) + ($counts['completed'] ?? 0);
$cancelCount = $counts['cancelled'] ?? 0;

$tabs = [
    'semua' => ['label' => 'Semua', 'count' => $totalAll],
    'pending' => ['label' => 'Pesanan dibuat', 'count' => $counts['pending'] ?? 0],
    'aktif' => ['label' => 'Aktif', 'count' => $activeCount],
    'selesai' => ['label' => 'Selesai', 'count' => $doneCount],
    'dibatalkan' => ['label' => 'Dibatalkan', 'count' => $cancelCount],
];

$pageTitle = 'Pesanan Saya — Rentify Outdoor Karawang';
$metaDescription = 'Pantau status pesanan sewa gear, pembayaran, dan pengembalian.';
$activeNav = 'pesanan';
$bodyClass = 'pb-16 lg:pb-0';
$pageScripts = [];
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';
?>
<main id="main" class="pb-16 lg:pb-24">
    <section class="app-hero !pb-14">
        <div class="absolute inset-0 topo-bg opacity-15"></div>
        <div class="container-app relative pt-3">
            <div class="flex items-center justify-between">
                <a href="<?= e(url('beranda.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10 lg:hidden" aria-label="Kembali"><?= icon('arrow-left', 'h-[1.1rem] w-[1.1rem]') ?></a>
                <div class="text-center lg:mx-auto">
                    <p class="text-[0.55rem] font-extrabold uppercase tracking-[0.2em] text-mint-300/70">Riwayat & status</p>
                    <h1 class="text-base font-extrabold tracking-tight">Pesanan saya</h1>
                </div>
                <a href="<?= e(url('keranjang.php')) ?>" class="icon-btn icon-btn-dark relative h-10 w-10 lg:hidden" aria-label="Keranjang">
                    <?= icon('cart', 'h-[1.1rem] w-[1.1rem]') ?>
                    <span class="nav-badge <?= cart_count() ? '' : 'hidden' ?>" data-cart-count><?= cart_count() ?></span>
                </a>
            </div>

            <div class="mt-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs text-white/55">Halo <?= e(explode(' ', $currentUser['name'])[0]) ?>, ini semua pesananmu.</p>
                    <div class="mt-2 flex flex-wrap gap-2 text-[0.62rem] font-semibold text-white/60">
                        <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-1"><?= $totalAll ?> total pesanan</span>
                        <?php if ($activeCount): ?><span class="rounded-full border border-mint-300/30 bg-mint-300/10 px-2.5 py-1 text-mint-200"><?= $activeCount ?> sedang aktif</span><?php endif; ?>
                    </div>
                </div>
                <a href="<?= e(url('katalog.php')) ?>" class="btn btn-accent btn-sm">Sewa lagi <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
            </div>
        </div>
        <div class="absolute inset-x-0 bottom-0 h-8 rounded-t-[1.7rem] bg-cream-50"></div>
    </section>

    <div class="container-app -mt-2">
        <div class="no-scrollbar -mx-5 flex gap-2 overflow-x-auto px-5 sm:mx-0 sm:flex-wrap sm:px-0">
            <?php foreach ($tabs as $key => $tab): ?>
                <a href="<?= e(url('pesanan.php' . ($key !== 'semua' ? '?status=' . $key : ''))) ?>" class="chip shrink-0 <?= $status === $key ? 'chip-active' : '' ?>">
                    <?= e($tab['label']) ?>
                    <span class="ml-1 rounded-full <?= $status === $key ? 'bg-white/15' : 'bg-cream-200' ?> px-1.5 py-0.5 text-[0.58rem] font-extrabold"><?= (int) $tab['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (!$bookings): ?>
            <div class="mt-6 rounded-[1.5rem] border border-dashed border-line bg-white px-6 py-16 text-center">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-cream-100 text-muted"><?= icon('clipboard', 'h-7 w-7') ?></span>
                <h2 class="mt-4 text-lg font-extrabold text-forest-900">Belum ada pesanan</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-muted">Mulai petualanganmu dengan memilih gear, atau minta rekomendasi dari AI Planner.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    <a href="<?= e(url('katalog.php')) ?>" class="btn btn-primary btn-sm">Jelajahi katalog</a>
                    <a href="<?= e(url('rekomendasi.php')) ?>" class="btn btn-outline btn-sm"><?= icon('sparkles', 'h-3.5 w-3.5') ?> AI Planner</a>
                </div>
            </div>
        <?php else: ?>
            <div class="mt-4 space-y-3">
                <?php foreach ($bookings as $booking): ?>
                    <article class="card overflow-hidden">
                        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:p-5">
                            <a href="<?= e(url('pesanan_detail.php?id=' . $booking['id'])) ?>" class="relative h-24 w-full shrink-0 overflow-hidden rounded-2xl bg-cream-100 sm:h-20 sm:w-24">
                                <img src="<?= e(upload_url($booking['cover_image'])) ?>" alt="" class="h-full w-full object-cover">
                                <span class="absolute inset-x-0 bottom-0 bg-forest-950/70 py-1 text-center text-[0.55rem] font-bold text-white"><?= (int) $booking['item_count'] ?> gear</span>
                            </a>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="badge <?= e(status_class($booking['status'])) ?>"><?= e(status_label($booking['status'], $booking['pickup_method'] ?? null)) ?></span>
                                    <span class="badge <?= e(payment_status_class($booking['payment_status'])) ?>"><?= e(payment_status_label($booking['payment_status'])) ?></span>
                                    <?php if ($booking['status'] === 'pending' && !empty($booking['hold_expires_at']) && strtotime($booking['hold_expires_at']) > time()): ?>
                                        <span class="text-[0.6rem] font-semibold text-muted">Bayar sebelum <?= e(format_datetime_id($booking['hold_expires_at'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="<?= e(url('pesanan_detail.php?id=' . $booking['id'])) ?>" class="mt-1.5 block text-sm font-extrabold text-forest-900 hover:text-forest-600"><?= e($booking['code']) ?></a>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[0.68rem] text-muted">
                                    <span class="inline-flex items-center gap-1"><?= icon('calendar', 'h-3.5 w-3.5') ?> <?= e(format_date_id($booking['start_date'])) ?> — <?= e(format_date_id($booking['end_date'])) ?></span>
                                    <span class="text-line">•</span>
                                    <span><?= (int) $booking['duration_days'] ?> hari</span>
                                    <span class="text-line">•</span>
                                    <span class="inline-flex items-center gap-1"><?= icon($booking['pickup_method'] === 'delivery' ? 'truck' : 'map-pin', 'h-3.5 w-3.5') ?> <?= $booking['pickup_method'] === 'delivery' ? 'Diantar' : e($booking['store_name'] ?: ($booking['pickup_name'] ?: 'Ambil di toko')) ?></span>
                                </div>
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <p class="text-base font-extrabold text-forest-900"><?= e(format_rupiah($booking['total'])) ?></p>
                                    <span class="text-[0.62rem] text-muted">Dibuat <?= e(time_ago($booking['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-2 sm:flex-col sm:items-stretch">
                                <?php if ($booking['payment_status'] === 'unpaid' && !in_array($booking['status'], ['cancelled', 'completed'], true)): ?>
                                    <a href="<?= e(url('pesanan_detail.php?id=' . $booking['id'] . '#pembayaran')) ?>" class="btn btn-primary btn-sm">Bayar sekarang</a>
                                <?php endif; ?>
                                <a href="<?= e(url('pesanan_detail.php?id=' . $booking['id'])) ?>" class="btn btn-outline btn-sm">Detail <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ((int) $pagination['pages'] > 1): ?>
                <nav class="mt-7 flex items-center justify-center gap-1.5" aria-label="Navigasi halaman pesanan">
                    <?php if ($pagination['has_prev']): ?>
                        <a href="<?= e(url('pesanan.php?' . http_build_query(array_filter(['status' => $status !== 'semua' ? $status : null, 'page' => (int) $pagination['page'] - 1])))) ?>" class="icon-btn h-9 w-9"><?= icon('chevron-left', 'h-4 w-4') ?></a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= (int) $pagination['pages']; $i++): ?>
                        <a href="<?= e(url('pesanan.php?' . http_build_query(array_filter(['status' => $status !== 'semua' ? $status : null, 'page' => $i])))) ?>" class="flex h-9 min-w-9 items-center justify-center rounded-full px-2 text-xs font-extrabold <?= $i === (int) $pagination['page'] ? 'bg-forest-900 text-white' : 'border border-line bg-white text-forest-700' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($pagination['has_next']): ?>
                        <a href="<?= e(url('pesanan.php?' . http_build_query(array_filter(['status' => $status !== 'semua' ? $status : null, 'page' => (int) $pagination['page'] + 1])))) ?>" class="icon-btn h-9 w-9"><?= icon('chevron-right', 'h-4 w-4') ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
