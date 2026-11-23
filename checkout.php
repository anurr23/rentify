<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/cart_render.php';

require_login();
$currentUser = auth_user();
$validation = cart_validate();
$cart = $validation['cart'];
if (!$validation['valid']) {
    flash('warning', implode(' ', $validation['errors']));
    redirect('keranjang.php');
}

$stores = Store::active();
$selectedStore = current_store($currentUser);
if (!$selectedStore || empty($selectedStore['is_active'])) {
    $selectedStore = $stores[0] ?? null;
}
$selectedStoreId = $selectedStore ? (int) $selectedStore['id'] : 0;
$nearestStore = null;
if ($currentUser && $currentUser['last_latitude'] !== null && $currentUser['last_longitude'] !== null) {
    $nearestList = Store::nearest((float) $currentUser['last_latitude'], (float) $currentUser['last_longitude'], 1);
    $nearestStore = $nearestList[0] ?? null;
}
$storePickupPoints = $selectedStore ? Store::pickupPoints($selectedStoreId) : [];
$primaryPoint = $storePickupPoints[0] ?? null;
$deliveryFee = $selectedStore ? (float) $selectedStore['delivery_fee'] : (float) setting('delivery_fee', '15000');
$holdHours = (int) setting('hold_hours', '24');
$subtotal = (float) $cart['subtotal'];
$deposit = (float) $cart['deposit'];
$total = $subtotal + $deposit;
$dates = $cart['dates'];

$pageTitle = 'Checkout — Rentify Outdoor Karawang';
$metaDescription = 'Lengkapi data pengambilan dan buat pesanan sewa gear outdoor.';
$activeNav = 'katalog';
$bodyClass = 'pb-16 lg:pb-0';
$pageScripts = ['location.js', 'booking.js'];
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';
?>
<main id="main" class="pb-10" data-checkout-root data-delivery-fee="<?= $deliveryFee ?>">
    <section class="app-hero !pb-14">
        <div class="absolute inset-0 topo-bg opacity-15"></div>
        <div class="container-app relative pt-3">
            <div class="flex items-center justify-between">
                <a href="<?= e(url('keranjang.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Kembali ke keranjang"><?= icon('arrow-left', 'h-[1.1rem] w-[1.1rem]') ?></a>
                <div class="text-center">
                    <p class="text-[0.55rem] font-extrabold uppercase tracking-[0.2em] text-mint-300/70">Langkah 2 dari 3</p>
                    <h1 class="text-base font-extrabold tracking-tight">Checkout</h1>
                </div>
                <a href="<?= e(url('pesanan.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Pesanan saya"><?= icon('clipboard', 'h-[1.1rem] w-[1.1rem]') ?></a>
            </div>
            <div class="mx-auto mt-6 max-w-xl">
                <div class="step-rail step-rail-dark">
                    <div class="step is-done"><span class="step-dot"><?= icon('check', 'h-3.5 w-3.5') ?></span><p>Keranjang</p></div>
                    <div class="step is-active"><span class="step-dot">2</span><p>Checkout</p></div>
                    <div class="step"><span class="step-dot">3</span><p>Bayar</p></div>
                </div>
            </div>
        </div>
        <div class="absolute inset-x-0 bottom-0 h-8 rounded-t-[1.7rem] bg-cream-50"></div>
    </section>

    <div class="container-app -mt-2">
        <form class="grid gap-6 lg:grid-cols-[1.3fr_.7fr] lg:items-start" data-checkout-form novalidate>
            <div class="space-y-5 min-w-0">
                <section class="card p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="section-kicker">Data penyewa</p>
                            <h2 class="mt-1 text-base font-extrabold text-forest-900">Pastikan kontak aktif</h2>
                        </div>
                        <a href="<?= e(url('profil.php')) ?>" class="text-[0.68rem] font-extrabold text-forest-700">Edit profil</a>
                    </div>
                    <div class="mt-4 flex items-center gap-3 rounded-2xl bg-cream-50 p-3">
                        <?php if (!empty($currentUser['avatar'])): ?>
                            <img src="<?= e(upload_url($currentUser['avatar'])) ?>" alt="" class="h-11 w-11 rounded-full object-cover">
                        <?php else: ?>
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-mint-300 text-sm font-extrabold text-forest-900"><?= e(initials($currentUser['name'])) ?></span>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <p class="text-sm font-extrabold text-forest-900"><?= e($currentUser['name']) ?></p>
                            <p class="text-[0.68rem] text-muted"><?= e($currentUser['email']) ?> · <?= e($currentUser['phone']) ?></p>
                        </div>
                        <span class="badge badge-mint ml-auto hidden sm:inline-flex"><?= e($currentUser['member_code'] ?? 'Member') ?></span>
                    </div>
                </section>

                <section class="card p-4 sm:p-5" data-checkout-store-card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="section-kicker">Toko cabang</p>
                            <h2 class="mt-1 text-base font-extrabold text-forest-900">Pilih cabang terdekat</h2>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" data-use-location data-location-target="#checkout-store"><?= icon('map-pin', 'h-3.5 w-3.5') ?> Gunakan lokasi saya</button>
                    </div>
                    <p class="mt-1 text-[0.68rem] leading-relaxed text-muted">Aktifkan lokasi agar Rentify merekomendasikan cabang terdekat. Gear disiapkan dari cabang yang kamu pilih.</p>
                    <div class="mt-3">
                        <label class="field-label" for="checkout-store">Cabang penyiapan gear</label>
                        <select id="checkout-store" class="select input" name="store_id" data-store-select>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?= (int) $store['id'] ?>"
                                    data-name="<?= e($store['name']) ?>"
                                    data-address="<?= e($store['address']) ?>"
                                    data-hours="<?= e($store['opening_hours']) ?>"
                                    data-city="<?= e($store['city']) ?>"
                                    data-fee="<?= (float) $store['delivery_fee'] ?>"
                                    data-pickup-id="<?= (int) (Store::pickupPoint((int) $store['id'])['id'] ?? 0) ?>"
                                    data-maps="<?= e(store_maps_link($store)) ?>"
                                    <?= (int) $store['id'] === $selectedStoreId ? 'selected' : '' ?>>
                                    <?= e($store['name']) ?> — <?= e($store['city']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="mt-2 flex items-center gap-1.5 text-[0.65rem] font-semibold text-forest-700" data-location-status <?= $nearestStore ? '' : 'data-location-auto' ?>>
                        <?= icon('map-pin', 'h-3.5 w-3.5') ?>
                        <?= $nearestStore ? 'Lokasi tersimpan · terdekat: ' . e($nearestStore['name']) . ' (' . e($nearestStore['distance_label']) . ')' : 'Aktifkan lokasi untuk rekomendasi cabang terdekat.' ?>
                    </p>
                    <div class="mt-3 rounded-2xl border border-line bg-cream-50 p-3.5">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-mint-100 text-forest-700"><?= icon('map-pin', 'h-4 w-4') ?></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-extrabold text-forest-900" data-store-name><?= e($selectedStore['name'] ?? 'Pilih cabang') ?></p>
                                <p class="mt-0.5 text-[0.68rem] leading-relaxed text-muted" data-store-address><?= e($selectedStore['address'] ?? '') ?></p>
                                <p class="mt-1 flex items-center gap-1 text-[0.62rem] text-muted"><?= icon('clock', 'h-3 w-3') ?><span data-store-hours><?= e($selectedStore['opening_hours'] ?? '') ?></span></p>
                            </div>
                            <a href="<?= e(store_maps_link($selectedStore)) ?>" target="_blank" rel="noopener" class="text-[0.65rem] font-extrabold text-forest-700" data-store-maps>Peta</a>
                        </div>
                    </div>
                    <input type="hidden" name="pickup_point_id" value="<?= (int) ($primaryPoint['id'] ?? 0) ?>" data-store-pickup>
                </section>

                <section class="card p-4 sm:p-5">
                    <p class="section-kicker">Metode pengambilan</p>
                    <h2 class="mt-1 text-base font-extrabold text-forest-900">Mau ambil sendiri atau diantar?</h2>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="pickup_method" value="pickup" class="peer sr-only" checked data-pickup-method>
                            <span class="flex h-full items-start gap-3 rounded-2xl border border-line bg-white p-3.5 transition peer-checked:border-forest-700 peer-checked:bg-mint-100/50 hover:border-sage-300">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cream-100 text-forest-700"><?= icon('map-pin', 'h-4 w-4') ?></span>
                                <span>
                                    <span class="block text-sm font-extrabold text-forest-900">Ambil di toko</span>
                                    <span class="mt-0.5 block text-[0.65rem] leading-relaxed text-muted">Gratis, cek gear bersama admin cabang.</span>
                                </span>
                            </span>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="pickup_method" value="delivery" class="peer sr-only" data-pickup-method>
                            <span class="flex h-full items-start gap-3 rounded-2xl border border-line bg-white p-3.5 transition peer-checked:border-forest-700 peer-checked:bg-mint-100/50 hover:border-sage-300">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cream-100 text-forest-700"><?= icon('truck', 'h-4 w-4') ?></span>
                                <span>
                                    <span class="block text-sm font-extrabold text-forest-900">Antar ke lokasi</span>
                                    <span class="mt-0.5 block text-[0.65rem] leading-relaxed text-muted">Area <span data-store-city-label><?= e($selectedStore['city'] ?? 'Karawang') ?></span>, +<span data-delivery-fee-label><?= e(format_rupiah($deliveryFee)) ?></span>.</span>
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="mt-4" data-pickup-fields>
                        <div class="rounded-2xl border border-line bg-cream-50 p-3.5">
                            <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.12em] text-muted">Titik pengambilan</p>
                            <p class="mt-1 text-sm font-extrabold text-forest-900" data-store-pickup-name><?= e($primaryPoint['name'] ?? $selectedStore['name'] ?? '') ?></p>
                            <p class="mt-0.5 text-[0.68rem] leading-relaxed text-muted" data-store-pickup-address><?= e($primaryPoint['address'] ?? $selectedStore['address'] ?? '') ?></p>
                            <p class="mt-1 flex items-center gap-1 text-[0.62rem] text-muted"><?= icon('clock', 'h-3 w-3') ?><span data-store-pickup-hours><?= e($primaryPoint['hours'] ?? $selectedStore['opening_hours'] ?? '') ?></span></p>
                        </div>
                    </div>

                    <div class="mt-4 hidden" data-delivery-fields>
                        <label class="field-label" for="delivery-address">Alamat pengantaran</label>
                        <textarea id="delivery-address" class="input min-h-[5rem]" name="delivery_address" rows="2" placeholder="Tulis alamat lengkap, patokan, dan nama penerima."><?= e($currentUser['address'] ?? '') ?></textarea>
                        <p class="helper mt-1.5">Pengantaran area <span data-store-city-label><?= e($selectedStore['city'] ?? 'Karawang') ?></span>. Admin cabang akan konfirmasi jadwal via WhatsApp.</p>
                    </div>
                </section>

                <section class="card p-4 sm:p-5">
                    <p class="section-kicker">Catatan pesanan</p>
                    <h2 class="mt-1 text-base font-extrabold text-forest-900">Ada permintaan khusus?</h2>
                    <textarea class="input mt-3 min-h-[5rem]" name="notes" rows="3" maxlength="500" placeholder="Contoh: tenda tolong dipastikan kering, tambah gas 2 buah."></textarea>
                </section>

                <section class="card p-4 sm:p-5">
                    <p class="section-kicker">Ringkasan gear</p>
                    <h2 class="mt-1 text-base font-extrabold text-forest-900"><?= (int) $cart['count'] ?> unit · <?= (int) $cart['duration'] ?> hari</h2>
                    <div class="mt-3 space-y-2">
                        <?php foreach ($cart['items'] as $line): ?>
                            <div class="flex items-center gap-3 rounded-xl bg-cream-50 p-2.5">
                                <div class="h-11 w-11 shrink-0 overflow-hidden rounded-lg bg-cream-100">
                                    <img src="<?= e(upload_url($line['image'])) ?>" alt="" class="h-full w-full object-cover">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-extrabold text-forest-900"><?= e($line['name']) ?><?= $line['variant_label'] ? ' · ' . e($line['variant_label']) : '' ?></p>
                                    <p class="text-[0.62rem] text-muted"><?= (int) $line['qty'] ?> unit × <?= (int) $cart['duration'] ?> hari</p>
                                </div>
                                <p class="text-xs font-extrabold text-forest-900"><?= e(format_rupiah($line['subtotal'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= e(url('keranjang.php')) ?>" class="mt-3 inline-flex items-center gap-1 text-[0.68rem] font-bold text-forest-600"><?= icon('edit', 'h-3.5 w-3.5') ?> Ubah keranjang</a>
                </section>

                <label class="flex items-start gap-2.5 rounded-2xl border border-line bg-white p-4 text-xs leading-relaxed text-muted">
                    <input type="checkbox" name="agree" value="1" class="mt-0.5 h-4 w-4 rounded border-line" required data-checkout-agree>
                    <span>Saya sudah membaca ketentuan sewa: gear dikembalikan tepat waktu dalam kondisi lengkap, keterlambatan dapat dikenakan biaya tambahan, dan deposit dikembalikan setelah pengecekan.</span>
                </label>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 min-w-0">
                <div class="card p-5">
                    <h2 class="text-base font-extrabold text-forest-900">Ringkasan pembayaran</h2>
                    <div class="mt-3 rounded-xl bg-cream-50 p-3">
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="font-bold text-forest-900"><?= e(format_date_id($dates['start'])) ?> — <?= e(format_date_id($dates['end'])) ?></span>
                            <span class="badge badge-mint"><?= (int) $cart['duration'] ?> hari</span>
                        </div>
                        <p class="mt-1 text-[0.62rem] text-muted">Ambil <?= e(format_date_id($dates['start'], 'd M')) ?>, kembali <?= e(format_date_id($dates['end'], 'd M')) ?>.</p>
                    </div>
                    <div class="mt-4 space-y-2.5 text-sm">
                        <div class="flex items-center justify-between"><span class="text-muted">Subtotal sewa</span><span class="font-bold text-forest-900"><?= e(format_rupiah($subtotal)) ?></span></div>
                        <div class="flex items-center justify-between"><span class="text-muted">Deposit (refund)</span><span class="font-bold text-forest-900"><?= e(format_rupiah($deposit)) ?></span></div>
                        <div class="flex items-center justify-between"><span class="text-muted">Biaya antar</span><span class="font-bold text-forest-900" data-checkout-delivery>Rp 0</span></div>
                        <div class="flex items-center justify-between border-t border-line pt-3"><span class="text-sm font-extrabold text-forest-900">Total</span><span class="text-xl font-extrabold text-forest-900" data-checkout-total><?= e(format_rupiah($total)) ?></span></div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-4 w-full" data-checkout-submit><?= icon('shield', 'h-4 w-4') ?> Buat pesanan</button>
                    <p class="mt-2 text-center text-[0.62rem] leading-relaxed text-muted">Stok ditahan <?= $holdHours ?> jam. Selesaikan pembayaran sebelum batas waktu agar pesanan tidak dibatalkan otomatis.</p>
                </div>
                <div class="rounded-[1.4rem] bg-forest-900 p-5 text-white">
                    <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.16em] text-mint-300">Pembayaran</p>
                    <p class="mt-1.5 text-sm font-extrabold">Transfer atau QRIS</p>
                    <p class="mt-1 text-xs leading-relaxed text-white/55">Setelah pesanan dibuat, unggah bukti pembayaran di halaman pesanan. Admin memverifikasi maksimal 1×24 jam.</p>
                    <div class="mt-3 flex flex-wrap gap-2 text-[0.62rem] font-semibold text-white/60">
                        <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-1"><?= e(setting('payment_bank', 'BCA')) ?> · <?= e(setting('payment_account', '1234567890')) ?></span>
                    </div>
                </div>
            </aside>
        </form>
    </div>
</main>

<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
