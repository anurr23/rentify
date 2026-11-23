<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/item_card.php';

$currentUser = auth_user();
$categories = Category::allActive();
$trending = Item::trending(8);
$popular = Item::search(['sort' => 'rating', 'per_page' => 8])['items'];
$upcoming = $currentUser ? Booking::upcomingForUser((int) $currentUser['id'], 1) : [];
$activeBooking = $upcoming[0] ?? null;
$heroImage = $categories[0]['hero_image'] ?? null;
$firstName = $currentUser ? explode(' ', $currentUser['name'])[0] : 'Petualang';
$userInitials = $currentUser ? initials($currentUser['name']) : 'P';
$homeStore = current_store($currentUser);
$nearestStore = null;
if ($currentUser && $currentUser['last_latitude'] !== null && $currentUser['last_longitude'] !== null) {
    $nearestList = Store::nearest((float) $currentUser['last_latitude'], (float) $currentUser['last_longitude'], 1);
    $nearestStore = $nearestList[0] ?? null;
}
$homeStore = $nearestStore ?: $homeStore;

$pageTitle = ($currentUser ? 'Halo, ' . $firstName : 'Beranda') . ' — Rentify Outdoor Karawang';
$metaDescription = 'Temukan gear outdoor terbaik di Karawang. Rekomendasi AI, katalog lengkap, dan pemesanan online.';
$activeNav = 'beranda';
$bodyClass = 'pb-16 lg:pb-0';
$pageScripts = ['location.js'];
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';
?>
<main id="main">
    <section class="app-hero">
        <div class="absolute inset-0 bg-cover bg-center opacity-40" style="background-image:url('<?= e(upload_url($heroImage)) ?>');"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-forest-950/55 via-forest-950/50 to-forest-900"></div>

        <div class="container-app relative pb-8 pt-5 sm:pb-10 sm:pt-7">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <?php if ($currentUser): ?>
                        <a href="<?= e(url('profil.php')) ?>" class="relative">
                            <?php if (!empty($currentUser['avatar'])): ?>
                                <img src="<?= e(upload_url($currentUser['avatar'])) ?>" alt="<?= e($currentUser['name']) ?>" class="h-12 w-12 rounded-full border-2 border-white/30 object-cover">
                            <?php else: ?>
                                <span class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/25 bg-mint-300 text-sm font-extrabold text-forest-900"><?= e($userInitials) ?></span>
                            <?php endif; ?>
                            <span class="absolute bottom-0 right-0 h-3.5 w-3.5 rounded-full border-2 border-forest-900 bg-success"></span>
                        </a>
                        <div class="hidden sm:block">
                            <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-white/45">Member aktif</p>
                            <p class="text-xs font-bold text-white/80"><?= e($currentUser['member_code'] ?? 'Rentify Member') ?></p>
                        </div>
                    <?php else: ?>
                        <a href="<?= e(url('login.php')) ?>" class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/25 bg-white/10 text-white"><?= icon('user', 'h-5 w-5') ?></a>
                        <div class="hidden sm:block">
                            <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-white/45">Selamat datang</p>
                            <p class="text-xs font-bold text-white/80">Masuk untuk mulai menyewa</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-2">
                    <a href="<?= e(url('keranjang.php')) ?>" class="relative flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-white/8 text-white backdrop-blur" aria-label="Keranjang">
                        <?= icon('cart', 'h-5 w-5') ?>
                        <span class="nav-badge <?= $cartCount ? '' : 'hidden' ?>" data-cart-count><?= $cartCount ?></span>
                    </a>
                    <details class="relative">
                        <summary class="relative flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-full border border-white/15 bg-white/8 text-white backdrop-blur" aria-label="Notifikasi">
                            <?= icon('bell', 'h-5 w-5') ?>
                            <span class="absolute right-2.5 top-2.5 h-2 w-2 rounded-full bg-mint-300 <?= $unreadNotifications ? '' : 'hidden' ?>" data-notif-dot></span>
                        </summary>
                        <div class="absolute right-0 z-30 mt-2 w-[min(20rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-line bg-white text-ink shadow-float">
                            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                                <p class="text-sm font-extrabold text-forest-900">Notifikasi</p>
                                <?php if ($currentUser): ?><button type="button" class="text-[0.68rem] font-bold text-forest-500" data-notif-read-all>Baca semua</button><?php endif; ?>
                            </div>
                            <div class="max-h-80 overflow-y-auto" data-notif-list>
                                <?php if (!$navNotifications): ?>
                                    <div class="px-4 py-8 text-center">
                                        <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-cream-100 text-muted"><?= icon('bell', 'h-5 w-5') ?></span>
                                        <p class="mt-2 text-xs text-muted">Belum ada notifikasi baru.</p>
                                    </div>
                                <?php endif; ?>
                                <?php foreach ($navNotifications as $notification): ?>
                                    <a href="<?= e(url($notification['link'] ?: ($currentUser ? 'pesanan.php' : 'login.php'))) ?>" class="block border-b border-line/70 px-4 py-3 transition hover:bg-cream-50">
                                        <div class="flex items-start gap-2.5">
                                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-mint-100 text-forest-700"><?= icon($notification['type'] === 'booking' ? 'clipboard' : ($notification['type'] === 'payment' ? 'credit-card' : 'bell'), 'h-4 w-4') ?></span>
                                            <div class="min-w-0">
                                                <p class="text-xs font-extrabold text-forest-900"><?= e($notification['title']) ?></p>
                                                <p class="mt-0.5 line-clamp-2 text-[0.7rem] text-muted"><?= e($notification['message']) ?></p>
                                                <p class="mt-1 text-[0.6rem] text-muted"><?= e(time_ago($notification['created_at'])) ?></p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </details>
                </div>
            </div>

            <div class="mt-7">
                <p class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-mint-300">Petualanganmu dimulai di sini</p>
                <h1 class="mt-1.5 text-3xl font-extrabold tracking-tight sm:text-4xl">Hello <?= e($firstName) ?> !</h1>
                <p class="mt-1 text-sm text-white/55">Siap jelajahi alam terbuka hari ini?</p>
            </div>

            <form action="<?= e(url('katalog.php')) ?>" method="get" class="hero-search mt-5" role="search">
                <span class="text-white/55"><?= icon('search', 'h-[1.15rem] w-[1.15rem]') ?></span>
                <input type="search" name="q" placeholder="Cari alat apa?" aria-label="Cari alat" autocomplete="off">
                <a href="<?= e(url('katalog.php')) ?>" class="search-submit" aria-label="Buka filter katalog"><?= icon('sliders', 'h-[1.1rem] w-[1.1rem]') ?></a>
            </form>

            <a href="<?= e(url('rekomendasi.php')) ?>" class="mt-3 flex items-center gap-3 rounded-[1.15rem] border border-white/15 bg-white/10 p-3 backdrop-blur transition hover:bg-white/15">
                <span class="ai-orb !h-11 !w-11"><?= icon('sparkles', 'h-5 w-5') ?></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-extrabold">AI Gear Recommender</p>
                    <p class="truncate text-[0.68rem] text-white/50">Temukan perlengkapan terbaik sesuai destinasi</p>
                </div>
                <span class="btn btn-accent btn-sm hidden shrink-0 sm:inline-flex">Coba Sekarang <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span>
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-mint-300 text-forest-900 sm:hidden"><?= icon('arrow-right', 'h-4 w-4') ?></span>
            </a>
        </div>

        <div class="relative -mb-5 h-6 rounded-t-[1.6rem] bg-cream-50"></div>
    </section>

    <div class="container-app relative z-10 -mt-1">
        <div class="no-scrollbar -mx-5 flex gap-2 overflow-x-auto px-5 py-4 sm:mx-0 sm:flex-wrap sm:px-0">
            <a href="<?= e(url('katalog.php')) ?>" class="chip chip-active shrink-0"><?= icon('grid', 'h-3.5 w-3.5') ?> Semua</a>
            <?php foreach ($categories as $category): ?>
                <a href="<?= e(url('katalog.php?category=' . $category['slug'])) ?>" class="chip shrink-0">
                    <?= icon($category['icon'] ?: 'package', 'h-3.5 w-3.5') ?> <?= e($category['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($activeBooking): ?>
        <section class="container-app mt-2">
            <a href="<?= e(url('pesanan_detail.php?id=' . $activeBooking['id'])) ?>" class="group relative block overflow-hidden rounded-[1.35rem] bg-forest-900 p-5 text-white shadow-soft">
                <div class="absolute -right-8 -top-10 h-32 w-32 rounded-full border border-mint-300/20"></div>
                <div class="absolute -right-2 -top-4 h-20 w-20 rounded-full border border-mint-300/15"></div>
                <div class="relative flex flex-wrap items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mint-300 text-forest-900"><?= icon('tent', 'h-6 w-6') ?></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[0.62rem] font-bold uppercase tracking-[0.16em] text-mint-300">Pesanan mendatang</p>
                        <p class="mt-1 truncate text-sm font-extrabold"><?= e($activeBooking['code']) ?> · <?= e(status_label($activeBooking['status'])) ?></p>
                        <p class="mt-0.5 text-xs text-white/50"><?= e(format_date_id($activeBooking['start_date'])) ?> — <?= e(format_date_id($activeBooking['end_date'])) ?> · <?= (int) $activeBooking['duration_days'] ?> hari</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 transition group-hover:bg-mint-300 group-hover:text-forest-900"><?= icon('arrow-right', 'h-4 w-4') ?></span>
                </div>
            </a>
        </section>
    <?php endif; ?>

    <section class="container-app mt-4" <?= (!$nearestStore && empty($currentUser['preferred_store_id'])) ? 'data-location-auto' : '' ?>>
        <div class="relative overflow-hidden rounded-[1.35rem] border border-line bg-white p-4 shadow-soft sm:p-5">
            <div class="absolute -right-8 -top-10 h-28 w-28 rounded-full bg-mint-100/70 blur-xl"></div>
            <div class="relative flex flex-wrap items-center gap-3.5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-forest-900 text-mint-300"><?= icon('map-pin', 'h-5 w-5') ?></span>
                <div class="min-w-0 flex-1">
                    <p class="text-[0.6rem] font-extrabold uppercase tracking-[0.14em] text-forest-500">Cabang terdekat</p>
                    <p class="mt-0.5 truncate text-sm font-extrabold text-forest-900" data-nearest-store-name><?= e($homeStore['name'] ?? 'Rentify Karawang Kota') ?></p>
                    <p class="mt-0.5 truncate text-[0.68rem] text-muted"><span data-nearest-store-address><?= e($homeStore['address'] ?? '') ?></span> · <span data-nearest-store-distance><?= $nearestStore ? Store::distanceLabel((float) $nearestStore['distance_km']) . ' dari lokasimu' : 'Aktifkan lokasi untuk jarak' ?></span></p>
                    <p class="mt-1 flex items-center gap-1 text-[0.62rem] font-semibold text-forest-700" data-location-status>
                        <?= icon('map-pin', 'h-3 w-3') ?> <?= $nearestStore ? 'Lokasi aktif · rekomendasi cabang terdekat' : 'Aktifkan lokasi untuk rekomendasi cabang terdekat' ?>
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <button type="button" class="btn btn-outline btn-sm" data-use-location><?= icon('compass', 'h-3.5 w-3.5') ?> Lokasi saya</button>
                    <a href="<?= e(url('toko.php')) ?>" class="btn btn-primary btn-sm">Semua toko <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
                </div>
            </div>
        </div>
    </section>

    <section class="container-app mt-7">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold tracking-tight text-forest-900 sm:text-2xl">Top trends</h2>
                <p class="mt-1 text-xs text-muted">Paling sering disewa pekan ini</p>
            </div>
            <a href="<?= e(url('katalog.php?sort=popular')) ?>" class="inline-flex items-center gap-1 text-xs font-extrabold text-forest-700">View all <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
        </div>
        <div class="scroll-row mt-4 lg:grid lg:grid-cols-4 lg:overflow-visible">
            <?php foreach (array_slice($trending, 0, 4) as $item): ?>
                <div class="w-[16.5rem] sm:w-[18rem] lg:w-auto"><?= item_card($item) ?></div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="container-app mt-9">
        <div class="relative overflow-hidden rounded-[1.5rem] border border-line bg-gradient-to-br from-mint-100 via-cream-50 to-white p-5 sm:p-7">
            <div class="absolute -right-10 -top-12 h-44 w-44 rounded-full bg-mint-300/40 blur-2xl"></div>
            <div class="relative grid gap-5 sm:grid-cols-[1.4fr_1fr] sm:items-center">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-[0.58rem] font-extrabold uppercase tracking-[0.12em] text-forest-700 shadow-sm"><?= icon('sparkles', 'h-3 w-3') ?> AI powered</span>
                    <h2 class="mt-3 text-xl font-extrabold leading-tight tracking-tight text-forest-900 sm:text-2xl">Gear apa yang cocok untukmu?</h2>
                    <p class="mt-2 max-w-md text-xs leading-relaxed text-muted sm:text-sm">Jawab 3 pertanyaan singkat, dapatkan daftar gear wajib & opsional lengkap dengan estimasi biaya.</p>
                    <a href="<?= e(url('rekomendasi.php')) ?>" class="btn btn-primary btn-sm mt-4">Mulai rekomendasi <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <?php foreach ([['footprint', 'Tektok'], ['tent', 'Camping'], ['mountain', 'Hiking']] as $activity): ?>
                        <div class="rounded-2xl border border-white bg-white/80 px-2 py-4 text-center shadow-sm backdrop-blur">
                            <span class="mx-auto flex h-9 w-9 items-center justify-center rounded-full bg-mint-100 text-forest-700"><?= icon($activity[0], 'h-4 w-4') ?></span>
                            <p class="mt-2 text-[0.66rem] font-extrabold text-forest-900"><?= e($activity[1]) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="container-app mt-9">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold tracking-tight text-forest-900 sm:text-2xl">Popular gear</h2>
                <p class="mt-1 text-xs text-muted">Rating tertinggi dari petualang</p>
            </div>
            <a href="<?= e(url('katalog.php?sort=rating')) ?>" class="inline-flex items-center gap-1 text-xs font-extrabold text-forest-700">View all <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-3.5 sm:gap-5 lg:grid-cols-4">
            <?php foreach (array_slice($popular, 0, 8) as $item): ?>
                <?= item_card($item) ?>
            <?php endforeach; ?>
        </div>
        <div class="mt-6 text-center">
            <a href="<?= e(url('katalog.php')) ?>" class="btn btn-outline btn-sm">Lihat katalog lengkap <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
        </div>
    </section>

    <section class="container-app mt-10">
        <div class="grid gap-3 sm:grid-cols-3">
            <?php
            $trust = [
                ['shield', 'Gear terverifikasi', 'Dicek & dicuci setiap kembali'],
                ['truck', 'Antar Karawang', 'Pickup point atau delivery'],
                ['whatsapp', 'Support lokal', 'Admin siap bantu via WhatsApp'],
            ];
            foreach ($trust as $item): ?>
                <div class="flex items-center gap-3 rounded-2xl border border-line bg-white p-4">
                    <span class="stat-icon !h-10 !w-10"><?= icon($item[0], 'h-4 w-4') ?></span>
                    <div>
                        <p class="text-xs font-extrabold text-forest-900"><?= e($item[1]) ?></p>
                        <p class="mt-0.5 text-[0.65rem] text-muted"><?= e($item[2]) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="container-app mt-10">
        <div class="relative overflow-hidden rounded-[1.6rem] bg-forest-900 px-6 py-8 text-white sm:px-9">
            <div class="absolute inset-0 topo-bg opacity-30"></div>
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-lg">
                    <p class="section-kicker text-mint-300">Basecamp Karawang</p>
                    <h2 class="mt-1.5 text-2xl font-extrabold leading-tight tracking-tight">Belum tahu harus mulai dari mana?</h2>
                    <p class="mt-2 text-sm text-white/55">Chat tim Rentify, ceritakan rencana perjalananmu. Kami bantu susun gear yang paling pas.</p>
                </div>
                <a href="<?= e(phone_wa_link(setting('whatsapp', '6281234567890'), 'Halo Rentify, saya butuh bantuan memilih gear.')) ?>" target="_blank" rel="noopener" class="btn btn-accent shrink-0"><?= icon('whatsapp', 'h-4 w-4') ?> Chat admin</a>
            </div>
        </div>
    </section>
</main>

<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
