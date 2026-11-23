<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_superadmin();
$stores = Store::all();
$activeCount = count(array_filter($stores, static fn (array $store): bool => (int) $store['is_active'] === 1));
$cityCount = count(array_unique(array_column($stores, 'city')));
$defaultStore = Store::defaultStore();

$adminTitle = 'Toko Cabang';
$adminPage = 'toko';
$pageStyles = ['vendor/leaflet/leaflet.css'];
$pageVendorScripts = ['leaflet/leaflet.js'];
$pageScripts = ['admin-stores.js'];
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Master data jaringan</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900"><?= count($stores) ?> toko cabang</h2>
            <p class="mt-1 text-xs text-muted"><?= $activeCount ?> aktif · <?= $cityCount ?> kota · titik lokasi dipilih lewat peta Leaflet.</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-admin-modal data-url="<?= e(url('api/admin.php?action=store_form&id=0')) ?>" data-admin-modal-title="Tambah toko cabang"><?= icon('plus', 'h-3.5 w-3.5') ?> Tambah toko</button>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <?php foreach ([
            ['map-pin', 'Total cabang', count($stores), 'text-forest-700'],
            ['check-circle', 'Cabang aktif', $activeCount, 'text-success'],
            ['users', 'Admin cabang', array_sum(array_map(static fn (array $store): int => (int) $store['admin_count'], $stores)), 'text-sky-700'],
            ['map', 'Kota terjangkau', $cityCount, 'text-star'],
        ] as $metric): ?>
            <div class="admin-card p-4">
                <span class="stat-icon <?= $metric[3] ?>"><?= icon($metric[0], 'h-4 w-4') ?></span>
                <p class="mt-2 text-[0.6rem] font-bold uppercase tracking-wide text-muted"><?= e($metric[1]) ?></p>
                <p class="mt-0.5 text-xl font-extrabold text-forest-900"><?= e((string) $metric[2]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3.5 sm:px-5">
            <div>
                <h3 class="text-sm font-extrabold text-forest-900">Daftar toko & lokasi</h3>
                <p class="mt-0.5 text-[0.62rem] text-muted">Cabang default dipakai saat customer belum mengizinkan lokasi.</p>
            </div>
            <?php if ($defaultStore): ?>
                <span class="badge badge-mint">Default: <?= e($defaultStore['name']) ?></span>
            <?php endif; ?>
        </div>
        <div class="overflow-x-auto">
            <table class="table-app min-w-[60rem]">
                <thead><tr><th>Toko</th><th>Kota</th><th>Koordinat</th><th>Admin</th><th>Pesanan</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                <?php if (!$stores): ?>
                    <tr><td colspan="7" class="py-12 text-center text-sm text-muted">Belum ada toko cabang.</td></tr>
                <?php endif; ?>
                <?php foreach ($stores as $store): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <div class="h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-cream-100">
                                    <img src="<?= e(upload_url($store['image'] ?? null)) ?>" alt="" class="h-full w-full object-cover">
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-extrabold text-forest-900"><?= e($store['name']) ?></p>
                                    <p class="text-[0.62rem] text-muted"><?= e($store['code']) ?><?= (int) $store['is_default'] ? ' · Default' : '' ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p class="text-xs font-bold text-forest-900"><?= e($store['city']) ?></p>
                            <p class="text-[0.62rem] text-muted"><?= e($store['province']) ?></p>
                        </td>
                        <td>
                            <?php if ($store['latitude'] !== null && $store['longitude'] !== null): ?>
                                <p class="font-mono text-[0.62rem] text-forest-700"><?= e($store['latitude']) ?>, <?= e($store['longitude']) ?></p>
                                <a href="<?= e(store_maps_link($store)) ?>" target="_blank" rel="noopener" class="text-[0.62rem] font-bold text-forest-600 hover:underline">Buka peta</a>
                            <?php else: ?>
                                <span class="badge badge-warning">Belum diset</span>
                            <?php endif; ?>
                        </td>
                        <td class="font-bold"><?= (int) $store['admin_count'] ?></td>
                        <td class="font-bold"><?= (int) $store['booking_count'] ?></td>
                        <td>
                            <span class="badge <?= (int) $store['is_active'] ? 'badge-success' : 'badge-danger' ?>"><?= (int) $store['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <a href="<?= e(url('toko.php?store=' . rawurlencode($store['slug']))) ?>" target="_blank" class="icon-btn h-8 w-8" aria-label="Lihat halaman toko"><?= icon('arrow-up-right', 'h-3.5 w-3.5') ?></a>
                                <button type="button" class="icon-btn h-8 w-8" data-admin-modal data-url="<?= e(url('api/admin.php?action=store_form&id=' . $store['id'])) ?>" data-admin-modal-title="Edit <?= e($store['name']) ?>" aria-label="Edit toko"><?= icon('edit', 'h-3.5 w-3.5') ?></button>
                                <button type="button" class="icon-btn h-8 w-8 <?= (int) $store['is_active'] ? 'text-danger' : '' ?>" data-store-toggle data-id="<?= (int) $store['id'] ?>" data-active="<?= (int) $store['is_active'] ?>" aria-label="Ubah status toko"><?= icon('shield', 'h-3.5 w-3.5') ?></button>
                                <button type="button" class="icon-btn h-8 w-8 text-danger" data-admin-delete="store" data-id="<?= (int) $store['id'] ?>" data-name="<?= e($store['name']) ?>" aria-label="Hapus toko"><?= icon('trash', 'h-3.5 w-3.5') ?></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-line bg-cream-50 px-4 py-3 text-[0.68rem] leading-relaxed text-muted">
        <?= icon('info', 'h-3.5 w-3.5') ?> Menghapus toko hanya bisa dilakukan jika belum ada pesanan dan admin cabang. Untuk menonaktifkan sementara, gunakan tombol status.
    </div>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
