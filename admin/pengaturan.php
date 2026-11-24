<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

require_superadmin();
$grouped = Setting::allGrouped();
$groupLabels = [
    'umum' => ['Pengaturan umum', 'Identitas toko yang tampil di seluruh aplikasi.', 'settings'],
    'kontak' => ['Kontak & alamat', 'Informasi yang dilihat pelanggan di footer dan halaman bantuan.', 'whatsapp'],
    'pembayaran' => ['Pembayaran', 'Rekening, QRIS, dan biaya pengantaran.', 'credit-card'],
    'sistem' => ['Sistem', 'Batas waktu pesanan dan kontrol AI Planner.', 'shield'],
];
$fieldLabels = [
    'app_name' => 'Nama aplikasi',
    'app_tagline' => 'Tagline',
    'whatsapp' => 'Nomor WhatsApp',
    'email' => 'Email',
    'address' => 'Alamat toko',
    'payment_bank' => 'Nama bank',
    'payment_account' => 'Nomor rekening',
    'payment_account_name' => 'Nama pemilik rekening',
    'qris_image' => 'Gambar QRIS',
    'delivery_fee' => 'Biaya antar (Rp)',
    'dp_percent' => 'DP (%)',
    'hold_hours' => 'Batas bayar (jam)',
    'ai_enabled' => 'AI Planner',
];

$adminTitle = 'Pengaturan';
$adminPage = 'pengaturan';
require ROOT_PATH . '/includes/admin_header.php';
?>
<div class="space-y-4">
    <div>
        <p class="text-xs font-semibold text-muted">Konfigurasi toko</p>
        <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900">Pengaturan Rentify</h2>
    </div>

    <div class="grid gap-4 xl:grid-cols-2 xl:items-start">
        <?php foreach ($grouped as $group => $settings): ?>
            <?php $meta = $groupLabels[$group] ?? [ucfirst($group), 'Pengaturan ' . $group, 'settings']; ?>
            <form class="admin-card p-4 sm:p-5" data-admin-form="settings" enctype="multipart/form-data">
                <input type="hidden" name="action" value="settings_save">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted"><?= e(strtoupper($group)) ?></p><h3 class="mt-1 text-base font-extrabold text-forest-900"><?= e($meta[0]) ?></h3><p class="mt-0.5 text-[0.68rem] text-muted"><?= e($meta[1]) ?></p></div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-cream-100 text-forest-700"><?= icon($meta[2], 'h-4 w-4') ?></span>
                </div>
                <div class="mt-4 space-y-3">
                    <?php foreach ($settings as $setting): ?>
                        <?php
                        $key = $setting['setting_key'];
                        $value = (string) $setting['setting_value'];
                        $type = $setting['setting_type'];
                        $label = $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
                        ?>
                        <?php if ($type === 'boolean'): ?>
                            <input type="hidden" name="settings[<?= e($key) ?>]" value="0">
                            <label class="flex items-center justify-between gap-3 rounded-xl bg-cream-50 px-3 py-3">
                                <span><span class="block text-xs font-extrabold text-forest-900"><?= e($label) ?></span><span class="mt-0.5 block text-[0.62rem] text-muted">Aktifkan rekomendasi AI untuk pelanggan.</span></span>
                                <input type="checkbox" name="settings[<?= e($key) ?>]" value="1" class="h-5 w-5 rounded border-line" <?= $value === '1' ? 'checked' : '' ?>>
                            </label>
                        <?php elseif ($type === 'textarea'): ?>
                            <div><label class="field-label" for="setting-<?= e($key) ?>"><?= e($label) ?></label><textarea id="setting-<?= e($key) ?>" class="input min-h-[4.5rem]" name="settings[<?= e($key) ?>]" rows="2"><?= e($value) ?></textarea></div>
                        <?php elseif ($type === 'number'): ?>
                            <div><label class="field-label" for="setting-<?= e($key) ?>"><?= e($label) ?></label><input id="setting-<?= e($key) ?>" class="input" type="number" min="0" name="settings[<?= e($key) ?>]" value="<?= e($value) ?>"></div>
                        <?php elseif ($type === 'image'): ?>
                            <div>
                                <p class="field-label"><?= e($label) ?></p>
                                <div class="flex items-center gap-3">
                                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-line bg-cream-100">
                                        <?php if ($value): ?><img src="<?= e(upload_url($value)) ?>" alt="" class="h-full w-full object-contain"><?php else: ?><span class="flex h-full w-full items-center justify-center text-muted"><?= icon('qr', 'h-6 w-6') ?></span><?php endif; ?>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <input type="hidden" name="settings[<?= e($key) ?>]" value="<?= e($value) ?>">
                                        <input class="input text-xs" type="file" name="qris_image" accept="image/jpeg,image/png,image/webp">
                                        <p class="helper mt-1">Unggah QRIS statis. Maks 3 MB.</p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div><label class="field-label" for="setting-<?= e($key) ?>"><?= e($label) ?></label><input id="setting-<?= e($key) ?>" class="input" name="settings[<?= e($key) ?>]" value="<?= e($value) ?>" <?= $key === 'email' ? 'type="email"' : '' ?>></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-sm mt-4">Simpan <?= e(strtolower($meta[0])) ?></button>
            </form>
        <?php endforeach; ?>
    </div>

    <section class="admin-card p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-muted">Jaringan cabang</p>
                <h3 class="mt-1 text-base font-extrabold text-forest-900">Titik pengambilan dikelola per toko</h3>
                <p class="mt-0.5 max-w-2xl text-[0.68rem] leading-relaxed text-muted">Setiap toko cabang otomatis memiliki titik pickup yang sinkron dengan alamat, jam operasional, dan koordinat Leaflet. Tambah atau ubah cabang di halaman Toko Cabang.</p>
            </div>
            <a href="<?= e(url('admin/toko.php')) ?>" class="btn btn-primary btn-sm"><?= icon('map-pin', 'h-3.5 w-3.5') ?> Kelola toko cabang</a>
        </div>
    </section>
</div>
<?php require ROOT_PATH . '/includes/admin_footer.php'; ?>
