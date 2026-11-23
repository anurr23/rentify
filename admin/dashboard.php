<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_admin();
$summary = Report::summary();
$monthly = Report::monthlyRevenue(8);
$statusRows = Report::bookingsByStatus();
$recent = Report::recentBookings(7);
$pendingPayments = Payment::pending(4);
$lowStock = Item::lowStock(4, 5);
$dueSoon = Booking::dueSoon();
$currentUser = auth_user();

$statusLabels = [];
$statusValues = [];
$statusColors = [
    'pending' => '#E5A440', 'confirmed' => '#53A8CF', 'preparing' => '#8B7CC8', 'ready' => '#B2D8C8',
    'ongoing' => '#53CF6C', 'returned' => '#8FA79A', 'completed' => '#1B3B2B', 'cancelled' => '#D9534F',
];
foreach ($statusRows as $row) {
    $statusLabels[] = status_label($row['status']);
    $statusValues[] = (int) $row['total'];
}

$adminTitle = 'Dashboard';
$adminPage = 'dashboard';
$inlineScript = 'window.RENTIFY_CHARTS = ' . json_encode([
    [
        'id' => 'revenueChart',
        'type' => 'line',
        'data' => [
            'labels' => array_column($monthly, 'label'),
            'datasets' => [[
                'label' => 'Pendapatan',
                'data' => array_map(static fn ($row) => (float) $row['total'], $monthly),
                'borderColor' => '#1B3B2B',
                'backgroundColor' => 'rgba(203, 232, 206, 0.35)',
                'fill' => true,
                'tension' => 0.38,
                'pointBackgroundColor' => '#032517',
                'pointRadius' => 3,
                'borderWidth' => 2.5,
            ]],
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'grid' => ['color' => '#eef1ec']],
                'x' => ['grid' => ['display' => false]],
            ],
        ],
    ],
    [
        'id' => 'statusChart',
        'type' => 'doughnut',
        'data' => [
            'labels' => $statusLabels,
            'datasets' => [[
                'data' => $statusValues,
                'backgroundColor' => array_map(static fn ($row) => $statusColors[$row['status']] ?? '#8FA79A', $statusRows),
                'borderWidth' => 0,
                'hoverOffset' => 4,
            ]],
        ],
        'options' => [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '68%',
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'font' => ['size' => 10]]]],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Selamat datang kembali, <?= e(explode(' ', $currentUser['name'] ?? 'Admin')[0]) ?>.</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900">Ringkasan bisnis hari ini</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= e(url('admin/barang.php?new=1')) ?>" class="btn btn-primary btn-sm"><?= icon('plus', 'h-3.5 w-3.5') ?> Tambah gear</a>
            <a href="<?= e(url('admin/pesanan.php')) ?>" class="btn btn-outline btn-sm"><?= icon('clipboard', 'h-3.5 w-3.5') ?> Pesanan</a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <?php
        $metrics = [
            ['wallet', 'Pendapatan bulan ini', format_rupiah($summary['revenue_month']), $summary['revenue_growth'], 'text-forest-700'],
            ['clipboard', 'Pesanan bulan ini', format_number($summary['bookings_month']), $summary['bookings_growth'], 'text-sky-700'],
            ['tent', 'Sedang berjalan', (string) $summary['active_rentals'], null, 'text-success'],
            ['clock', 'Pembayaran pending', (string) $summary['pending_payments'], null, 'text-star'],
        ];
        foreach ($metrics as $metric): ?>
            <div class="admin-card p-4">
                <div class="flex items-start justify-between">
                    <span class="stat-icon <?= $metric[4] ?>"><?= icon($metric[0], 'h-4 w-4') ?></span>
                    <?php if ($metric[3] !== null): ?>
                        <span class="inline-flex items-center gap-1 rounded-full <?= $metric[3] >= 0 ? 'bg-mint-100 text-forest-700' : 'bg-red-50 text-danger' ?> px-2 py-0.5 text-[0.6rem] font-extrabold">
                            <?= icon($metric[3] >= 0 ? 'arrow-up' : 'arrow-down', 'h-3 w-3') ?><?= number_format(abs((float) $metric[3]), 1, ',', '.') ?>%
                        </span>
                    <?php endif; ?>
                </div>
                <p class="mt-3 text-[0.6rem] font-bold uppercase tracking-[0.12em] text-muted"><?= e($metric[1]) ?></p>
                <p class="mt-1 text-xl font-extrabold tracking-tight text-forest-900"><?= e($metric[2]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1.35fr_.65fr]">
        <div class="admin-card p-4 sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Tren pendapatan</p>
                    <h3 class="mt-1 text-base font-extrabold text-forest-900">8 bulan terakhir</h3>
                </div>
                <a href="<?= e(url('admin/laporan.php')) ?>" class="text-xs font-extrabold text-forest-700">Lihat laporan</a>
            </div>
            <div class="mt-4 h-64"><canvas id="revenueChart" aria-label="Grafik pendapatan"></canvas></div>
        </div>
        <div class="admin-card p-4 sm:p-5">
            <div>
                <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Distribusi pesanan</p>
                <h3 class="mt-1 text-base font-extrabold text-forest-900">Semua status</h3>
            </div>
            <div class="mt-3 h-64"><canvas id="statusChart" aria-label="Grafik status pesanan"></canvas></div>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <section class="admin-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-4 py-3.5 sm:px-5">
                <div>
                    <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Pesanan terbaru</p>
                    <h3 class="mt-0.5 text-sm font-extrabold text-forest-900">Aktivitas masuk</h3>
                </div>
                <a href="<?= e(url('admin/pesanan.php')) ?>" class="text-xs font-extrabold text-forest-700">Semua</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table-app">
                    <thead><tr><th>Kode</th><th>Pelanggan</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (!$recent): ?><tr><td colspan="4" class="text-center text-muted">Belum ada pesanan.</td></tr><?php endif; ?>
                    <?php foreach ($recent as $booking): ?>
                        <tr>
                            <td><a href="<?= e(url('admin/pesanan.php?id=' . $booking['id'])) ?>" class="font-extrabold text-forest-900 hover:underline"><?= e($booking['code']) ?></a><p class="text-[0.62rem] text-muted"><?= e(time_ago($booking['created_at'])) ?></p></td>
                            <td><p class="font-semibold"><?= e($booking['user_name']) ?></p><p class="text-[0.62rem] text-muted"><?= e(format_date_id($booking['start_date'])) ?></p></td>
                            <td class="font-bold"><?= e(format_rupiah($booking['total'])) ?></td>
                            <td><span class="badge <?= e(status_class($booking['status'])) ?>"><?= e(status_label($booking['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-4 py-3.5 sm:px-5">
                <div>
                    <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Pembayaran</p>
                    <h3 class="mt-0.5 text-sm font-extrabold text-forest-900">Menunggu verifikasi</h3>
                </div>
                <a href="<?= e(url('admin/pesanan.php?payment_status=pending')) ?>" class="text-xs font-extrabold text-forest-700">Semua</a>
            </div>
            <div class="divide-y divide-line">
                <?php if (!$pendingPayments): ?><p class="px-5 py-8 text-center text-sm text-muted">Tidak ada pembayaran pending.</p><?php endif; ?>
                <?php foreach ($pendingPayments as $payment): ?>
                    <div class="flex items-center gap-3 px-4 py-3 sm:px-5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-50 text-star"><?= icon('credit-card', 'h-4 w-4') ?></span>
                        <div class="min-w-0 flex-1">
                            <a href="<?= e(url('admin/pesanan.php?id=' . $payment['booking_id'])) ?>" class="block truncate text-xs font-extrabold text-forest-900"><?= e($payment['booking_code']) ?> · <?= e($payment['user_name']) ?></a>
                            <p class="text-[0.62rem] text-muted"><?= e(payment_method_label($payment['method'])) ?> · <?= e(format_rupiah($payment['amount'])) ?> · <?= e(time_ago($payment['created_at'])) ?></p>
                        </div>
                        <a href="<?= e(url('admin/pesanan.php?id=' . $payment['booking_id'])) ?>" class="btn btn-outline btn-sm">Cek</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <section class="admin-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-extrabold text-forest-900">Stok menipis</h3>
                <a href="<?= e(url('admin/barang.php')) ?>" class="text-[0.68rem] font-bold text-forest-600">Kelola</a>
            </div>
            <div class="mt-3 space-y-2">
                <?php if (!$lowStock): ?><p class="py-4 text-center text-xs text-muted">Semua stok aman.</p><?php endif; ?>
                <?php foreach ($lowStock as $item): ?>
                    <div class="flex items-center gap-2.5 rounded-xl bg-cream-50 p-2">
                        <div class="h-9 w-9 overflow-hidden rounded-lg bg-cream-100"><img src="<?= e(upload_url($item['primary_image'])) ?>" alt="" class="h-full w-full object-cover"></div>
                        <div class="min-w-0 flex-1"><p class="truncate text-[0.7rem] font-bold text-forest-900"><?= e($item['name']) ?></p><p class="text-[0.6rem] text-muted"><?= e($item['category_name']) ?></p></div>
                        <span class="badge badge-warning"><?= (int) $item['stock_total'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-extrabold text-forest-900">Jatuh tempo</h3>
                <a href="<?= e(url('admin/pengembalian.php')) ?>" class="text-[0.68rem] font-bold text-forest-600">Kelola</a>
            </div>
            <div class="mt-3 space-y-2">
                <?php if (!$dueSoon): ?><p class="py-4 text-center text-xs text-muted">Tidak ada yang jatuh tempo.</p><?php endif; ?>
                <?php foreach (array_slice($dueSoon, 0, 4) as $booking): ?>
                    <div class="flex items-center gap-2.5 rounded-xl bg-cream-50 p-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-forest-700"><?= icon('rotate', 'h-4 w-4') ?></span>
                        <div class="min-w-0 flex-1"><p class="truncate text-[0.7rem] font-bold text-forest-900"><?= e($booking['user_name']) ?></p><p class="text-[0.6rem] text-muted"><?= e($booking['code']) ?> · <?= e(format_date_id($booking['end_date'])) ?></p></div>
                        <span class="badge <?= (int) $booking['days_left'] < 0 ? 'badge-danger' : 'badge-warning' ?>"><?= (int) $booking['days_left'] < 0 ? 'Telat ' . abs((int) $booking['days_left']) . 'h' : (int) $booking['days_left'] . 'h' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-card p-4 sm:p-5">
            <h3 class="text-sm font-extrabold text-forest-900">Akses cepat</h3>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <?php foreach ([
                    ['package', 'Tambah gear', 'admin/barang.php?new=1'],
                    ['grid', 'Kategori', 'admin/kategori.php'],
                    ['chart-line', 'Laporan', 'admin/laporan.php'],
                    ['sparkles', 'AI Planner', 'admin/rekomendasi.php'],
                ] as $action): ?>
                    <a href="<?= e(url($action[2])) ?>" class="rounded-xl border border-line p-3 transition hover:border-mint-300 hover:bg-mint-100/30">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-cream-100 text-forest-700"><?= icon($action[0], 'h-4 w-4') ?></span>
                        <p class="mt-2 text-[0.68rem] font-extrabold text-forest-900"><?= e($action[1]) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
<script src="<?= e(asset('vendor/chart.umd.js')) ?>" defer></script>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
