<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'beranda.php');
}

$redirect = trim((string) ($_GET['redirect'] ?? ''));
$flashMessages = get_flashes();
$pageTitle = 'Masuk — Rentify Outdoor Karawang';
$metaDescription = 'Masuk ke akun Rentify untuk menyewa gear, memantau pesanan, dan menyimpan wishlist.';
$bodyClass = 'bg-forest-950';
$pageScripts = ['auth.js'];
require ROOT_PATH . '/includes/header.php';
?>
<main class="auth-shell">
    <div class="container-app grid min-h-[100svh] items-center gap-8 py-8 lg:grid-cols-[1.05fr_.95fr] lg:gap-12">
        <section class="hidden lg:block">
            <a href="<?= e(url('index.php')) ?>" class="inline-flex"><?= brand(true, 'lg') ?></a>
            <div class="mt-12 max-w-lg">
                <p class="section-kicker text-mint-300">Selamat datang kembali</p>
                <h1 class="mt-3 text-4xl font-extrabold leading-[1.1] tracking-tight">Siapkan gear,<br>lanjutkan petualangan.</h1>
                <p class="mt-4 max-w-md text-sm leading-relaxed text-white/55">Masuk untuk memantau pesanan, menyelesaikan pembayaran, dan menyimpan gear favorit untuk trip berikutnya.</p>

                <div class="mt-8 grid gap-3 sm:grid-cols-2">
                    <?php foreach ([['sparkles', 'AI Gear Planner', 'Rekomendasi sesuai destinasi'], ['clipboard', 'Pesanan terpantau', 'Status & pembayaran real-time'], ['shield', 'Gear terverifikasi', 'Dicek dan dicuci bersih'], ['truck', 'Antar lokal', 'Pickup point atau delivery']] as $feature): ?>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3.5 backdrop-blur">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-mint-300 text-forest-900"><?= icon($feature[0], 'h-4 w-4') ?></span>
                            <p class="mt-2 text-xs font-extrabold text-white"><?= e($feature[1]) ?></p>
                            <p class="mt-0.5 text-[0.62rem] leading-relaxed text-white/45"><?= e($feature[2]) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-8 flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-4">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-mint-300 text-xs font-extrabold text-forest-900">GP</span>
                    <div>
                        <p class="text-xs font-bold text-white/80">“Tenda bersih, admin responsif. Proses ambil di basecamp cepat.”</p>
                        <p class="mt-1 text-[0.6rem] text-white/40">Galih Pratama · Customer Rentify</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="auth-card mx-auto w-full max-w-md p-6 sm:p-8">
            <div class="flex items-center justify-between lg:hidden">
                <?= brand(false) ?>
                <a href="<?= e(url('index.php')) ?>" class="icon-btn h-9 w-9" aria-label="Kembali ke beranda"><?= icon('arrow-left', 'h-4 w-4') ?></a>
            </div>
            <div class="mt-6 lg:mt-0">
                <p class="section-kicker">Akun Rentify</p>
                <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900">Masuk ke akunmu</h2>
                <p class="mt-1 text-sm text-muted">Gunakan email, nomor WhatsApp, atau member ID.</p>
            </div>

            <?php if ($flashMessages): ?>
                <div class="mt-4 rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-800">
                    <?php foreach ($flashMessages as $flash): ?><p><?= e($flash['message']) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-4 hidden rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700" data-auth-error role="alert"></div>

            <form class="mt-5 space-y-4" data-auth-form="login" novalidate>
                <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
                <div>
                    <label class="field-label" for="login-identity">Email / WhatsApp / Member ID</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted"><?= icon('user', 'h-4 w-4') ?></span>
                        <input id="login-identity" class="input input-icon" name="login" required autocomplete="username" placeholder="nama@email.com" autofocus>
                    </div>
                    <p class="mt-1 hidden text-[0.68rem] font-bold text-danger" data-error-for="login"></p>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label class="field-label" for="login-password">Kata sandi</label>
                        <a href="<?= e(phone_wa_link(setting('whatsapp', '6281234567890'), 'Halo Rentify, saya lupa kata sandi akun saya.')) ?>" target="_blank" rel="noopener" class="text-[0.68rem] font-bold text-forest-600 hover:underline">Lupa sandi?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted"><?= icon('lock', 'h-4 w-4') ?></span>
                        <input id="login-password" class="input input-icon pr-12" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                        <button type="button" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-muted hover:bg-cream-100" data-toggle-password="#login-password" aria-label="Tampilkan kata sandi"><?= icon('eye', 'h-4 w-4') ?></button>
                    </div>
                    <p class="mt-1 hidden text-[0.68rem] font-bold text-danger" data-error-for="password"></p>
                </div>
                <label class="flex items-center gap-2 text-xs font-semibold text-forest-700">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-line"> Ingat saya di perangkat ini
                </label>
                <button type="submit" class="btn btn-primary w-full" data-auth-submit><?= icon('arrow-right', 'h-4 w-4') ?> Masuk</button>
            </form>

            <div class="mt-5 rounded-xl bg-cream-50 p-3">
                <p class="text-[0.62rem] font-extrabold uppercase tracking-[0.12em] text-muted">Akun demo</p>
                <div class="mt-2 grid grid-cols-3 gap-2">
                    <button type="button" class="btn btn-outline btn-sm" data-demo-login data-email="admin@rentify.id" data-password="password">Superadmin</button>
                    <button type="button" class="btn btn-outline btn-sm" data-demo-login data-email="asep@rentify.id" data-password="password">Admin Cabang</button>
                    <button type="button" class="btn btn-outline btn-sm" data-demo-login data-email="galih@petualang.id" data-password="password">Customer</button>
                </div>
            </div>

            <p class="mt-5 text-center text-xs text-muted">Belum punya akun? <a href="<?= e(url('register.php' . ($redirect ? '?redirect=' . rawurlencode($redirect) : ''))) ?>" class="font-extrabold text-forest-900 hover:underline">Daftar gratis</a></p>
            <div class="mt-4 flex items-center justify-center gap-4 text-[0.62rem] text-muted">
                <a href="<?= e(url('katalog.php')) ?>" class="hover:text-forest-900">Lihat katalog</a>
                <span class="h-3 w-px bg-line"></span>
                <a href="<?= e(url('index.php')) ?>" class="hover:text-forest-900">Tentang Rentify</a>
            </div>
        </section>
    </div>
</main>
<?php require ROOT_PATH . '/includes/auth_footer.php'; ?>
