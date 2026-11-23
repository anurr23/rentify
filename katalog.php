<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/catalog_render.php';

$categorySlug = trim((string) ($_GET['category'] ?? ''));
$category = null;
if ($categorySlug !== '') {
    $category = Category::findBySlug($categorySlug);
    if (!$category) {
        flash('error', 'Kategori tidak ditemukan.');
        redirect('katalog.php');
    }
}

$categoryFilters = $category ? Category::filters((int) $category['id']) : [];
$allowed = [];
foreach ($categoryFilters as $filter) {
    $allowed[$filter['filter_key']] = $filter['options'];
}
$activeFilters = [];
$rawFilters = $_GET['f'] ?? [];
if (is_array($rawFilters)) {
    foreach ($rawFilters as $key => $value) {
        if (!is_string($key) || !isset($allowed[$key]) || !is_string($value)) {
            continue;
        }
        if (in_array($value, $allowed[$key], true)) {
            $activeFilters[$key] = $value;
        }
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'nearest');
$allowedSorts = ['nearest', 'popular', 'rating', 'price_asc', 'price_desc', 'newest'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'nearest';
}

$storeSlug = trim((string) ($_GET['store'] ?? ''));
$storeFilter = $storeSlug !== '' ? Store::findBySlug($storeSlug) : null;
if ($storeSlug !== '' && (!$storeFilter || (int) $storeFilter['is_active'] !== 1)) {
    $storeFilter = null;
}
$stores = Store::active();
$currentUser = auth_user();
$catalogLat = $currentUser && $currentUser['last_latitude'] !== null ? (float) $currentUser['last_latitude'] : null;
$catalogLng = $currentUser && $currentUser['last_longitude'] !== null ? (float) $currentUser['last_longitude'] : null;
$preferredStore = current_store($currentUser);
$catalogStoreId = $storeFilter ? (int) $storeFilter['id'] : 0;

$page = page_number();
$perPage = $category ? 9 : 12;
$hasFilterContext = $q !== '' || $activeFilters || $sort !== 'popular' || $page > 1 || (bool) $storeFilter;
$result = Item::search([
    'category_id' => $category['id'] ?? 0,
    'q' => $q,
    'filters' => $activeFilters,
    'sort' => $sort,
    'page' => $page,
    'per_page' => $perPage,
    'store_id' => $catalogStoreId,
    'preferred_store_id' => $preferredStore ? (int) $preferredStore['id'] : 0,
    'lat' => $catalogLat,
    'lng' => $catalogLng,
]);

$categories = Category::allActive();
$popularItems = Item::trending(4, $catalogStoreId ?: ($preferredStore['id'] ?? 0));
$topItem = $result['items'][0] ?? ($popularItems[0] ?? null);
$categoryAdvisors = [
    'tenda-shelter' => 'AI Tent Advisor',
    'carrier-tas' => 'AI Carrier Advisor',
    'sepatu-sandal' => 'AI Footwear Matcher',
    'pakaian-jaket' => 'AI Layering Advisor',
    'sleeping-system' => 'AI Warm Advisor',
    'lampu-penerangan' => 'AI Lumens Advisor',
    'trekking-navigasi' => 'AI Route Advisor',
    'kompor-cooking' => 'AI Chef Recommender',
    'hammock-tarp' => 'AI Camp & Hygiene',
    'sanitasi-kebersihan' => 'AI Hygiene Advisor',
    'power-gadget' => 'AI Power Advisor',
    'paket-camping' => 'AI Package Advisor',
];
$advisorTitle = $category ? ($categoryAdvisors[$category['slug']] ?? 'AI Gear Advisor') : 'AI Gear Recommender';
$advisorSubtitle = $category ? 'Temukan ' . strtolower(explode(' ', $category['name'])[0]) . ' yang paling pas untukmu' : 'Temukan perlengkapan terbaik sesuai aktivitas';
$heroImage = $category['hero_image'] ?? ($categories[0]['hero_image'] ?? null);

$pageTitle = ($category ? $category['name'] : 'Semua Kategori') . ' — Rentify Outdoor Karawang';
$metaDescription = $category ? truncate_text($category['description'], 150) : 'Jelajahi 12 kategori perlengkapan outdoor di Rentify Karawang.';
$activeNav = 'katalog';
$bodyClass = 'pb-16 lg:pb-0';
$pageScripts = ['catalog.js'];
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<main id="main" class="pb-16 lg:pb-24">
    <section class="app-hero -mb-7 pb-14">
        <div class="absolute inset-0 bg-cover bg-center opacity-35" style="background-image:url('<?= e(upload_url($heroImage)) ?>');"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-forest-950/65 via-forest-950/70 to-forest-900"></div>

        <div class="container-app relative pt-3">
            <div class="flex items-center justify-between">
                <a href="<?= e(url('katalog.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Kembali"><?= icon('arrow-left', 'h-[1.1rem] w-[1.1rem]') ?></a>
                <div class="text-center">
                    <p class="text-[0.55rem] font-extrabold uppercase tracking-[0.2em] text-mint-300/70">Rentify</p>
                    <h1 class="text-base font-extrabold tracking-tight"><?= e($category['name'] ?? 'Semua Kategori') ?></h1>
                </div>
                <a href="<?= e(url('keranjang.php')) ?>" class="icon-btn icon-btn-dark relative h-10 w-10" aria-label="Keranjang">
                    <?= icon('cart', 'h-[1.1rem] w-[1.1rem]') ?>
                    <span class="nav-badge <?= $cartCount ? '' : 'hidden' ?>" data-cart-count><?= $cartCount ?></span>
                </a>
            </div>

            <div class="mx-auto mt-6 max-w-2xl text-center">
                <p class="text-xs text-white/55"><?= $category ? (int) $category['item_count'] . ' unit siap sewa · ' . e($category['description']) : '12 kategori perlengkapan outdoor di Karawang' ?></p>
            </div>

            <form action="<?= e(url('katalog.php')) ?>" method="get" class="hero-search mx-auto mt-5 max-w-2xl" role="search" data-catalog-search-form>
                <?php if ($category): ?><input type="hidden" name="category" value="<?= e($category['slug']) ?>"><?php endif; ?>
                <?php if ($storeFilter): ?><input type="hidden" name="store" value="<?= e($storeFilter['slug']) ?>"><?php endif; ?>
                <span class="text-white/55"><?= icon('search', 'h-[1.15rem] w-[1.15rem]') ?></span>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= $category ? 'Cari di ' . e(explode(' ', $category['name'])[0]) . '...' : 'Cari tenda, kompor, carrier ultralight...' ?>" aria-label="Cari gear" data-catalog-search autocomplete="off">
                <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-white/50 transition hover:bg-white/10 hover:text-white <?= $q === '' ? 'hidden' : '' ?>" data-catalog-search-clear aria-label="Hapus pencarian"><?= icon('x', 'h-4 w-4') ?></button>
                <button type="submit" class="search-submit" aria-label="Cari sekarang"><?= icon('search', 'h-[1.1rem] w-[1.1rem]') ?></button>
            </form>
            <p class="mx-auto mt-2 max-w-2xl text-center text-[0.65rem] font-semibold text-white/45" data-catalog-search-hint aria-live="polite">
                <?= $q !== '' ? 'Hasil pencarian untuk &ldquo;' . e($q) . '&rdquo;' : 'Ketik untuk mencari secara langsung' ?>
            </p>

            <a href="<?= e(url('rekomendasi.php' . ($category ? '?' . http_build_query(['category' => $category['slug']]) : ''))) ?>" class="mx-auto mt-3 flex max-w-2xl items-center gap-3 rounded-[1.15rem] border border-white/15 bg-white/10 p-3 backdrop-blur transition hover:bg-white/15">
                <span class="ai-orb !h-10 !w-10"><?= icon('sparkles', 'h-4 w-4') ?></span>
                <div class="min-w-0 flex-1">
                    <p class="text-[0.82rem] font-extrabold"><?= e($advisorTitle) ?></p>
                    <p class="truncate text-[0.65rem] text-white/50"><?= e($advisorSubtitle) ?></p>
                </div>
                <span class="btn btn-accent btn-sm shrink-0">Coba Sekarang <?= icon('arrow-right', 'h-3 w-3') ?></span>
            </a>
        </div>

        <div class="absolute inset-x-0 bottom-0 h-8 rounded-t-[1.7rem] bg-cream-50"></div>
    </section>

    <div class="container-app relative">
        <?php if (!$category): ?>
            <section class="pt-6">
                <div class="flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-sm font-extrabold text-forest-900"><?= icon('star-filled', 'h-4 w-4 text-star') ?> Paling Sering Disewa</h2>
                    <span class="text-[0.62rem] font-semibold text-muted">Pekan ini</span>
                </div>
                <div class="no-scrollbar mt-3 flex gap-2 overflow-x-auto pb-1">
                    <?php foreach ($popularItems as $item): ?>
                        <a href="<?= e(url('detail.php?slug=' . $item['slug'])) ?>" class="chip shrink-0 gap-2">
                            <?= icon($item['category_icon'] ?? 'package', 'h-3.5 w-3.5') ?>
                            <span class="max-w-[9rem] truncate"><?= e($item['name']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <section class="pt-6">
                <div class="card p-4 sm:p-5" data-filter-card>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.16em] text-forest-500">Filter kategori</p>
                            <p class="mt-0.5 text-xs text-muted">Pilih spesifikasi yang kamu butuhkan</p>
                        </div>
                        <button type="button" class="text-[0.68rem] font-bold text-forest-500 hover:text-forest-900" data-reset-filters>Reset Filter</button>
                    </div>
                    <div class="mt-4">
                        <?= render_category_filters($categoryFilters, $activeFilters) ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!$category): ?>
            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-start">
                <div class="min-w-0">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <label class="text-[0.65rem] font-extrabold uppercase tracking-[0.1em] text-muted" for="catalog-store">Cabang</label>
                            <select id="catalog-store" class="select input h-9 !w-auto !rounded-full !py-0 !pl-3 pr-8 text-xs font-bold" data-catalog-store data-searchable="pill">
                                <option value="">Semua cabang · terdekat</option>
                                <?php foreach ($stores as $storeOption): ?>
                                    <option value="<?= e($storeOption['slug']) ?>" <?= $storeFilter && (int) $storeFilter['id'] === (int) $storeOption['id'] ? 'selected' : '' ?>><?= e($storeOption['name']) ?> · <?= e($storeOption['city']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <p class="text-[0.62rem] text-muted"><?= $catalogLat !== null ? 'Diurutkan dari lokasimu' : 'Aktifkan lokasi untuk melihat jarak cabang' ?></p>
                    </div>
                    <section id="katalog-lengkap" class="scroll-mt-24 pb-4" data-catalog-root data-catalog-base="<?= e(url('katalog.php')) ?>" data-category="" data-store="<?= e($storeFilter['slug'] ?? '') ?>">
                        <div data-catalog-results>
                            <?= render_catalog_results($result, $category) ?>
                        </div>
                    </section>

                    <?php if ($topItem && !$hasFilterContext): ?>
                        <section class="mt-8">
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <h2 class="text-base font-extrabold text-forest-900">Paling direkomendasikan di Karawang</h2>
                                    <p class="mt-1 text-xs text-muted">Paling banyak disewa untuk camping & pendakian sekitar Karawang</p>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-full bg-mint-300 px-2.5 py-1 text-[0.6rem] font-extrabold text-forest-900"><?= icon('sparkles', 'h-3 w-3') ?> Pilihan AI</span>
                            </div>
                            <div class="mt-4"><?= item_card($topItem, ['variant' => 'featured', 'class' => 'sm:grid sm:grid-cols-2 sm:items-stretch']) ?></div>
                        </section>
                    <?php endif; ?>
                </div>

                <aside class="min-w-0 space-y-4 lg:sticky lg:top-24" aria-label="Kategori katalog">
                    <section class="card min-w-0 p-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.14em] text-forest-500">Jelajahi</p>
                                <h2 class="mt-0.5 text-sm font-extrabold text-forest-900">Katalog Kategori</h2>
                            </div>
                            <span class="badge badge-mint"><?= count($categories) ?></span>
                        </div>
                        <div class="no-scrollbar mt-3 flex w-full min-w-0 max-w-full gap-2 overflow-x-auto pb-1 lg:grid lg:grid-cols-2 lg:overflow-visible lg:pb-0">
                            <?php foreach ($categories as $itemCategory): ?>
                                <a href="<?= e(url('katalog.php?category=' . $itemCategory['slug'])) ?>" class="group relative block w-[7.5rem] shrink-0 overflow-hidden rounded-xl lg:w-auto" aria-label="Buka kategori <?= e($itemCategory['name']) ?>">
                                    <div class="relative aspect-[4/3] overflow-hidden bg-cream-100">
                                        <img src="<?= e(upload_url($itemCategory['hero_image'])) ?>" alt="<?= e($itemCategory['name']) ?>" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                        <div class="absolute inset-0 bg-gradient-to-t from-forest-950/90 via-forest-950/15 to-transparent"></div>
                                        <div class="absolute inset-x-0 bottom-0 p-2">
                                            <p class="text-[0.5rem] font-bold uppercase tracking-[0.08em] text-mint-300"><?= (int) $itemCategory['item_count'] ?> unit</p>
                                            <h3 class="mt-0.5 line-clamp-2 text-[0.62rem] font-extrabold leading-tight text-white"><?= e($itemCategory['name']) ?></h3>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <a href="#katalog-lengkap" class="mt-3 flex items-center justify-center gap-1 rounded-full border border-line px-3 py-2 text-[0.65rem] font-extrabold text-forest-700 transition hover:bg-cream-50">
                            Lihat semua gear <?= icon('chevron-down', 'h-3.5 w-3.5') ?>
                        </a>
                    </section>
                </aside>
            </div>
        <?php else: ?>
            <div class="mt-5 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <label class="text-[0.65rem] font-extrabold uppercase tracking-[0.1em] text-muted" for="catalog-store">Cabang</label>
                    <select id="catalog-store" class="select input h-9 !w-auto !rounded-full !py-0 !pl-3 pr-8 text-xs font-bold" data-catalog-store data-searchable="pill">
                        <option value="">Semua cabang · terdekat</option>
                        <?php foreach ($stores as $storeOption): ?>
                            <option value="<?= e($storeOption['slug']) ?>" <?= $storeFilter && (int) $storeFilter['id'] === (int) $storeOption['id'] ? 'selected' : '' ?>><?= e($storeOption['name']) ?> · <?= e($storeOption['city']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="text-[0.62rem] text-muted"><?= $catalogLat !== null ? 'Diurutkan dari lokasimu' : 'Aktifkan lokasi untuk melihat jarak cabang' ?></p>
            </div>
            <section id="katalog-lengkap" class="mt-4 scroll-mt-24 pb-4" data-catalog-root data-catalog-base="<?= e(url('katalog.php')) ?>" data-category="<?= e($category['slug'] ?? '') ?>" data-store="<?= e($storeFilter['slug'] ?? '') ?>">
                <div data-catalog-results>
                    <?= render_catalog_results($result, $category) ?>
                </div>
            </section>

            <?php if ($topItem && !$hasFilterContext): ?>
                <section class="mt-8">
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <h2 class="text-base font-extrabold text-forest-900">Paling direkomendasikan di Karawang</h2>
                            <p class="mt-1 text-xs text-muted">Paling banyak disewa untuk camping & pendakian sekitar Karawang</p>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-mint-300 px-2.5 py-1 text-[0.6rem] font-extrabold text-forest-900"><?= icon('sparkles', 'h-3 w-3') ?> Pilihan AI</span>
                    </div>
                    <div class="mt-4"><?= item_card($topItem, ['variant' => 'featured', 'class' => 'sm:grid sm:grid-cols-2 sm:items-stretch']) ?></div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
