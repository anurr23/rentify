<?php
declare(strict_types=1);

require_once ROOT_PATH . '/includes/item_card.php';

/**
 * Render hasil katalog. Dipakai langsung oleh halaman dan respons AJAX.
 */
function render_catalog_results(array $result, ?array $category = null, string $view = 'grid'): string
{
    $items = $result['items'] ?? [];
    $pagination = $result['pagination'] ?? ['total' => 0, 'page' => 1, 'pages' => 1];
    $total = (int) ($result['total'] ?? $pagination['total'] ?? 0);
    ob_start();
    ?>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <h2 class="text-lg font-extrabold tracking-tight text-forest-900 sm:text-xl">
                <?= $category ? 'Katalog ' . e(explode(' ', $category['name'])[0]) : 'Katalog lengkap' ?>
            </h2>
            <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-cream-200 px-2 text-[0.65rem] font-extrabold text-forest-700" data-catalog-total><?= $total ?></span>
        </div>
        <div class="flex items-center gap-2">
            <span class="hidden text-[0.68rem] font-semibold text-muted sm:inline">Urutkan</span>
            <select class="select input h-9 !w-auto !rounded-full !py-0 !pl-3 text-xs font-bold" data-catalog-sort aria-label="Urutkan katalog">
                <?php
                $sorts = ['popular' => 'Paling populer', 'rating' => 'Rating tertinggi', 'price_asc' => 'Harga terendah', 'price_desc' => 'Harga tertinggi', 'newest' => 'Terbaru'];
                $activeSort = $_GET['sort'] ?? 'popular';
                foreach ($sorts as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $activeSort === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="hidden items-center gap-1 rounded-full border border-line bg-white p-1 sm:flex">
                <button type="button" class="flex h-7 w-7 items-center justify-center rounded-full <?= $view === 'grid' ? 'bg-forest-900 text-white' : 'text-muted' ?>" data-catalog-view="grid" aria-label="Tampilan grid"><?= icon('grid', 'h-3.5 w-3.5') ?></button>
                <button type="button" class="flex h-7 w-7 items-center justify-center rounded-full <?= $view === 'list' ? 'bg-forest-900 text-white' : 'text-muted' ?>" data-catalog-view="list" aria-label="Tampilan daftar"><?= icon('list', 'h-3.5 w-3.5') ?></button>
            </div>
        </div>
    </div>

    <div class="mt-4 <?= $view === 'list' ? 'grid grid-cols-1 gap-4 lg:grid-cols-2' : 'grid grid-cols-2 gap-3.5 sm:gap-5 lg:grid-cols-3' ?>" data-catalog-grid data-view="<?= e($view) ?>">
        <?php if (!$items): ?>
            <div class="col-span-full rounded-[1.35rem] border border-dashed border-line bg-white px-6 py-14 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-cream-100 text-muted"><?= icon('search', 'h-6 w-6') ?></span>
                <h3 class="mt-4 text-base font-extrabold text-forest-900">Gear tidak ditemukan</h3>
                <p class="mx-auto mt-1 max-w-sm text-sm text-muted">Coba kata kunci lain, longgarkan filter, atau minta rekomendasi AI.</p>
                <button type="button" class="btn btn-outline btn-sm mt-4" data-reset-filters>Reset semua filter</button>
            </div>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <?= item_card($item) ?>
        <?php endforeach; ?>
    </div>

    <?php if ((int) ($pagination['pages'] ?? 1) > 1): ?>
        <nav class="mt-7 flex flex-col items-center gap-2.5" data-catalog-pagination aria-label="Muat katalog">
            <p class="text-[0.68rem] font-semibold text-muted">
                Menampilkan <span data-catalog-shown><?= count($items) ?></span> dari <?= $total ?> gear
            </p>
            <?php if (!empty($pagination['has_next'])): ?>
                <button type="button" class="btn btn-outline btn-sm" data-load-more data-next-page="<?= (int) $pagination['page'] + 1 ?>">
                    <?= icon('chevron-down', 'h-3.5 w-3.5') ?> Muat lebih banyak
                </button>
            <?php else: ?>
                <p class="text-[0.68rem] font-semibold text-muted">Semua gear sudah ditampilkan.</p>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
    <?php
    return (string) ob_get_clean();
}

/**
 * Render filter chips untuk kategori.
 */
function render_category_filters(array $filters, array $active): string
{
    if (!$filters) {
        return '';
    }
    ob_start();
    ?>
    <div class="space-y-4" data-filter-panel>
        <?php foreach ($filters as $filter): ?>
            <?php
            $key = $filter['filter_key'];
            $options = $filter['options'] ?? [];
            $selected = $active[$key] ?? '';
            $isSize = $filter['type'] === 'size';
            ?>
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-forest-500">
                        <?= e($filter['label']) ?> <span class="ml-1 font-semibold normal-case tracking-normal text-muted"><?= count($options) ?> pilihan</span>
                    </p>
                    <?php if ($selected !== ''): ?>
                        <button type="button" class="text-[0.62rem] font-bold text-forest-500 hover:text-forest-900" data-filter-reset-key="<?= e($key) ?>">Reset</button>
                    <?php endif; ?>
                </div>
                <div class="<?= $isSize ? 'grid grid-cols-4 gap-2' : 'no-scrollbar flex gap-2 overflow-x-auto pb-1' ?>">
                    <button type="button" class="chip shrink-0 <?= $selected === '' ? 'chip-active' : '' ?>" data-filter-key="<?= e($key) ?>" data-filter-value="">
                        <?= $selected === '' ? icon('check', 'h-3.5 w-3.5') : '' ?>
                        Semua <?= e(explode(' ', $filter['label'])[0]) ?>
                    </button>
                    <?php foreach ($options as $option): ?>
                        <button type="button" class="chip shrink-0 <?= $isSize ? '!justify-center !px-2' : '' ?> <?= $selected === $option ? 'chip-mint font-extrabold' : '' ?>" data-filter-key="<?= e($key) ?>" data-filter-value="<?= e($option) ?>">
                            <?= e($option) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}
