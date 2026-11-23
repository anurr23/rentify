<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$activities = Activity::allActive();
$destinations = Destination::allActive();
$presetActivity = (string) ($_GET['activity'] ?? 'camping');
$presetDestination = (int) ($_GET['destination'] ?? 0);
$defaultStart = tomorrow();
$user = auth_user();
$aiStore = current_store($user);

$pageTitle = 'AI Gear Planner — Rentify Outdoor Karawang';
$metaDescription = 'Rekomendasi perlengkapan camping dan hiking personal dengan AI. Pilih aktivitas, destinasi, dan durasi, lalu dapatkan daftar gear wajib dan opsional.';
$activeNav = 'ai';
$bodyClass = 'pb-16 lg:pb-0';
$pageScripts = ['ai.js'];
require ROOT_PATH . '/includes/header.php';
require ROOT_PATH . '/includes/customer_nav.php';
?>
<main id="main" class="pb-16 lg:pb-24" data-ai-root>
    <section class="app-hero !pb-16">
        <div class="absolute inset-0 topo-bg opacity-20"></div>
        <div class="absolute -right-20 top-0 h-64 w-64 rounded-full bg-mint-300/10 blur-3xl"></div>
        <div class="container-app relative pt-3">
            <div class="flex items-center justify-between">
                <a href="<?= e(url('beranda.php')) ?>" class="icon-btn icon-btn-dark h-10 w-10" aria-label="Kembali"><?= icon('arrow-left', 'h-[1.1rem] w-[1.1rem]') ?></a>
                <div class="text-center">
                    <p class="text-[0.55rem] font-extrabold uppercase tracking-[0.2em] text-mint-300/70">Rentify Intelligence</p>
                    <h1 class="text-base font-extrabold tracking-tight">AI Gear Planner</h1>
                </div>
                <a href="<?= e(url('keranjang.php')) ?>" class="icon-btn icon-btn-dark relative h-10 w-10" aria-label="Keranjang">
                    <?= icon('cart', 'h-[1.1rem] w-[1.1rem]') ?>
                    <span class="nav-badge <?= cart_count() ? '' : 'hidden' ?>" data-cart-count><?= cart_count() ?></span>
                </a>
            </div>

            <div class="mx-auto mt-7 max-w-2xl text-center">
                <span class="ai-orb mx-auto !h-16 !w-16"><?= icon('sparkles', 'h-7 w-7') ?></span>
                <h2 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Rencana gear,<br><span class="text-mint-300">tanpa bingung.</span></h2>
                <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-white/55">Ceritakan perjalananmu. AI akan memilih gear yang paling relevan dari katalog Rentify, lengkap dengan prioritas wajib dan opsional.</p>
            </div>

            <div class="mx-auto mt-6 flex max-w-xl flex-wrap items-center justify-center gap-2 text-[0.62rem] font-semibold text-white/55">
                <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-1.5"><?= icon('shield', 'h-3 w-3') ?> Stok dicek real-time</span>
                <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-1.5"><?= icon('sparkles', 'h-3 w-3') ?> Gemini via 9router</span>
                <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-1.5"><?= icon('bolt', 'h-3 w-3') ?> Tetap jalan saat AI offline</span>
            </div>
        </div>
        <div class="absolute inset-x-0 bottom-0 h-8 rounded-t-[1.7rem] bg-cream-50"></div>
    </section>

    <div class="container-app relative -mt-4">
        <div class="grid gap-6 lg:grid-cols-[.92fr_1.08fr] lg:items-start">
            <form class="card p-5 sm:p-6 lg:sticky lg:top-24" data-ai-form novalidate>
                <input type="hidden" name="store_id" value="<?= (int) ($aiStore['id'] ?? 0) ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="section-kicker">Langkah 1</p>
                        <h2 class="mt-1 text-lg font-extrabold text-forest-900">Rencana perjalanan</h2>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-mint-100 text-forest-700"><?= icon('compass', 'h-4 w-4') ?></span>
                </div>
                <?php if ($aiStore): ?>
                    <p class="mt-2 flex items-center gap-1.5 rounded-xl bg-cream-50 px-3 py-2 text-[0.65rem] font-bold text-forest-600"><?= icon('map-pin', 'h-3.5 w-3.5') ?> Rekomendasi & stok dari <?= e($aiStore['name']) ?></p>
                <?php endif; ?>

                <div class="mt-4">
                    <p class="field-label">Aktivitas</p>
                    <div class="grid grid-cols-2 gap-2" data-ai-activities>
                        <?php foreach ($activities as $index => $activity): ?>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="activity_id" value="<?= (int) $activity['id'] ?>" class="peer sr-only" <?= ($presetActivity === $activity['slug'] || (!$presetActivity && $index === 0)) ? 'checked' : '' ?>>
                                <span class="flex h-full flex-col gap-2 rounded-2xl border border-line bg-cream-50 p-3 transition peer-checked:border-forest-700 peer-checked:bg-forest-900 peer-checked:text-white hover:border-sage-300">
                                    <span class="flex items-center gap-2">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-forest-700 shadow-sm"><?= icon($activity['icon'] ?: 'mountain', 'h-4 w-4') ?></span>
                                        <span class="text-xs font-extrabold"><?= e($activity['name']) ?></span>
                                    </span>
                                    <span class="text-[0.62rem] leading-relaxed text-muted peer-checked:text-white/55"><?= e($activity['description']) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="field-label" for="ai-destination">Destinasi</label>
                        <select id="ai-destination" class="select input" name="destination_id" data-ai-destination data-searchable>
                            <?php foreach ($destinations as $destination): ?>
                                <option value="<?= (int) $destination['id'] ?>" <?= $presetDestination === (int) $destination['id'] ? 'selected' : '' ?>><?= e($destination['name']) ?> — <?= e($destination['region']) ?></option>
                            <?php endforeach; ?>
                            <option value="0">Lainnya / ketik manual</option>
                        </select>
                        <input type="text" name="destination" class="input mt-2 hidden" placeholder="Tulis destinasi, mis. Gunung Papandayan" maxlength="150" data-ai-destination-text>
                    </div>
                    <div>
                        <label class="field-label" for="ai-start">Tanggal mulai</label>
                        <input id="ai-start" type="date" class="input" name="start_date" value="<?= e($defaultStart) ?>" min="<?= e(today()) ?>">
                    </div>
                    <div>
                        <label class="field-label" for="ai-days">Durasi (hari)</label>
                        <div class="stepper w-full justify-between !rounded-2xl !px-3" data-ai-days data-min="1" data-max="14">
                            <button type="button" data-step-dec aria-label="Kurangi hari"><?= icon('minus', 'h-3.5 w-3.5') ?></button>
                            <span data-step-value>2</span>
                            <button type="button" data-step-inc aria-label="Tambah hari"><?= icon('plus', 'h-3.5 w-3.5') ?></button>
                            <input type="hidden" name="days" value="2" data-step-input>
                        </div>
                        <p class="helper mt-1.5">2D1N = 2 hari (inklusif)</p>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="field-label" for="ai-participants">Jumlah peserta</label>
                        <div class="stepper w-full justify-between !rounded-2xl !px-3" data-ai-participants data-min="1" data-max="12">
                            <button type="button" data-step-dec aria-label="Kurangi peserta"><?= icon('minus', 'h-3.5 w-3.5') ?></button>
                            <span data-step-value>2</span>
                            <button type="button" data-step-inc aria-label="Tambah peserta"><?= icon('plus', 'h-3.5 w-3.5') ?></button>
                            <input type="hidden" name="participants" value="2" data-step-input>
                        </div>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="field-label" for="ai-notes">Catatan <span class="font-semibold text-muted">(opsional)</span></label>
                        <input id="ai-notes" class="input" name="notes" maxlength="300" placeholder="Suka hujan, bawa anak, budget...">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-5 w-full">
                    <?= icon('sparkles', 'h-4 w-4') ?> Buat rekomendasi AI
                </button>
                <p class="mt-2 text-center text-[0.62rem] text-muted">AI hanya memilih dari katalog Rentify. Harga & stok divalidasi ulang oleh server.</p>
            </form>

            <div class="min-w-0">
                <div class="rounded-[1.6rem] border border-dashed border-line bg-white/70 p-6 text-center" data-ai-idle>
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-cream-100 text-forest-500"><?= icon('sparkles', 'h-6 w-6') ?></span>
                    <h2 class="mt-4 text-lg font-extrabold text-forest-900">Hasil rekomendasi akan muncul di sini</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-muted">Isi rencana perjalanan di samping, lalu tekan <strong>Buat rekomendasi AI</strong>. Kamu bisa mengubah tanggal, jumlah, dan memilih item mana saja yang ingin dimasukkan ke keranjang.</p>
                    <div class="mx-auto mt-5 grid max-w-sm grid-cols-3 gap-2">
                        <?php foreach (['wajib' => 'Prioritas wajib', 'opsional' => 'Pelengkap', 'hemat' => 'Sesuai budget'] as $label => $text): ?>
                            <div class="rounded-xl bg-cream-50 p-2.5 text-center">
                                <p class="text-[0.6rem] font-extrabold uppercase tracking-wide text-forest-500"><?= e($label) ?></p>
                                <p class="mt-1 text-[0.62rem] text-muted"><?= e($text) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="hidden space-y-4" data-ai-loading>
                    <div class="card overflow-hidden">
                        <div class="relative bg-forest-900 px-5 py-6 text-white">
                            <div class="absolute inset-0 topo-bg opacity-20"></div>
                            <div class="relative flex items-center gap-3">
                                <span class="ai-orb !h-12 !w-12 animate-spin-slow"><?= icon('sparkles', 'h-5 w-5') ?></span>
                                <div>
                                    <p class="text-sm font-extrabold">Menyusun gear terbaik...</p>
                                    <p class="mt-0.5 text-xs text-white/50" data-ai-loading-text>Membaca destinasi & durasi perjalanan</p>
                                </div>
                            </div>
                            <div class="relative mt-4 h-1.5 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full w-1/2 animate-pulse rounded-full bg-mint-300"></div>
                            </div>
                        </div>
                    </div>
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="card flex gap-3 p-3">
                            <div class="skeleton-box h-20 w-20 shrink-0 rounded-xl"></div>
                            <div class="flex-1 space-y-2 py-1">
                                <div class="skeleton-box h-3 w-1/3 rounded-full"></div>
                                <div class="skeleton-box h-4 w-3/4 rounded-full"></div>
                                <div class="skeleton-box h-3 w-full rounded-full"></div>
                                <div class="skeleton-box h-3 w-1/2 rounded-full"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="hidden" data-ai-error>
                    <div class="rounded-[1.5rem] border border-red-100 bg-red-50 p-5 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white text-danger"><?= icon('alert-circle', 'h-6 w-6') ?></span>
                        <h2 class="mt-3 text-base font-extrabold text-forest-900">Rekomendasi gagal dibuat</h2>
                        <p class="mx-auto mt-1 max-w-sm text-sm text-muted" data-ai-error-text>Terjadi kesalahan. Coba lagi.</p>
                        <button type="button" class="btn btn-outline btn-sm mt-4" data-ai-retry>Coba lagi</button>
                    </div>
                </div>

                <div class="hidden space-y-4" data-ai-results>
                    <div class="relative overflow-hidden rounded-[1.6rem] bg-forest-900 p-5 text-white sm:p-6">
                        <div class="absolute inset-0 topo-bg opacity-20"></div>
                        <div class="relative">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-mint-300 px-2.5 py-1 text-[0.58rem] font-extrabold uppercase tracking-[0.1em] text-forest-900" data-ai-source><?= icon('sparkles', 'h-3 w-3') ?> AI</span>
                                <span class="rounded-full border border-white/15 bg-white/5 px-2.5 py-1 text-[0.62rem] font-semibold text-white/60" data-ai-meta></span>
                            </div>
                            <h2 class="mt-3 text-xl font-extrabold leading-tight tracking-tight" data-ai-title>Rekomendasi gear</h2>
                            <p class="mt-2 text-sm leading-relaxed text-white/60" data-ai-summary></p>
                            <p class="mt-2 hidden rounded-xl bg-white/10 px-3 py-2 text-[0.68rem] leading-relaxed text-mint-100" data-ai-notice></p>
                        </div>
                    </div>

                    <div class="card p-4 sm:p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="section-kicker">Langkah 2</p>
                                <h2 class="mt-1 text-base font-extrabold text-forest-900">Sesuaikan pilihanmu</h2>
                            </div>
                            <div class="flex items-center gap-1 rounded-full border border-line bg-white p-1" data-ai-filters>
                                <button type="button" class="rounded-full px-3 py-1.5 text-[0.68rem] font-extrabold bg-forest-900 text-white" data-ai-filter="all">Semua</button>
                                <button type="button" class="rounded-full px-3 py-1.5 text-[0.68rem] font-extrabold text-muted" data-ai-filter="wajib">Wajib</button>
                                <button type="button" class="rounded-full px-3 py-1.5 text-[0.68rem] font-extrabold text-muted" data-ai-filter="opsional">Opsional</button>
                            </div>
                        </div>
                        <div class="mt-4 space-y-3" data-ai-items></div>
                        <p class="mt-3 hidden text-center text-xs text-muted" data-ai-empty>Semua item disembunyikan. Aktifkan salah satu untuk dimasukkan ke keranjang.</p>
                    </div>

                    <div class="card p-5">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="section-kicker">Langkah 3</p>
                                <h2 class="mt-1 text-base font-extrabold text-forest-900">Masukkan ke keranjang</h2>
                            </div>
                            <span class="badge badge-mint" data-ai-selected-count>0 dipilih</span>
                        </div>
                        <div class="mt-4 space-y-2 text-sm">
                            <div class="flex items-center justify-between"><span class="text-muted">Subtotal sewa</span><span class="font-bold text-forest-900" data-ai-subtotal>Rp 0</span></div>
                            <div class="flex items-center justify-between"><span class="text-muted">Deposit</span><span class="font-bold text-forest-900" data-ai-deposit>Rp 0</span></div>
                            <div class="flex items-center justify-between border-t border-line pt-2"><span class="font-extrabold text-forest-900">Total estimasi</span><span class="text-lg font-extrabold text-forest-900" data-ai-total>Rp 0</span></div>
                        </div>
                        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                            <button type="button" class="btn btn-primary flex-1" data-ai-add="cart"><?= icon('cart', 'h-4 w-4') ?> Tambahkan yang dipilih</button>
                            <button type="button" class="btn btn-outline flex-1" data-ai-add="checkout">Tambah & ke keranjang</button>
                        </div>
                        <p class="helper mt-2 text-center">Harga belum termasuk biaya antar (jika memilih delivery) dan deposit yang dikembalikan.</p>
                    </div>

                    <div class="card p-5" data-ai-tips-wrap>
                        <h2 class="flex items-center gap-2 text-sm font-extrabold text-forest-900"><?= icon('bolt', 'h-4 w-4 text-star') ?> Tips dari Rentify</h2>
                        <ul class="mt-3 space-y-2 text-sm text-forest-700" data-ai-tips></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require ROOT_PATH . '/includes/customer_footer.php'; ?>
