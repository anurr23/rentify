<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_superadmin();
$aiConfig = require ROOT_PATH . '/config/ai.php';
$sessionId = (int) ($_GET['id'] ?? 0);
$session = null;
$sessionItems = [];
if ($sessionId) {
    $stmt = Database::conn()->prepare(
        'SELECT s.*, a.name AS activity_name, u.name AS user_name FROM ai_sessions s
         LEFT JOIN activities a ON a.id = s.activity_id LEFT JOIN users u ON u.id = s.user_id
         WHERE s.id = ? LIMIT 1'
    );
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch() ?: null;
    if ($session) {
        $stmt = Database::conn()->prepare(
            'SELECT si.*, i.name AS item_name, i.slug, i.primary_image, i.price_per_day, c.name AS category_name
             FROM ai_session_items si JOIN items i ON i.id = si.item_id JOIN categories c ON c.id = i.category_id
             WHERE si.ai_session_id = ? ORDER BY si.sort_order ASC'
        );
        $stmt->execute([$sessionId]);
        $sessionItems = $stmt->fetchAll();
    }
}
$stats = AiRecommender::stats();
$sessions = AiRecommender::recentSessions(20);
$fallbackRate = $stats['sessions'] > 0 ? round($stats['fallback'] / $stats['sessions'] * 100, 1) : 0;
$successRate = $stats['sessions'] > 0 ? round($stats['success'] / $stats['sessions'] * 100, 1) : 0;
$aiHost = (string) (parse_url($aiConfig['base_url'], PHP_URL_HOST) ?: 'localhost');
$aiPort = parse_url($aiConfig['base_url'], PHP_URL_PORT);
$aiEndpointLabel = $aiHost . ($aiPort ? ':' . $aiPort : '');
$aiKeyLabel = trim((string) $aiConfig['api_key']) !== '' ? 'Terkonfigurasi' : 'Belum diatur';

$adminTitle = 'AI Planner';
$adminPage = 'rekomendasi';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold text-muted">Monitoring rekomendasi</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900">AI Gear Planner</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="badge <?= setting('ai_enabled', '1') === '1' ? 'badge-success' : 'badge-danger' ?>"><?= icon('sparkles', 'h-3 w-3') ?> AI <?= setting('ai_enabled', '1') === '1' ? 'aktif' : 'nonaktif' ?></span>
            <a href="<?= e(url('rekomendasi.php')) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><?= icon('external', 'h-3.5 w-3.5') ?> Buka planner</a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <?php foreach ([
            ['sparkles', 'Total sesi', format_number($stats['sessions'])],
            ['check-circle', 'Berhasil AI', $successRate . '%'],
            ['shield', 'Fallback', $fallbackRate . '%'],
            ['bolt', 'Latensi rata-rata', number_format($stats['avg_latency'] / 1000, 1, ',', '.') . 's'],
        ] as $card): ?>
            <div class="admin-card p-4">
                <span class="stat-icon"><?= icon($card[0], 'h-4 w-4') ?></span>
                <p class="mt-2 text-[0.6rem] font-bold uppercase tracking-wide text-muted"><?= e($card[1]) ?></p>
                <p class="mt-0.5 text-xl font-extrabold tracking-tight text-forest-900"><?= e($card[2]) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1fr_.28fr] xl:items-start">
        <div class="space-y-4 min-w-0">
            <?php if ($session): ?>
                <section class="admin-card p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <a href="<?= e(url('admin/rekomendasi.php')) ?>" class="text-[0.62rem] font-bold text-forest-600">â† Semua sesi</a>
                            <h3 class="mt-1 text-lg font-extrabold text-forest-900">Sesi #<?= (int) $session['id'] ?> Â· <?= e($session['activity_name'] ?: 'Aktivitas') ?></h3>
                            <p class="mt-0.5 text-xs text-muted"><?= e($session['destination_text'] ?: '-') ?> Â· <?= (int) $session['duration_days'] ?> hari Â· <?= (int) $session['participants'] ?> orang Â· <?= e(format_datetime_id($session['created_at'])) ?></p>
                        </div>
                        <span class="badge <?= $session['status'] === 'success' ? 'badge-success' : ($session['status'] === 'failed' ? 'badge-danger' : 'badge-warning') ?>"><?= e(['success' => 'AI berhasil', 'fallback' => 'Fallback', 'failed' => 'Gagal'][$session['status']] ?? $session['status']) ?></span>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-forest-700"><?= e($session['summary'] ?: 'Tidak ada ringkasan.') ?></p>
                    <?php $tips = safe_json_decode($session['tips'], []); ?>
                    <?php if ($tips): ?>
                        <ul class="mt-3 space-y-1.5 text-xs text-muted">
                            <?php foreach ($tips as $tip): ?><li class="flex gap-2"><span class="text-forest-500">â€¢</span><?= e($tip) ?></li><?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="mt-4 space-y-2">
                        <?php foreach ($sessionItems as $item): ?>
                            <div class="flex items-center gap-3 rounded-xl border border-line p-2.5">
                                <div class="h-11 w-11 overflow-hidden rounded-lg bg-cream-100"><img src="<?= e(upload_url($item['primary_image'])) ?>" alt="" class="h-full w-full object-cover"></div>
                                <div class="min-w-0 flex-1"><a href="<?= e(url('detail.php?slug=' . $item['slug'])) ?>" target="_blank" rel="noopener" class="block truncate text-xs font-extrabold text-forest-900"><?= e($item['item_name']) ?></a><p class="text-[0.62rem] text-muted"><?= e($item['category_name']) ?> Â· <?= (int) $item['qty'] ?> unit</p></div>
                                <span class="badge <?= $item['priority'] === 'wajib' ? 'badge-mint' : 'badge-neutral' ?>"><?= e(priority_label($item['priority'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($session['raw_response']): ?>
                        <details class="mt-4">
                            <summary class="cursor-pointer text-[0.62rem] font-bold text-forest-600">Lihat respons mentah AI</summary>
                            <pre class="mt-2 max-h-64 overflow-auto rounded-xl bg-forest-950 p-3 text-[0.62rem] leading-relaxed text-mint-100"><?= e($session['raw_response']) ?></pre>
                        </details>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="admin-card overflow-hidden">
                <div class="border-b border-line px-4 py-3.5"><h3 class="text-sm font-extrabold text-forest-900">Sesi terbaru</h3></div>
                <div class="overflow-x-auto">
                    <table class="table-app min-w-[48rem]">
                        <thead><tr><th>Waktu</th><th>Pengguna</th><th>Rencana</th><th>Hasil</th><th>Model</th><th>Durasi</th><th class="text-right">Aksi</th></tr></thead>
                        <tbody>
                        <?php if (!$sessions): ?><tr><td colspan="7" class="py-10 text-center text-sm text-muted">Belum ada sesi AI.</td></tr><?php endif; ?>
                        <?php foreach ($sessions as $row): ?>
                            <tr>
                                <td><p class="text-xs font-bold"><?= e(time_ago($row['created_at'])) ?></p><p class="text-[0.6rem] text-muted"><?= e(format_datetime_id($row['created_at'])) ?></p></td>
                                <td class="text-xs"><?= e($row['user_name'] ?: 'Tamu') ?></td>
                                <td><p class="text-xs font-bold text-forest-900"><?= e($row['activity_name'] ?: 'â€”') ?></p><p class="text-[0.62rem] text-muted"><?= e($row['destination_text'] ?: 'â€”') ?> Â· <?= (int) $row['duration_days'] ?>h</p></td>
                                <td><span class="badge <?= $row['status'] === 'success' ? 'badge-success' : ($row['status'] === 'failed' ? 'badge-danger' : 'badge-warning') ?>"><?= e($row['status']) ?></span><p class="mt-0.5 text-[0.6rem] text-muted"><?= (int) $row['item_count'] ?> gear</p></td>
                                <td class="text-[0.62rem] text-muted"><?= e($row['model'] ?: 'â€”') ?></td>
                                <td class="text-xs"><?= $row['latency_ms'] ? number_format(((int) $row['latency_ms']) / 1000, 1, ',', '.') . 's' : 'â€”' ?></td>
                                <td><div class="flex justify-end"><a href="<?= e(url('admin/rekomendasi.php?id=' . $row['id'])) ?>" class="btn btn-outline btn-sm">Detail</a></div></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside class="space-y-4 min-w-0">
            <div class="admin-card p-4">
                <h3 class="text-sm font-extrabold text-forest-900">Konfigurasi AI</h3>
                <dl class="mt-3 space-y-2 text-[0.68rem]">
                    <div class="flex justify-between gap-2"><dt class="text-muted">Model</dt><dd class="max-w-[9rem] break-all text-right font-bold text-forest-900"><?= e($aiConfig['model']) ?></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-muted">Endpoint</dt><dd class="font-bold text-forest-900"><?= e($aiEndpointLabel) ?></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-muted">Timeout</dt><dd class="font-bold text-forest-900"><?= (int) $aiConfig['timeout'] ?>s</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-muted">Rate limit</dt><dd class="font-bold text-forest-900"><?= (int) $aiConfig['rate_limit_per_hour'] ?>/jam</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-muted">API key</dt><dd class="font-bold <?= $aiKeyLabel === 'Terkonfigurasi' ? 'text-success' : 'text-danger' ?>"><?= e($aiKeyLabel) ?></dd></div>
                </dl>
                <p class="mt-3 rounded-xl bg-cream-50 p-3 text-[0.62rem] leading-relaxed text-muted">AI hanya boleh memilih item dari kandidat server. Harga, stok, dan kuantitas selalu divalidasi ulang. Fallback aturan kategori aktif jika 9router tidak merespons.</p>
            </div>
            <div class="admin-card p-4">
                <h3 class="text-sm font-extrabold text-forest-900">Kualitas rekomendasi</h3>
                <div class="mt-3 space-y-3">
                    <?php foreach ([['AI berhasil', $successRate, 'bg-success'], ['Fallback', $fallbackRate, 'bg-star']] as $bar): ?>
                        <div>
                            <div class="flex justify-between text-[0.65rem]"><span class="font-bold"><?= e($bar[0]) ?></span><span class="text-muted"><?= $bar[1] ?>%</span></div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-cream-100"><div class="h-full rounded-full <?= $bar[2] ?>" style="width:<?= min(100, $bar[1]) ?>%"></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
