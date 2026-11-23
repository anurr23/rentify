<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/item_card.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$item = $slug !== '' ? Item::findBySlug($slug) : null;
if (!$item) {
    http_response_code(404);
    $pageTitle = 'Gear tidak ditemukan — Rentify';
    $metaDescription = 'Gear yang Anda cari tidak tersedia.';
    require ROOT_PATH . '/includes/header.php';
    require ROOT_PATH . '/includes/customer_nav.php';
    ?>
    <main id="main" class="container-app py-20 text-center">
        <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-cream-100 text-muted"><?= icon('search', 'h-8 w-8') ?></span>
        <h1 class="mt-5 text-2xl font-extrabold text-forest-900">Gear tidak ditemukan</h1>
        <p class="mx-auto mt-2 max-w-md text-sm text-muted">Mungkin sudah tidak aktif atau tautannya berubah. Jelajahi katalog untuk menemukan gear lain.</p>
        <a href="<?= e(url('katalog.php')) ?>" class="btn btn-primary mt-6">Kembali ke katalog</a>
    </main>
    <?php require ROOT_PATH . '/includes/customer_footer.php';
    exit;
}

$category = Category::find((int) $item['category_id']);
$reviews = Item::reviews((int) $item['id'], 8);
$breakdown = Item::ratingBreakdown((int) $item['id']);
$related = Item::related((int) $item['id'], (int) $item['category_id'], 4);
$filters = Item::filterValues((int) $item['id']);
$wishlistIds = is_logged_in() ? Wishlist::ids((int) auth_user()['id']) : [];
$isWished = in_array((int) $item['id'], $wishlistIds, true);

$gallery = [];
if (!empty($item['primary_image'])) {
    $gallery[] = $item['primary_image'];
}
foreach ($item['images'] as $image) {
    if (!empty($image['path']) && !in_array($image['path'], $gallery, true)) {
        $gallery[] = $image['path'];
    }
}
if (!$gallery) {
    $gallery[] = null;
}

$variants = $item['variants'] ?? [];
$selectedVariant = $variants[0] ?? null;
$selectedVariantId = $selectedVariant ? (int) $selectedVariant['id'] : null;
$unitPrice = (float) ($selectedVariant['price_per_day'] ?? $item['price_per_day']);
$stock = $selectedVariant ? (int) $selectedVariant['stock_total'] : (int) $item['stock_total'];

$cartDates = cart_dates();
$minDays = max(1, (int) $item['min_duration']);
$defaultStart = $cartDates['start'] ?: tomorrow();
$defaultDays = $cartDates['start'] ? max(1, duration_days($cartDates['start'], $cartDates['end'])) : max(2, $minDays);
$defaultEnd = $cartDates['end'] ?: date('Y-m-d', strtotime($defaultStart . ' +' . ($defaultDays - 1) . ' days'));
if ($defaultEnd < $defaultStart) {
    $defaultEnd = date('Y-m-d', strtotime($defaultStart . ' +' . max(1, $minDays - 1) . ' days'));
}
$duration = duration_days($defaultStart, $defaultEnd);
$initialAvailable = item_available_qty(Database::conn(), (int) $item['id'], $selectedVariantId, $defaultStart, $defaultEnd);
$initialSubtotal = $unitPrice * $duration;

$pageTitle = $item['name'] . ' — Rentify Outdoor Karawang';
$metaDescription = truncate_text($item['short_description'] ?: $item['description'], 155);
$activeNav = 'katalog';
$bodyClass = 'pb-28 lg:pb-0';
$pageScripts = ['detail.js'];
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';
?>
<main id="main" data-detail-root
      data-item-id="<?= (int) $item['id'] ?>"
      data-item-name="<?= e($item['name']) ?>"
      data-base-price="<?= $unitPrice ?>"
      data-deposit="<?= (float) $item['deposit'] ?>"
      data-start="<?= e($defaultStart) ?>"
      data-end="<?= e($defaultEnd) ?>"
      data-min-days="<?= $minDays ?>"
      data-max-days="<?= (int) $item['max_duration'] ?>">
    <section class="relative bg-forest-950 text-white">
        <div class="container-app pb-24 pt-3">
            <div class="flex items-center justify-between">
                <a href="<?= e(url('katalog.php?category=' . $category['slug'])) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Kembali"><?= icon('arrow-left', 'h-[1.1rem] w-[1.1rem]') ?></a>
                <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.18em] text-mint-300/70"><?= e($item['category_name']) ?></p>
                <a href="<?= e(url('keranjang.php')) ?>" class="icon-btn icon-btn-dark relative h-10 w-10" aria-label="Keranjang">
                    <?= icon('cart', 'h-[1.1rem] w-[1.1rem]') ?>
                    <span class="nav-badge <?= cart_count() ? '' : 'hidden' ?>" data-cart-count><?= cart_count() ?></span>
                </a>
            </div>
        </div>
    </section>

    <div class="container-app relative -mt-20 pb-6">
        <section class="grid gap-4 lg:grid-cols-[1.15fr_1fr] lg:items-start lg:gap-8">
            <div class="overflow-hidden rounded-[1.6rem] border border-line bg-white shadow-soft">
                <div class="relative aspect-[4/3] bg-cream-100 sm:aspect-[16/10]" data-gallery-main>
                    <img src="<?= e(upload_url($gallery[0])) ?>" alt="<?= e($item['name']) ?>" class="h-full w-full object-cover" data-gallery-image>
                    <span class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-extrabold text-forest-900 backdrop-blur">
                        <?= icon('star-filled', 'h-3.5 w-3.5 text-star') ?>
                        <?= (float) $item['rating_avg'] > 0 ? number_format((float) $item['rating_avg'], 1, ',', '.') : 'Baru' ?>
                        <span class="font-semibold text-muted">(<?= (int) $item['rating_count'] ?>)</span>
                    </span>
                    <?php if (!empty($item['is_featured'])): ?>
                        <span class="absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-forest-900/90 px-3 py-1.5 text-[0.62rem] font-extrabold uppercase tracking-[0.1em] text-mint-300 backdrop-blur"><?= icon('sparkles', 'h-3 w-3') ?> Pilihan Rentify</span>
                    <?php endif; ?>
                </div>
                <?php if (count($gallery) > 1): ?>
                    <div class="flex gap-2 overflow-x-auto p-3">
                        <?php foreach ($gallery as $index => $image): ?>
                            <button type="button" class="h-16 w-20 shrink-0 overflow-hidden rounded-xl border-2 <?= $index === 0 ? 'border-forest-900' : 'border-transparent opacity-70' ?> transition hover:opacity-100" data-gallery-thumb data-src="<?= e(upload_url($image)) ?>" aria-label="Foto <?= $index + 1 ?>">
                                <img src="<?= e(upload_url($image)) ?>" alt="" class="h-full w-full object-cover">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="rounded-[1.6rem] border border-line bg-white p-5 shadow-card sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[0.64rem] font-extrabold uppercase tracking-[0.15em] text-forest-500"><?= e($item['brand'] ?: $item['category_name']) ?></p>
                        <h1 class="mt-1 text-2xl font-extrabold leading-tight tracking-tight text-forest-900 sm:text-[1.7rem]"><?= e($item['name']) ?></h1>
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                            <span class="inline-flex items-center gap-1"><?= icon('map-pin', 'h-3.5 w-3.5') ?> Karawang</span>
                            <span class="text-line">•</span>
                            <span class="inline-flex items-center gap-1"><?= icon('package', 'h-3.5 w-3.5') ?> <?= (int) $item['stock_total'] ?> unit</span>
                            <span class="text-line">•</span>
                            <span class="inline-flex items-center gap-1"><?= icon('star-filled', 'h-3.5 w-3.5 text-star') ?> <?= (int) $item['rating_count'] ?> review</span>
                        </div>
                    </div>
                    <button type="button" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-line bg-white transition hover:bg-cream-50 <?= $isWished ? '!bg-white !text-danger' : 'text-forest-700' ?>" data-wishlist-button data-item-id="<?= (int) $item['id'] ?>" aria-label="<?= $isWished ? 'Hapus dari wishlist' : 'Simpan ke wishlist' ?>" aria-pressed="<?= $isWished ? 'true' : 'false' ?>">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="<?= $isWished ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 8.5c0 5.5-8.5 11-8.5 11s-8.5-5.5-8.5-11a5 5 0 0 1 8.5-3.5 5 5 0 0 1 8.5 3.5Z"/></svg>
                    </button>
                </div>

                <p class="mt-3 text-sm leading-relaxed text-muted"><?= e($item['short_description'] ?: truncate_text($item['description'], 180)) ?></p>

                <?php if ($item['features']): ?>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <?php foreach (array_slice($item['features'], 0, 4) as $feature): ?>
                            <span class="feature-tag"><?= e($feature) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4 flex items-end justify-between rounded-2xl bg-cream-100 px-4 py-3">
                    <div>
                        <p class="text-[0.6rem] font-bold uppercase tracking-[0.12em] text-muted">Mulai dari</p>
                        <p class="mt-0.5 text-2xl font-extrabold tracking-tight text-forest-900" data-detail-price><?= e(format_rupiah($unitPrice)) ?><span class="text-xs font-semibold text-muted">/hari</span></p>
                    </div>
                    <div class="text-right text-[0.68rem] text-muted">
                        <p>Deposit <span class="font-bold text-forest-800" data-detail-deposit><?= e(format_rupiah($item['deposit'])) ?></span></p>
                        <p class="mt-0.5">Min. <?= $minDays ?> hari</p>
                    </div>
                </div>

                <?php if ($variants): ?>
                    <div class="mt-4">
                        <p class="field-label">Pilih ukuran / varian</p>
                        <div class="flex flex-wrap gap-2" data-variant-picker>
                            <?php foreach ($variants as $index => $variant): ?>
                                <button type="button" class="chip <?= $index === 0 ? 'chip-active' : '' ?>" data-variant-id="<?= (int) $variant['id'] ?>" data-price="<?= (float) ($variant['price_per_day'] ?? $item['price_per_day']) ?>" data-stock="<?= (int) $variant['stock_total'] ?>" data-label="<?= e($variant['label']) ?>">
                                    <?= e($variant['label']) ?>
                                    <span class="ml-1 text-[0.62rem] font-semibold opacity-70"><?= (int) $variant['stock_total'] ?> stok</span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-4 rounded-2xl border border-line p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-extrabold text-forest-900">Atur jadwal sewa</p>
                        <span class="inline-flex items-center gap-1 rounded-full bg-mint-100 px-2.5 py-1 text-[0.62rem] font-extrabold text-forest-700" data-availability-badge>
                            <?= icon('check-circle', 'h-3 w-3') ?> <?= e(item_availability_text($initialAvailable)) ?>
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div>
                            <label class="field-label !mb-1.5 text-[0.68rem]" for="detail-start">Tanggal mulai</label>
                            <input id="detail-start" type="date" class="input !py-2.5 text-xs" value="<?= e($defaultStart) ?>" min="<?= e(today()) ?>" data-detail-start>
                        </div>
                        <div>
                            <label class="field-label !mb-1.5 text-[0.68rem]" for="detail-end">Tanggal kembali</label>
                            <input id="detail-end" type="date" class="input !py-2.5 text-xs" value="<?= e($defaultEnd) ?>" min="<?= e($defaultStart) ?>" data-detail-end>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-muted">Jumlah</span>
                            <div class="stepper" data-detail-stepper>
                                <button type="button" data-detail-dec aria-label="Kurangi"><?= icon('minus', 'h-3.5 w-3.5') ?></button>
                                <span data-detail-qty>1</span>
                                <button type="button" data-detail-inc aria-label="Tambah"><?= icon('plus', 'h-3.5 w-3.5') ?></button>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[0.6rem] font-semibold text-muted"><span data-detail-days><?= $duration ?></span> hari · estimasi sewa</p>
                            <p class="text-base font-extrabold text-forest-900" data-detail-subtotal><?= e(format_rupiah($initialSubtotal)) ?></p>
                        </div>
                    </div>
                    <p class="mt-2 hidden text-[0.68rem] font-bold text-danger" data-detail-error></p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-[1fr_auto]">
                        <button type="button" class="btn btn-primary w-full" data-detail-add="cart"><?= icon('cart', 'h-4 w-4') ?> Masukkan keranjang</button>
                        <button type="button" class="btn btn-outline w-full sm:w-auto" data-detail-add="checkout">Sewa langsung</button>
                    </div>
                    <p class="helper mt-2 text-center">Dihitung inklusif: <?= e(format_date_id($defaultStart)) ?> sampai <?= e(format_date_id($defaultEnd)) ?> = <?= $duration ?> hari.</p>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <?php foreach ([['shield', 'Gear dicek'], ['droplet', 'Dicuci bersih'], ['truck', 'Antar lokal']] as $trust): ?>
                        <div class="rounded-xl bg-cream-50 px-2 py-2.5">
                            <span class="mx-auto flex h-7 w-7 items-center justify-center rounded-full bg-white text-forest-700 shadow-sm"><?= icon($trust[0], 'h-3.5 w-3.5') ?></span>
                            <p class="mt-1.5 text-[0.6rem] font-bold text-forest-800"><?= e($trust[1]) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="mt-8 grid gap-6 lg:grid-cols-[1.15fr_1fr] lg:items-start">
            <div class="space-y-6">
                <div class="card p-5 sm:p-6">
                    <h2 class="text-lg font-extrabold text-forest-900">Tentang gear ini</h2>
                    <div class="prose-app mt-3 text-sm leading-relaxed text-forest-700">
                        <?= nl2br(e($item['description'] ?: $item['short_description'] ?: 'Belum ada deskripsi lengkap.')) ?>
                    </div>
                    <?php if ($item['features']): ?>
                        <h3 class="mt-5 text-sm font-extrabold text-forest-900">Yang kamu dapat</h3>
                        <ul class="mt-2 grid gap-2 sm:grid-cols-2">
                            <?php foreach ($item['features'] as $feature): ?>
                                <li class="flex items-start gap-2 text-sm text-forest-700"><span class="mt-0.5 text-forest-500"><?= icon('check-circle', 'h-4 w-4') ?></span><?= e($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <?php if ($item['specs']): ?>
                    <div class="card p-5 sm:p-6">
                        <h2 class="text-lg font-extrabold text-forest-900">Spesifikasi</h2>
                        <dl class="mt-3 divide-y divide-line">
                            <?php foreach ($item['specs'] as $key => $value): ?>
                                <div class="flex items-start justify-between gap-4 py-2.5 text-sm">
                                    <dt class="text-muted"><?= e($key) ?></dt>
                                    <dd class="text-right font-bold text-forest-900"><?= e($value) ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                <?php endif; ?>

                <div class="card p-5 sm:p-6" id="ulasan">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-forest-900">Ulasan petualang</h2>
                            <p class="mt-1 text-xs text-muted">Pengalaman nyata setelah menyewa gear ini.</p>
                        </div>
                        <div class="flex items-center gap-2 rounded-full bg-cream-100 px-3 py-1.5">
                            <span class="text-xl font-extrabold text-forest-900"><?= number_format((float) $item['rating_avg'], 1, ',', '.') ?></span>
                            <div>
                                <div class="text-star"><?= rating_stars((float) $item['rating_avg']) ?></div>
                                <p class="text-[0.6rem] text-muted"><?= (int) $item['rating_count'] ?> ulasan</p>
                            </div>
                        </div>
                    </div>

                    <?php $totalReviews = array_sum($breakdown); ?>
                    <?php if ($totalReviews > 0): ?>
                        <div class="mt-4 space-y-1.5">
                            <?php for ($star = 5; $star >= 1; $star--): ?>
                                <div class="flex items-center gap-2 text-[0.68rem] text-muted">
                                    <span class="w-3 text-right font-bold text-forest-800"><?= $star ?></span>
                                    <?= icon('star-filled', 'h-3 w-3 text-star') ?>
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-cream-200"><div class="h-full rounded-full bg-star" style="width:<?= $totalReviews ? round(($breakdown[$star] / $totalReviews) * 100) : 0 ?>%"></div></div>
                                    <span class="w-5 text-right"><?= $breakdown[$star] ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-5 space-y-4">
                        <?php if (!$reviews): ?>
                            <div class="rounded-2xl border border-dashed border-line px-5 py-8 text-center">
                                <p class="text-sm font-bold text-forest-900">Belum ada ulasan</p>
                                <p class="mt-1 text-xs text-muted">Jadilah yang pertama setelah menyelesaikan penyewaan.</p>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($reviews as $review): ?>
                            <article class="flex gap-3 border-b border-line pb-4 last:border-0 last:pb-0">
                                <?php if (!empty($review['user_avatar'])): ?>
                                    <img src="<?= e(upload_url($review['user_avatar'])) ?>" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover">
                                <?php else: ?>
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-mint-300 text-xs font-extrabold text-forest-900"><?= e(initials($review['user_name'])) ?></span>
                                <?php endif; ?>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-1">
                                        <p class="text-sm font-extrabold text-forest-900"><?= e($review['user_name']) ?></p>
                                        <span class="text-[0.62rem] text-muted"><?= e(time_ago($review['created_at'])) ?></span>
                                    </div>
                                    <div class="text-xs text-star"><?= rating_stars((float) $review['rating']) ?></div>
                                    <p class="mt-1 text-sm leading-relaxed text-forest-700"><?= e($review['comment']) ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="space-y-6 lg:sticky lg:top-24">
                <div class="card overflow-hidden">
                    <div class="bg-forest-900 px-5 py-4 text-white">
                        <p class="text-[0.62rem] font-bold uppercase tracking-[0.16em] text-mint-300">Butuh rekomendasi?</p>
                        <h2 class="mt-1 text-lg font-extrabold">Cocok untuk trip seperti apa?</h2>
                    </div>
                    <div class="p-5">
                        <p class="text-sm leading-relaxed text-muted">Ceritakan aktivitas, destinasi, dan durasi perjalananmu. AI Planner akan menyusun daftar gear lengkap dengan prioritas wajib dan opsional.</p>
                        <a href="<?= e(url('rekomendasi.php')) ?>" class="btn btn-primary btn-sm mt-4 w-full"><?= icon('sparkles', 'h-4 w-4') ?> Coba AI Planner</a>
                    </div>
                </div>

                <?php if ($filters): ?>
                    <div class="card p-5">
                        <h2 class="text-sm font-extrabold text-forest-900">Ringkasan spesifikasi</h2>
                        <div class="mt-3 space-y-2.5">
                            <?php foreach ($filters as $key => $values): ?>
                                <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                    <span class="text-muted"><?= e(ucwords(str_replace('_', ' ', $key))) ?></span>
                                    <span class="font-bold text-forest-900"><?= e(implode(', ', $values)) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card p-5">
                    <h2 class="text-sm font-extrabold text-forest-900">Cara sewa</h2>
                    <ol class="mt-3 space-y-3">
                        <?php foreach (['Pilih tanggal & jumlah gear.', 'Bayar DP/full dan unggah bukti.', 'Ambil di basecamp atau minta antar.', 'Kembalikan tepat waktu, gear dicek bersama.'] as $index => $step): ?>
                            <li class="flex gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-mint-100 text-[0.62rem] font-extrabold text-forest-800"><?= $index + 1 ?></span>
                                <p class="text-xs leading-relaxed text-muted"><?= e($step) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
        </section>

        <?php if ($related): ?>
            <section class="mt-10">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight text-forest-900">Gear serupa</h2>
                        <p class="mt-1 text-xs text-muted">Lengkapi kebutuhan <?= e(strtolower($item['category_name'])) ?> kamu.</p>
                    </div>
                    <a href="<?= e(url('katalog.php?category=' . $category['slug'])) ?>" class="text-xs font-extrabold text-forest-700">Lihat semua <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3.5 sm:gap-5 lg:grid-cols-4">
                    <?php foreach ($related as $relatedItem): ?>
                        <?= item_card($relatedItem) ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div class="mobile-action-bar fixed inset-x-0 z-40 border-t border-line bg-white/95 px-4 py-3 backdrop-blur lg:hidden no-print">
        <div class="mx-auto flex max-w-lg items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-[0.6rem] font-semibold text-muted">Total sewa</p>
                <p class="truncate text-lg font-extrabold text-forest-900" data-detail-subtotal-mobile><?= e(format_rupiah($initialSubtotal)) ?></p>
            </div>
            <button type="button" class="btn btn-primary shrink-0" data-detail-add="cart"><?= icon('cart', 'h-4 w-4') ?> Keranjang</button>
        </div>
    </div>
</main>

<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
