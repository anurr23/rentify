<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_superadmin();
$q = trim((string) ($_GET['q'] ?? ''));
$role = (string) ($_GET['role'] ?? '');
if (!in_array($role, ['', 'superadmin', 'admin', 'customer'], true)) {
    $role = '';
}
$superadminCount = (int) Database::conn()->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin' AND is_active = 1")->fetchColumn();
$adminCount = (int) Database::conn()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();
$currentUser = auth_user();

$adminTitle = 'Pengguna';
$adminPage = 'pengguna';
$pageStyles = ['vendor/datatables/dataTables.min.css', 'css/datatables.css'];
$pageVendorScripts = ['datatables/dataTables.core.min.js', 'datatables/dataTables.min.js'];
$pageScripts = ['admin-tables.js'];
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4" data-table-section>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Akses & akun</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900"><span data-table-count="users">?</span> pengguna</h2>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-admin-modal data-url="<?= e(url('api/admin.php?action=user_form&id=0')) ?>" data-admin-modal-title="Tambah pengguna"><?= icon('plus', 'h-3.5 w-3.5') ?> Tambah pengguna</button>
    </div>

    <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-[0.68rem] leading-relaxed text-amber-800">
        <?= icon('shield', 'h-3.5 w-3.5') ?> Ada <strong><?= $superadminCount ?> superadmin</strong> dan <strong><?= $adminCount ?> admin cabang</strong> aktif. Superadmin mengontrol semua cabang; admin cabang hanya melihat pesanan dan pelanggan cabangnya.
    </div>

    <form class="admin-card flex flex-wrap items-end gap-2 p-3.5" method="get" data-table-filters="users">
        <div class="relative min-w-[14rem] flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted"><?= icon('search', 'h-4 w-4') ?></span>
            <input class="input input-icon !py-2.5 text-sm" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama, email, telepon, member ID...">
        </div>
        <select class="select input !w-auto !py-2.5 text-sm" name="role">
            <option value="">Semua role</option>
            <option value="superadmin" <?= $role === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin Cabang</option>
            <option value="customer" <?= $role === 'customer' ? 'selected' : '' ?>>Customer</option>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
    </form>

    <div class="admin-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-app min-w-[56rem]" data-datatable="users" data-page-length="15" data-order='[[0,"asc"]]'>
                <thead><tr><th>Pengguna</th><th>Kontak</th><th>Role</th><th>Toko</th><th>Bergabung</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
