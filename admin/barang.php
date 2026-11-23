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
$page = page_number();
$result = StoreItem::searchInventory($storeFilterId, $filters, $page, 15);
$inventory = $result['items'];
$pagination = $result['pagination'];
$categories = Category::all();
$storeCounts = StoreItem::countsByStore();
$defaultStore = $filterStore ?: Store::defaultStore();
$addStoreId = $filterStore ? (int) $filterStore['id'] : (int) ($defaultStore['id'] ?? 0);
$addUrl = url('api/admin.php?action=item_form&id=0&store_id=' . $addStoreId);

$activeCount = 0;
$lowStockCount = 0;
foreach ($inventory as $row) {
    if ((int) $row['is_active'] === 1) {
        $activeCount++;
    }
    if ((int) $row['stock_total'] <= 3) {
        $lowStockCount++;
    }
}

$adminTitle = $scopeStoreId !== null ? 'Barang Toko' : 'Inventaris Cabang';
$adminPage = 'barang';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Stok, harga, dan varian per cabang</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900">
                <?= $filterStore ? e($filterStore['name']) : 'Semua cabang' ?>
            </h2>
            <p class="mt-1 text-xs text-muted">
                <?= format_number((int) $pagination['total']) ?> barang di <?= $filterStore ? 'cabang ini' : count($stores) . ' cabang' ?>
                · <?= $activeCount ?> aktif di halaman ini
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
        <div class="admin-card flex flex-wrap items-end gap-2 p-3.5" data-inventory-toolbar>
            <form class="flex flex-1 flex-wrap items-end gap-2" method="get">
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
        </div>
    <?php else: ?>
        <form class="admin-card flex flex-wrap items-end gap-2 p-3.5" method="get">
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
            ['package', 'Barang tampil', (int) $pagination['total'], 'text-forest-700'],
            ['check-circle', 'Aktif', $activeCount, 'text-success'],
            ['alert-triangle', 'Stok ≤ 3', $lowStockCount, 'text-star'],
            ['map-pin', 'Cabang', $filterStore ? $filterStore['city'] : count($stores) . ' cabang', 'text-sky-700'],
        ] as $metric): ?>
            <div class="admin-card p-4">
                <span class="stat-icon <?= $metric[3] ?>"><?= icon($metric[0], 'h-4 w-4') ?></span>
                <p class="mt-2 text-[0.6rem] font-bold uppercase tracking-wide text-muted"><?= e($metric[1]) ?></p>
                <p class="mt-0.5 truncate text-xl font-extrabold text-forest-900"><?= e((string) $metric[2]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-app min-w-[64rem]">
                <thead><tr><th>Gear</th><th>Kategori</th><?php if ($scopeStoreId === null): ?><th>Cabang</th><?php endif; ?><th>Harga/hari</th><th>Stok</th><th>Varian</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                <?php if (!$inventory): ?>
                    <tr><td colspan="<?= $scopeStoreId === null ? 8 : 7 ?>" class="py-12 text-center text-sm text-muted">Belum ada barang untuk filter ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($inventory as $row): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <div class="h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-cream-100">
                                    <img src="<?= e(upload_url($row['primary_image'])) ?>" alt="" class="h-full w-full object-cover">
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-extrabold text-forest-900"><?= e($row['name']) ?></p>
                                    <p class="text-[0.62rem] text-muted"><?= e($row['brand'] ?: 'Rentify Select') ?><?= (int) ($row['owner_store_id'] ?? 0) === (int) $row['store_id'] ? ' · Milik cabang' : '' ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="text-xs text-muted"><?= e($row['category_name']) ?></td>
                        <?php if ($scopeStoreId === null): ?>
                            <td>
                                <p class="text-xs font-bold text-forest-900"><?= e($row['store_name']) ?></p>
                                <p class="text-[0.62rem] text-muted"><?= e($row['store_city']) ?></p>
                            </td>
                        <?php endif; ?>
                        <td class="font-bold"><?= e(format_rupiah($row['price_per_day'])) ?></td>
                        <td>
                            <span class="badge <?= (int) $row['stock_total'] <= 0 ? 'badge-danger' : ((int) $row['stock_total'] <= 3 ? 'badge-warning' : 'badge-mint') ?>"><?= (int) $row['stock_total'] ?> unit</span>
                        </td>
                        <td class="text-xs font-bold"><?= (int) $row['variant_count'] ?></td>
                        <td><span class="badge <?= (int) $row['is_active'] ? 'badge-success' : 'badge-neutral' ?>"><?= (int) $row['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <button type="button" class="icon-btn h-8 w-8" data-admin-modal data-url="<?= e(url('api/admin.php?action=item_form&id=' . (int) $row['item_id'] . '&store_id=' . (int) $row['store_id'])) ?>" data-admin-modal-title="Edit <?= e($row['name']) ?>" aria-label="Edit barang"><?= icon('edit', 'h-3.5 w-3.5') ?></button>
                                <button type="button" class="icon-btn h-8 w-8 text-danger" data-admin-delete="item" data-id="<?= (int) $row['item_id'] ?>" data-store-id="<?= (int) $row['store_id'] ?>" data-name="<?= e($row['name']) ?>" aria-label="Hapus dari cabang"><?= icon('trash', 'h-3.5 w-3.5') ?></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ((int) $pagination['pages'] > 1): ?>
            <div class="flex items-center justify-between border-t border-line px-4 py-3 text-xs text-muted">
                <span>Halaman <?= (int) $pagination['page'] ?> dari <?= (int) $pagination['pages'] ?></span>
                <div class="flex gap-1.5">
                    <?php for ($i = 1; $i <= (int) $pagination['pages']; $i++): ?>
                        <a href="<?= e(url('admin/barang.php?' . http_build_query(array_filter(['q' => $filters['q'] ?: null, 'store_id' => $storeFilterId ?: null, 'category' => $filters['category_id'] ?: null, 'status' => $filters['status'] !== 'semua' ? $filters['status'] : null, 'page' => $i])))) ?>" class="flex h-8 min-w-8 items-center justify-center rounded-full px-2 font-extrabold <?= $i === (int) $pagination['page'] ? 'bg-forest-900 text-white' : 'border border-line bg-white' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <p class="rounded-2xl border border-line bg-cream-50 px-4 py-3 text-[0.65rem] leading-relaxed text-muted">
        <?= icon('info', 'h-3.5 w-3.5') ?> Harga, stok, varian, dan status di halaman ini hanya berlaku untuk cabang yang dipilih. Produk global yang dipakai bersama hanya bisa diubah inventarisnya oleh admin cabang.
    </p>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
