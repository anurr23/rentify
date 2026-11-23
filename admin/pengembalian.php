<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_admin();
$storeScope = admin_store_id();
$adminTitle = 'Pengembalian';
$adminPage = 'pengembalian';
$pageStyles = ['vendor/datatables/dataTables.min.css', 'css/datatables.css'];
$pageVendorScripts = ['datatables/dataTables.core.min.js', 'datatables/dataTables.min.js'];
$pageScripts = ['admin-tables.js'];
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4" data-table-section>
    <div>
        <p class="text-xs font-semibold text-muted">Kontrol pengembalian gear</p>
        <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900"><span data-table-count="returns">?</span> pesanan perlu dipantau</h2>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <?php foreach ([['alert-triangle', 'Terlambat', 'overdue', 'text-danger'], ['clock', 'Jatuh tempo hari ini', 'today_due', 'text-star'], ['calendar', 'Akan kembali', 'upcoming', 'text-sky-700'], ['rotate', 'Total aktif', 'total', 'text-forest-700']] as $card): ?>
            <div class="admin-card p-4">
                <span class="stat-icon <?= $card[3] ?>"><?= icon($card[0], 'h-4 w-4') ?></span>
                <p class="mt-2 text-[0.6rem] font-bold uppercase tracking-wide text-muted"><?= e($card[1]) ?></p>
                <p class="mt-0.5 text-xl font-extrabold text-forest-900" data-stat="<?= e($card[2]) ?>">?</p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3.5">
            <div><h3 class="text-sm font-extrabold text-forest-900">Daftar pengembalian</h3><p class="mt-0.5 text-[0.62rem] text-muted">Urut dari tanggal kembali terdekat.</p></div>
            <a href="<?= e(url('admin/pesanan.php?status=ongoing')) ?>" class="text-xs font-extrabold text-forest-700">Lihat semua pesanan aktif</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-app min-w-[52rem]" data-datatable="returns" data-page-length="10" data-order='[[5,"asc"]]'>
                <thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Jadwal</th><th>Gear</th><th>Status</th><th>Keterlambatan</th><th class="text-right">Aksi</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
