<?php
declare(strict_types=1);

$adminNav = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'chart', 'url' => 'admin/dashboard.php'],
    ['key' => 'pesanan', 'label' => 'Pesanan', 'icon' => 'clipboard', 'url' => 'admin/pesanan.php', 'badge' => $pendingBookingCount ?? 0],
    ['key' => 'barang', 'label' => 'Barang & Gear', 'icon' => 'package', 'url' => 'admin/barang.php'],
    ['key' => 'kategori', 'label' => 'Kategori', 'icon' => 'grid', 'url' => 'admin/kategori.php'],
    ['key' => 'pelanggan', 'label' => 'Pelanggan', 'icon' => 'users', 'url' => 'admin/pelanggan.php'],
    ['key' => 'pengembalian', 'label' => 'Pengembalian', 'icon' => 'rotate', 'url' => 'admin/pengembalian.php'],
    ['key' => 'laporan', 'label' => 'Laporan', 'icon' => 'chart-line', 'url' => 'admin/laporan.php'],
    ['key' => 'rekomendasi', 'label' => 'AI Planner', 'icon' => 'sparkles', 'url' => 'admin/rekomendasi.php'],
    ['key' => 'pengguna', 'label' => 'Pengguna', 'icon' => 'shield', 'url' => 'admin/pengguna.php'],
    ['key' => 'pengaturan', 'label' => 'Pengaturan', 'icon' => 'settings', 'url' => 'admin/pengaturan.php'],
];
?>
<aside class="fixed inset-y-0 left-0 z-[70] hidden w-[17rem] flex-col bg-forest-950 text-white lg:flex" data-admin-sidebar>
    <div class="flex h-[4.6rem] items-center px-5">
        <?= brand(true) ?>
    </div>
    <div class="mx-4 mb-4 rounded-2xl border border-white/10 bg-white/5 p-3">
        <div class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-mint-300 text-xs font-extrabold text-forest-900"><?= e(initials(auth_user()['name'] ?? 'Admin')) ?></span>
            <div class="min-w-0">
                <p class="truncate text-xs font-extrabold"><?= e(auth_user()['name'] ?? 'Admin') ?></p>
                <p class="text-[0.62rem] text-white/45">Owner / Administrator</p>
            </div>
        </div>
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-4">
        <?php foreach ($adminNav as $navItem): ?>
            <a href="<?= e(url($navItem['url'])) ?>" class="admin-link <?= ($adminPage ?? '') === $navItem['key'] ? 'is-active' : '' ?>">
                <?= icon($navItem['icon'], 'h-[1.1rem] w-[1.1rem]') ?>
                <span class="flex-1"><?= e($navItem['label']) ?></span>
                <?php if (!empty($navItem['badge'])): ?>
                    <span class="rounded-full bg-mint-300 px-1.5 py-0.5 text-[0.6rem] font-extrabold text-forest-900"><?= (int) $navItem['badge'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="border-t border-white/10 p-4">
        <div class="rounded-2xl bg-gradient-to-br from-forest-700 to-forest-800 p-4">
            <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.14em] text-mint-300">AI Gear Planner</p>
            <p class="mt-1.5 text-xs leading-relaxed text-white/65">Pantau sesi rekomendasi dan kualitas jawaban AI.</p>
            <a href="<?= e(url('admin/rekomendasi.php')) ?>" class="mt-3 inline-flex items-center gap-1 text-xs font-extrabold text-mint-300">Buka panel <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
        </div>
        <a href="<?= e(url('logout.php')) ?>" class="mt-3 flex items-center gap-2 px-2 py-2 text-xs font-semibold text-white/45 transition hover:text-white"><?= icon('logout', 'h-4 w-4') ?> Keluar dari akun</a>
    </div>
</aside>
