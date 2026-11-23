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
$pageStyles = ['vendor/leaflet/leaflet.css', 'vendor/datatables/dataTables.min.css', 'css/datatables.css'];
$pageVendorScripts = ['leaflet/leaflet.js', 'datatables/dataTables.core.min.js', 'datatables/dataTables.min.js'];
$pageScripts = ['admin-stores.js', 'admin-tables.js'];
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
            <table class="table-app min-w-[60rem]" data-datatable="stores" data-ordering="0" data-page-length="10">
                <thead><tr><th>Toko</th><th>Kota</th><th>Koordinat</th><th>Admin</th><th>Pesanan</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-line bg-cream-50 px-4 py-3 text-[0.68rem] leading-relaxed text-muted">
        <?= icon('info', 'h-3.5 w-3.5') ?> Menghapus toko hanya bisa dilakukan jika belum ada pesanan dan admin cabang. Untuk menonaktifkan sementara, gunakan tombol status.
    </div>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
