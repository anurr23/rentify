<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_admin();
$storeScope = admin_store_id();
$where = "b.status IN ('confirmed','preparing','ready','ongoing')";
$params = [];
if ($storeScope !== null) {
    $where .= ' AND b.store_id = ?';
    $params[] = $storeScope;
}
$stmt = Database::conn()->prepare(
    "SELECT b.*, u.name AS user_name, u.phone AS user_phone, s.name AS store_name,
        DATEDIFF(b.end_date, CURDATE()) AS days_left,
        (SELECT COALESCE(SUM(bi.qty), 0) FROM booking_items bi WHERE bi.booking_id = b.id) AS qty_total
     FROM bookings b JOIN users u ON u.id = b.user_id
     LEFT JOIN stores s ON s.id = b.store_id
     WHERE $where
     ORDER BY b.end_date ASC, b.id ASC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();
$overdue = array_values(array_filter($rows, static fn ($row) => (int) $row['days_left'] < 0));
$todayDue = array_values(array_filter($rows, static fn ($row) => (int) $row['days_left'] === 0));
$upcoming = array_values(array_filter($rows, static fn ($row) => (int) $row['days_left'] > 0));

$adminTitle = 'Pengembalian';
$adminPage = 'pengembalian';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4">
    <div>
        <p class="text-xs font-semibold text-muted">Kontrol pengembalian gear</p>
        <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900"><?= count($rows) ?> pesanan perlu dipantau</h2>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <?php foreach ([['alert-triangle', 'Terlambat', count($overdue), 'text-danger'], ['clock', 'Jatuh tempo hari ini', count($todayDue), 'text-star'], ['calendar', 'Akan kembali', count($upcoming), 'text-sky-700'], ['rotate', 'Total aktif', count($rows), 'text-forest-700']] as $card): ?>
            <div class="admin-card p-4">
                <span class="stat-icon <?= $card[3] ?>"><?= icon($card[0], 'h-4 w-4') ?></span>
                <p class="mt-2 text-[0.6rem] font-bold uppercase tracking-wide text-muted"><?= e($card[1]) ?></p>
                <p class="mt-0.5 text-xl font-extrabold text-forest-900"><?= $card[2] ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3.5">
            <div><h3 class="text-sm font-extrabold text-forest-900">Daftar pengembalian</h3><p class="mt-0.5 text-[0.62rem] text-muted">Urut dari tanggal kembali terdekat.</p></div>
            <a href="<?= e(url('admin/pesanan.php?status=ongoing')) ?>" class="text-xs font-extrabold text-forest-700">Lihat semua pesanan aktif</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-app min-w-[52rem]">
                <thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Jadwal</th><th>Gear</th><th>Status</th><th>Keterlambatan</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="7" class="py-12 text-center text-sm text-muted">Tidak ada gear yang sedang dipinjam.</td></tr><?php endif; ?>
                <?php foreach ($rows as $booking): ?>
                    <?php $daysLeft = (int) $booking['days_left']; ?>
                    <tr class="<?= $daysLeft < 0 ? 'bg-red-50/40' : '' ?>">
                        <td><a href="<?= e(url('admin/pesanan.php?id=' . $booking['id'])) ?>" class="font-extrabold text-forest-900 hover:underline"><?= e($booking['code']) ?></a><p class="text-[0.62rem] text-muted"><?= $booking['pickup_method'] === 'delivery' ? 'Delivery' : 'Pickup' ?></p></td>
                        <td><p class="font-bold"><?= e($booking['user_name']) ?></p><a href="<?= e(phone_wa_link($booking['user_phone'], 'Halo ' . $booking['user_name'] . ', pengingat pengembalian gear ' . $booking['code'] . '.')) ?>" target="_blank" rel="noopener" class="text-[0.62rem] font-bold text-forest-600 hover:underline"><?= e($booking['user_phone']) ?></a></td>
                        <td><p class="text-xs font-semibold"><?= e(format_date_id($booking['start_date'])) ?> — <?= e(format_date_id($booking['end_date'])) ?></p><p class="text-[0.62rem] text-muted"><?= (int) $booking['duration_days'] ?> hari</p></td>
                        <td class="font-bold"><?= (int) $booking['qty_total'] ?> unit</td>
                        <td><span class="badge <?= e(status_class($booking['status'])) ?>"><?= e(status_label($booking['status'])) ?></span></td>
                        <td>
                            <?php if ($daysLeft < 0): ?>
                                <span class="badge badge-danger">Telat <?= abs($daysLeft) ?> hari</span>
                            <?php elseif ($daysLeft === 0): ?>
                                <span class="badge badge-warning">Hari ini</span>
                            <?php else: ?>
                                <span class="text-xs font-bold text-forest-700"><?= $daysLeft ?> hari lagi</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                <?php if ($booking['status'] !== 'returned'): ?>
                                    <button type="button" class="btn btn-outline btn-sm" data-admin-status data-booking-id="<?= (int) $booking['id'] ?>" data-status="returned" data-confirm="Tandai gear sudah dikembalikan?">Dikembalikan</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-primary btn-sm" data-admin-status data-booking-id="<?= (int) $booking['id'] ?>" data-status="completed" data-confirm="Selesaikan pesanan ini? Pastikan gear sudah dicek lengkap.">Selesai</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
