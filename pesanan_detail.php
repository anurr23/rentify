<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

require_login();
$currentUser = auth_user();
$bookingId = (int) ($_GET['id'] ?? 0);
$booking = Booking::find($bookingId);
if (!$booking || ((int) $booking['user_id'] !== (int) $currentUser['id'] && $currentUser['role'] !== 'admin')) {
    http_response_code(404);
    $pageTitle = 'Pesanan tidak ditemukan — Rentify';
    require ROOT_PATH . '/includes/header.php';
    require ROOT_PATH . '/includes/customer_nav.php';
    ?>
    <main id="main" class="container-app py-20 text-center">
        <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-cream-100 text-muted"><?= icon('clipboard', 'h-8 w-8') ?></span>
        <h1 class="mt-5 text-2xl font-extrabold text-forest-900">Pesanan tidak ditemukan</h1>
        <p class="mx-auto mt-2 max-w-md text-sm text-muted">Pesanan ini tidak ada atau bukan milik akun Anda.</p>
        <a href="<?= e(url('pesanan.php')) ?>" class="btn btn-primary mt-6">Kembali ke pesanan</a>
    </main>
    <?php require ROOT_PATH . '/includes/customer_footer.php';
    exit;
}

$items = Booking::items($bookingId);
$payments = Booking::payments($bookingId);
$logs = Booking::logs($bookingId);
$reviewedItemIds = [];
$stmt = Database::conn()->prepare('SELECT item_id FROM reviews WHERE booking_id = ?');
$stmt->execute([$bookingId]);
foreach ($stmt->fetchAll() as $row) {
    $reviewedItemIds[] = (int) $row['item_id'];
}
$canReview = in_array($booking['status'], ['returned', 'completed'], true);
$canPay = in_array($booking['payment_status'], ['unpaid'], true) && !in_array($booking['status'], ['cancelled', 'completed'], true);
$canCancel = in_array($booking['status'], ['pending', 'confirmed'], true) && $booking['payment_status'] !== 'paid';
$latestPayment = $payments[0] ?? null;
$bank = setting('payment_bank', 'BCA');
$account = setting('payment_account', '1234567890');
$accountName = setting('payment_account_name', 'Rentify Outdoor Karawang');
$qrisImage = setting('qris_image', '');
$waLink = phone_wa_link(setting('whatsapp', '6281234567890'), 'Halo Rentify, saya ingin menanyakan pesanan ' . $booking['code'] . '.');

$steps = [
    'pending' => ['label' => 'Pesanan dibuat', 'icon' => 'receipt'],
    'confirmed' => ['label' => 'Pembayaran lunas', 'icon' => 'check-circle'],
    'preparing' => ['label' => 'Gear disiapkan', 'icon' => 'package'],
    'ready' => ['label' => 'Siap diambil', 'icon' => 'map-pin'],
    'ongoing' => ['label' => 'Sedang berjalan', 'icon' => 'tent'],
    'returned' => ['label' => 'Dikembalikan', 'icon' => 'rotate'],
    'completed' => ['label' => 'Selesai', 'icon' => 'star-filled'],
];
$flowKeys = array_keys($steps);
$currentIndex = array_search($booking['status'], $flowKeys, true);
$currentIndex = $currentIndex === false ? -1 : (int) $currentIndex;

$pageTitle = 'Pesanan ' . $booking['code'] . ' — Rentify Outdoor Karawang';
$metaDescription = 'Detail pesanan, pembayaran, dan status pengembalian gear.';
$activeNav = 'pesanan';
$bodyClass = 'pb-16 lg:pb-0';
$pageScripts = ['booking.js'];
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';
?>
<main id="main" class="pb-16 lg:pb-24" data-order-root data-booking-id="<?= (int) $booking['id'] ?>">
    <section class="app-hero !pb-16">
        <div class="absolute inset-0 topo-bg opacity-15"></div>
        <div class="container-app relative pt-3">
            <div class="flex items-center justify-between">
                <a href="<?= e(url('pesanan.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10 lg:hidden" aria-label="Kembali"><?= icon('arrow-left', 'h-[1.1rem] w-[1.1rem]') ?></a>
                <div class="text-center lg:mx-auto">
                    <p class="text-[0.55rem] font-extrabold uppercase tracking-[0.2em] text-mint-300/70">Detail pesanan</p>
                    <h1 class="text-base font-extrabold tracking-tight"><?= e($booking['code']) ?></h1>
                </div>
                <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Chat admin"><?= icon('whatsapp', 'h-[1.1rem] w-[1.1rem]') ?></a>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge <?= e(status_class($booking['status'])) ?> !border-white/15"><?= e(status_label($booking['status'])) ?></span>
                    <span class="badge <?= e(payment_status_class($booking['payment_status'])) ?> !border-white/15"><?= e(payment_status_label($booking['payment_status'])) ?></span>
                    <span class="text-[0.62rem] text-white/50">Dibuat <?= e(format_datetime_id($booking['created_at'])) ?></span>
                </div>
                <?php if ($booking['status'] === 'pending' && !empty($booking['hold_expires_at']) && strtotime($booking['hold_expires_at']) > time()): ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-300/30 bg-amber-300/10 px-3 py-1.5 text-[0.62rem] font-bold text-amber-200"><?= icon('clock', 'h-3.5 w-3.5') ?> Bayar sebelum <?= e(format_datetime_id($booking['hold_expires_at'])) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($currentIndex >= 0): ?>
                <div class="mt-5 overflow-x-auto pb-1">
                    <div class="flex min-w-[36rem] items-center">
                        <?php foreach ($steps as $key => $step): ?>
                            <?php $stepIndex = array_search($key, $flowKeys, true); $done = $stepIndex <= $currentIndex; ?>
                            <div class="flex flex-1 items-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full border <?= $done ? 'border-mint-300 bg-mint-300 text-forest-900' : 'border-white/20 bg-white/5 text-white/40' ?>"><?= icon($step['icon'], 'h-3.5 w-3.5') ?></span>
                                    <span class="whitespace-nowrap text-[0.55rem] font-semibold <?= $done ? 'text-white/80' : 'text-white/35' ?>"><?= e($step['label']) ?></span>
                                </div>
                                <?php if ($key !== 'completed'): ?><span class="mx-1 mb-5 h-px flex-1 <?= $stepIndex < $currentIndex ? 'bg-mint-300/60' : 'bg-white/10' ?>"></span><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($booking['status'] === 'cancelled'): ?>
                <div class="mt-5 rounded-xl border border-red-300/20 bg-red-400/10 px-3 py-2 text-xs text-red-100">Pesanan dibatalkan. <?= e($booking['cancel_reason'] ?: '') ?></div>
            <?php endif; ?>
        </div>
        <div class="absolute inset-x-0 bottom-0 h-8 rounded-t-[1.7rem] bg-cream-50"></div>
    </section>

    <div class="container-app -mt-2">
        <div class="grid gap-6 lg:grid-cols-[1.35fr_.65fr] lg:items-start">
            <div class="space-y-5 min-w-0">
                <section class="card p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="section-kicker">Jadwal & pengambilan</p>
                            <h2 class="mt-1 text-base font-extrabold text-forest-900"><?= e(format_date_id($booking['start_date'], 'd F Y')) ?> — <?= e(format_date_id($booking['end_date'], 'd F Y')) ?></h2>
                            <p class="mt-1 text-xs text-muted"><?= (int) $booking['duration_days'] ?> hari sewa · inklusif</p>
                        </div>
                        <?php if (!empty($booking['pickup_code'])): ?>
                            <div class="rounded-xl bg-forest-900 px-4 py-2 text-center text-white">
                                <p class="text-[0.55rem] font-bold uppercase tracking-[0.14em] text-mint-300">Kode ambil</p>
                                <p class="text-lg font-extrabold tracking-[0.15em]"><?= e($booking['pickup_code']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($booking['store_name'])): ?>
                        <div class="mt-4 flex flex-wrap items-center gap-3 rounded-xl bg-forest-900 p-3 text-white">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-mint-300 text-forest-900"><?= icon('map-pin', 'h-4 w-4') ?></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[0.58rem] font-bold uppercase tracking-wide text-mint-300">Toko cabang</p>
                                <p class="mt-0.5 text-xs font-extrabold"><?= e($booking['store_name']) ?> · <?= e($booking['store_city']) ?></p>
                                <p class="mt-0.5 text-[0.62rem] text-white/55"><?= e($booking['store_address']) ?></p>
                            </div>
                            <a href="<?= e(store_wa_link(['whatsapp' => $booking['store_whatsapp'] ?? '', 'phone' => $booking['store_phone'] ?? ''])) ?>" target="_blank" rel="noopener" class="btn btn-accent btn-sm"><?= icon('whatsapp', 'h-3.5 w-3.5') ?> Chat</a>
                        </div>
                    <?php endif; ?>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <div class="rounded-xl bg-cream-50 p-3">
                            <p class="text-[0.6rem] font-bold uppercase tracking-wide text-muted">Metode</p>
                            <p class="mt-0.5 text-xs font-extrabold text-forest-900"><?= $booking['pickup_method'] === 'delivery' ? 'Diantar ke lokasi' : 'Ambil di toko' ?></p>
                        </div>
                        <div class="rounded-xl bg-cream-50 p-3">
                            <p class="text-[0.6rem] font-bold uppercase tracking-wide text-muted"><?= $booking['pickup_method'] === 'delivery' ? 'Alamat' : 'Titik ambil' ?></p>
                            <p class="mt-0.5 text-xs font-extrabold text-forest-900"><?= $booking['pickup_method'] === 'delivery' ? e($booking['delivery_address']) : e($booking['pickup_name'] ?: '-') ?></p>
                            <?php if ($booking['pickup_method'] !== 'delivery' && $booking['pickup_address']): ?><p class="mt-0.5 text-[0.62rem] text-muted"><?= e($booking['pickup_address']) ?></p><?php endif; ?>
                        </div>
                    </div>
                    <?php if ($booking['notes']): ?>
                        <div class="mt-3 rounded-xl border border-line p-3">
                            <p class="text-[0.6rem] font-bold uppercase tracking-wide text-muted">Catatan kamu</p>
                            <p class="mt-1 text-xs leading-relaxed text-forest-700"><?= nl2br(e($booking['notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($booking['admin_notes']): ?>
                        <div class="mt-3 rounded-xl border border-mint-200 bg-mint-100/50 p-3">
                            <p class="text-[0.6rem] font-bold uppercase tracking-wide text-forest-600">Catatan admin</p>
                            <p class="mt-1 text-xs leading-relaxed text-forest-700"><?= nl2br(e($booking['admin_notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="card p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="section-kicker">Gear yang disewa</p>
                            <h2 class="mt-1 text-base font-extrabold text-forest-900"><?= count($items) ?> jenis gear</h2>
                        </div>
                        <a href="<?= e(url('katalog.php')) ?>" class="text-[0.68rem] font-extrabold text-forest-700">Sewa lagi</a>
                    </div>
                    <div class="mt-3 space-y-3">
                        <?php foreach ($items as $item): ?>
                            <?php $reviewed = in_array((int) $item['item_id'], $reviewedItemIds, true); ?>
                            <div class="rounded-2xl border border-line p-3">
                                <div class="flex gap-3">
                                    <a href="<?= e(url('detail.php?slug=' . $item['slug'])) ?>" class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-cream-100">
                                        <img src="<?= e(upload_url($item['primary_image'])) ?>" alt="<?= e($item['item_name']) ?>" class="h-full w-full object-cover">
                                    </a>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <a href="<?= e(url('detail.php?slug=' . $item['slug'])) ?>" class="block truncate text-sm font-extrabold text-forest-900"><?= e($item['item_name']) ?></a>
                                                <p class="mt-0.5 text-[0.62rem] text-muted"><?= e($item['category_name']) ?><?= $item['variant_label'] ? ' · ' . e($item['variant_label']) : '' ?></p>
                                            </div>
                                            <p class="shrink-0 text-sm font-extrabold text-forest-900"><?= e(format_rupiah($item['subtotal'])) ?></p>
                                        </div>
                                        <p class="mt-1 text-[0.62rem] text-muted"><?= (int) $item['qty'] ?> × <?= e(format_rupiah($item['price_per_day'])) ?>/hari × <?= (int) $item['duration_days'] ?> hari</p>
                                        <?php if ($canReview): ?>
                                            <?php if ($reviewed): ?>
                                                <span class="mt-1.5 inline-flex items-center gap-1 text-[0.62rem] font-bold text-success"><?= icon('check-circle', 'h-3.5 w-3.5') ?> Sudah direview</span>
                                            <?php else: ?>
                                                <details class="mt-2">
                                                    <summary class="inline-flex cursor-pointer list-none items-center gap-1 rounded-full bg-mint-100 px-2.5 py-1 text-[0.62rem] font-extrabold text-forest-700"><?= icon('star', 'h-3.5 w-3.5') ?> Beri review</summary>
                                                    <form class="mt-2.5 rounded-xl bg-cream-50 p-3" data-review-form>
                                                        <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                                                        <input type="hidden" name="item_id" value="<?= (int) $item['item_id'] ?>">
                                                        <input type="hidden" name="rating" value="5">
                                                        <div class="flex items-center gap-1" data-review-stars>
                                                            <?php for ($star = 1; $star <= 5; $star++): ?>
                                                                <button type="button" class="text-star" data-review-star="<?= $star ?>" aria-label="<?= $star ?> bintang">
                                                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" stroke="currentColor" stroke-width="1.5"><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/></svg>
                                                                </button>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <textarea class="input mt-2 min-h-[4rem] text-xs" name="comment" rows="2" maxlength="1000" placeholder="Ceritakan pengalamanmu memakai gear ini..." required></textarea>
                                                        <button type="submit" class="btn btn-primary btn-sm mt-2" data-review-submit>Kirim review</button>
                                                    </form>
                                                </details>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="card p-4 sm:p-5">
                    <p class="section-kicker">Riwayat status</p>
                    <h2 class="mt-1 text-base font-extrabold text-forest-900">Perjalanan pesananmu</h2>
                    <div class="timeline mt-4">
                        <?php foreach (array_reverse($logs) as $log): ?>
                            <div class="timeline-item">
                                <span class="status-dot <?= e(status_class($log['status'])) ?>"></span>
                                <div class="flex flex-wrap items-center justify-between gap-1">
                                    <p class="text-xs font-extrabold text-forest-900"><?= e(status_label($log['status'])) ?></p>
                                    <span class="text-[0.6rem] text-muted"><?= e(time_ago($log['created_at'])) ?></span>
                                </div>
                                <p class="mt-0.5 text-[0.68rem] leading-relaxed text-muted"><?= e($log['note'] ?: status_label($log['status'])) ?><?= $log['user_name'] ? ' · ' . e($log['user_name']) : '' ?></p>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$logs): ?>
                            <p class="text-xs text-muted">Belum ada riwayat status.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 min-w-0">
                <div class="card p-5" id="pembayaran">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-extrabold text-forest-900">Pembayaran</h2>
                        <span class="badge <?= e(payment_status_class($booking['payment_status'])) ?>"><?= e(payment_status_label($booking['payment_status'])) ?></span>
                    </div>

                    <?php if ($booking['payment_status'] === 'paid'): ?>
                        <div class="mt-4 rounded-2xl bg-mint-100 p-4 text-center">
                            <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-success"><?= icon('check-circle', 'h-6 w-6') ?></span>
                            <p class="mt-2 text-sm font-extrabold text-forest-900">Pembayaran lunas</p>
                            <p class="mt-1 text-xs text-muted">Terima kasih. Pesananmu sedang diproses.</p>
                        </div>
                    <?php elseif ($booking['payment_status'] === 'waiting'): ?>
                        <div class="mt-4 rounded-2xl bg-amber-50 p-4 text-center">
                            <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-amber-600"><?= icon('clock', 'h-6 w-6') ?></span>
                            <p class="mt-2 text-sm font-extrabold text-forest-900">Menunggu verifikasi</p>
                            <p class="mt-1 text-xs text-muted">Bukti pembayaran sudah dikirim. Admin akan memverifikasi maksimal 1×24 jam.</p>
                        </div>
                    <?php elseif ($canPay): ?>
                        <?php if ($latestPayment && $latestPayment['status'] === 'rejected'): ?>
                            <div class="mt-4 rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-[0.68rem] leading-relaxed text-red-700">
                                Pembayaran sebelumnya ditolak. <?= e($latestPayment['note'] ?: 'Silakan unggah bukti yang valid.') ?>
                            </div>
                        <?php endif; ?>
                        <form class="mt-4 space-y-3" data-payment-form enctype="multipart/form-data">
                            <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                            <div>
                                <p class="field-label !mb-2">Metode pembayaran</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="method" value="transfer" class="peer sr-only" data-payment-method checked>
                                        <span class="flex items-center justify-center gap-1.5 rounded-xl border border-line bg-white px-3 py-2.5 text-xs font-bold text-forest-800 peer-checked:border-forest-700 peer-checked:bg-mint-100"><?= icon('wallet', 'h-4 w-4') ?> Transfer</span>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="method" value="qris" class="peer sr-only" data-payment-method>
                                        <span class="flex items-center justify-center gap-1.5 rounded-xl border border-line bg-white px-3 py-2.5 text-xs font-bold text-forest-800 peer-checked:border-forest-700 peer-checked:bg-mint-100"><?= icon('qr', 'h-4 w-4') ?> QRIS</span>
                                    </label>
                                </div>
                            </div>
                            <div data-payment-transfer>
                                <div class="rounded-xl bg-cream-50 p-3">
                                    <div class="flex items-center justify-between gap-2">
                                        <div>
                                            <p class="text-[0.6rem] font-bold uppercase tracking-wide text-muted"><?= e($bank) ?></p>
                                            <p class="mt-0.5 text-sm font-extrabold tracking-wide text-forest-900"><?= e($account) ?></p>
                                            <p class="text-[0.62rem] text-muted">a.n. <?= e($accountName) ?></p>
                                        </div>
                                        <button type="button" class="icon-btn h-8 w-8" data-copy="<?= e($account) ?>" aria-label="Salin nomor rekening"><?= icon('copy', 'h-3.5 w-3.5') ?></button>
                                    </div>
                                </div>
                            </div>
                            <div class="hidden" data-payment-qris>
                                <?php if ($qrisImage): ?>
                                    <div class="rounded-xl border border-line bg-white p-3 text-center">
                                        <img src="<?= e(upload_url($qrisImage)) ?>" alt="QRIS Rentify" class="mx-auto max-h-52 rounded-lg object-contain">
                                        <p class="mt-2 text-[0.62rem] text-muted">Scan QRIS, lalu unggah bukti pembayaran.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="rounded-xl bg-cream-50 p-3 text-center text-[0.68rem] text-muted">QRIS belum diatur admin. Silakan gunakan transfer bank.</div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="field-label" for="payment-proof">Bukti pembayaran <span class="text-danger">*</span></label>
                                <div class="upload-drop">
                                    <input id="payment-proof" type="file" name="proof" accept="image/jpeg,image/png,image/webp" class="text-xs" data-preview-input="#proof-preview">
                                    <div id="proof-preview" class="mt-2 hidden h-24 overflow-hidden rounded-xl border border-line"></div>
                                    <p class="helper mt-1.5">JPG/PNG/WebP maks 5 MB. Pastikan nominal dan tanggal terlihat.</p>
                                </div>
                            </div>
                            <div>
                                <label class="field-label" for="payment-note">Catatan <span class="font-semibold text-muted">(opsional)</span></label>
                                <input id="payment-note" class="input !py-2.5 text-xs" name="note" placeholder="mis. transfer dari BCA a.n. Galih">
                            </div>
                            <div class="rounded-xl bg-forest-900 p-3 text-white">
                                <p class="text-[0.6rem] font-semibold uppercase tracking-wide text-mint-300">Total yang harus dibayar</p>
                                <p class="mt-0.5 text-xl font-extrabold"><?= e(format_rupiah($booking['total'])) ?></p>
                                <p class="mt-0.5 text-[0.6rem] text-white/50">Termasuk deposit <?= e(format_rupiah($booking['deposit_total'])) ?> yang dikembalikan.</p>
                            </div>
                            <button type="submit" class="btn btn-primary w-full" data-payment-submit><?= icon('upload', 'h-4 w-4') ?> Kirim bukti pembayaran</button>
                        </form>
                    <?php else: ?>
                        <p class="mt-4 text-xs text-muted">Tidak ada pembayaran yang perlu dilakukan saat ini.</p>
                    <?php endif; ?>

                    <?php if ($payments): ?>
                        <div class="mt-4 border-t border-line pt-3">
                            <p class="text-[0.62rem] font-extrabold uppercase tracking-wide text-muted">Riwayat pembayaran</p>
                            <div class="mt-2 space-y-2">
                                <?php foreach ($payments as $payment): ?>
                                    <div class="flex items-center justify-between gap-2 text-[0.68rem]">
                                        <div>
                                            <p class="font-bold text-forest-900"><?= e(payment_method_label($payment['method'])) ?> · <?= e(format_rupiah($payment['amount'])) ?></p>
                                            <p class="text-muted"><?= e(format_datetime_id($payment['created_at'])) ?><?= $payment['note'] ? ' · ' . e($payment['note']) : '' ?></p>
                                        </div>
                                        <span class="badge <?= $payment['status'] === 'verified' ? 'badge-success' : ($payment['status'] === 'rejected' ? 'badge-danger' : 'badge-warning') ?>"><?= e(['verified' => 'Terverifikasi', 'rejected' => 'Ditolak', 'pending' => 'Menunggu'][$payment['status']] ?? $payment['status']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card p-5">
                    <h2 class="text-sm font-extrabold text-forest-900">Ringkasan biaya</h2>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between"><span class="text-muted">Subtotal sewa</span><span class="font-bold text-forest-900"><?= e(format_rupiah($booking['subtotal'])) ?></span></div>
                        <div class="flex items-center justify-between"><span class="text-muted">Deposit</span><span class="font-bold text-forest-900"><?= e(format_rupiah($booking['deposit_total'])) ?></span></div>
                        <?php if ((float) $booking['delivery_fee'] > 0): ?>
                            <div class="flex items-center justify-between"><span class="text-muted">Biaya antar</span><span class="font-bold text-forest-900"><?= e(format_rupiah($booking['delivery_fee'])) ?></span></div>
                        <?php endif; ?>
                        <?php if ((float) $booking['discount'] > 0): ?>
                            <div class="flex items-center justify-between text-success"><span>Diskon</span><span class="font-bold">−<?= e(format_rupiah($booking['discount'])) ?></span></div>
                        <?php endif; ?>
                        <div class="flex items-center justify-between border-t border-line pt-2"><span class="font-extrabold text-forest-900">Total</span><span class="text-lg font-extrabold text-forest-900"><?= e(format_rupiah($booking['total'])) ?></span></div>
                    </div>
                </div>

                <div class="space-y-2">
                    <a href="<?= e($waLink) ?>" target="_blank" rel="noopener" class="btn btn-outline w-full"><?= icon('whatsapp', 'h-4 w-4') ?> Chat admin</a>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="<?= e(url('admin/pesanan.php?id=' . $booking['id'])) ?>" class="btn btn-primary w-full"><?= icon('settings', 'h-4 w-4') ?> Kelola di admin</a>
                    <?php endif; ?>
                    <?php if ($canCancel): ?>
                        <button type="button" class="btn btn-ghost w-full !border-red-100 !bg-red-50 !text-danger" data-booking-cancel="<?= (int) $booking['id'] ?>">Batalkan pesanan</button>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
