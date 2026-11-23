<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_admin();
if (is_superadmin()) {
    redirect('admin/toko.php');
}
$store = admin_current_store();
if (!$store) {
    $adminTitle = 'Toko Saya';
    $adminPage = 'toko_saya';
    require ROOT_PATH . '/includes/admin_header.php';
    ?>
    <div class="admin-card p-6 text-center">
        <h2 class="text-lg font-extrabold text-forest-900">Akun belum terhubung ke toko</h2>
        <p class="mt-2 text-sm text-muted">Hubungi superadmin untuk mengaitkan akun ini dengan cabang.</p>
    </div>
    <?php require ROOT_PATH . '/includes/admin_footer.php';
    exit;
}

$storeId = (int) $store['id'];
$counts = StoreItem::countsByStore()[$storeId] ?? ['items' => 0, 'stock' => 0];
$summary = Report::summary($storeId);
$recentItems = StoreItem::forStore($storeId, 6);
$publicUrl = url('toko.php?store=' . rawurlencode($store['slug']));

$adminTitle = 'Toko Saya';
$adminPage = 'toko_saya';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Profil cabang yang Anda pegang</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900"><?= e($store['name']) ?></h2>
            <p class="mt-1 flex items-center gap-1.5 text-xs font-bold text-forest-600"><?= icon('map-pin', 'h-3.5 w-3.5') ?> <?= e($store['city']) ?>, <?= e($store['province']) ?> · <?= e($store['code']) ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= e($publicUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><?= icon('external', 'h-3.5 w-3.5') ?> Lihat halaman toko</a>
            <button type="button" class="btn btn-primary btn-sm" data-admin-modal data-url="<?= e(url('api/admin.php?action=store_form&id=' . $storeId)) ?>" data-admin-modal-title="Edit profil toko"><?= icon('edit', 'h-3.5 w-3.5') ?> Edit profil & lokasi</button>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <?php foreach ([
            ['package', 'Barang aktif', (string) $counts['items'], 'text-forest-700'],
            ['box', 'Total unit', (string) $counts['stock'], 'text-sky-700'],
            ['clipboard', 'Pesanan bulan ini', (string) $summary['bookings_month'], 'text-success'],
            ['wallet', 'Omzet bulan ini', format_rupiah_short($summary['revenue_month']), 'text-star'],
        ] as $metric): ?>
            <div class="admin-card p-4">
                <span class="stat-icon <?= $metric[3] ?>"><?= icon($metric[0], 'h-4 w-4') ?></span>
                <p class="mt-2 text-[0.6rem] font-bold uppercase tracking-wide text-muted"><?= e($metric[1]) ?></p>
                <p class="mt-0.5 truncate text-xl font-extrabold text-forest-900"><?= e($metric[2]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1.1fr_.9fr] xl:items-start">
        <section class="admin-card overflow-hidden">
            <div class="relative h-48 bg-cream-100">
                <img src="<?= e(upload_url($store['image'] ?? null)) ?>" alt="<?= e($store['name']) ?>" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-forest-950/80 via-forest-950/20 to-transparent"></div>
                <div class="absolute inset-x-0 bottom-0 p-5 text-white">
                    <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.16em] text-mint-300"><?= e($store['code']) ?></p>
                    <h3 class="mt-1 text-xl font-extrabold"><?= e($store['name']) ?></h3>
                    <p class="mt-1 text-xs text-white/65"><?= e($store['address']) ?></p>
                </div>
            </div>
            <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-5">
                <?php foreach ([
                    ['clock', 'Jam operasional', $store['opening_hours'] ?: '-'],
                    ['phone', 'Telepon', $store['phone'] ?: '-'],
                    ['whatsapp', 'WhatsApp', $store['whatsapp'] ?: '-'],
                    ['mail', 'Email', $store['email'] ?: '-'],
                    ['truck', 'Biaya antar', format_rupiah($store['delivery_fee'])],
                    ['map', 'Koordinat', ($store['latitude'] ?? '-') . ', ' . ($store['longitude'] ?? '-')],
                ] as $info): ?>
                    <div class="rounded-xl bg-cream-50 p-3">
                        <p class="flex items-center gap-1 text-[0.58rem] font-bold uppercase tracking-wide text-muted"><?= icon($info[0], 'h-3 w-3') ?> <?= e($info[1]) ?></p>
                        <p class="mt-1 break-words text-xs font-extrabold text-forest-900"><?= e($info[2]) ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (!empty($store['description'])): ?>
                    <div class="rounded-xl bg-cream-50 p-3 sm:col-span-2">
                        <p class="text-[0.58rem] font-bold uppercase tracking-wide text-muted">Deskripsi</p>
                        <p class="mt-1 text-xs leading-relaxed text-forest-700"><?= nl2br(e($store['description'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <div class="space-y-4">
            <section class="admin-card p-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Inventaris cabang</p>
                        <h3 class="mt-1 text-base font-extrabold text-forest-900">Barang di toko ini</h3>
                    </div>
                    <a href="<?= e(url('admin/barang.php')) ?>" class="text-xs font-extrabold text-forest-700">Kelola</a>
                </div>
                <div class="mt-3 space-y-2">
                    <?php if (!$recentItems): ?><p class="py-4 text-center text-xs text-muted">Belum ada barang.</p><?php endif; ?>
                    <?php foreach ($recentItems as $item): ?>
                        <div class="flex items-center gap-2.5 rounded-xl bg-cream-50 p-2">
                            <div class="h-9 w-9 overflow-hidden rounded-lg bg-cream-100"><img src="<?= e(upload_url($item['primary_image'])) ?>" alt="" class="h-full w-full object-cover"></div>
                            <div class="min-w-0 flex-1"><p class="truncate text-[0.7rem] font-bold text-forest-900"><?= e($item['name']) ?></p><p class="text-[0.6rem] text-muted"><?= e(format_rupiah_short($item['price_per_day'])) ?>/hari · <?= (int) $item['stock_total'] ?> unit</p></div>
                            <span class="badge <?= (int) $item['is_active'] ? 'badge-success' : 'badge-neutral' ?>"><?= (int) $item['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= e(url('admin/barang.php')) ?>" class="btn btn-outline btn-sm mt-3 w-full">Lihat semua inventaris</a>
            </section>

            <section class="admin-card p-4 sm:p-5">
                <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Kontak cabang</p>
                <h3 class="mt-1 text-base font-extrabold text-forest-900">Customer menghubungi nomor ini</h3>
                <p class="mt-1 text-xs leading-relaxed text-muted">Nomor telepon, WhatsApp, dan email di atas tampil di halaman toko dan detail barang untuk cabang ini.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="<?= e(store_wa_link($store, 'Halo ' . $store['name'] . ', saya ingin bertanya.')) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><?= icon('whatsapp', 'h-3.5 w-3.5') ?> WhatsApp</a>
                    <a href="tel:<?= e($store['phone'] ?? '') ?>" class="btn btn-outline btn-sm"><?= icon('phone', 'h-3.5 w-3.5') ?> Telepon</a>
                </div>
            </section>
        </div>
    </div>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
