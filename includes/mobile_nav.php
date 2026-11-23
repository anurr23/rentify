<?php
declare(strict_types=1);

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$bottomItems = [
    ['label' => 'Beranda', 'icon' => 'home', 'url' => 'beranda.php', 'key' => 'beranda'],
    ['label' => 'Katalog', 'icon' => 'grid', 'url' => 'katalog.php', 'key' => 'katalog'],
    ['label' => 'AI', 'icon' => 'sparkles', 'url' => 'rekomendasi.php', 'key' => 'ai'],
    ['label' => 'Pesanan', 'icon' => 'clipboard', 'url' => 'pesanan.php', 'key' => 'pesanan'],
    ['label' => 'Profil', 'icon' => 'user', 'url' => $currentUser ? 'profil.php' : 'login.php', 'key' => 'profil'],
];
?>
<nav class="bottom-nav lg:hidden no-print" aria-label="Navigasi utama">
    <div class="mx-auto flex max-w-lg items-stretch px-2">
        <?php foreach ($bottomItems as $navItem): ?>
            <a href="<?= e(url($navItem['url'])) ?>" class="<?= ($activeNav ?? '') === $navItem['key'] ? 'active' : '' ?>">
                <?= icon($navItem['icon'], 'h-[1.35rem] w-[1.35rem]') ?>
                <span><?= e($navItem['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
