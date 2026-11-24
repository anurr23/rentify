<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

require_login();
$currentUser = auth_user();
$stats = User::stats((int) $currentUser['id']);
$recent = Booking::forUser((int) $currentUser['id'], 'semua', 1, 3)['items'];
$wishlistCount = Wishlist::count((int) $currentUser['id']);

$pageTitle = 'Profil Saya — Rentify Outdoor Karawang';
$metaDescription = 'Kelola data akun, kontak, identitas, dan keamanan akun Rentify Anda.';
$activeNav = 'profil';
$bodyClass = 'pb-16 lg:pb-0';
$pageScripts = ['profile.js'];
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';
?>
<main id="main" class="pb-16 lg:pb-24" data-profile-root>
    <section class="app-hero !pb-16">
        <div class="absolute inset-0 topo-bg opacity-15"></div>
        <div class="container-app relative pt-3">
            <div class="flex items-center justify-between">
                <a href="<?= e(url('beranda.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10 lg:hidden" aria-label="Kembali"><?= icon('arrow-left', 'h-[1.1rem] w-[1.1rem]') ?></a>
                <div class="text-center lg:mx-auto">
                    <p class="text-[0.55rem] font-extrabold uppercase tracking-[0.2em] text-mint-300/70">Akun saya</p>
                    <h1 class="text-base font-extrabold tracking-tight">Profil</h1>
                </div>
                <a href="<?= e(url('katalog.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Katalog"><?= icon('grid', 'h-[1.1rem] w-[1.1rem]') ?></a>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-4">
                <div class="relative">
                    <div class="h-20 w-20 overflow-hidden rounded-full border-4 border-white/20 bg-mint-300" data-profile-avatar>
                        <?php if (!empty($currentUser['avatar'])): ?>
                            <img src="<?= e(upload_url($currentUser['avatar'])) ?>" alt="<?= e($currentUser['name']) ?>" class="h-full w-full object-cover">
                        <?php else: ?>
                            <span class="flex h-full w-full items-center justify-center text-xl font-extrabold text-forest-900" data-profile-initials><?= e(initials($currentUser['name'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 flex h-6 w-6 items-center justify-center rounded-full border-2 border-forest-900 <?= (int) $currentUser['is_verified'] ? 'bg-success text-white' : 'bg-white/20 text-white' ?>">
                        <?= icon((int) $currentUser['is_verified'] ? 'check' : 'clock', 'h-3 w-3') ?>
                    </span>
                </div>
                <div class="min-w-0">
                    <h2 class="text-2xl font-extrabold tracking-tight"><?= e($currentUser['name']) ?></h2>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-white/55">
                        <span class="inline-flex items-center gap-1 rounded-full border border-white/15 bg-white/5 px-2.5 py-1"><?= icon('user-check', 'h-3.5 w-3.5') ?> <?= e($currentUser['member_code'] ?? 'Member') ?></span>
                        <span><?= (int) $currentUser['is_verified'] ? 'Identitas terverifikasi' : 'Identitas belum diverifikasi' ?></span>
                    </div>
                </div>
                <div class="ml-auto hidden gap-2 sm:flex">
                    <a href="<?= e(url('wishlist.php')) ?>" class="btn btn-ghost btn-sm"><?= icon('heart', 'h-4 w-4') ?> Wishlist (<?= $wishlistCount ?>)</a>
                    <a href="<?= e(url('pesanan.php')) ?>" class="btn btn-accent btn-sm"><?= icon('clipboard', 'h-4 w-4') ?> Pesanan</a>
                </div>
            </div>
        </div>
        <div class="absolute inset-x-0 bottom-0 h-8 rounded-t-[1.7rem] bg-cream-50"></div>
    </section>

    <div class="container-app relative z-10 -mt-2">
        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
            <?php
            $statCards = [
                ['clipboard', 'Total pesanan', (string) $stats['bookings'], 'text-forest-700'],
                ['tent', 'Sedang aktif', (string) $stats['active'], 'text-success'],
                ['wallet', 'Total belanja', format_rupiah_short($stats['spent']), 'text-forest-700'],
                ['heart', 'Wishlist', (string) $stats['wishlist'], 'text-danger'],
            ];
            foreach ($statCards as $card): ?>
                <div class="card p-3.5">
                    <span class="stat-icon !h-9 !w-9 <?= $card[3] ?>"><?= icon($card[0], 'h-4 w-4') ?></span>
                    <p class="mt-2 text-[0.58rem] font-bold uppercase tracking-[0.1em] text-muted"><?= e($card[1]) ?></p>
                    <p class="mt-0.5 text-lg font-extrabold text-forest-900"><?= e($card[2]) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-[1.25fr_.75fr] lg:items-start">
            <div class="space-y-5 min-w-0">
                <form class="card p-4 sm:p-5" data-profile-form enctype="multipart/form-data">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="section-kicker">Data pribadi</p>
                            <h2 class="mt-1 text-base font-extrabold text-forest-900">Lengkapi profilmu</h2>
                            <p class="mt-1 text-xs text-muted">Data ini dipakai untuk konfirmasi pesanan dan pengembalian gear.</p>
                        </div>
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-mint-100 text-forest-700"><?= icon('user', 'h-4 w-4') ?></span>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="field-label" for="profile-name">Nama lengkap</label>
                            <input id="profile-name" class="input" name="name" required maxlength="120" value="<?= e($currentUser['name']) ?>">
                        </div>
                        <div>
                            <label class="field-label" for="profile-email">Email</label>
                            <input id="profile-email" class="input !bg-cream-100 !text-muted" value="<?= e($currentUser['email']) ?>" disabled>
                            <p class="helper mt-1">Email tidak dapat diubah. Hubungi admin jika perlu.</p>
                        </div>
                        <div>
                            <label class="field-label" for="profile-phone">Nomor WhatsApp</label>
                            <input id="profile-phone" class="input" name="phone" required value="<?= e($currentUser['phone']) ?>" inputmode="tel">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label" for="profile-address">Alamat</label>
                            <textarea id="profile-address" class="input min-h-[5rem]" name="address" rows="3" placeholder="Alamat lengkap untuk pengantaran jika diperlukan."><?= e($currentUser['address']) ?></textarea>
                        </div>
                        <div>
                            <label class="field-label" for="profile-identity">NIK / nomor identitas</label>
                            <input id="profile-identity" class="input" name="identity_number" value="<?= e($currentUser['identity_number']) ?>" inputmode="numeric" placeholder="Opsional, untuk verifikasi">
                        </div>
                        <div>
                            <label class="field-label" for="profile-avatar">Foto profil</label>
                            <input id="profile-avatar" class="input text-xs" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-preview-input="[data-profile-avatar]">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label" for="profile-identity-photo">Foto identitas <span class="font-semibold text-muted">(opsional)</span></label>
                            <input id="profile-identity-photo" class="input text-xs" type="file" name="identity_photo" accept="image/jpeg,image/png,image/webp">
                            <p class="helper mt-1">KTP/SIM difoto jelas. Data hanya dipakai untuk verifikasi penyewaan.</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col-reverse gap-2 border-t border-line pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[0.62rem] text-muted">Terakhir masuk <?= e(format_datetime_id($currentUser['last_login_at'])) ?></p>
                        <button type="submit" class="btn btn-primary btn-sm">Simpan perubahan</button>
                    </div>
                </form>

                <form class="card p-4 sm:p-5" data-password-form>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="section-kicker">Keamanan</p>
                            <h2 class="mt-1 text-base font-extrabold text-forest-900">Ganti kata sandi</h2>
                        </div>
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-cream-100 text-forest-700"><?= icon('lock', 'h-4 w-4') ?></span>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="field-label" for="current-password">Kata sandi saat ini</label>
                            <input id="current-password" class="input" type="password" name="current_password" autocomplete="current-password" required>
                        </div>
                        <div>
                            <label class="field-label" for="new-password">Kata sandi baru</label>
                            <input id="new-password" class="input" type="password" name="new_password" autocomplete="new-password" minlength="8" required>
                        </div>
                        <div>
                            <label class="field-label" for="confirm-password">Ulangi kata sandi</label>
                            <input id="confirm-password" class="input" type="password" name="new_password_confirmation" autocomplete="new-password" minlength="8" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline btn-sm mt-3">Perbarui kata sandi</button>
                </form>

                <?php if ($recent): ?>
                    <section class="card p-4 sm:p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="section-kicker">Aktivitas terakhir</p>
                                <h2 class="mt-1 text-base font-extrabold text-forest-900">Pesanan terbaru</h2>
                            </div>
                            <a href="<?= e(url('pesanan.php')) ?>" class="text-[0.68rem] font-extrabold text-forest-700">Semua pesanan</a>
                        </div>
                        <div class="mt-3 space-y-2">
                            <?php foreach ($recent as $booking): ?>
                                <a href="<?= e(url('pesanan_detail.php?id=' . $booking['id'])) ?>" class="flex items-center gap-3 rounded-xl border border-line p-2.5 transition hover:bg-cream-50">
                                    <div class="h-11 w-11 shrink-0 overflow-hidden rounded-lg bg-cream-100">
                                        <img src="<?= e(upload_url($booking['cover_image'])) ?>" alt="" class="h-full w-full object-cover">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-extrabold text-forest-900"><?= e($booking['code']) ?></p>
                                        <p class="text-[0.62rem] text-muted"><?= e(format_date_id($booking['start_date'])) ?> · <?= e(format_rupiah($booking['total'])) ?></p>
                                    </div>
                                    <span class="badge <?= e(status_class($booking['status'])) ?>"><?= e(status_label($booking['status'], $booking['pickup_method'] ?? null)) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="space-y-4 min-w-0">
                <div class="card p-5">
                    <h2 class="text-sm font-extrabold text-forest-900">Verifikasi identitas</h2>
                    <div class="mt-3 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full <?= (int) $currentUser['is_verified'] ? 'bg-mint-100 text-success' : 'bg-amber-50 text-star' ?>"><?= icon((int) $currentUser['is_verified'] ? 'check-circle' : 'alert-circle', 'h-5 w-5') ?></span>
                        <div>
                            <p class="text-xs font-extrabold text-forest-900"><?= (int) $currentUser['is_verified'] ? 'Terverifikasi' : 'Belum terverifikasi' ?></p>
                            <p class="mt-0.5 text-[0.62rem] text-muted"><?= (int) $currentUser['is_verified'] ? 'Kamu bisa menyewa tanpa verifikasi tambahan.' : 'Unggah identitas agar proses ambil gear lebih cepat.' ?></p>
                        </div>
                    </div>
                    <?php if (!empty($currentUser['identity_photo'])): ?>
                        <a href="<?= e(upload_url($currentUser['identity_photo'])) ?>" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-[0.62rem] font-bold text-forest-600"><?= icon('image', 'h-3.5 w-3.5') ?> Lihat foto identitas</a>
                    <?php endif; ?>
                </div>

                <div class="rounded-[1.4rem] bg-forest-900 p-5 text-white">
                    <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.16em] text-mint-300">Butuh bantuan?</p>
                    <p class="mt-1.5 text-sm font-extrabold">Admin Rentify siap membantu</p>
                    <p class="mt-1 text-xs leading-relaxed text-white/55">Ubah nomor, verifikasi identitas, atau tanya soal pesanan lewat WhatsApp.</p>
                    <a href="<?= e(phone_wa_link(setting('whatsapp', '6281234567890'), 'Halo Rentify, saya butuh bantuan terkait akun saya.')) ?>" target="_blank" rel="noopener" class="btn btn-accent btn-sm mt-3 w-full"><?= icon('whatsapp', 'h-4 w-4') ?> Chat admin</a>
                </div>

                <a href="<?= e(url('logout.php')) ?>" class="btn btn-outline w-full !text-danger"><?= icon('logout', 'h-4 w-4') ?> Keluar dari akun</a>
            </aside>
        </div>
    </div>
</main>

<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
