<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/cart_render.php';

$cart = cart_enrich();
$dates = $cart['dates'];
$startDate = $dates['start'] ?: tomorrow();
$endDate = $dates['end'] ?: date('Y-m-d', strtotime($startDate . ' +1 day'));
$duration = duration_days($startDate, $endDate);
$hasItems = (bool) $cart['items'];
$readyToCheckout = $hasItems && $dates['start'] && $dates['end'] && !$cart['errors'];
$addons = Item::trending(3);
$currentUser = auth_user();

$pageTitle = 'Keranjang Sewa — Rentify Outdoor Karawang';
$metaDescription = 'Periksa gear pilihanmu, atur tanggal sewa, dan lanjutkan ke checkout.';
$activeNav = 'katalog';
$bodyClass = 'pb-16 lg:pb-0';
$pageScripts = ['cart.js'];
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';
?>
<main id="main" class="pb-10" data-cart-root
      data-has-items="<?= $hasItems ? '1' : '0' ?>"
      data-has-dates="<?= ($dates['start'] && $dates['end']) ? '1' : '0' ?>">
    <section class="app-hero !pb-14">
        <div class="absolute inset-0 topo-bg opacity-15"></div>
        <div class="container-app relative pt-3">
            <div class="flex items-center justify-between">
                <a href="<?= e(url('katalog.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Kembali"><?= icon('arrow-left', 'h-[1.1rem] w-[1.1rem]') ?></a>
                <div class="text-center">
                    <p class="text-[0.55rem] font-extrabold uppercase tracking-[0.2em] text-mint-300/70">Langkah 1 dari 3</p>
                    <h1 class="text-base font-extrabold tracking-tight">Keranjang sewa</h1>
                </div>
                <a href="<?= e(url('beranda.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Beranda"><?= icon('home', 'h-[1.1rem] w-[1.1rem]') ?></a>
            </div>

            <div class="mx-auto mt-6 max-w-xl">
                <div class="step-rail step-rail-dark">
                    <div class="step is-active"><span class="step-dot">1</span><p>Keranjang</p></div>
                    <div class="step"><span class="step-dot">2</span><p>Checkout</p></div>
                    <div class="step"><span class="step-dot">3</span><p>Bayar</p></div>
                </div>
            </div>
        </div>
        <div class="absolute inset-x-0 bottom-0 h-8 rounded-t-[1.7rem] bg-cream-50"></div>
    </section>

    <div class="container-app -mt-2">
        <div class="grid gap-6 lg:grid-cols-[1.35fr_.65fr] lg:items-start">
            <div class="space-y-5 min-w-0">
                <section class="card p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="section-kicker">Jadwal sewa</p>
                            <h2 class="mt-1 text-base font-extrabold text-forest-900">Kapan kamu mau memakai gear ini?</h2>
                            <p class="mt-1 text-xs text-muted">Durasi dihitung inklusif. 2D1N = 2 hari.</p>
                        </div>
                        <span class="badge badge-info" data-cart-duration><?= $dates['start'] && $dates['end'] ? $duration . ' hari' : 'Belum diatur' ?></span>
                    </div>
                    <form class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end" data-cart-dates-form>
                        <div>
                            <label class="field-label" for="cart-start">Tanggal mulai</label>
                            <input id="cart-start" type="date" class="input" name="start_date" value="<?= e($dates['start'] ?: $startDate) ?>" min="<?= e(today()) ?>" required>
                        </div>
                        <div>
                            <label class="field-label" for="cart-end">Tanggal kembali</label>
                            <input id="cart-end" type="date" class="input" name="end_date" value="<?= e($dates['end'] ?: $endDate) ?>" min="<?= e($dates['start'] ?: $startDate) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= icon('calendar', 'h-4 w-4') ?> Simpan</button>
                    </form>
                    <p class="helper mt-2">Deposit akan dikembalikan penuh setelah gear dicek lengkap saat pengembalian.</p>
                </section>

                <section class="card p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="section-kicker">Gear pilihanmu</p>
                            <h2 class="mt-1 text-base font-extrabold text-forest-900"><span data-cart-total-count><?= (int) $cart['count'] ?></span> unit di keranjang</h2>
                        </div>
                        <?php if ($hasItems): ?>
                            <button type="button" class="text-[0.68rem] font-bold text-danger hover:underline" data-cart-clear>Kosongkan</button>
                        <?php endif; ?>
                    </div>

                    <?php if ($cart['errors']): ?>
                        <div class="mt-3 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-[0.68rem] leading-relaxed text-amber-800">
                            <?= e(implode(' ', $cart['errors'])) ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-2" data-cart-lines>
                        <?= render_cart_lines($cart) ?>
                    </div>
                </section>

                <?php if ($addons): ?>
                    <section class="card p-4 sm:p-5">
                        <div class="flex items-end justify-between gap-2">
                            <div>
                                <p class="section-kicker">Sering disewa bersama</p>
                                <h2 class="mt-1 text-base font-extrabold text-forest-900">Lengkapi perlengkapanmu</h2>
                            </div>
                            <a href="<?= e(url('katalog.php')) ?>" class="text-[0.68rem] font-extrabold text-forest-700">Lihat semua</a>
                        </div>
                        <div class="mt-3 space-y-2">
                            <?php foreach ($addons as $addon): ?>
                                <div class="flex items-center gap-3 rounded-2xl border border-line p-2.5">
                                    <a href="<?= e(url('detail.php?slug=' . $addon['slug'])) ?>" class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-cream-100">
                                        <img src="<?= e(upload_url($addon['primary_image'])) ?>" alt="<?= e($addon['name']) ?>" class="h-full w-full object-cover" loading="lazy">
                                    </a>
                                    <div class="min-w-0 flex-1">
                                        <a href="<?= e(url('detail.php?slug=' . $addon['slug'])) ?>" class="block truncate text-xs font-extrabold text-forest-900"><?= e($addon['name']) ?></a>
                                        <p class="mt-0.5 text-[0.65rem] text-muted"><?= e(format_rupiah($addon['price_per_day'])) ?>/hari · <?= (int) $addon['available_now'] ?> tersedia</p>
                                    </div>
                                    <button type="button" class="icon-btn h-9 w-9 shrink-0" data-cart-add="<?= (int) $addon['id'] ?>" aria-label="Tambahkan <?= e($addon['name']) ?>"><?= icon('plus', 'h-4 w-4') ?></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 min-w-0">
                <div class="card p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-extrabold text-forest-900">Ringkasan biaya</h2>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-mint-100 text-forest-700"><?= icon('receipt', 'h-4 w-4') ?></span>
                    </div>
                    <div class="mt-4" data-cart-summary>
                        <?= render_cart_summary($cart, 0) ?>
                    </div>
                    <a href="<?= e(url($currentUser ? 'checkout.php' : 'login.php?redirect=' . rawurlencode('checkout.php'))) ?>"
                       class="btn btn-primary mt-4 w-full <?= $readyToCheckout ? '' : 'pointer-events-none opacity-50' ?>"
                       data-cart-checkout>
                        Lanjut ke checkout <?= icon('arrow-right', 'h-4 w-4') ?>
                    </a>
                    <?php if (!$dates['start'] || !$dates['end']): ?>
                        <p class="mt-2 text-center text-[0.62rem] font-bold text-star" data-cart-checkout-hint>Atur tanggal sewa terlebih dahulu.</p>
                    <?php endif; ?>
                    <div class="mt-3 flex items-center justify-center gap-1.5 text-[0.62rem] text-muted"><?= icon('lock', 'h-3.5 w-3.5') ?> Pembayaran aman & terverifikasi</div>
                </div>

                <div class="rounded-[1.4rem] bg-forest-900 p-5 text-white">
                    <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.16em] text-mint-300">Butuh bantuan?</p>
                    <p class="mt-1.5 text-sm font-extrabold">Tim Rentify siap membantu</p>
                    <p class="mt-1 text-xs leading-relaxed text-white/55">Tanya ketersediaan, ukuran, atau rekomendasi gear lewat WhatsApp.</p>
                    <a href="<?= e(phone_wa_link(setting('whatsapp', '6281234567890'), 'Halo Rentify, saya butuh bantuan dengan keranjang sewa saya.')) ?>" target="_blank" rel="noopener" class="btn btn-accent btn-sm mt-3 w-full"><?= icon('whatsapp', 'h-4 w-4') ?> Chat admin</a>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
