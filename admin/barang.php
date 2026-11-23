<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_admin();
$scopeStoreId = admin_store_id();
$stores = Store::all(true);
$storeFilterId = $scopeStoreId !== null ? $scopeStoreId : (int) ($_GET['store_id'] ?? 0);
$filterStore = $storeFilterId > 0 ? Store::find($storeFilterId) : null;

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'category_id' => (int) ($_GET['category'] ?? 0),
    'status' => (string) ($_GET['status'] ?? 'semua'),
];
$categories = Category::all();
$defaultStore = $filterStore ?: Store::defaultStore();
$addStoreId = $filterStore ? (int) $filterStore['id'] : (int) ($defaultStore['id'] ?? 0);
$addUrl = url('api/admin.php?action=item_form&id=0&store_id=' . $addStoreId);

$adminTitle = $scopeStoreId !== null ? 'Barang Toko' : 'Inventaris Cabang';
$adminPage = 'barang';
$pageStyles = ['vendor/datatables/dataTables.min.css', 'css/datatables.css'];
$pageVendorScripts = ['datatables/dataTables.core.min.js', 'datatables/dataTables.min.js'];
$pageScripts = ['admin-tables.js'];
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4" data-table-section>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Stok, harga, dan varian per cabang</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900">
                <?= $filterStore ? e($filterStore['name']) : 'Semua cabang' ?>
            </h2>
            <p class="mt-1 text-xs text-muted">
                <span data-table-count="inventory">—</span> barang di <?= $filterStore ? 'cabang ini' : count($stores) . ' cabang' ?>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if ($storeFilterId > 0): ?>
                <a href="<?= e(url('katalog.php?store=' . rawurlencode((string) ($filterStore['slug'] ?? '')))) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><?= icon('external', 'h-3.5 w-3.5') ?> Lihat katalog cabang</a>
            <?php endif; ?>
            <button type="button" class="btn btn-primary btn-sm" data-admin-modal data-url="<?= e($addUrl) ?>" data-admin-modal-title="Tambah barang">
                <?= icon('plus', 'h-3.5 w-3.5') ?> Tambah barang
            </button>
        </div>
    </div>

    <?php if ($scopeStoreId === null): ?>
        <form class="admin-card flex flex-wrap items-end gap-2 p-3.5" method="get" data-table-filters="inventory">
            <div class="relative min-w-[12rem] flex-1">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted"><?= icon('search', 'h-4 w-4') ?></span>
                <input class="input input-icon !py-2.5 text-sm" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Cari nama, brand, slug...">
            </div>
            <select class="select input !w-auto !py-2.5 text-sm" name="store_id" data-searchable>
                <option value="0">Semua cabang</option>
                <?php foreach ($stores as $store): ?>
                    <option value="<?= (int) $store['id'] ?>" <?= $storeFilterId === (int) $store['id'] ? 'selected' : '' ?>><?= e($store['name']) ?> — <?= e($store['city']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="select input !w-auto !py-2.5 text-sm" name="category" data-searchable>
                <option value="0">Semua kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= $filters['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="select input !w-auto !py-2.5 text-sm" name="status">
                <option value="semua" <?= $filters['status'] === 'semua' ? 'selected' : '' ?>>Semua status</option>
                <option value="aktif" <?= $filters['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $filters['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                <option value="habis" <?= $filters['status'] === 'habis' ? 'selected' : '' ?>>Stok habis</option>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        </form>
    <?php else: ?>
        <form class="admin-card flex flex-wrap items-end gap-2 p-3.5" method="get" data-table-filters="inventory">
            <div class="relative min-w-[12rem] flex-1">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted"><?= icon('search', 'h-4 w-4') ?></span>
                <input class="input input-icon !py-2.5 text-sm" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Cari nama, brand, slug...">
            </div>
            <select class="select input !w-auto !py-2.5 text-sm" name="category" data-searchable>
                <option value="0">Semua kategori</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= $filters['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="select input !w-auto !py-2.5 text-sm" name="status">
                <option value="semua" <?= $filters['status'] === 'semua' ? 'selected' : '' ?>>Semua status</option>
                <option value="aktif" <?= $filters['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $filters['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                <option value="habis" <?= $filters['status'] === 'habis' ? 'selected' : '' ?>>Stok habis</option>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        </form>
    <?php endif; ?>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <?php foreach ([
            ['package', 'Barang tampil', 'total', 'text-forest-700'],
            ['check-circle', 'Aktif', 'active', 'text-success'],
            ['alert-triangle', 'Stok ≤ 3', 'low', 'text-star'],
            ['map-pin', 'Cabang', $filterStore ? $filterStore['city'] : count($stores) . ' cabang', 'text-sky-700'],
        ] as $metric): ?>
            <div class="admin-card p-4">
                <span class="stat-icon <?= $metric[3] ?>"><?= icon($metric[0], 'h-4 w-4') ?></span>
                <p class="mt-2 text-[0.6rem] font-bold uppercase tracking-wide text-muted"><?= e($metric[1]) ?></p>
                <?php if (in_array($metric[2], ['total', 'active', 'low'], true)): ?>
                    <p class="mt-0.5 truncate text-xl font-extrabold text-forest-900" data-stat="<?= e($metric[2]) ?>">—</p>
                <?php else: ?>
                    <p class="mt-0.5 truncate text-xl font-extrabold text-forest-900"><?= e((string) $metric[2]) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-app min-w-[64rem]" data-datatable="inventory" data-page-length="15" data-order='[[6,"desc"]]'>
                <thead><tr><th>Gear</th><th>Kategori</th><th>Cabang</th><th>Harga/hari</th><th>Stok</th><th>Varian</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <p class="rounded-2xl border border-line bg-cream-50 px-4 py-3 text-[0.65rem] leading-relaxed text-muted">
        <?= icon('info', 'h-3.5 w-3.5') ?> Harga, stok, varian, dan status di halaman ini hanya berlaku untuk cabang yang dipilih. Produk global yang dipakai bersama hanya bisa diubah inventarisnya oleh admin cabang.
    </p>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
