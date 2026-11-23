<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'beranda.php');
}

$redirect = trim((string) ($_GET['redirect'] ?? ''));
$pageTitle = 'Daftar — Rentify Outdoor Karawang';
$metaDescription = 'Buat akun Rentify gratis untuk menyewa gear outdoor, memantau pesanan, dan mendapatkan rekomendasi AI.';
$bodyClass = 'bg-forest-950';
$pageScripts = ['auth.js'];
require ROOT_PATH . '/includes/header.php';
?>
<main class="auth-shell">
    <div class="container-app grid min-h-[100svh] items-center gap-8 py-8 lg:grid-cols-[1fr_.95fr] lg:gap-12">
        <section class="hidden lg:block">
            <a href="<?= e(url('index.php')) ?>" class="inline-flex"><?= brand(true, 'lg') ?></a>
            <div class="mt-12 max-w-lg">
                <p class="section-kicker text-mint-300">Bergabung gratis</p>
                <h1 class="mt-3 text-4xl font-extrabold leading-[1.1] tracking-tight">Petualangan lebih ringan,<br>gear lebih siap.</h1>
                <p class="mt-4 max-w-md text-sm leading-relaxed text-white/55">Satu akun untuk menyewa tenda, carrier, sleeping bag, dan perlengkapan outdoor lain di Karawang.</p>

                <div class="mt-8 space-y-3">
                    <?php foreach ([['sparkles', 'Rekomendasi gear personal', 'AI Planner menyusun daftar wajib & opsional sesuai trip.'], ['shield', 'Deposit aman', 'Deposit dikembalikan setelah gear dicek lengkap.'], ['whatsapp', 'Support lokal', 'Admin Karawang siap bantu lewat WhatsApp.']] as $benefit): ?>
                        <div class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-3.5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-mint-300 text-forest-900"><?= icon($benefit[0], 'h-4 w-4') ?></span>
                            <div>
                                <p class="text-xs font-extrabold text-white"><?= e($benefit[1]) ?></p>
                                <p class="mt-0.5 text-[0.65rem] leading-relaxed text-white/45"><?= e($benefit[2]) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-8 rounded-[1.4rem] border border-white/10 bg-white/5 p-5">
                    <div class="flex items-center gap-1 text-star"><?= str_repeat(icon('star-filled', 'h-3.5 w-3.5'), 5) ?></div>
                    <p class="mt-2 text-sm leading-relaxed text-white/70">“Baru pertama camping langsung dibantu pilih gear lewat AI. Ternyata semuanya kepakai.”</p>
                    <p class="mt-2 text-[0.62rem] text-white/40">William Saputra · Customer Rentify</p>
                </div>
            </div>
        </section>

        <section class="auth-card mx-auto w-full max-w-xl p-6 sm:p-8">
            <div class="flex items-center justify-between lg:hidden">
                <?= brand(false) ?>
                <a href="<?= e(url('index.php')) ?>" class="icon-btn h-9 w-9" aria-label="Kembali"><?= icon('arrow-left', 'h-4 w-4') ?></a>
            </div>
            <div class="mt-6 lg:mt-0">
                <p class="section-kicker">Akun baru</p>
                <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-forest-900">Daftar dalam 1 menit</h2>
                <p class="mt-1 text-sm text-muted">Gratis, tanpa biaya pendaftaran.</p>
            </div>

            <div class="mt-4 hidden rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700" data-auth-error role="alert"></div>

            <form class="mt-5 space-y-4" data-auth-form="register" novalidate>
                <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="field-label" for="register-name">Nama lengkap</label>
                        <input id="register-name" class="input" name="name" required minlength="3" maxlength="120" autocomplete="name" placeholder="Nama sesuai identitas">
                        <p class="mt-1 hidden text-[0.68rem] font-bold text-danger" data-error-for="name"></p>
                    </div>
                    <div>
                        <label class="field-label" for="register-email">Email</label>
                        <input id="register-email" class="input" type="email" name="email" required autocomplete="email" placeholder="nama@email.com">
                        <p class="mt-1 hidden text-[0.68rem] font-bold text-danger" data-error-for="email"></p>
                    </div>
                    <div>
                        <label class="field-label" for="register-phone">WhatsApp</label>
                        <input id="register-phone" class="input" name="phone" required inputmode="tel" autocomplete="tel" placeholder="0812xxxxxxx">
                        <p class="mt-1 hidden text-[0.68rem] font-bold text-danger" data-error-for="phone"></p>
                    </div>
                    <div>
                        <label class="field-label" for="register-password">Kata sandi</label>
                        <div class="relative">
                            <input id="register-password" class="input pr-12" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter">
                            <button type="button" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-muted hover:bg-cream-100" data-toggle-password="#register-password" aria-label="Tampilkan kata sandi"><?= icon('eye', 'h-4 w-4') ?></button>
                        </div>
                        <p class="mt-1 hidden text-[0.68rem] font-bold text-danger" data-error-for="password"></p>
                    </div>
                    <div>
                        <label class="field-label" for="register-confirm">Ulangi kata sandi</label>
                        <input id="register-confirm" class="input" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" placeholder="Ulangi kata sandi">
                        <p class="mt-1 hidden text-[0.68rem] font-bold text-danger" data-error-for="password_confirmation"></p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="field-label" for="register-address">Alamat <span class="font-semibold text-muted">(opsional)</span></label>
                        <textarea id="register-address" class="input" name="address" rows="2" placeholder="Alamat area Karawang untuk pengantaran."></textarea>
                    </div>
                </div>
                <label class="flex items-start gap-2.5 text-xs leading-relaxed text-muted">
                    <input type="checkbox" name="agree" value="1" class="mt-0.5 h-4 w-4 rounded border-line" required data-register-agree>
                    <span>Saya setuju dengan ketentuan sewa dan kebijakan privasi Rentify.</span>
                </label>
                <button type="submit" class="btn btn-primary w-full" data-auth-submit><?= icon('user-check', 'h-4 w-4') ?> Buat akun</button>
            </form>

            <p class="mt-5 text-center text-xs text-muted">Sudah punya akun? <a href="<?= e(url('login.php' . ($redirect ? '?redirect=' . rawurlencode($redirect) : ''))) ?>" class="font-extrabold text-forest-900 hover:underline">Masuk di sini</a></p>
        </section>
    </div>
</main>
<?php require ROOT_PATH . '/includes/auth_footer.php'; ?>
