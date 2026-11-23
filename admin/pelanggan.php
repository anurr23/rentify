<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_admin();
$storeScope = admin_store_id();
$customerId = (int) ($_GET['id'] ?? 0);
$customer = $customerId ? User::find($customerId) : null;
if ($customerId && (!$customer || $customer['role'] !== 'customer')) {
    flash('error', 'Pelanggan tidak ditemukan.');
    redirect('admin/pelanggan.php');
}
if ($customer && $storeScope !== null) {
    $stmt = Database::conn()->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = ? AND store_id = ?');
    $stmt->execute([$customerId, $storeScope]);
    if ((int) $stmt->fetchColumn() === 0) {
        flash('error', 'Pelanggan ini belum pernah menyewa di cabang Anda.');
        redirect('admin/pelanggan.php');
    }
}

if ($customer) {
    $stats = User::stats($customerId, $storeScope);
    $bookings = Booking::forUser($customerId, 'semua', 1, 20, $storeScope)['items'];
    $adminTitle = 'Pelanggan — ' . $customer['name'];
    $adminPage = 'pelanggan';
    require ROOT_PATH . '/includes/admin_header.php';
    ?>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a href="<?= e(url('admin/pelanggan.php')) ?>" class="icon-btn h-9 w-9"><?= icon('arrow-left', 'h-4 w-4') ?></a>
                <div><p class="text-xs font-semibold text-muted">Profil pelanggan</p><h2 class="text-xl font-extrabold tracking-tight text-forest-900"><?= e($customer['name']) ?></h2></div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="<?= e(phone_wa_link($customer['phone'], 'Halo ' . $customer['name'] . ', kami dari Rentify.')) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><?= icon('whatsapp', 'h-3.5 w-3.5') ?> WhatsApp</a>
                <button type="button" class="btn btn-outline btn-sm <?= (int) $customer['is_active'] ? '!text-danger' : '' ?>" data-user-toggle data-id="<?= (int) $customer['id'] ?>" data-active="<?= (int) $customer['is_active'] ?>"><?= icon('shield', 'h-3.5 w-3.5') ?> <?= (int) $customer['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[.8fr_1.2fr] xl:items-start">
            <section class="admin-card p-5">
                <div class="flex items-center gap-3">
                    <?php if (!empty($customer['avatar'])): ?>
                        <img src="<?= e(upload_url($customer['avatar'])) ?>" alt="" class="h-16 w-16 rounded-full object-cover">
                    <?php else: ?>
                        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-mint-300 text-lg font-extrabold text-forest-900"><?= e(initials($customer['name'])) ?></span>
                    <?php endif; ?>
                    <div>
                        <h3 class="text-lg font-extrabold text-forest-900"><?= e($customer['name']) ?></h3>
                        <p class="text-xs text-muted"><?= e($customer['member_code'] ?: 'Member') ?></p>
                        <div class="mt-1 flex gap-1.5">
                            <span class="badge <?= (int) $customer['is_verified'] ? 'badge-success' : 'badge-warning' ?>"><?= (int) $customer['is_verified'] ? 'Terverifikasi' : 'Belum verifikasi' ?></span>
                            <span class="badge <?= (int) $customer['is_active'] ? 'badge-mint' : 'badge-danger' ?>"><?= (int) $customer['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
                        </div>
                    </div>
                </div>
                <dl class="mt-4 space-y-2 text-xs">
                    <div class="flex justify-between gap-2"><dt class="text-muted">Email</dt><dd class="font-bold text-forest-900"><?= e($customer['email']) ?></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-muted">WhatsApp</dt><dd class="font-bold text-forest-900"><?= e($customer['phone']) ?></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-muted">NIK</dt><dd class="font-bold text-forest-900"><?= e($customer['identity_number'] ?: '—') ?></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-muted">Bergabung</dt><dd class="font-bold text-forest-900"><?= e(format_date_id($customer['created_at'])) ?></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-muted">Login terakhir</dt><dd class="font-bold text-forest-900"><?= e($customer['last_login_at'] ? time_ago($customer['last_login_at']) : '—') ?></dd></div>
                </dl>
                <?php if ($customer['address']): ?>
                    <div class="mt-3 rounded-xl bg-cream-50 p-3"><p class="text-[0.6rem] font-bold uppercase tracking-wide text-muted">Alamat</p><p class="mt-1 text-xs leading-relaxed text-forest-700"><?= nl2br(e($customer['address'])) ?></p></div>
                <?php endif; ?>
                <?php if (!empty($customer['identity_photo'])): ?>
                    <a href="<?= e(upload_url($customer['identity_photo'])) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm mt-3 w-full"><?= icon('image', 'h-3.5 w-3.5') ?> Lihat foto identitas</a>
                <?php endif; ?>
            </section>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <?php foreach ([['clipboard', 'Pesanan', (string) $stats['bookings']], ['tent', 'Aktif', (string) $stats['active']], ['wallet', 'Belanja', format_rupiah_short($stats['spent'])], ['heart', 'Wishlist', (string) $stats['wishlist']]] as $metric): ?>
                        <div class="admin-card p-3.5">
                            <span class="stat-icon !h-8 !w-8"><?= icon($metric[0], 'h-3.5 w-3.5') ?></span>
                            <p class="mt-2 text-[0.58rem] font-bold uppercase tracking-wide text-muted"><?= e($metric[1]) ?></p>
                            <p class="mt-0.5 text-base font-extrabold text-forest-900"><?= e($metric[2]) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <section class="admin-card overflow-hidden">
                    <div class="border-b border-line px-4 py-3.5"><h3 class="text-sm font-extrabold text-forest-900">Riwayat pesanan</h3></div>
                    <div class="overflow-x-auto">
                        <table class="table-app min-w-[32rem]">
                            <thead><tr><th>Kode</th><th>Jadwal</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php if (!$bookings): ?><tr><td colspan="4" class="py-8 text-center text-sm text-muted">Belum ada pesanan.</td></tr><?php endif; ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><a href="<?= e(url('admin/pesanan.php?id=' . $booking['id'])) ?>" class="font-extrabold text-forest-900 hover:underline"><?= e($booking['code']) ?></a><p class="text-[0.62rem] text-muted"><?= e(time_ago($booking['created_at'])) ?></p></td>
                                    <td class="text-xs"><?= e(format_date_id($booking['start_date'])) ?> — <?= e(format_date_id($booking['end_date'])) ?></td>
                                    <td class="font-bold"><?= e(format_rupiah($booking['total'])) ?></td>
                                    <td><span class="badge <?= e(status_class($booking['status'])) ?>"><?= e(status_label($booking['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <?php require ROOT_PATH . '/includes/admin_footer.php';
    exit;
}

$q = trim((string) ($_GET['q'] ?? ''));
$page = page_number();
$result = User::customersWithStats($q, $page, 15, $storeScope);
$customers = $result['items'];
$pagination = $result['pagination'];

$adminTitle = 'Pelanggan';
$adminPage = 'pelanggan';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Database pelanggan</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900"><?= format_number((int) $pagination['total']) ?> pelanggan</h2>
        </div>
        <form class="flex gap-2" method="get">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted"><?= icon('search', 'h-4 w-4') ?></span>
                <input class="input input-icon !py-2.5 text-sm" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama, email, telepon...">
            </div>
            <button type="submit" class="btn btn-outline btn-sm">Cari</button>
        </form>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-app min-w-[48rem]">
                <thead><tr><th>Pelanggan</th><th>Kontak</th><th>Pesanan</th><th>Total belanja</th><th>Terakhir pesan</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                <?php if (!$customers): ?><tr><td colspan="7" class="py-12 text-center text-sm text-muted">Tidak ada pelanggan yang cocok.</td></tr><?php endif; ?>
                <?php foreach ($customers as $row): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-2.5">
                                <?php if (!empty($row['avatar'])): ?>
                                    <img src="<?= e(upload_url($row['avatar'])) ?>" alt="" class="h-9 w-9 rounded-full object-cover">
                                <?php else: ?>
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-mint-300 text-[0.62rem] font-extrabold text-forest-900"><?= e(initials($row['name'])) ?></span>
                                <?php endif; ?>
                                <div><p class="font-extrabold text-forest-900"><?= e($row['name']) ?></p><p class="text-[0.62rem] text-muted"><?= e($row['member_code'] ?: '—') ?> · <?= e(time_ago($row['created_at'])) ?></p></div>
                            </div>
                        </td>
                        <td><p class="text-xs"><?= e($row['email']) ?></p><p class="text-[0.62rem] text-muted"><?= e($row['phone']) ?></p></td>
                        <td class="font-bold"><?= (int) $row['total_bookings'] ?></td>
                        <td class="font-bold"><?= e(format_rupiah($row['total_spent'])) ?></td>
                        <td class="text-xs text-muted"><?= e($row['last_booking'] ? time_ago($row['last_booking']) : '—') ?></td>
                        <td><span class="badge <?= (int) $row['is_active'] ? 'badge-success' : 'badge-danger' ?>"><?= (int) $row['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td><div class="flex justify-end gap-1"><a href="<?= e(url('admin/pelanggan.php?id=' . $row['id'])) ?>" class="btn btn-outline btn-sm">Detail</a><button type="button" class="icon-btn h-8 w-8 <?= (int) $row['is_active'] ? 'text-danger' : '' ?>" data-user-toggle data-id="<?= (int) $row['id'] ?>" data-active="<?= (int) $row['is_active'] ?>" aria-label="Ubah status akun"><?= icon('shield', 'h-3.5 w-3.5') ?></button></div></td>
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
                        <a href="<?= e(url('admin/pelanggan.php?' . http_build_query(array_filter(['q' => $q ?: null, 'page' => $i])))) ?>" class="flex h-8 min-w-8 items-center justify-center rounded-full px-2 font-extrabold <?= $i === (int) $pagination['page'] ? 'bg-forest-900 text-white' : 'border border-line bg-white' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
