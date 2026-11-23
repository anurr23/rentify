<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_superadmin();
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

$performance = Report::storePerformance($from, $to);
$totals = [
    'revenue' => array_sum(array_column($performance, 'revenue')),
    'orders' => array_sum(array_column($performance, 'orders')),
    'customers' => array_sum(array_column($performance, 'customers')),
    'items' => array_sum(array_column($performance, 'items')),
    'stock' => array_sum(array_column($performance, 'stock')),
    'pending' => array_sum(array_column($performance, 'pending_payments')),
];
$best = $performance[0] ?? null;

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rentify-perbandingan-toko-' . $from . '-sampai-' . $to . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Rank', 'Toko', 'Kode', 'Kota', 'Omzet', 'Pesanan', 'AOV', 'Growth %', 'Utilisasi %', 'Telat', 'Pembayaran Pending', 'Rating', 'Barang', 'Stok'], ';', '"', '');
    foreach ($performance as $row) {
        fputcsv($out, [
            $row['rank'], $row['name'], $row['code'], $row['city'],
            $row['revenue'], $row['orders'], round($row['aov']), $row['growth'], $row['utilization'],
            $row['late_returns'], $row['pending_payments'], number_format($row['rating'], 2, ',', '.'),
            $row['items'], $row['stock'],
        ], ';', '"', '');
    }
    fclose($out);
    log_audit('report.store_comparison_export', 'report', null, ['from' => $from, 'to' => $to]);
    exit;
}

$monthly = Report::monthlyRevenueByStore(8);
$chartColors = ['#1B3B2B', '#53CF6C', '#E5A440', '#53A8CF', '#8B7CC8', '#D9534F', '#8FA79A'];
$trendDatasets = [];
foreach ($monthly['datasets'] as $index => $dataset) {
    $color = $chartColors[$index % count($chartColors)];
    $trendDatasets[] = [
        'label' => $dataset['label'],
        'data' => $dataset['data'],
        'borderColor' => $color,
        'backgroundColor' => $color,
        'tension' => 0.35,
        'borderWidth' => 2,
        'pointRadius' => 2.5,
    ];
}

$adminTitle = 'Perbandingan Toko';
$adminPage = 'perbandingan';
$inlineScript = 'window.RENTIFY_CHARTS = ' . json_encode([
    [
        'id' => 'storeRevenueChart',
        'type' => 'bar',
        'data' => [
            'labels' => array_column($performance, 'name'),
            'datasets' => [[
                'label' => 'Omzet',
                'data' => array_map(static fn (array $row): float => (float) $row['revenue'], $performance),
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
        'id' => 'storeShareChart',
        'type' => 'doughnut',
        'data' => [
            'labels' => array_column($performance, 'name'),
            'datasets' => [[
                'data' => array_map(static fn (array $row): float => (float) $row['revenue'], $performance),
                'backgroundColor' => array_slice($chartColors, 0, count($performance)),
                'borderWidth' => 0,
                'hoverOffset' => 4,
            ]],
        ],
        'options' => [
            'responsive' => true, 'maintainAspectRatio' => false, 'cutout' => '66%',
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'font' => ['size' => 10]]]],
        ],
    ],
    [
        'id' => 'storeTrendChart',
        'type' => 'line',
        'data' => [
            'labels' => $monthly['labels'],
            'datasets' => $trendDatasets,
        ],
        'options' => [
            'responsive' => true, 'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'font' => ['size' => 10]]]],
            'scales' => ['y' => ['beginAtZero' => true, 'grid' => ['color' => '#eef1ec']], 'x' => ['grid' => ['display' => false]]],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Leaderboard performa cabang</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900">Perbandingan Toko</h2>
            <?php if ($best): ?><p class="mt-1 flex items-center gap-1.5 text-xs font-bold text-forest-600"><?= icon('star-filled', 'h-3.5 w-3.5 text-star') ?> Terbaik: <?= e($best['name']) ?> · <?= e(format_rupiah($best['revenue'])) ?></p><?php endif; ?>
        </div>
        <a href="<?= e(url('admin/perbandingan.php?' . http_build_query(['from' => $from, 'to' => $to, 'export' => 'csv']))) ?>" class="btn btn-outline btn-sm"><?= icon('download', 'h-3.5 w-3.5') ?> Export CSV</a>
    </div>

    <form class="admin-card flex flex-wrap items-end gap-2 p-3.5" method="get">
        <div><label class="field-label !mb-1.5 text-[0.68rem]" for="compare-from">Dari</label><input id="compare-from" class="input !py-2.5 text-sm" type="date" name="from" value="<?= e($from) ?>"></div>
        <div><label class="field-label !mb-1.5 text-[0.68rem]" for="compare-to">Sampai</label><input id="compare-to" class="input !py-2.5 text-sm" type="date" name="to" value="<?= e($to) ?>"></div>
        <button type="submit" class="btn btn-primary btn-sm">Terapkan</button>
        <a href="<?= e(url('admin/perbandingan.php')) ?>" class="btn btn-outline btn-sm">Bulan ini</a>
        <a href="<?= e(url('admin/perbandingan.php?' . http_build_query(['from' => date('Y-m-01', strtotime('-2 months')), 'to' => today()]))) ?>" class="btn btn-outline btn-sm">3 bulan</a>
    </form>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <?php foreach ([
            ['wallet', 'Total omzet', format_rupiah($totals['revenue']), 'text-forest-700'],
            ['clipboard', 'Pesanan', format_number($totals['orders']), 'text-sky-700'],
            ['users', 'Pelanggan unik', format_number($totals['customers']), 'text-star'],
            ['clock', 'Pembayaran pending', format_number($totals['pending']), 'text-danger'],
        ] as $metric): ?>
            <div class="admin-card p-4">
                <span class="stat-icon <?= $metric[3] ?>"><?= icon($metric[0], 'h-4 w-4') ?></span>
                <p class="mt-2 text-[0.6rem] font-bold uppercase tracking-wide text-muted"><?= e($metric[1]) ?></p>
                <p class="mt-0.5 truncate text-xl font-extrabold text-forest-900"><?= e($metric[2]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1.2fr_.8fr]">
        <section class="admin-card p-4 sm:p-5">
            <div><p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Omzet per cabang</p><h3 class="mt-1 text-base font-extrabold text-forest-900"><?= e(format_date_id($from)) ?> — <?= e(format_date_id($to)) ?></h3></div>
            <div class="mt-4 h-72"><canvas id="storeRevenueChart" aria-label="Grafik omzet per cabang"></canvas></div>
        </section>
        <section class="admin-card p-4 sm:p-5">
            <div><p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Kontribusi omzet</p><h3 class="mt-1 text-base font-extrabold text-forest-900">Share tiap cabang</h3></div>
            <div class="mt-3 h-72"><canvas id="storeShareChart" aria-label="Distribusi omzet cabang"></canvas></div>
        </section>
    </div>

    <section class="admin-card p-4 sm:p-5">
        <div><p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Tren 8 bulan</p><h3 class="mt-1 text-base font-extrabold text-forest-900">Omzet per cabang</h3></div>
        <div class="mt-4 h-80"><canvas id="storeTrendChart" aria-label="Tren omzet tiap cabang"></canvas></div>
    </section>

    <div class="admin-card overflow-hidden">
        <div class="border-b border-line px-4 py-3.5 sm:px-5">
            <h3 class="text-sm font-extrabold text-forest-900">Leaderboard cabang</h3>
            <p class="mt-0.5 text-[0.62rem] text-muted">Diurutkan berdasarkan omzet periode terpilih.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="table-app min-w-[70rem]">
                <thead><tr><th>Rank</th><th>Toko</th><th>Omzet</th><th>Pesanan</th><th>AOV</th><th>Growth</th><th>Utilisasi</th><th>Telat</th><th>Pending</th><th>Rating</th><th>Barang / Stok</th></tr></thead>
                <tbody>
                <?php if (!$performance): ?><tr><td colspan="11" class="py-12 text-center text-sm text-muted">Belum ada data cabang.</td></tr><?php endif; ?>
                <?php foreach ($performance as $row): ?>
                    <tr>
                        <td>
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full <?= $row['rank'] <= 3 ? 'bg-forest-900 text-mint-300' : 'bg-cream-100 text-forest-700' ?> text-xs font-extrabold">#<?= (int) $row['rank'] ?></span>
                        </td>
                        <td>
                            <p class="font-extrabold text-forest-900"><?= e($row['name']) ?></p>
                            <p class="text-[0.62rem] text-muted"><?= e($row['code']) ?> · <?= e($row['city']) ?></p>
                        </td>
                        <td class="font-extrabold"><?= e(format_rupiah($row['revenue'])) ?></td>
                        <td class="font-bold"><?= (int) $row['orders'] ?></td>
                        <td><?= e(format_rupiah_short($row['aov'])) ?></td>
                        <td><span class="badge <?= $row['growth'] >= 0 ? 'badge-success' : 'badge-danger' ?>"><?= ($row['growth'] >= 0 ? '+' : '') . number_format($row['growth'], 1, ',', '.') ?>%</span></td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-16 overflow-hidden rounded-full bg-cream-200"><div class="h-full rounded-full bg-forest-700" style="width:<?= (int) $row['utilization'] ?>%"></div></div>
                                <span class="text-[0.65rem] font-bold text-forest-700"><?= (int) $row['utilization'] ?>%</span>
                            </div>
                        </td>
                        <td><span class="badge <?= (int) $row['late_returns'] ? 'badge-danger' : 'badge-neutral' ?>"><?= (int) $row['late_returns'] ?></span></td>
                        <td><span class="badge <?= (int) $row['pending_payments'] ? 'badge-warning' : 'badge-neutral' ?>"><?= (int) $row['pending_payments'] ?></span></td>
                        <td class="text-xs font-bold"><?= $row['rating'] > 0 ? number_format($row['rating'], 1, ',', '.') : '—' ?></td>
                        <td class="text-xs"><span class="font-bold text-forest-900"><?= (int) $row['items'] ?></span> / <?= (int) $row['stock'] ?> unit</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="<?= e(asset('vendor/chart.umd.js')) ?>" defer></script>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
