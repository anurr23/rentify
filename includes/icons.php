<?php
declare(strict_types=1);

/**
 * Kumpulan ikon SVG inline. Memakai stroke currentColor agar mudah diwarnai.
 */
function icon_paths(): array
{
    return [
        'mountain' => '<path d="m3 20 6.5-11 4 6 2.5-3.5L21 20H3Z"/><path d="m9.5 9 1.5 2.5"/>',
        'tent' => '<path d="m12 4 8.5 15H3.5L12 4Z"/><path d="m12 4 2.5 15"/><path d="M8.5 19 12 12l3.5 7"/>',
        'backpack' => '<path d="M7 8a5 5 0 0 1 10 0v11a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V8Z"/><path d="M9 4h6"/><path d="M8 12h8"/><path d="M11 17h2"/><path d="M7 9H5v5h2"/>',
        'boot' => '<path d="M6 3h6v6l2 4h4a2 2 0 0 1 2 2v4H6V3Z"/><path d="M6 14h6"/><path d="M10 3v6"/>',
        'jacket' => '<path d="m8 4-4 3 2 6 2-1v8h8v-8l2 1 2-6-4-3a4 4 0 0 1-8 0Z"/><path d="M12 4v16"/>',
        'sleep' => '<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z"/>',
        'lamp' => '<path d="M9 18h6"/><path d="M10 22h4"/><path d="M8 13a5 5 0 1 1 8 0c-.8.9-1 1.8-1 2H9c0-.2-.2-1.1-1-2Z"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5 5-2Z"/>',
        'stove' => '<path d="M12 3c2 3.5 1 5.5-.5 7S9 13.5 10 16c-1.8-.7-3-2.4-3-4.5C7 8 9.4 5.2 12 3Z"/><path d="M5 20h14"/><path d="M7 16h10"/>',
        'hammock' => '<path d="M3 4v16"/><path d="M21 4v16"/><path d="M4 8c4 6 12 6 16 0"/><path d="M8 13c1.5 3 6.5 3 8 0"/>',
        'droplet' => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11Z"/>',
        'bolt' => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>',
        'package' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5"/><path d="M12 12v9"/><path d="m8 5.2 8 4.5"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'filter' => '<path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/>',
        'sliders' => '<path d="M4 7h6"/><path d="M14 7h6"/><path d="M4 17h3"/><path d="M11 17h9"/><circle cx="12" cy="7" r="2"/><circle cx="9" cy="17" r="2"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'list' => '<path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/><circle cx="4.5" cy="6" r=".6" fill="currentColor"/><circle cx="4.5" cy="12" r=".6" fill="currentColor"/><circle cx="4.5" cy="18" r=".6" fill="currentColor"/>',
        'heart' => '<path d="M20.5 8.5c0 5.5-8.5 11-8.5 11s-8.5-5.5-8.5-11a5 5 0 0 1 8.5-3.5 5 5 0 0 1 8.5 3.5Z"/>',
        'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/>',
        'map-pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'arrow-right' => '<path d="M4 12h16"/><path d="m14 6 6 6-6 6"/>',
        'arrow-left' => '<path d="M20 12H4"/><path d="m10 6-6 6 6 6"/>',
        'arrow-up-right' => '<path d="M7 17 17 7"/><path d="M8 7h9v9"/>',
        'arrow-up' => '<path d="M12 20V4"/><path d="m6 10 6-6 6 6"/>',
        'chevron-right' => '<path d="m9 5 7 7-7 7"/>',
        'chevron-left' => '<path d="m15 5-7 7 7 7"/>',
        'chevron-down' => '<path d="m5 9 7 7 7-7"/>',
        'chevron-up' => '<path d="m5 15 7-7 7 7"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 8-3 8h18s-3-1-3-8Z"/><path d="M10 21h4"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18.5 14.5A6.5 6.5 0 0 1 22 20"/>',
        'cart' => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M3 4h2.5l2.6 11.5h11L21 7H6"/>',
        'menu' => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
        'x' => '<path d="m6 6 12 12"/><path d="m18 6-12 12"/>',
        'home' => '<path d="m3 11 9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/>',
        'box' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5"/><path d="M12 12v9"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/>',
        'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2.5"/><path d="M9 4V3h6v1"/><path d="M9 10h6"/><path d="M9 14h6"/><path d="M9 18h4"/>',
        'chart' => '<path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/>',
        'chart-line' => '<path d="M3 20h18"/><path d="m4 15 5-5 4 3 6-7"/><path d="M15 6h4v4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.8 1.8 0 0 0 .36 2 2 2 0 1 1-2.8 2.8 1.8 1.8 0 0 0-2-.36 1.8 1.8 0 0 0-1.1 1.65V21a2 2 0 1 1-4 0v-.23a1.8 1.8 0 0 0-1.1-1.65 1.8 1.8 0 0 0-2 .36 2 2 0 1 1-2.8-2.8 1.8 1.8 0 0 0 .36-2 1.8 1.8 0 0 0-1.65-1.1H3a2 2 0 1 1 0-4h.23a1.8 1.8 0 0 0 1.65-1.1 1.8 1.8 0 0 0-.36-2 2 2 0 1 1 2.8-2.8 1.8 1.8 0 0 0 2 .36H9.4a1.8 1.8 0 0 0 1.1-1.65V3a2 2 0 1 1 4 0v.23a1.8 1.8 0 0 0 1.1 1.65 1.8 1.8 0 0 0 2-.36 2 2 0 1 1 2.8 2.8 1.8 1.8 0 0 0-.36 2v.08a1.8 1.8 0 0 0 1.65 1.1H21a2 2 0 1 1 0 4h-.23a1.8 1.8 0 0 0-1.65 1.1Z"/>',
        'logout' => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'trash' => '<path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M6 7l1 13h10l1-13"/><path d="M9 7V4h6v3"/>',
        'check' => '<path d="m5 12 4.5 4.5L19 7"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>',
        'x-circle' => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6"/><path d="m15 9-6 6"/>',
        'camera' => '<path d="M4 7h3l1.5-2h7L17 7h3a1 1 0 0 1 1 1v11H3V8a1 1 0 0 1 1-1Z"/><circle cx="12" cy="13" r="4"/>',
        'upload' => '<path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"/>',
        'sparkles' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="M19 16l.7 2.3L22 19l-2.3.7L19 22l-.7-2.3L16 19l2.3-.7L19 16Z"/>',
        'whatsapp' => '<path d="M20 11.5A8 8 0 0 1 8.3 18.7L4 20l1.3-4.2A8 8 0 1 1 20 11.5Z"/><path d="M9 9c0 4 2.5 6 5.5 6l1-1.5-2-1-1 .8c-1-.4-1.8-1.2-2.2-2.2l.8-1-1-2L9 9Z"/>',
        'shield' => '<path d="M12 3 4.5 6v5c0 5 3.4 8.5 7.5 10 4.1-1.5 7.5-5 7.5-10V6L12 3Z"/><path d="m9 12 2 2 4-4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'truck' => '<path d="M3 6h11v11H3z"/><path d="M14 10h4l3 3v4h-7"/><circle cx="7" cy="18" r="1.7"/><circle cx="18" cy="18" r="1.7"/>',
        'phone' => '<path d="M6 3h3l1.5 5-2 1.5a13 13 0 0 0 6 6l1.5-2 5 1.5v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4 5.2 2 2 0 0 1 6 3Z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.5 6.5 8.5 7 8.5-7"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
        'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8Z"/>',
        'edit' => '<path d="M4 20h4l11-11a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'download' => '<path d="M12 3v13"/><path d="m7 11 5 5 5-5"/><path d="M4 20h16"/>',
        'refresh' => '<path d="M20 11a8 8 0 1 0-2.3 6.7"/><path d="M20 4v7h-7"/>',
        'rotate' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>',
        'alert-triangle' => '<path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v5"/><path d="M12 17.5h.01"/>',
        'alert-circle' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.5h.01"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 7.5h.01"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m4.9 19.1 1.4-1.4"/><path d="m17.7 6.3 1.4-1.4"/>',
        'volume' => '<path d="M4 9h4l5-4v14l-5-4H4V9Z"/><path d="M17 9a4 4 0 0 1 0 6"/><path d="M19.5 6.5a8 8 0 0 1 0 11"/>',
        'volume-off' => '<path d="M4 9h4l5-4v14l-5-4H4V9Z"/><path d="m17 9 5 6"/><path d="m22 9-5 6"/>',
        'play' => '<path d="M8 5v14l11-7L8 5Z"/>',
        'footprint' => '<path d="M5 16c-1-3 0-8 2-10 2-1.5 4 .5 3.5 3S8 13 8 15c0 2-2.3 3-3 1Z"/><path d="M14 21c-1.5-2.5-1-7 0-9 1-1.5 3.5-.5 3.5 2S15 17 16 19c.6 1.3-.8 3-2 2Z"/><path d="M5 19h2"/><path d="M15 23h2"/>',
        'flag' => '<path d="M5 21V4"/><path d="M5 5c5-3 9 3 14 0v9c-5 3-9-3-14 0"/>',
        'coffee' => '<path d="M4 8h12v6a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4V8Z"/><path d="M16 9h2a2 2 0 0 1 0 4h-2"/><path d="M4 21h12"/>',
        'credit-card' => '<rect x="2.5" y="5" width="19" height="14" rx="3"/><path d="M2.5 10h19"/><path d="M6 15h3"/>',
        'wallet' => '<path d="M4 6h14a2 2 0 0 1 2 2v10H4a2 2 0 0 1-2-2V6Z"/><path d="M2 8V5a2 2 0 0 1 2-2h12"/><path d="M16 12h4"/>',
        'qr' => '<rect x="3" y="3" width="6" height="6" rx="1"/><rect x="15" y="3" width="6" height="6" rx="1"/><rect x="3" y="15" width="6" height="6" rx="1"/><path d="M15 15h2v2h-2z"/><path d="M19 15h2v2"/><path d="M15 19h2v2h-2z"/><path d="M20 20h1v1"/>',
        'receipt' => '<path d="M6 3h12v18l-2-1.5-2 1.5-2-1.5L10 21l-2-1.5L6 21V3Z"/><path d="M9 8h6"/><path d="M9 12h6"/><path d="M9 16h4"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m4 17 5-5 4 4 3-3 4 4"/>',
        'copy' => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>',
        'loader' => '<path d="M21 12a9 9 0 1 1-2.6-6.4"/>',
        'lock' => '<rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9"/><path d="m17 4 3 3"/><path d="m14 7 3 3"/>',
        'tag' => '<path d="M3 12V5a2 2 0 0 1 2-2h7l9 9-9 9-9-9Z"/><circle cx="7.5" cy="7.5" r="1.2"/>',
        'percent' => '<path d="M19 5 5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>',
        'printer' => '<path d="M7 8V3h10v5"/><rect x="3" y="8" width="18" height="8" rx="2"/><path d="M7 14h10v7H7z"/>',
        'mail-open' => '<path d="M3 9l9-6 9 6v10H3V9Z"/><path d="m3 9 9 6 9-6"/>',
        'user-check' => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 11.5-5.4"/><path d="m16 18 2 2 4-4"/>',
        'archive' => '<rect x="3" y="4" width="18" height="5" rx="1.5"/><path d="M5 9v11h14V9"/><path d="M10 13h4"/>',
        'map' => '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z"/><path d="M9 3v15"/><path d="M15 6v15"/>',
        'ruler' => '<path d="m3 16 13-13 5 5L8 21l-5-5Z"/><path d="m7 12 2 2"/><path d="m11 8 2 2"/><path d="m15 4 2 2"/>',
        'weight' => '<path d="M6 8h12l2 13H4L6 8Z"/><circle cx="12" cy="13" r="3"/>',
        'thermometer' => '<path d="M10 13V5a2 2 0 1 1 4 0v8a4 4 0 1 1-4 0Z"/><path d="M12 9v6"/>',
        'wind' => '<path d="M3 8h11a3 3 0 1 0-3-3"/><path d="M3 12h15a3 3 0 1 1-3 3"/><path d="M3 16h7"/>',
        'water' => '<path d="M3 12c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/><path d="M3 17c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/><path d="M3 7c2-2 4-2 6 0s4 2 6 0 4-2 6 0"/>',
        'zap' => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>',
        'tag-filled' => '<path d="M3 12V5a2 2 0 0 1 2-2h7l9 9-9 9-9-9Z" fill="currentColor" stroke="none"/><circle cx="7.5" cy="7.5" r="1.2" fill="#fff"/>',
        'spinner' => '<path d="M21 12a9 9 0 1 1-2.6-6.4"/>',
    ];
}

function icon(string $name, string $class = 'h-5 w-5', string $strokeWidth = '1.8'): string
{
    $paths = icon_paths();
    $inner = $paths[$name] ?? $paths['info'];
    $fill = in_array($name, ['star-filled'], true) ? 'currentColor' : 'none';
    if ($name === 'star-filled') {
        $inner = '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z" fill="currentColor" stroke="none"/>';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" class="' . e($class) . '" viewBox="0 0 24 24" fill="' . $fill
        . '" stroke="currentColor" stroke-width="' . e($strokeWidth) . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $inner . '</svg>';
}

function logo_mark(string $class = 'h-10 w-10', bool $dark = false): string
{
    $background = $dark ? '#CBE8CE' : '#F8FAF6';
    $foreground = $dark ? '#032517' : '#032517';
    return '<span class="' . e($class) . ' inline-flex items-center justify-center rounded-xl" style="background:' . $background . ';color:' . $foreground . '">'
        . '<svg viewBox="0 0 32 32" class="h-3/5 w-3/5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<path d="m4 25 8-15 5 8 3-4.5L28 25H4Z"/><path d="m12 10 2.5 4.5"/><path d="M9 25h14"/></svg></span>';
}

function brand(bool $light = true, string $size = 'md'): string
{
    $text = $light ? 'text-white' : 'text-forest-900';
    $sub = $light ? 'text-white/55' : 'text-muted';
    $markSize = $size === 'lg' ? 'h-11 w-11' : 'h-9 w-9';
    $title = $size === 'lg' ? 'text-lg' : 'text-[0.95rem]';
    return '<a href="' . e(url('beranda.php')) . '" class="inline-flex items-center gap-2.5 ' . $text . '">'
        . logo_mark($markSize, !$light)
        . '<span class="leading-tight"><span class="block ' . $title . ' font-extrabold tracking-[0.12em]">RENTIFY</span>'
        . '<span class="block ' . $sub . ' text-[0.52rem] font-semibold tracking-[0.18em]">GEAR UP, GO FURTHER</span></span></a>';
}
