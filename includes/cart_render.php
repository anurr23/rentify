<?php
declare(strict_types=1);

require_once ROOT_PATH . '/includes/icons.php';

/**
 * Baris item keranjang. Dipakai halaman keranjang dan respons AJAX.
 */
function render_cart_lines(array $cart, bool $editable = true): string
{
    $items = $cart['items'] ?? [];
    ob_start();
    if (!$items):
        ?>
        <div class="rounded-[1.35rem] border border-dashed border-line bg-white px-6 py-14 text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-cream-100 text-muted"><?= icon('cart', 'h-7 w-7') ?></span>
            <h2 class="mt-4 text-base font-extrabold text-forest-900">Keranjang masih kosong</h2>
            <p class="mx-auto mt-1 max-w-sm text-sm text-muted">Yuk cari gear yang kamu butuhkan, atau minta rekomendasi dari AI Planner.</p>
            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <a href="<?= e(url('katalog.php')) ?>" class="btn btn-primary btn-sm">Jelajahi katalog <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
                <a href="<?= e(url('rekomendasi.php')) ?>" class="btn btn-outline btn-sm"><?= icon('sparkles', 'h-3.5 w-3.5') ?> AI Planner</a>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    endif;
    foreach ($items as $line):
        ?>
        <article class="flex gap-3 border-b border-line py-4 first:pt-0 last:border-0 last:pb-0" data-cart-line="<?= e($line['key']) ?>">
            <a href="<?= e(url('detail.php?slug=' . rawurlencode($line['slug']) . '&store=' . rawurlencode((string) ($line['store_slug'] ?? '')))) ?>" class="item-media h-[5.5rem] w-[5.5rem] shrink-0 overflow-hidden rounded-2xl">
                <img src="<?= e(upload_url($line['image'])) ?>" alt="<?= e($line['name']) ?>" loading="lazy" class="h-full w-full object-cover">
            </a>
            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.12em] text-forest-500"><?= e($line['category_name']) ?></p>
                        <a href="<?= e(url('detail.php?slug=' . rawurlencode($line['slug']) . '&store=' . rawurlencode((string) ($line['store_slug'] ?? '')))) ?>" class="mt-0.5 block truncate text-sm font-extrabold text-forest-900 hover:text-forest-600"><?= e($line['name']) ?></a>
                        <?php if (!empty($line['store_name'])): ?>
                            <p class="mt-0.5 flex items-center gap-1 truncate text-[0.62rem] font-bold text-forest-600"><?= icon('map-pin', 'h-3 w-3') ?> <?= e($line['store_name']) ?><?= !empty($line['store_city']) ? ' · ' . e($line['store_city']) : '' ?></p>
                        <?php endif; ?>
                        <p class="mt-0.5 text-[0.68rem] text-muted">
                            <?= $line['variant_label'] ? e($line['variant_label']) . ' · ' : '' ?>
                            <?= e(format_rupiah($line['price_per_day'])) ?>/hari
                        </p>
                    </div>
                    <?php if ($editable): ?>
                        <button type="button" class="icon-btn h-8 w-8 shrink-0 text-danger" data-cart-remove="<?= e($line['key']) ?>" aria-label="Hapus <?= e($line['name']) ?>">
                            <?= icon('trash', 'h-4 w-4') ?>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="mt-2.5 flex items-end justify-between gap-2">
                    <?php if ($editable): ?>
                        <div class="stepper" data-cart-stepper data-key="<?= e($line['key']) ?>" data-qty="<?= (int) $line['qty'] ?>" data-max="<?= (int) $line['available'] ?>">
                            <button type="button" data-cart-dec aria-label="Kurangi jumlah" <?= (int) $line['qty'] <= 1 ? 'disabled' : '' ?>><?= icon('minus', 'h-3.5 w-3.5') ?></button>
                            <span data-cart-qty><?= (int) $line['qty'] ?></span>
                            <button type="button" data-cart-inc aria-label="Tambah jumlah" <?= (int) $line['qty'] >= (int) $line['available'] ? 'disabled' : '' ?>><?= icon('plus', 'h-3.5 w-3.5') ?></button>
                        </div>
                    <?php else: ?>
                        <span class="badge badge-neutral"><?= (int) $line['qty'] ?> unit</span>
                    <?php endif; ?>
                    <div class="text-right">
                        <p class="text-sm font-extrabold text-forest-900" data-cart-line-total><?= e(format_rupiah($line['subtotal'])) ?></p>
                        <p class="text-[0.62rem] text-muted"><?= (int) $line['qty'] ?> × <?= (int) $cart['duration'] ?> hari</p>
                    </div>
                </div>
                <?php if (($line['available'] ?? 0) <= 3): ?>
                    <p class="mt-1.5 text-[0.62rem] font-bold text-star">Sisa <?= (int) $line['available'] ?> unit untuk tanggal ini.</p>
                <?php endif; ?>
            </div>
        </article>
        <?php
    endforeach;
    return (string) ob_get_clean();
}

/**
 * Ringkasan biaya keranjang.
 */
function render_cart_summary(array $cart, float $deliveryFee = 0, float $discount = 0): string
{
    $subtotal = (float) ($cart['subtotal'] ?? 0);
    $deposit = (float) ($cart['deposit'] ?? 0);
    $total = max(0, $subtotal + $deposit + $deliveryFee - $discount);
    ob_start();
    ?>
    <div class="space-y-2.5 text-sm" data-cart-summary-body>
        <?php if (!empty($cart['store']['name'])): ?>
            <div class="flex items-center justify-between gap-3">
                <span class="text-muted">Cabang</span>
                <span class="font-bold text-forest-900"><?= e($cart['store']['name']) ?></span>
            </div>
        <?php endif; ?>
        <div class="flex items-center justify-between gap-3">
            <span class="text-muted">Subtotal sewa</span>
            <span class="font-bold text-forest-900" data-summary-subtotal><?= e(format_rupiah($subtotal)) ?></span>
        </div>
        <div class="flex items-center justify-between gap-3">
            <span class="text-muted">Deposit (dikembalikan)</span>
            <span class="font-bold text-forest-900" data-summary-deposit><?= e(format_rupiah($deposit)) ?></span>
        </div>
        <?php if ($deliveryFee > 0): ?>
            <div class="flex items-center justify-between gap-3">
                <span class="text-muted">Biaya antar</span>
                <span class="font-bold text-forest-900"><?= e(format_rupiah($deliveryFee)) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($discount > 0): ?>
            <div class="flex items-center justify-between gap-3 text-success">
                <span>Diskon</span>
                <span class="font-bold">−<?= e(format_rupiah($discount)) ?></span>
            </div>
        <?php endif; ?>
        <div class="flex items-center justify-between gap-3 border-t border-line pt-3">
            <span class="text-sm font-extrabold text-forest-900">Total</span>
            <span class="text-lg font-extrabold text-forest-900" data-summary-total><?= e(format_rupiah($total)) ?></span>
        </div>
        <p class="text-[0.66rem] leading-relaxed text-muted">Deposit akan dikembalikan setelah gear dicek dalam kondisi lengkap.</p>
    </div>
    <?php
    return (string) ob_get_clean();
}
