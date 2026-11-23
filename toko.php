<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/item_card.php';

$currentUser = auth_user();
$stores = Store::active();
$preferredStoreId = (int) ($currentUser['preferred_store_id'] ?? 0);
$nearest = null;
if ($currentUser && $currentUser['last_latitude'] !== null && $currentUser['last_longitude'] !== null) {
    $nearestList = Store::nearest((float) $currentUser['last_latitude'], (float) $currentUser['last_longitude'], 1);
    $nearest = $nearestList[0] ?? null;
}
$selectedSlug = trim((string) ($_GET['store'] ?? ''));
$selectedStore = $selectedSlug !== '' ? Store::findBySlug($selectedSlug) : null;
$userLat = $currentUser && $currentUser['last_latitude'] !== null ? (float) $currentUser['last_latitude'] : null;
$userLng = $currentUser && $currentUser['last_longitude'] !== null ? (float) $currentUser['last_longitude'] : null;
if ($selectedStore && $userLat !== null && $userLng !== null
    && $selectedStore['latitude'] !== null && $selectedStore['longitude'] !== null) {
    $selectedStore['distance_km'] = Store::distanceKm(
        $userLat,
        $userLng,
        (float) $selectedStore['latitude'],
        (float) $selectedStore['longitude']
    );
    $selectedStore['distance_label'] = Store::distanceLabel((float) $selectedStore['distance_km']);
}
$storeItems = [];
$storeItemTotal = 0;
if ($selectedStore) {
    $storeSearch = Item::search([
        'store_id' => (int) $selectedStore['id'],
        'per_page' => 6,
        'sort' => 'popular',
        'lat' => $userLat,
        'lng' => $userLng,
    ]);
    $storeItems = $storeSearch['items'];
    $storeItemTotal = (int) $storeSearch['total'];
}
$mapCenter = $selectedStore ?: ($nearest ?: ($stores[0] ?? null));
$mapCenterLat = $mapCenter['latitude'] !== null ? (float) $mapCenter['latitude'] : -6.301;
$mapCenterLng = $mapCenter['longitude'] !== null ? (float) $mapCenter['longitude'] : 107.305;

$storeData = array_map(static function (array $store) use ($preferredStoreId): array {
    return [
        'id' => (int) $store['id'],
        'code' => $store['code'],
        'name' => $store['name'],
        'slug' => $store['slug'],
        'address' => $store['address'],
        'city' => $store['city'],
        'province' => $store['province'],
        'latitude' => $store['latitude'] !== null ? (float) $store['latitude'] : null,
        'longitude' => $store['longitude'] !== null ? (float) $store['longitude'] : null,
        'opening_hours' => $store['opening_hours'],
        'phone' => $store['phone'],
        'whatsapp' => $store['whatsapp'],
        'image' => $store['image'] ? upload_url($store['image']) : upload_url(null),
        'maps_url' => store_maps_link($store),
        'url' => url('toko.php?store=' . rawurlencode($store['slug'])),
        'is_preferred' => (int) $store['id'] === $preferredStoreId,
    ];
}, $stores);

$pageTitle = 'Toko Cabang — Rentify Outdoor Karawang';
$metaDescription = 'Temukan basecamp Rentify terdekat di Karawang, Bandung, Jakarta, Bogor, dan Surabaya. Aktifkan lokasi untuk rekomendasi cabang terdekat.';
$activeNav = 'toko';
$bodyClass = 'pb-16 lg:pb-0';
$pageStyles = ['vendor/leaflet/leaflet.css'];
$pageVendorScripts = ['leaflet/leaflet.js'];
$pageScripts = ['location.js', 'map-stores.js'];
$inlineScript = 'window.RENTIFY_STORES = ' . json_encode($storeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';
?>
<main id="main" class="pb-16 lg:pb-24">
    <section class="app-hero !pb-16">
        <div class="absolute inset-0 topo-bg opacity-20"></div>
        <div class="container-app relative pt-4">
            <div class="flex items-center justify-between gap-3">
                <a href="<?= e(url($currentUser ? 'beranda.php' : 'index.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Kembali"><?= icon('arrow-left', 'h-[1.1rem] w-[1.1rem]') ?></a>
                <span class="badge border border-white/15 bg-white/10 text-white"><?= count($stores) ?> cabang aktif</span>
            </div>
            <div class="mt-8 max-w-2xl">
                <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.2em] text-mint-300">Jaringan basecamp</p>
                <h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Satu standar gear,<br class="hidden sm:block"> banyak titik jemput.</h1>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-white/55">Pilih cabang terdekat, lihat jam operasional, dan jadikan toko utama agar checkout berikutnya langsung tepat sasaran.</p>
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <button type="button" class="btn btn-accent" data-use-location><?= icon('compass', 'h-4 w-4') ?> Gunakan lokasi saya</button>
                    <a href="<?= e(url('katalog.php')) ?>" class="btn btn-ghost">Jelajahi gear <?= icon('arrow-right', 'h-4 w-4') ?></a>
                </div>
                <p class="mt-3 flex items-center gap-1.5 text-[0.68rem] font-semibold text-white/60" data-location-status <?= (!$nearest && !$preferredStoreId) ? 'data-location-auto' : '' ?>>
                    <?= icon('map-pin', 'h-3.5 w-3.5') ?> <?= $nearest ? 'Lokasi aktif · cabang terdekat: ' . e($nearest['name']) . ' (' . e($nearest['distance_label']) . ')' : 'Aktifkan lokasi untuk melihat jarak ke setiap cabang.' ?>
                </p>
            </div>
        </div>
        <div class="absolute inset-x-0 bottom-0 h-8 rounded-t-[1.7rem] bg-cream-50"></div>
    </section>

    <div class="container-app -mt-2">
        <div class="overflow-hidden rounded-[1.5rem] border border-line bg-white shadow-soft">
            <div id="store-map" class="h-[24rem] w-full sm:h-[30rem]" aria-label="Peta cabang Rentify"></div>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[0.65rem] text-muted">
            <p class="flex items-center gap-1.5"><?= icon('map', 'h-3.5 w-3.5') ?> Klik marker untuk melihat detail cabang.</p>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <p data-nearest-store-distance><?= $nearest ? e(Store::distanceLabel((float) $nearest['distance_km'])) . ' dari lokasimu' : 'Izinkan lokasi untuk menghitung jarak.' ?></p>
                <p class="text-[0.6rem] text-muted/80">Peta: Esri World Topographic · © OpenStreetMap</p>
            </div>
        </div>

        <?php if ($selectedStore): ?>
            <section class="mt-8" aria-label="Detail cabang <?= e($selectedStore['name']) ?>">
                <div class="grid gap-5 xl:grid-cols-[.85fr_1.15fr] xl:items-start">
                    <div class="card overflow-hidden">
                        <div class="relative h-48 bg-cream-100">
                            <img src="<?= e(upload_url($selectedStore['image'] ?? null)) ?>" alt="<?= e($selectedStore['name']) ?>" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-forest-950/85 via-forest-950/20 to-transparent"></div>
                            <div class="absolute inset-x-0 bottom-0 p-4 text-white">
                                <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.16em] text-mint-300"><?= e($selectedStore['code']) ?></p>
                                <h2 class="mt-1 text-xl font-extrabold"><?= e($selectedStore['name']) ?></h2>
                                <p class="mt-1 text-[0.65rem] text-white/65"><?= e($selectedStore['city']) ?>, <?= e($selectedStore['province']) ?></p>
                            </div>
                        </div>
                        <div class="p-4">
                            <p class="text-xs leading-relaxed text-muted"><?= e($selectedStore['address']) ?></p>
                            <?php if (!empty($selectedStore['description'])): ?><p class="mt-2 text-xs leading-relaxed text-forest-700"><?= e($selectedStore['description']) ?></p><?php endif; ?>
                            <div class="mt-3 space-y-2 text-[0.68rem]">
                                <p class="flex items-center gap-2 text-forest-700"><span class="text-forest-500"><?= icon('clock', 'h-3.5 w-3.5') ?></span> <?= e($selectedStore['opening_hours'] ?: 'Jam operasional menyusul') ?></p>
                                <p class="flex items-center gap-2 text-forest-700"><span class="text-forest-500"><?= icon('phone', 'h-3.5 w-3.5') ?></span> <?= e($selectedStore['phone'] ?: '-') ?></p>
                                <p class="flex items-center gap-2 text-forest-700"><span class="text-forest-500"><?= icon('whatsapp', 'h-3.5 w-3.5') ?></span> <?= e($selectedStore['whatsapp'] ?: '-') ?></p>
                                <p class="flex items-center gap-2 text-forest-700"><span class="text-forest-500"><?= icon('mail', 'h-3.5 w-3.5') ?></span> <?= e($selectedStore['email'] ?: '-') ?></p>
                                <?php if (!empty($selectedStore['distance_label'])): ?><p class="flex items-center gap-2 font-bold text-forest-900"><span class="text-forest-500"><?= icon('map-pin', 'h-3.5 w-3.5') ?></span> <?= e($selectedStore['distance_label']) ?> dari lokasimu</p><?php endif; ?>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <a href="<?= e(store_wa_link($selectedStore, 'Halo ' . $selectedStore['name'] . ', saya ingin bertanya.')) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><?= icon('whatsapp', 'h-3.5 w-3.5') ?> WhatsApp</a>
                                <a href="<?= e(store_maps_link($selectedStore)) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><?= icon('map', 'h-3.5 w-3.5') ?> Peta</a>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="section-kicker">Katalog cabang</p>
                                <h2 class="mt-1 text-xl font-extrabold tracking-tight text-forest-900">Barang di <?= e($selectedStore['name']) ?></h2>
                                <p class="mt-1 text-xs text-muted"><?= $storeItemTotal ?> jenis gear tersedia di cabang ini.</p>
                            </div>
                            <a href="<?= e(url('katalog.php?store=' . rawurlencode($selectedStore['slug']))) ?>" class="btn btn-outline btn-sm">Lihat semua gear <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3.5 sm:gap-4">
                            <?php foreach ($storeItems as $storeItem): ?>
                                <?= item_card($storeItem) ?>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!$storeItems): ?>
                            <div class="rounded-2xl border border-dashed border-line bg-white p-8 text-center text-sm text-muted">Belum ada barang aktif di cabang ini.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="mt-8">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="section-kicker">Daftar cabang</p>
                    <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900">Pilih basecamp-mu</h2>
                    <p class="mt-1 text-xs text-muted">Setiap cabang punya stok dan kontak sendiri.</p>
                </div>
                <?php if ($nearest): ?>
                    <span class="chip chip-active"><?= icon('map-pin', 'h-3.5 w-3.5') ?> Terdekat: <?= e($nearest['name']) ?></span>
                <?php endif; ?>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($stores as $store): ?>
                    <?php $isPreferred = (int) $store['id'] === $preferredStoreId; ?>
                    <article class="group overflow-hidden rounded-[1.4rem] border <?= $isPreferred ? 'border-forest-700 ring-1 ring-forest-700/15' : 'border-line' ?> bg-white shadow-soft" data-store-card="<?= (int) $store['id'] ?>">
                        <div class="relative h-40 overflow-hidden bg-cream-100">
                            <img src="<?= e(upload_url($store['image'] ?? null)) ?>" alt="<?= e($store['name']) ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                            <div class="absolute inset-0 bg-gradient-to-t from-forest-950/75 via-forest-950/10 to-transparent"></div>
                            <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-2 p-4 text-white">
                                <div>
                                    <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.14em] text-mint-300"><?= e($store['code']) ?></p>
                                    <h3 class="mt-0.5 text-base font-extrabold leading-tight"><?= e($store['name']) ?></h3>
                                    <p class="mt-0.5 text-[0.65rem] text-white/65"><?= e($store['city']) ?>, <?= e($store['province']) ?></p>
                                </div>
                                <?php if ($isPreferred): ?><span class="badge bg-mint-300 text-forest-900">Toko utama</span><?php endif; ?>
                            </div>
                        </div>
                        <div class="p-4">
                            <p class="line-clamp-2 min-h-[2.4rem] text-[0.7rem] leading-relaxed text-muted"><?= e($store['address']) ?></p>
                            <div class="mt-2 space-y-1 text-[0.65rem] text-muted">
                                <p class="flex items-center gap-1.5"><?= icon('clock', 'h-3.5 w-3.5 text-forest-500') ?> <?= e($store['opening_hours'] ?: 'Jam operasional menyusul') ?></p>
                                <p class="flex items-center gap-1.5"><?= icon('phone', 'h-3.5 w-3.5 text-forest-500') ?> <?= e($store['phone'] ?: '-') ?></p>
                                <p class="flex items-center gap-1.5 font-bold text-forest-700" data-store-distance="<?= (int) $store['id'] ?>">
                                    <?= icon('map-pin', 'h-3.5 w-3.5') ?> <?= $store['id'] === ($nearest['id'] ?? 0) ? e($nearest['distance_label']) . ' dari lokasimu' : 'Aktifkan lokasi untuk jarak' ?>
                                </p>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline btn-sm flex-1" data-set-store="<?= (int) $store['id'] ?>" data-saved="<?= $isPreferred ? '1' : '0' ?>">
                                    <?= icon('check-circle', 'h-3.5 w-3.5') ?> <?= $isPreferred ? 'Toko utama' : 'Jadikan utama' ?>
                                </button>
                                <a href="<?= e(store_maps_link($store)) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><?= icon('map', 'h-3.5 w-3.5') ?> Peta</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="mt-10">
            <div class="relative overflow-hidden rounded-[1.6rem] bg-forest-900 px-6 py-8 text-white sm:px-9">
                <div class="absolute inset-0 topo-bg opacity-30"></div>
                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="max-w-lg">
                        <p class="section-kicker text-mint-300">Butuh bantuan?</p>
                        <h2 class="mt-1.5 text-2xl font-extrabold leading-tight tracking-tight">Tim cabang siap membantu.</h2>
                        <p class="mt-2 text-sm text-white/55">Tanyakan stok, jam ambil, atau rekomendasi gear untuk trip berikutnya.</p>
                    </div>
                    <a href="<?= e(store_wa_link($mapCenter, 'Halo Rentify, saya ingin bertanya tentang sewa gear.')) ?>" target="_blank" rel="noopener" class="btn btn-accent shrink-0"><?= icon('whatsapp', 'h-4 w-4') ?> Chat <?= e($mapCenter['city'] ?? 'Rentify') ?></a>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
