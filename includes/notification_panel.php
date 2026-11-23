<?php
declare(strict_types=1);

/**
 * Panel notifikasi reusable untuk header desktop dan hero mobile.
 * Variabel: $panelVariant ('header'|'hero'), $navNotifications, $unreadNotifications, $currentUser.
 */
$panelVariant = $panelVariant ?? 'header';
$panelClass = $panelVariant === 'hero'
    ? 'fixed right-4 top-[5.25rem] z-[70] w-[min(20rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-line bg-white text-ink shadow-float lg:hidden'
    : 'absolute right-0 z-[70] mt-2 w-[min(20rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-line bg-white text-ink shadow-float';
?>
<div class="<?= $panelClass ?>" data-notif-panel>
    <div class="flex items-center justify-between border-b border-line px-4 py-3">
        <p class="text-sm font-extrabold text-forest-900">Notifikasi</p>
        <?php if (!empty($currentUser)): ?><button type="button" class="text-[0.68rem] font-bold text-forest-500" data-notif-read-all>Baca semua</button><?php endif; ?>
    </div>
    <div class="max-h-80 overflow-y-auto" data-notif-list>
        <?php if (!$navNotifications): ?>
            <div class="px-4 py-8 text-center">
                <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-cream-100 text-muted"><?= icon('bell', 'h-5 w-5') ?></span>
                <p class="mt-2 text-xs text-muted">Belum ada notifikasi baru.</p>
            </div>
        <?php endif; ?>
        <?php foreach ($navNotifications as $notification): ?>
            <a href="<?= e(url($notification['link'] ?: (!empty($currentUser) ? 'pesanan.php' : 'login.php'))) ?>" class="block border-b border-line/70 px-4 py-3 transition hover:bg-cream-50">
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
