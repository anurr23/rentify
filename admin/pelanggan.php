<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_admin();
$pageStyles = ['vendor/datatables/dataTables.min.css', 'css/datatables.css'];
$pageVendorScripts = ['datatables/dataTables.core.min.js', 'datatables/dataTables.min.js'];
$pageScripts = ['admin-tables.js'];
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
                <?php if (is_superadmin()): ?>
                    <button type="button" class="btn btn-outline btn-sm <?= (int) $customer['is_active'] ? '!text-danger' : '' ?>" data-user-toggle data-id="<?= (int) $customer['id'] ?>" data-active="<?= (int) $customer['is_active'] ?>"><?= icon('shield', 'h-3.5 w-3.5') ?> <?= (int) $customer['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                <?php endif; ?>
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
                        <table class="table-app min-w-[32rem]" data-datatable="customer_bookings" data-user-id="<?= (int) $customer['id'] ?>" data-ordering="0" data-page-length="10">
                            <thead><tr><th>Kode</th><th>Jadwal</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody></tbody>
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

$adminTitle = 'Pelanggan';
$adminPage = 'pelanggan';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4" data-table-section>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Database pelanggan</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900"><span data-table-count="customers">?</span> pelanggan</h2>
        </div>
        <form class="flex gap-2" method="get" data-table-filters="customers">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted"><?= icon('search', 'h-4 w-4') ?></span>
                <input class="input input-icon !py-2.5 text-sm" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama, email, telepon...">
            </div>
            <button type="submit" class="btn btn-outline btn-sm">Cari</button>
        </form>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-app min-w-[48rem]" data-datatable="customers" data-page-length="15" data-order='[[0,"asc"]]'>
                <thead><tr><th>Pelanggan</th><th>Kontak</th><th>Pesanan</th><th>Total belanja</th><th>Terakhir pesan</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
