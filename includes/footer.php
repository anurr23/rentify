<?php
declare(strict_types=1);

$footerCategories = [];
try {
    $footerCategories = Category::allActive();
} catch (Throwable) {
    $footerCategories = [];
}
$wa = setting('whatsapp', '6281234567890');
$year = date('Y');
?>
<footer class="bg-forest-950 pb-24 pt-14 text-white/70 lg:pb-10 no-print">
    <div class="container-app">
        <div class="grid gap-10 border-b border-white/10 pb-10 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
            <div>
                <?= brand(true, 'lg') ?>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/55">
                    Rental perlengkapan outdoor di Karawang. Gear bersih, terawat, dan siap menemani setiap langkah pendakian Anda.
                </p>
                <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold text-white/65">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5"><?= icon('shield', 'h-3.5 w-3.5') ?> Gear terverifikasi</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5"><?= icon('truck', 'h-3.5 w-3.5') ?> Antar Karawang</span>
                </div>
                <div class="mt-5 flex gap-2">
                    <a href="<?= e(phone_wa_link($wa, 'Halo Rentify, saya ingin bertanya tentang sewa gear.')) ?>" target="_blank" rel="noopener" class="icon-btn icon-btn-dark h-10 w-10" aria-label="WhatsApp"><?= icon('whatsapp', 'h-4 w-4') ?></a>
                    <a href="https://instagram.com" target="_blank" rel="noopener" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Instagram"><?= icon('instagram', 'h-4 w-4') ?></a>
                    <a href="mailto:<?= e(setting('email', 'hello@rentify.id')) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Email"><?= icon('mail', 'h-4 w-4') ?></a>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-extrabold uppercase tracking-[0.16em] text-white">Jelajahi</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a class="transition hover:text-mint-300" href="<?= e(url('beranda.php')) ?>">Beranda</a></li>
                    <li><a class="transition hover:text-mint-300" href="<?= e(url('katalog.php')) ?>">Semua gear</a></li>
                    <li><a class="transition hover:text-mint-300" href="<?= e(url('rekomendasi.php')) ?>">AI Gear Planner</a></li>
                    <li><a class="transition hover:text-mint-300" href="<?= e(url('pesanan.php')) ?>">Lacak pesanan</a></li>
                    <li><a class="transition hover:text-mint-300" href="<?= e(url('wishlist.php')) ?>">Wishlist</a></li>
                </ul>
            </div>

            <div>
                <h2 class="text-sm font-extrabold uppercase tracking-[0.16em] text-white">Kategori</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <?php foreach (array_slice($footerCategories, 0, 5) as $category): ?>
                        <li><a class="transition hover:text-mint-300" href="<?= e(url('katalog.php?category=' . $category['slug'])) ?>"><?= e($category['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <h2 class="text-sm font-extrabold uppercase tracking-[0.16em] text-white">Kunjungi</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    <li class="flex gap-2.5"><span class="mt-0.5 text-mint-300"><?= icon('map-pin', 'h-4 w-4') ?></span><span><?= e(setting('address', 'Basecamp Karawang Kota, Jawa Barat')) ?></span></li>
                    <li class="flex gap-2.5"><span class="mt-0.5 text-mint-300"><?= icon('clock', 'h-4 w-4') ?></span><span>Setiap hari, 08.00–21.00 WIB</span></li>
                    <li class="flex gap-2.5"><span class="mt-0.5 text-mint-300"><?= icon('phone', 'h-4 w-4') ?></span><a class="hover:text-mint-300" href="<?= e(phone_wa_link($wa)) ?>">+<?= e(ltrim($wa, '0')) ?></a></li>
                    <li class="flex gap-2.5"><span class="mt-0.5 text-mint-300"><?= icon('mail', 'h-4 w-4') ?></span><a class="hover:text-mint-300" href="mailto:<?= e(setting('email', 'hello@rentify.id')) ?>"><?= e(setting('email', 'hello@rentify.id')) ?></a></li>
                </ul>
            </div>
        </div>

        <div class="flex flex-col gap-4 pt-6 text-xs text-white/45 sm:flex-row sm:items-center sm:justify-between">
            <p>© <?= $year ?> <?= e(APP_NAME) ?>. Dibuat untuk petualangan yang lebih jauh.</p>
            <div class="flex flex-wrap items-center gap-4">
                <span class="inline-flex items-center gap-1.5"><?= icon('lock', 'h-3.5 w-3.5') ?> Pembayaran aman</span>
                <span class="inline-flex items-center gap-1.5"><?= icon('whatsapp', 'h-3.5 w-3.5') ?> Support lokal</span>
                <a href="<?= e(url('index.php')) ?>" class="hover:text-mint-300">Tentang Rentify</a>
            </div>
        </div>
    </div>
</footer>

<div id="modal-root"></div>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php foreach (($pageScripts ?? []) as $script): ?>
    <?php if (str_ends_with($script, '.mjs') || str_contains($script, 'module:')): ?>
        <script type="module" src="<?= e(asset('js/' . str_replace('module:', '', $script))) ?>"></script>
    <?php else: ?>
        <script src="<?= e(asset('js/' . $script)) ?>" defer></script>
    <?php endif; ?>
<?php endforeach; ?>
<?php if (isset($inlineScript)): ?>
<script><?= $inlineScript ?></script>
<?php endif; ?>
</body>
</html>
