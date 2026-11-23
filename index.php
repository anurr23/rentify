<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/item_card.php';

$featuredItems = [];
$categories = [];
$reviews = [];
$destinations = [];
try {
    $featuredItems = Item::featured(8);
    $categories = Category::allActive();
    $reviews = Review::recent(3);
    $destinations = Destination::allActive();
} catch (Throwable $exception) {
    error_log('Landing data error: ' . $exception->getMessage());
}

$pageTitle = 'Rentify Outdoor Karawang — Sewa Gear Camping & Hiking';
$metaDescription = 'Sewa tenda, carrier, sleeping bag, dan perlengkapan outdoor lainnya di Karawang. Gear bersih, harga transparan, antar cepat.';
$pageScripts = ['module:landing-3d.js'];
$currentUser = auth_user();
require ROOT_PATH . '/includes/header.php';
?>
<section class="relative isolate flex min-h-[100svh] flex-col overflow-hidden bg-forest-950 text-white grain">
    <div class="absolute inset-0 bg-cover bg-center opacity-45" style="background-image:url('<?= e(url('assets/img/hero-mountains.jpg')) ?>');"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-forest-950/60 via-forest-950/15 to-forest-950"></div>
    <canvas id="landing-canvas" class="landing-canvas" aria-hidden="true"></canvas>
    <div class="hero-vignette"></div>

    <header class="relative z-20 border-b border-white/10">
        <div class="container-app flex h-[4.8rem] items-center justify-between gap-4">
            <a href="<?= e(url('index.php')) ?>" class="inline-flex items-center gap-2.5 text-white">
                <?= logo_mark('h-10 w-10') ?>
                <span class="leading-tight">
                    <span class="block text-[0.98rem] font-extrabold tracking-[0.16em]">RENTIFY</span>
                    <span class="block text-[0.5rem] font-semibold tracking-[0.18em] text-white/55">GEAR UP, GO FURTHER</span>
                </span>
            </a>
            <nav class="hidden items-center gap-1 text-sm font-semibold text-white/70 md:flex">
                <a href="<?= e(url('katalog.php')) ?>" class="rounded-full px-3.5 py-2 transition hover:bg-white/10 hover:text-white">Katalog</a>
                <a href="<?= e(url('toko.php')) ?>" class="rounded-full px-3.5 py-2 transition hover:bg-white/10 hover:text-white">Toko</a>
                <a href="<?= e(url('rekomendasi.php')) ?>" class="rounded-full px-3.5 py-2 transition hover:bg-white/10 hover:text-white">AI Planner</a>
                <a href="#cara-sewa" class="rounded-full px-3.5 py-2 transition hover:bg-white/10 hover:text-white">Cara sewa</a>
                <a href="#cerita" class="rounded-full px-3.5 py-2 transition hover:bg-white/10 hover:text-white">Cerita</a>
            </nav>
            <div class="flex items-center gap-2">
                <a href="<?= e(url('katalog.php')) ?>" class="hidden text-sm font-bold text-white/80 transition hover:text-white sm:block">Cari gear</a>
                <a href="<?= e(url($currentUser ? ($currentUser['role'] === 'admin' ? 'admin/dashboard.php' : 'beranda.php') : 'login.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="<?= $currentUser ? 'Buka akun' : 'Masuk' ?>">
                    <?= icon('user', 'h-[1.1rem] w-[1.1rem]') ?>
                </a>
            </div>
        </div>
    </header>

    <div class="relative z-10 flex flex-1 flex-col justify-end pb-8 pt-16 sm:pb-12">
        <div class="container-app">
            <div class="max-w-3xl">
                <p class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/8 px-3.5 py-1.5 text-[0.65rem] font-extrabold uppercase tracking-[0.18em] text-mint-300 backdrop-blur">
                    <span class="h-1.5 w-1.5 rounded-full bg-success"></span>
                    Basecamp Karawang · Sejak 2019
                </p>
                <h1 class="mt-5 text-balance text-[clamp(2.5rem,6.5vw,5.4rem)] font-extrabold leading-[0.98] tracking-[-0.045em]">
                    Lengkapi Setiap<br>
                    <span class="text-mint-300">Langkah Pendakian.</span>
                </h1>
                <p class="mt-5 max-w-xl text-pretty text-sm leading-relaxed text-white/65 sm:text-base">
                    Temukan, sewa, dan jelajahi perlengkapan outdoor berkualitas tinggi untuk petualangan puncak Anda berikutnya. Semua gear dicuci, dicek, dan siap pakai.
                </p>

                <div class="mt-7 flex flex-wrap items-center gap-2.5">
                    <button type="button" id="sound-toggle" class="icon-btn icon-btn-dark h-[3.35rem] w-[3.35rem]" aria-label="Nyalakan suara ambience" aria-pressed="false">
                        <span data-sound-on><?= icon('volume', 'h-5 w-5') ?></span>
                        <span data-sound-off class="hidden"><?= icon('volume-off', 'h-5 w-5') ?></span>
                    </button>
                    <a href="<?= e(url('beranda.php')) ?>" class="btn btn-accent btn-lg group min-w-[13rem] justify-between">
                        Mulai Petualangan
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-forest-900 text-mint-300 transition group-hover:translate-x-0.5"><?= icon('arrow-right', 'h-4 w-4') ?></span>
                    </a>
                    <a href="<?= e(url($currentUser ? 'profil.php' : 'login.php')) ?>" class="hidden items-center gap-2.5 rounded-full border border-white/20 bg-white/5 py-2 pl-2 pr-4 text-left backdrop-blur transition hover:bg-white/10 sm:flex">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/12"><?= icon('user', 'h-4 w-4') ?></span>
                        <span class="leading-tight">
                            <span class="block text-[0.62rem] text-white/50"><?= $currentUser ? 'Selamat datang' : 'Punya akun?' ?></span>
                            <span class="block text-xs font-bold"><?= $currentUser ? e(explode(' ', $currentUser['name'])[0]) : 'Masuk / Daftar' ?></span>
                        </span>
                    </a>
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-x-7 gap-y-3 text-xs font-semibold text-white/55">
                    <span class="inline-flex items-center gap-2"><?= icon('shield', 'h-4 w-4 text-mint-300') ?> Peralatan bersih & terawat</span>
                    <span class="inline-flex items-center gap-2"><?= icon('truck', 'h-4 w-4 text-mint-300') ?> Antar-jemput Karawang</span>
                    <span class="inline-flex items-center gap-2"><?= icon('whatsapp', 'h-4 w-4 text-mint-300') ?> Support lokal 24/7</span>
                </div>
            </div>
        </div>

        <div class="container-app mt-10 flex items-end justify-between">
            <div class="flex items-center gap-5 text-[0.62rem] font-bold uppercase tracking-[0.16em] text-white/35">
                <span class="hidden sm:inline">SCROLL</span>
                <span class="h-10 w-px bg-gradient-to-b from-transparent via-white/40 to-transparent"></span>
            </div>
            <div class="flex items-center gap-2 text-[0.62rem] font-semibold text-white/40">
                <?= icon('map-pin', 'h-3.5 w-3.5') ?> Karawang, Jawa Barat
            </div>
        </div>
    </div>
</section>

<section class="border-b border-line bg-white">
    <div class="container-app grid grid-cols-2 divide-x divide-line py-7 sm:grid-cols-4">
        <?php
        $stats = [
            ['value' => count($categories) . '+', 'label' => 'Kategori gear'],
            ['value' => number_format(array_sum(array_map(static fn ($c) => (int) $c['item_count'], $categories))) . '+', 'label' => 'Unit siap sewa'],
            ['value' => '500+', 'label' => 'Petualang lokal'],
            ['value' => '4.9/5', 'label' => 'Rating pelanggan'],
        ];
        foreach ($stats as $index => $stat): ?>
            <div class="px-3 text-center sm:px-6 <?= $index % 2 !== 0 ? 'border-l-0 sm:border-l' : '' ?> <?= $index >= 2 ? 'mt-6 border-t border-line pt-6 sm:mt-0 sm:border-t-0 sm:pt-0' : '' ?>">
                <p class="text-2xl font-extrabold tracking-tight text-forest-900 sm:text-3xl"><?= e($stat['value']) ?></p>
                <p class="mt-1 text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-muted"><?= e($stat['label']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<main id="main">
    <section class="bg-cream-50 py-16 sm:py-20">
        <div class="container-app">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="reveal">
                    <p class="section-kicker">Pilihan editor</p>
                    <h2 class="section-title mt-2">Gear yang paling dicari<br class="hidden sm:block"> para petualang.</h2>
                </div>
                <a href="<?= e(url('katalog.php')) ?>" class="btn btn-outline btn-sm reveal">Lihat semua <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-3.5 sm:gap-5 lg:grid-cols-4">
                <?php foreach (array_slice($featuredItems, 0, 8) as $item): ?>
                    <div class="reveal"><?= item_card($item) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="relative overflow-hidden bg-forest-900 py-16 text-white sm:py-20">
        <div class="absolute inset-0 bg-radial-forest"></div>
        <div class="container-app relative grid items-center gap-10 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="reveal">
                <span class="inline-flex items-center gap-2 rounded-full border border-mint-300/30 bg-mint-300/10 px-3.5 py-1.5 text-[0.65rem] font-extrabold uppercase tracking-[0.16em] text-mint-300">
                    <?= icon('sparkles', 'h-3.5 w-3.5') ?> AI Gear Planner
                </span>
                <h2 class="mt-5 text-balance text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Bingung bawa apa?<br>Biar AI kami yang menyusun.</h2>
                <p class="mt-4 max-w-lg text-sm leading-relaxed text-white/60">
                    Pilih aktivitas, destinasi, dan durasi. Rentify menyusun daftar gear yang wajib dan opsional — lengkap dengan alasan dan estimasi biaya. Semua bisa langsung masuk keranjang.
                </p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="<?= e(url('rekomendasi.php')) ?>" class="btn btn-accent">Coba AI Planner <?= icon('arrow-right', 'h-4 w-4') ?></a>
                    <a href="<?= e(url('katalog.php')) ?>" class="btn btn-ghost">Jelajahi katalog</a>
                </div>
                <div class="mt-8 grid max-w-lg grid-cols-3 gap-3 text-center">
                    <?php foreach ([['Tektok', 'Ringan & cepat'], ['Camping', 'Lengkap & nyaman'], ['Ekspedisi', 'Siap medan berat']] as $activity): ?>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <p class="text-xs font-extrabold text-mint-300"><?= e($activity[0]) ?></p>
                            <p class="mt-1 text-[0.62rem] text-white/45"><?= e($activity[1]) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="reveal">
                <div class="relative mx-auto max-w-md rounded-[2rem] border border-white/12 bg-forest-950/70 p-4 shadow-float backdrop-blur">
                    <div class="flex items-center gap-3 border-b border-white/10 pb-4">
                        <span class="ai-orb"><?= icon('sparkles', 'h-5 w-5') ?></span>
                        <div>
                            <p class="text-sm font-extrabold">Rentify Gear Planner</p>
                            <p class="flex items-center gap-1.5 text-[0.65rem] text-success"><span class="status-dot"></span> Online · siap membantu</p>
                        </div>
                        <span class="ml-auto rounded-full bg-white/8 px-2.5 py-1 text-[0.58rem] font-bold text-white/50">AI</span>
                    </div>
                    <div class="space-y-3 py-4">
                        <div class="ml-auto max-w-[82%] rounded-2xl rounded-tr-md bg-mint-300 px-3.5 py-2.5 text-xs font-semibold text-forest-900">
                            Camping 2 hari di Gunung Sanggabuana, berdua. Lagi musim hujan.
                        </div>
                        <div class="max-w-[90%] rounded-2xl rounded-tl-md border border-white/10 bg-white/6 px-3.5 py-3 text-xs leading-relaxed text-white/70">
                            <p class="mb-2 font-bold text-white">Rekomendasi wajib untukmu:</p>
                            <ul class="space-y-1.5">
                                <li class="flex items-center gap-2"><?= icon('tent', 'h-3.5 w-3.5 text-mint-300') ?> Tenda Dome 4P — nyaman saat hujan</li>
                                <li class="flex items-center gap-2"><?= icon('sleep', 'h-3.5 w-3.5 text-mint-300') ?> 2 sleeping bag comfort 2°C</li>
                                <li class="flex items-center gap-2"><?= icon('lamp', 'h-3.5 w-3.5 text-mint-300') ?> Headlamp 1200 lumens</li>
                                <li class="flex items-center gap-2"><?= icon('stove', 'h-3.5 w-3.5 text-mint-300') ?> Kompor ultralight + cookset</li>
                            </ul>
                            <div class="mt-3 rounded-xl bg-forest-900/80 px-3 py-2 text-[0.68rem]">
                                <span class="text-white/45">Estimasi 2 hari:</span>
                                <span class="ml-1 font-extrabold text-mint-300">Rp 285.000</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2.5 text-[0.68rem] text-white/35">
                        <span class="chat-typing flex items-center gap-0.5"><span></span><span></span><span></span></span>
                        Menyusun gear terbaik untukmu...
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white py-16 sm:py-20">
        <div class="container-app">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="reveal">
                    <p class="section-kicker">Kategori lengkap</p>
                    <h2 class="section-title mt-2">Dari tenda sampai headlamp.</h2>
                    <p class="mt-2 max-w-xl text-sm text-muted">Semua kebutuhan outdoor dalam satu basecamp. Pilih kategori, temukan gear yang pas.</p>
                </div>
                <a href="<?= e(url('katalog.php')) ?>" class="text-sm font-extrabold text-forest-900 hover:text-forest-600">Semua kategori →</a>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-3.5 md:grid-cols-3 lg:grid-cols-4">
                <?php foreach ($categories as $index => $category): ?>
                    <a href="<?= e(url('katalog.php?category=' . $category['slug'])) ?>" class="reveal group <?= $index === 0 ? 'col-span-2 row-span-2 md:col-span-2' : '' ?>">
                        <div class="relative overflow-hidden rounded-[1.35rem] <?= $index === 0 ? 'h-full min-h-[22rem]' : 'aspect-[4/3]' ?>">
                            <img src="<?= e(upload_url($category['hero_image'])) ?>" alt="<?= e($category['name']) ?>" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-forest-950/90 via-forest-950/15 to-transparent"></div>
                            <span class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition group-hover:bg-mint-300 group-hover:text-forest-900"><?= icon('arrow-up-right', 'h-4 w-4') ?></span>
                            <div class="absolute inset-x-0 bottom-0 p-4">
                                <p class="text-[0.58rem] font-bold uppercase tracking-[0.14em] text-mint-300"><?= (int) $category['item_count'] ?> unit siap sewa</p>
                                <h3 class="mt-1 <?= $index === 0 ? 'text-xl sm:text-2xl' : 'text-sm' ?> font-extrabold text-white"><?= e($category['name']) ?></h3>
                                <?php if ($index === 0): ?>
                                    <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-white/55"><?= e($category['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="cara-sewa" class="bg-cream-200 py-16 sm:py-20">
        <div class="container-app">
            <div class="mx-auto max-w-2xl text-center reveal">
                <p class="section-kicker">Semudah tiga langkah</p>
                <h2 class="section-title mt-2">Dari klik ke basecamp.</h2>
                <p class="mt-3 text-sm text-muted">Tanpa antre, tanpa ribet. Pesan dari mana saja, ambil di Karawang atau minta diantar.</p>
            </div>
            <div class="mt-10 grid gap-4 md:grid-cols-3">
                <?php
                $steps = [
                    ['icon' => 'search', 'title' => 'Pilih gear', 'text' => 'Jelajahi katalog atau minta rekomendasi AI sesuai aktivitas dan destinasi.', 'number' => '01'],
                    ['icon' => 'calendar', 'title' => 'Atur tanggal', 'text' => 'Tentukan tanggal sewa, varian ukuran, dan metode pengambilan.', 'number' => '02'],
                    ['icon' => 'backpack', 'title' => 'Siap berangkat', 'text' => 'Bayar via transfer/QRIS, lalu ambil gear yang sudah dicek dan dibersihkan.', 'number' => '03'],
                ];
                foreach ($steps as $step): ?>
                    <div class="reveal relative overflow-hidden rounded-[1.35rem] border border-line bg-white p-6 shadow-card">
                        <span class="absolute right-5 top-4 text-4xl font-extrabold tracking-tighter text-cream-300"><?= e($step['number']) ?></span>
                        <span class="stat-icon"><?= icon($step['icon'], 'h-5 w-5') ?></span>
                        <h3 class="mt-4 text-base font-extrabold text-forest-900"><?= e($step['title']) ?></h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted"><?= e($step['text']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="bg-white py-16 sm:py-20" id="destinasi">
        <div class="container-app">
            <div class="grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">
                <div class="reveal">
                    <p class="section-kicker">Jelajahi sekitar</p>
                    <h2 class="section-title mt-2">Destinasi favorit<br>dari Karawang.</h2>
                    <p class="mt-3 max-w-md text-sm leading-relaxed text-muted">Dari hutan Sanggabuana sampai savana Puncak Sempur. Pilih destinasi, kami bantu siapkan gear yang tepat.</p>
                    <a href="<?= e(url('rekomendasi.php')) ?>" class="btn btn-primary mt-6">Rencanakan perjalanan <?= icon('arrow-right', 'h-4 w-4') ?></a>
                    <div class="mt-8 flex items-center gap-4 rounded-2xl border border-line bg-cream-50 p-4">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-mint-300"><?= icon('compass', 'h-6 w-6') ?></span>
                        <div>
                            <p class="text-sm font-extrabold text-forest-900">Baru pertama kali mendaki?</p>
                            <p class="mt-0.5 text-xs text-muted">Tim kami siap bantu pilih gear via WhatsApp.</p>
                        </div>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <?php foreach (array_slice($destinations, 0, 4) as $index => $destination): ?>
                        <a href="<?= e(url('rekomendasi.php?destination=' . $destination['id'])) ?>" class="reveal group rounded-[1.35rem] border border-line bg-cream-50 p-5 transition hover:border-mint-300 hover:bg-mint-100/40 <?= $index === 0 ? 'sm:row-span-2 sm:flex sm:flex-col sm:justify-between' : '' ?>">
                            <div class="flex items-start justify-between gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-forest-700 shadow-sm"><?= icon('mountain', 'h-5 w-5') ?></span>
                                <span class="text-[0.62rem] font-bold uppercase tracking-[0.1em] text-muted"><?= $destination['elevation_m'] ? (int) $destination['elevation_m'] . ' mdpl' : 'Karawang' ?></span>
                            </div>
                            <div class="<?= $index === 0 ? 'sm:mt-16' : 'mt-6' ?>">
                                <h3 class="text-sm font-extrabold text-forest-900"><?= e($destination['name']) ?></h3>
                                <p class="mt-1 text-xs text-muted"><?= e($destination['terrain']) ?></p>
                                <span class="mt-3 inline-flex items-center gap-1 text-[0.68rem] font-extrabold text-forest-700">Lihat rekomendasi <?= icon('arrow-right', 'h-3 w-3 transition group-hover:translate-x-0.5') ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section id="cerita" class="bg-cream-50 py-16 sm:py-20">
        <div class="container-app">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="reveal">
                    <p class="section-kicker">Cerita petualang</p>
                    <h2 class="section-title mt-2">Mereka sudah kembali<br class="hidden sm:block"> dengan cerita.</h2>
                </div>
                <div class="flex items-center gap-2 text-sm font-bold text-forest-900"><?= icon('star-filled', 'h-5 w-5 text-star') ?> 4.9 dari 500+ ulasan</div>
            </div>
            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <?php if ($reviews): ?>
                    <?php foreach ($reviews as $review): ?>
                        <figure class="reveal flex flex-col rounded-[1.35rem] border border-line bg-white p-5 shadow-card">
                            <div class="flex gap-0.5 text-star"><?= str_repeat(icon('star-filled', 'h-3.5 w-3.5'), (int) $review['rating']) ?></div>
                            <blockquote class="mt-3 flex-1 text-sm leading-relaxed text-forest-700">“<?= e($review['comment']) ?>”</blockquote>
                            <figcaption class="mt-5 flex items-center gap-2.5 border-t border-line pt-4">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-mint-300 text-xs font-extrabold text-forest-900"><?= e(initials($review['user_name'])) ?></span>
                                <div>
                                    <p class="text-xs font-extrabold text-forest-900"><?= e($review['user_name']) ?></p>
                                    <p class="text-[0.62rem] text-muted">Penyewa <?= e($review['item_name']) ?></p>
                                </div>
                            </figcaption>
                        </figure>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-sm text-muted">Ulasan akan muncul di sini.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="relative overflow-hidden bg-forest-900 py-16 text-white sm:py-20">
        <div class="absolute inset-0 bg-cover bg-center opacity-20" style="background-image:url('<?= e(url('assets/img/hero-mountains.jpg')) ?>');"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-forest-950 via-forest-950/80 to-transparent"></div>
        <div class="container-app relative">
            <div class="max-w-xl reveal">
                <p class="section-kicker text-mint-300">Siap berangkat?</p>
                <h2 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Petualangan berikutnya<br>sudah menunggu.</h2>
                <p class="mt-4 text-sm leading-relaxed text-white/60">Pesan sekarang, ambil di basecamp Karawang Kota atau minta diantar. Gear sudah dicuci dan dicek sebelum sampai di tangan Anda.</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="<?= e(url('katalog.php')) ?>" class="btn btn-accent">Mulai sewa sekarang <?= icon('arrow-right', 'h-4 w-4') ?></a>
                    <a href="<?= e(phone_wa_link('6281234567890', 'Halo Rentify, saya mau tanya gear untuk pendakian.')) ?>" target="_blank" rel="noopener" class="btn btn-ghost"><?= icon('whatsapp', 'h-4 w-4') ?> Chat admin</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require ROOT_PATH . '/includes/footer.php'; ?>
