<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_admin();
$storeScope = admin_store_id();
$adminStore = admin_current_store();
$from = (string) ($_GET['from'] ?? date('Y-m-01'));
$to = (string) ($_GET['to'] ?? today());
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $from = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $to = today();
}
if ($to < $from) {
    [$from, $to] = [$to, $from];
}

if (($_GET['export'] ?? '') === 'csv') {
    $rows = Report::exportRows($from, $to, $storeScope);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rentify-laporan-' . $from . '-sampai-' . $to . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Kode', 'Dibuat', 'Toko', 'Pelanggan', 'Telepon', 'Mulai', 'Kembali', 'Durasi', 'Subtotal', 'Deposit', 'Antar', 'Diskon', 'Total', 'Status', 'Pembayaran'], ';', '"', '');
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['code'], $row['created_at'], $row['store_name'], $row['customer'], $row['phone'], $row['start_date'], $row['end_date'],
            $row['duration_days'], $row['subtotal'], $row['deposit_total'], $row['delivery_fee'], $row['discount'],
            $row['total'], status_label($row['status']), payment_status_label($row['payment_status']),
        ], ';', '"', '');
    }
    fclose($out);
    log_audit('report.export', 'booking', null, ['from' => $from, 'to' => $to, 'store_id' => $storeScope]);
    exit;
}

$summary = Report::summary($storeScope);
$storeWhere = $storeScope !== null ? ' AND b.store_id = ' . (int) $storeScope : '';
$stmt = Database::conn()->prepare(
    "SELECT COALESCE(SUM(p.amount), 0) AS revenue,
        (SELECT COUNT(*) FROM bookings b WHERE DATE(b.created_at) BETWEEN ? AND ?$storeWhere) AS bookings,
        (SELECT COUNT(DISTINCT b.user_id) FROM bookings b WHERE DATE(b.created_at) BETWEEN ? AND ?$storeWhere) AS customers
     FROM payments p JOIN bookings b ON b.id = p.booking_id
     WHERE p.status = 'verified' AND DATE(p.paid_at) BETWEEN ? AND ?$storeWhere"
);
$stmt->execute([$from, $to, $from, $to, $from, $to]);
$rangeStats = $stmt->fetch() ?: ['revenue' => 0, 'bookings' => 0, 'customers' => 0];
$rangeRevenue = (float) $rangeStats['revenue'];
$monthly = Report::monthlyRevenue(8, $storeScope);
$statusRows = Report::bookingsByStatus($storeScope);
$topItems = Report::topItems(7, $storeScope);
$topCategories = Report::topCategories($storeScope);
$statusColors = [
    'pending' => '#E5A440', 'confirmed' => '#53A8CF', 'preparing' => '#8B7CC8', 'ready' => '#B2D8C8',
    'ongoing' => '#53CF6C', 'returned' => '#8FA79A', 'completed' => '#1B3B2B', 'cancelled' => '#D9534F',
];

$adminTitle = 'Laporan';
$adminPage = 'laporan';
$pageStyles = ['vendor/datatables/dataTables.min.css', 'css/datatables.css'];
$pageVendorScripts = ['datatables/dataTables.core.min.js', 'datatables/dataTables.min.js'];
$pageScripts = ['admin-tables.js'];
$inlineScript = 'window.RENTIFY_CHARTS = ' . json_encode([
    [
        'id' => 'reportRevenueChart',
        'type' => 'bar',
        'data' => [
            'labels' => array_column($monthly, 'label'),
            'datasets' => [[
                'label' => 'Pendapatan',
                'data' => array_map(static fn ($row) => (float) $row['total'], $monthly),
                'backgroundColor' => '#CBE8CE',
                'borderColor' => '#1B3B2B',
                'borderWidth' => 1.5,
                'borderRadius' => 8,
            ]],
        ],
        'options' => [
            'responsive' => true, 'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'grid' => ['color' => '#eef1ec']], 'x' => ['grid' => ['display' => false]]],
        ],
    ],
    [
        'id' => 'reportStatusChart',
        'type' => 'doughnut',
        'data' => [
            'labels' => array_map(static fn ($row) => status_label($row['status']), $statusRows),
            'datasets' => [[
                'data' => array_map(static fn ($row) => (int) $row['total'], $statusRows),
                'backgroundColor' => array_map(static fn ($row) => $statusColors[$row['status']] ?? '#8FA79A', $statusRows),
                'borderWidth' => 0,
            ]],
        ],
        'options' => ['responsive' => true, 'maintainAspectRatio' => false, 'cutout' => '66%', 'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'font' => ['size' => 10]]]]],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4" data-table-section>
    <div class="flex flex-wrap items-end justify-between gap-3 no-print">
        <div>
            <p class="text-xs font-semibold text-muted">Analitik & ekspor</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900">Laporan bisnis</h2>
            <p class="mt-1 flex items-center gap-1.5 text-xs font-bold text-forest-600"><?= icon('map-pin', 'h-3.5 w-3.5') ?> <?= $adminStore ? e($adminStore['name']) : 'Semua cabang' ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline btn-sm" onclick="window.print()"><?= icon('printer', 'h-3.5 w-3.5') ?> Cetak</button>
            <a href="<?= e(url('admin/laporan.php?' . http_build_query(['from' => $from, 'to' => $to, 'export' => 'csv']))) ?>" class="btn btn-primary btn-sm"><?= icon('download', 'h-3.5 w-3.5') ?> Export CSV</a>
        </div>
    </div>

    <form class="admin-card flex flex-wrap items-end gap-2 p-3.5 no-print" method="get">
        <div><label class="field-label !mb-1.5 text-[0.68rem]" for="report-from">Dari</label><input id="report-from" class="input !py-2.5 text-sm" type="date" name="from" value="<?= e($from) ?>"></div>
        <div><label class="field-label !mb-1.5 text-[0.68rem]" for="report-to">Sampai</label><input id="report-to" class="input !py-2.5 text-sm" type="date" name="to" value="<?= e($to) ?>"></div>
        <button type="submit" class="btn btn-outline btn-sm">Terapkan</button>
        <div class="ml-auto flex flex-wrap gap-1.5 text-[0.62rem] text-muted">
            <a class="chip !py-1.5" href="<?= e(url('admin/laporan.php?' . http_build_query(['from' => date('Y-m-01'), 'to' => today()]))) ?>">Bulan ini</a>
            <a class="chip !py-1.5" href="<?= e(url('admin/laporan.php?' . http_build_query(['from' => date('Y-m-d', strtotime('-30 days')), 'to' => today()]))) ?>">30 hari</a>
            <a class="chip !py-1.5" href="<?= e(url('admin/laporan.php?' . http_build_query(['from' => date('Y-01-01'), 'to' => today()]))) ?>">Tahun ini</a>
        </div>
    </form>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <?php foreach ([
            ['wallet', 'Pendapatan periode', format_rupiah($rangeRevenue), 'Transaksi lunas'],
            ['clipboard', 'Pesanan periode', format_number($rangeStats['bookings']), 'Semua status'],
            ['users', 'Pelanggan unik', format_number($rangeStats['customers']), 'Yang memesan'],
            ['percent', 'Utilisasi stok', $summary['utilization'] . '%', $summary['rented_now'] . ' dari ' . $summary['stock_total'] . ' unit'],
        ] as $card): ?>
            <div class="admin-card p-4">
                <span class="stat-icon"><?= icon($card[0], 'h-4 w-4') ?></span>
                <p class="mt-2 text-[0.6rem] font-bold uppercase tracking-wide text-muted"><?= e($card[1]) ?></p>
                <p class="mt-0.5 text-xl font-extrabold tracking-tight text-forest-900"><?= e($card[2]) ?></p>
                <p class="mt-0.5 text-[0.6rem] text-muted"><?= e($card[3]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1.35fr_.65fr]">
        <div class="admin-card p-4 sm:p-5">
            <div class="flex items-center justify-between"><div><p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Pendapatan</p><h3 class="mt-1 text-base font-extrabold text-forest-900">8 bulan terakhir</h3></div><span class="badge badge-mint">Rp</span></div>
            <div class="mt-4 h-64"><canvas id="reportRevenueChart"></canvas></div>
        </div>
        <div class="admin-card p-4 sm:p-5">
            <div><p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Status pesanan</p><h3 class="mt-1 text-base font-extrabold text-forest-900">Semua waktu</h3></div>
            <div class="mt-3 h-64"><canvas id="reportStatusChart"></canvas></div>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <section class="admin-card overflow-hidden">
            <div class="border-b border-line px-4 py-3.5"><h3 class="text-sm font-extrabold text-forest-900">Gear terlaris</h3></div>
            <div class="overflow-x-auto">
                <table class="table-app min-w-[30rem]" data-datatable="report_top_items" data-ordering="0" data-page-length="7">
                    <thead><tr><th>Gear</th><th>Disewa</th><th>Pendapatan</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>
        <section class="admin-card p-4 sm:p-5">
            <h3 class="text-sm font-extrabold text-forest-900">Kategori populer</h3>
            <div class="mt-3 space-y-3">
                <?php $maxRentals = max(1, ...array_map(static fn ($row) => (int) $row['total_rentals'], $topCategories)); ?>
                <?php foreach ($topCategories as $category): ?>
                    <div>
                        <div class="flex items-center justify-between text-xs"><span class="font-bold text-forest-900"><?= e($category['name']) ?></span><span class="text-muted"><?= (int) $category['total_rentals'] ?> sewa</span></div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-cream-100"><div class="h-full rounded-full bg-forest-700" style="width:<?= round(((int) $category['total_rentals'] / $maxRentals) * 100) ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <div class="rounded-xl bg-cream-50 p-3"><p class="text-[0.58rem] font-bold uppercase text-muted">Rating rata-rata</p><p class="mt-1 text-lg font-extrabold text-forest-900"><?= number_format($summary['avg_rating'], 1, ',', '.') ?></p></div>
                <div class="rounded-xl bg-cream-50 p-3"><p class="text-[0.58rem] font-bold uppercase text-muted">Pelanggan total</p><p class="mt-1 text-lg font-extrabold text-forest-900"><?= (int) $summary['customers'] ?></p></div>
            </div>
        </section>
    </div>

    <section class="admin-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-4 py-3.5">
            <div><h3 class="text-sm font-extrabold text-forest-900">Pesanan dalam periode</h3><p class="mt-0.5 text-[0.62rem] text-muted"><?= e(format_date_id($from)) ?> &mdash; <?= e(format_date_id($to)) ?> &middot; <span data-table-count="report_bookings">&mdash;</span> pesanan</p></div>
            <span class="text-[0.62rem] text-muted">Total <span data-stat="report_total"><?= e(format_rupiah($rangeRevenue)) ?></span></span>
        </div>
        <div class="max-h-[28rem] overflow-auto">
            <table class="table-app min-w-[46rem]" data-datatable="report_bookings" data-page-length="15" data-order='[[0,"desc"]]'>
                <thead><tr><th>Kode</th><th>Pelanggan</th><th>Jadwal</th><th>Total</th><th>Status</th><th>Bayar</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </section>
</div>
<script src="<?= e(asset('vendor/chart.umd.js')) ?>" defer></script>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
