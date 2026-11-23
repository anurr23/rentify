<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_superadmin();
$q = trim((string) ($_GET['q'] ?? ''));
$categoryId = (int) ($_GET['category'] ?? 0);
$statusFilter = (string) ($_GET['status'] ?? 'semua');
$page = page_number();
$perPage = 15;

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(i.name LIKE ? OR i.brand LIKE ? OR i.slug LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($categoryId > 0) {
    $where[] = 'i.category_id = ?';
    $params[] = $categoryId;
}
if ($statusFilter === 'aktif') {
    $where[] = 'i.is_active = 1';
} elseif ($statusFilter === 'nonaktif') {
    $where[] = 'i.is_active = 0';
}
$whereSql = implode(' AND ', $where);
$countStmt = Database::conn()->prepare("SELECT COUNT(*) FROM items i WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pagination = paginate($total, $perPage, $page);
$stmt = Database::conn()->prepare(
    "SELECT i.*, c.name AS category_name,
        (SELECT COUNT(*) FROM item_variants v WHERE v.item_id = i.id AND v.is_active = 1) AS variant_count
     FROM items i JOIN categories c ON c.id = i.category_id
     WHERE $whereSql ORDER BY i.created_at DESC, i.id DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}"
);
$stmt->execute($params);
$items = $stmt->fetchAll();
$categories = Category::all();
$autoOpen = isset($_GET['new']);

$adminTitle = 'Barang & Gear';
$adminPage = 'barang';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Kelola inventaris rental</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900"><?= format_number($total) ?> gear terdaftar</h2>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-admin-modal data-url="<?= e(url('api/admin.php?action=item_form&id=0')) ?>" data-auto-open="<?= $autoOpen ? '1' : '0' ?>">
            <?= icon('plus', 'h-3.5 w-3.5') ?> Tambah gear
        </button>
    </div>

    <form class="admin-card flex flex-wrap items-end gap-2 p-3.5" method="get">
        <div class="min-w-[12rem] flex-1">
            <label class="sr-only" for="gear-search">Cari gear</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted"><?= icon('search', 'h-4 w-4') ?></span>
                <input id="gear-search" class="input input-icon !py-2.5 text-sm" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama, brand, slug...">
            </div>
        </div>
        <select class="select input !w-auto !py-2.5 text-sm" name="category">
            <option value="0">Semua kategori</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="select input !w-auto !py-2.5 text-sm" name="status">
            <option value="semua" <?= $statusFilter === 'semua' ? 'selected' : '' ?>>Semua status</option>
            <option value="aktif" <?= $statusFilter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= $statusFilter === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <?php if ($q !== '' || $categoryId || $statusFilter !== 'semua'): ?>
            <a href="<?= e(url('admin/barang.php')) ?>" class="text-xs font-bold text-muted hover:text-forest-900">Reset</a>
        <?php endif; ?>
    </form>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-app min-w-[52rem]">
                <thead>
                    <tr><th>Gear</th><th>Kategori</th><th>Harga/hari</th><th>Stok</th><th>Rating</th><th>Status</th><th class="text-right">Aksi</th></tr>
                </thead>
                <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="7" class="py-12 text-center text-sm text-muted">Tidak ada gear yang cocok dengan filter.</td></tr>
                <?php endif; ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <div class="h-11 w-11 shrink-0 overflow-hidden rounded-xl bg-cream-100"><img src="<?= e(upload_url($item['primary_image'])) ?>" alt="" class="h-full w-full object-cover"></div>
                                <div class="min-w-0">
                                    <p class="max-w-[16rem] truncate font-extrabold text-forest-900"><?= e($item['name']) ?></p>
                                    <p class="text-[0.62rem] text-muted"><?= e($item['brand'] ?: 'No brand') ?><?= (int) $item['variant_count'] ? ' Â· ' . (int) $item['variant_count'] . ' varian' : '' ?><?= !empty($item['is_featured']) ? ' Â· Pilihan' : '' ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="text-xs text-forest-700"><?= e($item['category_name']) ?></td>
                        <td class="font-bold"><?= e(format_rupiah($item['price_per_day'])) ?></td>
                        <td><span class="<?= (int) $item['stock_total'] <= 3 ? 'text-danger' : '' ?> font-bold"><?= (int) $item['stock_total'] ?></span></td>
                        <td><span class="inline-flex items-center gap-1 text-xs font-bold"><?= icon('star-filled', 'h-3.5 w-3.5 text-star') ?> <?= (float) $item['rating_avg'] > 0 ? number_format((float) $item['rating_avg'], 1, ',', '.') : 'â€”' ?></span></td>
                        <td><span class="badge <?= (int) $item['is_active'] ? 'badge-success' : 'badge-neutral' ?>"><?= (int) $item['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= e(url('detail.php?slug=' . $item['slug'])) ?>" target="_blank" rel="noopener" class="icon-btn h-8 w-8" aria-label="Lihat publik"><?= icon('external', 'h-3.5 w-3.5') ?></a>
                                <button type="button" class="icon-btn h-8 w-8" data-admin-modal data-url="<?= e(url('api/admin.php?action=item_form&id=' . $item['id'])) ?>" aria-label="Edit gear"><?= icon('edit', 'h-3.5 w-3.5') ?></button>
                                <button type="button" class="icon-btn h-8 w-8 text-danger" data-admin-delete="item" data-id="<?= (int) $item['id'] ?>" data-name="<?= e($item['name']) ?>" aria-label="Hapus gear"><?= icon('trash', 'h-3.5 w-3.5') ?></button>
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
                        <a href="<?= e(url('admin/barang.php?' . http_build_query(array_filter(['q' => $q, 'category' => $categoryId ?: null, 'status' => $statusFilter !== 'semua' ? $statusFilter : null, 'page' => $i])))) ?>" class="flex h-8 min-w-8 items-center justify-center rounded-full px-2 font-extrabold <?= $i === (int) $pagination['page'] ? 'bg-forest-900 text-white' : 'border border-line bg-white' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
