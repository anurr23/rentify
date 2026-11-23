<?php
declare(strict_types=1);

require_once ROOT_PATH . '/includes/icons.php';

/**
 * Kartu item reusable untuk halaman dan respons AJAX.
 *
 * @param array $item data item (minimal id, name, slug, price_per_day, primary_image)
 * @param array{width?:string,variant?:string,show_stock?:bool,class?:string} $options
 */
function item_card(array $item, array $options = []): string
{
    static $wishlistIds = null;
    if ($wishlistIds === null) {
        $user = auth_user();
        $wishlistIds = $user ? Wishlist::ids((int) $user['id']) : [];
    }

    $variant = $options['variant'] ?? 'default';
    $width = $options['width'] ?? '';
    $showStock = $options['show_stock'] ?? true;
    $extraClass = $options['class'] ?? '';
    $itemId = (int) ($item['id'] ?? 0);
    $detailUrl = url('detail.php?slug=' . rawurlencode((string) ($item['slug'] ?? '')));
    $image = upload_url($item['primary_image'] ?? null);
    $rating = (float) ($item['rating_avg'] ?? 0);
    $ratingCount = (int) ($item['rating_count'] ?? 0);
    $features = $item['features'] ?? [];
    if (!is_array($features)) {
        $features = safe_json_decode($features, []);
    }
    $available = $item['available_now'] ?? ($item['stock_total'] ?? 0);
    $isWished = in_array($itemId, $wishlistIds, true);
    $stockTotal = (int) ($item['stock_total'] ?? 0);
    $cardWidth = $variant === 'compact' ? 'w-[16.5rem] sm:w-[18rem]' : 'w-full';
    if ($width !== '') {
        $cardWidth = $width;
    }
    $mediaClass = $variant === 'featured' ? 'aspect-[16/10]' : ($variant === 'compact' ? 'aspect-[4/3]' : 'aspect-[4/3]');

    ob_start();
    ?>
    <article class="card card-hover group relative flex flex-col overflow-hidden <?= e($cardWidth . ' ' . $extraClass) ?>" data-item-card="<?= $itemId ?>">
        <a href="<?= e($detailUrl) ?>" class="item-media block <?= e($mediaClass) ?>" aria-label="<?= e($item['name'] ?? 'Detail item') ?>">
            <img src="<?= e($image) ?>" alt="<?= e($item['name'] ?? '') ?>" loading="lazy" decoding="async" class="h-full w-full object-cover">
            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-full bg-white/90 px-2.5 py-1 text-[0.68rem] font-extrabold text-forest-900 backdrop-blur">
                <span class="text-star"><?= icon('star-filled', 'h-3.5 w-3.5') ?></span>
                <?= $rating > 0 ? number_format($rating, 1, ',', '.') : 'Baru' ?>
                <?php if ($ratingCount > 0): ?><span class="font-semibold text-muted">(<?= $ratingCount ?>)</span><?php endif; ?>
            </span>
            <?php if ($variant === 'featured' && !empty($item['is_featured'])): ?>
                <span class="absolute left-3 top-11 inline-flex items-center gap-1 rounded-full bg-forest-900/85 px-2.5 py-1 text-[0.62rem] font-extrabold tracking-wide text-mint-300 backdrop-blur">
                    <?= icon('sparkles', 'h-3 w-3') ?> PILIHAN RENTIFY
                </span>
            <?php endif; ?>
            <?php if ($stockTotal <= 0): ?>
                <span class="absolute inset-x-0 bottom-0 bg-forest-950/80 px-3 py-2 text-center text-xs font-bold text-white">Stok habis untuk saat ini</span>
            <?php endif; ?>
        </a>

        <button type="button"
                class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full border border-white/40 bg-forest-950/25 text-white backdrop-blur transition hover:bg-forest-950/45 <?= $isWished ? 'is-wished !bg-white !text-danger' : '' ?>"
                data-wishlist-button
                data-item-id="<?= $itemId ?>"
                aria-label="<?= $isWished ? 'Hapus dari wishlist' : 'Simpan ke wishlist' ?>"
                aria-pressed="<?= $isWished ? 'true' : 'false' ?>">
            <svg viewBox="0 0 24 24" class="h-[1.05rem] w-[1.05rem]" fill="<?= $isWished ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 8.5c0 5.5-8.5 11-8.5 11s-8.5-5.5-8.5-11a5 5 0 0 1 8.5-3.5 5 5 0 0 1 8.5 3.5Z"/></svg>
        </button>

        <div class="flex flex-1 flex-col p-3.5 sm:p-4">
            <div class="flex items-center justify-between gap-2">
                <p class="truncate text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-forest-500"><?= e($item['brand'] ?? ($item['category_name'] ?? 'Rentify Select')) ?></p>
                <?php if ($showStock): ?>
                    <span class="shrink-0 text-[0.62rem] font-bold <?= $available > 0 ? 'text-forest-500' : 'text-danger' ?>"><?= $available > 0 ? (int) $available . ' tersedia' : 'Habis' ?></span>
                <?php endif; ?>
            </div>
            <a href="<?= e($detailUrl) ?>" class="mt-1 block">
                <h3 class="line-clamp-2 text-[0.95rem] font-extrabold leading-snug text-forest-900 transition group-hover:text-forest-600"><?= e($item['name'] ?? '') ?></h3>
            </a>
            <div class="mt-1.5 flex items-center gap-1 text-[0.7rem] text-muted">
                <?= icon('map-pin', 'h-3.5 w-3.5') ?>
                <span>Karawang</span>
                <?php if (!empty($item['category_name'])): ?>
                    <span class="text-line">•</span>
                    <span class="truncate"><?= e($item['category_name']) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($features): ?>
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                    <?php foreach (array_slice($features, 0, 2) as $feature): ?>
                        <span class="feature-tag"><?= e($feature) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-3 flex items-end justify-between gap-2 border-t border-line pt-3">
                <div>
                    <p class="text-[0.58rem] font-bold uppercase tracking-[0.12em] text-muted">Mulai</p>
                    <p class="text-base font-extrabold tracking-tight text-forest-900"><?= e(format_rupiah_short($item['price_per_day'] ?? 0)) ?><span class="ml-0.5 text-[0.62rem] font-semibold text-muted">/hari</span></p>
                </div>
                <a href="<?= e($detailUrl) ?>" class="btn btn-primary btn-sm !px-3.5" aria-label="Sewa <?= e($item['name'] ?? '') ?>">
                    Sewa <?= icon('arrow-right', 'h-3.5 w-3.5') ?>
                </a>
            </div>
        </div>
    </article>
    <?php
    return (string) ob_get_clean();
}
