<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_admin();
$storeScope = admin_store_id();
$bookingId = (int) ($_GET['id'] ?? 0);
$booking = $bookingId ? Booking::find($bookingId) : null;

if ($bookingId && !$booking) {
    flash('error', 'Pesanan tidak ditemukan.');
    redirect('admin/pesanan.php');
}
if ($booking && $storeScope !== null && (int) ($booking['store_id'] ?? 0) !== $storeScope) {
    flash('error', 'Pesanan ini bukan milik cabang Anda.');
    redirect('admin/pesanan.php');
}

if ($booking) {
    $items = Booking::items($bookingId);
    $payments = Booking::payments($bookingId);
    $logs = Booking::logs($bookingId);
    $adminTitle = 'Pesanan ' . $booking['code'];
    $adminPage = 'pesanan';
    require ROOT_PATH . '/includes/admin_header.php';
    ?>
    <div class="space-y-4" data-admin-order data-booking-id="<?= (int) $booking['id'] ?>">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a href="<?= e(url('admin/pesanan.php')) ?>" class="icon-btn h-9 w-9"><?= icon('arrow-left', 'h-4 w-4') ?></a>
                <div>
                    <p class="text-xs font-semibold text-muted">Detail pesanan</p>
                    <h2 class="text-xl font-extrabold tracking-tight text-forest-900"><?= e($booking['code']) ?></h2>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="<?= e(url('admin/pesanan.php')) ?>" class="btn btn-outline btn-sm">Semua pesanan</a>
                <a href="<?= e(phone_wa_link($booking['user_phone'], 'Halo ' . $booking['user_name'] . ', kami dari Rentify terkait pesanan ' . $booking['code'] . '.')) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><?= icon('whatsapp', 'h-3.5 w-3.5') ?> Chat customer</a>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.45fr_.55fr] xl:items-start">
            <div class="space-y-4 min-w-0">
                <section class="admin-card p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Informasi pesanan</p>
                            <h3 class="mt-1 text-lg font-extrabold text-forest-900"><?= e(format_date_id($booking['start_date'], 'd F Y')) ?> — <?= e(format_date_id($booking['end_date'], 'd F Y')) ?></h3>
                            <p class="mt-1 text-xs text-muted"><?= (int) $booking['duration_days'] ?> hari · <?= $booking['pickup_method'] === 'delivery' ? 'Diantar' : 'Ambil di ' . e($booking['pickup_name'] ?: 'basecamp') ?></p>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="badge <?= e(status_class($booking['status'])) ?>"><?= e(status_label($booking['status'])) ?></span>
                            <span class="badge <?= e(payment_status_class($booking['payment_status'])) ?>"><?= e(payment_status_label($booking['payment_status'])) ?></span>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <?php foreach ([
                            ['user', 'Pelanggan', $booking['user_name']],
                            ['phone', 'WhatsApp', $booking['user_phone']],
                            ['mail', 'Email', $booking['user_email']],
                            ['calendar', 'Dibuat', format_datetime_id($booking['created_at'])],
                            ['map-pin', 'Toko cabang', ($booking['store_name'] ?? '-') . ' · ' . ($booking['store_city'] ?? '')],
                        ] as $info): ?>
                            <div class="rounded-xl bg-cream-50 p-3">
                                <p class="flex items-center gap-1 text-[0.58rem] font-bold uppercase tracking-wide text-muted"><?= icon($info[0], 'h-3 w-3') ?> <?= e($info[1]) ?></p>
                                <p class="mt-1 break-words text-xs font-extrabold text-forest-900"><?= e($info[2]) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($booking['pickup_method'] === 'delivery'): ?>
                        <div class="mt-3 rounded-xl border border-line p-3">
                            <p class="text-[0.6rem] font-bold uppercase tracking-wide text-muted">Alamat pengantaran</p>
                            <p class="mt-1 text-xs leading-relaxed text-forest-700"><?= nl2br(e($booking['delivery_address'])) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($booking['notes']): ?>
                        <div class="mt-3 rounded-xl bg-cream-50 p-3">
                            <p class="text-[0.6rem] font-bold uppercase tracking-wide text-muted">Catatan customer</p>
                            <p class="mt-1 text-xs leading-relaxed text-forest-700"><?= nl2br(e($booking['notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="admin-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-line px-4 py-3.5 sm:px-5">
                        <h3 class="text-sm font-extrabold text-forest-900">Gear dalam pesanan</h3>
                        <span class="badge badge-neutral"><?= count($items) ?> jenis</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-app min-w-[36rem]">
                            <thead><tr><th>Gear</th><th>Qty</th><th>Harga/hari</th><th>Durasi</th><th class="text-right">Subtotal</th></tr></thead>
                            <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2.5">
                                            <div class="h-9 w-9 overflow-hidden rounded-lg bg-cream-100"><img src="<?= e(upload_url($item['primary_image'])) ?>" alt="" class="h-full w-full object-cover"></div>
                                            <div><p class="font-bold text-forest-900"><?= e($item['item_name']) ?></p><p class="text-[0.62rem] text-muted"><?= e($item['category_name']) ?><?= $item['variant_label'] ? ' · ' . e($item['variant_label']) : '' ?></p></div>
                                        </div>
                                    </td>
                                    <td class="font-bold"><?= (int) $item['qty'] ?></td>
                                    <td><?= e(format_rupiah($item['price_per_day'])) ?></td>
                                    <td><?= (int) $item['duration_days'] ?> hari</td>
                                    <td class="text-right font-extrabold"><?= e(format_rupiah($item['subtotal'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-line px-4 py-3 sm:px-5">
                        <div class="ml-auto max-w-xs space-y-1.5 text-sm">
                            <div class="flex justify-between"><span class="text-muted">Subtotal</span><span class="font-bold"><?= e(format_rupiah($booking['subtotal'])) ?></span></div>
                            <div class="flex justify-between"><span class="text-muted">Deposit</span><span class="font-bold"><?= e(format_rupiah($booking['deposit_total'])) ?></span></div>
                            <div class="flex justify-between"><span class="text-muted">Biaya antar</span><span class="font-bold"><?= e(format_rupiah($booking['delivery_fee'])) ?></span></div>
                            <div class="flex justify-between border-t border-line pt-1.5"><span class="font-extrabold text-forest-900">Total</span><span class="font-extrabold text-forest-900"><?= e(format_rupiah($booking['total'])) ?></span></div>
                        </div>
                    </div>
                </section>

                <section class="admin-card p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-extrabold text-forest-900">Pembayaran</h3>
                        <a href="<?= e(url('pesanan_detail.php?id=' . $booking['id'] . '#pembayaran')) ?>" target="_blank" class="text-[0.68rem] font-bold text-forest-600">Lihat halaman customer</a>
                    </div>
                    <div class="mt-3 space-y-2">
                        <?php if (!$payments): ?>
                            <p class="rounded-xl bg-cream-50 px-3 py-5 text-center text-xs text-muted">Belum ada pembayaran diunggah.</p>
                        <?php endif; ?>
                        <?php foreach ($payments as $payment): ?>
                            <div class="rounded-xl border border-line p-3">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <p class="text-xs font-extrabold text-forest-900"><?= e(payment_method_label($payment['method'])) ?> · <?= e(format_rupiah($payment['amount'])) ?></p>
                                        <p class="mt-0.5 text-[0.62rem] text-muted"><?= e(format_datetime_id($payment['created_at'])) ?><?= $payment['verifier_name'] ? ' · diverifikasi ' . e($payment['verifier_name']) : '' ?></p>
                                        <?php if ($payment['note']): ?><p class="mt-1 text-[0.65rem] text-forest-700">Catatan: <?= e($payment['note']) ?></p><?php endif; ?>
                                    </div>
                                    <span class="badge <?= $payment['status'] === 'verified' ? 'badge-success' : ($payment['status'] === 'rejected' ? 'badge-danger' : 'badge-warning') ?>"><?= e(['verified' => 'Terverifikasi', 'rejected' => 'Ditolak', 'pending' => 'Menunggu'][$payment['status']] ?? $payment['status']) ?></span>
                                </div>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <?php if ($payment['proof_path']): ?>
                                        <a href="<?= e(upload_url($payment['proof_path'])) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><?= icon('image', 'h-3.5 w-3.5') ?> Bukti</a>
                                    <?php endif; ?>
                                    <?php if ($payment['status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-primary btn-sm" data-payment-verify data-payment-id="<?= (int) $payment['id'] ?>" data-approve="1"><?= icon('check', 'h-3.5 w-3.5') ?> Verifikasi</button>
                                        <button type="button" class="btn btn-outline btn-sm !text-danger" data-payment-verify data-payment-id="<?= (int) $payment['id'] ?>" data-approve="0">Tolak</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="admin-card p-4 sm:p-5">
                    <h3 class="text-sm font-extrabold text-forest-900">Riwayat status</h3>
                    <div class="timeline mt-3">
                        <?php foreach (array_reverse($logs) as $log): ?>
                            <div class="timeline-item">
                                <div class="flex flex-wrap items-center justify-between gap-1">
                                    <p class="text-xs font-extrabold text-forest-900"><?= e(status_label($log['status'])) ?></p>
                                    <span class="text-[0.6rem] text-muted"><?= e(format_datetime_id($log['created_at'])) ?></span>
                                </div>
                                <p class="mt-0.5 text-[0.68rem] text-muted"><?= e($log['note'] ?: '-') ?><?= $log['user_name'] ? ' · ' . e($log['user_name']) : '' ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <aside class="space-y-4 xl:sticky xl:top-24 min-w-0">
                <form class="admin-card p-4 sm:p-5" data-admin-form="booking-status">
                    <input type="hidden" name="action" value="booking_status">
                    <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                    <h3 class="text-sm font-extrabold text-forest-900">Ubah status</h3>
                    <p class="mt-1 text-[0.68rem] text-muted">Status saat ini: <?= e(status_label($booking['status'])) ?></p>
                    <div class="mt-3">
                        <label class="field-label" for="order-status">Status baru</label>
                        <select id="order-status" class="select input !py-2.5 text-sm" name="status">
                            <?php foreach (['pending', 'confirmed', 'preparing', 'ready', 'ongoing', 'returned', 'completed', 'cancelled'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= $booking['status'] === $status ? 'selected' : '' ?>><?= e(status_label($status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mt-3">
                        <label class="field-label" for="order-note">Catatan (opsional)</label>
                        <textarea id="order-note" class="input min-h-[4rem] text-xs" name="note" rows="2" placeholder="Alasan perubahan status..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-3 w-full">Simpan status</button>
                </form>

                <div class="admin-card p-4 sm:p-5">
                    <h3 class="text-sm font-extrabold text-forest-900">Data pengambilan</h3>
                    <div class="mt-3 space-y-2 text-xs">
                        <div class="flex justify-between gap-2"><span class="text-muted">Metode</span><span class="font-bold text-forest-900"><?= $booking['pickup_method'] === 'delivery' ? 'Delivery' : 'Pickup' ?></span></div>
                        <div class="flex justify-between gap-2"><span class="text-muted">Kode ambil</span><span class="font-bold tracking-wider text-forest-900"><?= e($booking['pickup_code'] ?: '—') ?></span></div>
                        <div class="flex justify-between gap-2"><span class="text-muted">Batas bayar</span><span class="font-bold text-forest-900"><?= e($booking['hold_expires_at'] ? format_datetime_id($booking['hold_expires_at']) : '—') ?></span></div>
                        <div class="flex justify-between gap-2"><span class="text-muted">Dikembalikan</span><span class="font-bold text-forest-900"><?= e($booking['returned_at'] ? format_datetime_id($booking['returned_at']) : '—') ?></span></div>
                    </div>
                </div>

                <button type="button" class="btn btn-outline w-full !text-danger" data-booking-cancel-admin data-booking-id="<?= (int) $booking['id'] ?>" data-code="<?= e($booking['code']) ?>"><?= icon('x-circle', 'h-4 w-4') ?> Batalkan pesanan</button>
            </aside>
        </div>
    </div>
    <?php require ROOT_PATH . '/includes/admin_footer.php';
    exit;
}

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'status' => (string) ($_GET['status'] ?? 'semua'),
    'payment_status' => (string) ($_GET['payment_status'] ?? 'semua'),
    'from' => (string) ($_GET['from'] ?? ''),
    'to' => (string) ($_GET['to'] ?? ''),
];
if ($storeScope !== null) {
    $filters['store_id'] = $storeScope;
} elseif (input_int('store_id') > 0) {
    $filters['store_id'] = input_int('store_id');
}
$page = page_number();
$result = Booking::search($filters, $page, 12);
$bookings = $result['items'];
$pagination = $result['pagination'];
if ($storeScope === null) {
    $pendingCount = (int) Database::conn()->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
} else {
    $stmt = Database::conn()->prepare("SELECT COUNT(*) FROM payments p JOIN bookings b ON b.id = p.booking_id WHERE p.status = 'pending' AND b.store_id = ?");
    $stmt->execute([$storeScope]);
    $pendingCount = (int) $stmt->fetchColumn();
}
$storeOptions = is_superadmin() ? Store::all() : [];

$adminTitle = 'Pesanan';
$adminPage = 'pesanan';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Semua transaksi sewa</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900"><?= format_number((int) $pagination['total']) ?> pesanan</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if ($pendingCount): ?><a href="<?= e(url('admin/pesanan.php?payment_status=pending')) ?>" class="btn btn-primary btn-sm"><?= icon('credit-card', 'h-3.5 w-3.5') ?> <?= $pendingCount ?> pembayaran pending</a><?php endif; ?>
        </div>
    </div>

    <form class="admin-card grid gap-2 p-3.5 sm:grid-cols-2 xl:grid-cols-[1.3fr_auto_auto_auto_auto_auto_auto]" method="get">
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted"><?= icon('search', 'h-4 w-4') ?></span>
            <input class="input input-icon !py-2.5 text-sm" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Cari kode, nama, email, atau telepon...">
        </div>
        <?php if ($storeOptions): ?>
            <select class="select input !py-2.5 text-sm" name="store_id">
                <option value="0">Semua cabang</option>
                <?php foreach ($storeOptions as $storeOption): ?>
                    <option value="<?= (int) $storeOption['id'] ?>" <?= (int) ($filters['store_id'] ?? 0) === (int) $storeOption['id'] ? 'selected' : '' ?>><?= e($storeOption['name']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <select class="select input !py-2.5 text-sm" name="status">
            <option value="semua">Semua status</option>
            <?php foreach (['pending', 'confirmed', 'preparing', 'ready', 'ongoing', 'returned', 'completed', 'cancelled'] as $status): ?>
                <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e(status_label($status)) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="select input !py-2.5 text-sm" name="payment_status">
            <option value="semua">Semua pembayaran</option>
            <?php foreach (['unpaid', 'waiting', 'paid', 'refunded'] as $status): ?>
                <option value="<?= e($status) ?>" <?= $filters['payment_status'] === $status ? 'selected' : '' ?>><?= e(payment_status_label($status)) ?></option>
            <?php endforeach; ?>
        </select>
        <input class="input !py-2.5 text-sm" type="date" name="from" value="<?= e($filters['from']) ?>" aria-label="Dari tanggal">
        <input class="input !py-2.5 text-sm" type="date" name="to" value="<?= e($filters['to']) ?>" aria-label="Sampai tanggal">
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
    </form>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-app min-w-[56rem]">
                <thead><tr><th>Kode</th><th>Pelanggan</th><th>Toko</th><th>Jadwal</th><th>Item</th><th>Total</th><th>Status</th><th>Pembayaran</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                <?php if (!$bookings): ?>
                    <tr><td colspan="9" class="py-12 text-center text-sm text-muted">Tidak ada pesanan pada filter ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($bookings as $row): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('admin/pesanan.php?id=' . $row['id'])) ?>" class="font-extrabold text-forest-900 hover:underline"><?= e($row['code']) ?></a>
                            <p class="text-[0.62rem] text-muted"><?= e(time_ago($row['created_at'])) ?></p>
                        </td>
                        <td><p class="font-bold"><?= e($row['user_name']) ?></p><p class="text-[0.62rem] text-muted"><?= e($row['user_phone']) ?></p></td>
                        <td><p class="text-xs font-semibold"><?= e($row['store_name'] ?: '-') ?></p><p class="text-[0.62rem] text-muted"><?= e($row['store_city'] ?: '') ?></p></td>
                        <td><p class="text-xs font-semibold"><?= e(format_date_id($row['start_date'])) ?></p><p class="text-[0.62rem] text-muted">s/d <?= e(format_date_id($row['end_date'])) ?> · <?= (int) $row['duration_days'] ?>h</p></td>
                        <td class="font-bold"><?= (int) $row['item_count'] ?></td>
                        <td class="font-extrabold"><?= e(format_rupiah($row['total'])) ?></td>
                        <td><span class="badge <?= e(status_class($row['status'])) ?>"><?= e(status_label($row['status'])) ?></span></td>
                        <td><span class="badge <?= e(payment_status_class($row['payment_status'])) ?>"><?= e(payment_status_label($row['payment_status'])) ?></span></td>
                        <td><div class="flex justify-end"><a href="<?= e(url('admin/pesanan.php?id=' . $row['id'])) ?>" class="btn btn-outline btn-sm">Detail</a></div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ((int) $pagination['pages'] > 1): ?>
            <div class="flex items-center justify-between border-t border-line px-4 py-3 text-xs text-muted">
                <span>Halaman <?= (int) $pagination['page'] ?> dari <?= (int) $pagination['pages'] ?></span>
                <div class="flex gap-1.5">
                    <?php for ($i = 1; $i <= (int) $pagination['pages']; $i++): ?>
                        <a href="<?= e(url('admin/pesanan.php?' . http_build_query(array_filter([...$filters, 'status' => $filters['status'] !== 'semua' ? $filters['status'] : null, 'payment_status' => $filters['payment_status'] !== 'semua' ? $filters['payment_status'] : null, 'q' => $filters['q'] ?: null, 'from' => $filters['from'] ?: null, 'to' => $filters['to'] ?: null, 'page' => $i])))) ?>" class="flex h-8 min-w-8 items-center justify-center rounded-full px-2 font-extrabold <?= $i === (int) $pagination['page'] ? 'bg-forest-900 text-white' : 'border border-line bg-white' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
