<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_superadmin();
$categories = Category::all();
$filterCounts = [];
foreach (Database::conn()->query('SELECT category_id, COUNT(*) AS total FROM category_filters GROUP BY category_id')->fetchAll() as $row) {
    $filterCounts[(int) $row['category_id']] = (int) $row['total'];
}
$totalItems = array_sum(array_map(static fn ($category) => (int) $category['item_count'], $categories));

$adminTitle = 'Kategori';
$adminPage = 'kategori';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Struktur katalog</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900"><?= count($categories) ?> kategori Â· <?= $totalItems ?> gear</h2>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-admin-modal data-url="<?= e(url('api/admin.php?action=category_form&id=0')) ?>"><?= icon('plus', 'h-3.5 w-3.5') ?> Tambah kategori</button>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($categories as $category): ?>
            <article class="admin-card overflow-hidden">
                <div class="relative h-24 overflow-hidden bg-cream-100">
                    <?php if (!empty($category['hero_image'])): ?>
                        <img src="<?= e(upload_url($category['hero_image'])) ?>" alt="" class="h-full w-full object-cover">
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-gradient-to-t from-forest-950/75 to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-2 p-3">
                        <div>
                            <p class="text-[0.55rem] font-bold uppercase tracking-[0.14em] text-mint-300"><?= (int) $category['item_count'] ?> gear</p>
                            <h3 class="text-sm font-extrabold text-white"><?= e($category['name']) ?></h3>
                        </div>
                        <span class="badge <?= (int) $category['is_active'] ? 'badge-mint' : 'badge-neutral' ?>"><?= (int) $category['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
                    </div>
                </div>
                <div class="p-3.5">
                    <p class="line-clamp-2 text-[0.68rem] leading-relaxed text-muted"><?= e($category['description'] ?: 'Belum ada deskripsi.') ?></p>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-[0.62rem] text-muted">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full" style="background:<?= e($category['accent_color'] ?: '#CBE8CE') ?>"><?= icon($category['icon'] ?: 'package', 'h-3.5 w-3.5 text-forest-900') ?></span>
                            <span><?= (int) ($filterCounts[(int) $category['id']] ?? 0) ?> filter</span>
                            <span>Â·</span>
                            <span>Urutan <?= (int) $category['sort_order'] ?></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <a href="<?= e(url('katalog.php?category=' . $category['slug'])) ?>" target="_blank" rel="noopener" class="icon-btn h-8 w-8" aria-label="Lihat katalog"><?= icon('external', 'h-3.5 w-3.5') ?></a>
                            <button type="button" class="icon-btn h-8 w-8" data-admin-modal data-url="<?= e(url('api/admin.php?action=category_form&id=' . $category['id'])) ?>" aria-label="Edit kategori"><?= icon('edit', 'h-3.5 w-3.5') ?></button>
                            <button type="button" class="icon-btn h-8 w-8 text-danger" data-admin-delete="category" data-id="<?= (int) $category['id'] ?>" data-name="<?= e($category['name']) ?>" aria-label="Hapus kategori"><?= icon('trash', 'h-3.5 w-3.5') ?></button>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="rounded-2xl border border-dashed border-line bg-white/60 px-4 py-3 text-[0.68rem] leading-relaxed text-muted">
        <?= icon('info', 'h-3.5 w-3.5') ?> Filter spesifikasi per kategori disimpan di tabel <code>category_filters</code> dan dipakai otomatis oleh katalog serta form gear. Kategori yang masih memiliki gear tidak dapat dihapus.
    </div>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
