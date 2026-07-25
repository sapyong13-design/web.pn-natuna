<?php
$root = dirname(__DIR__);
$index = file_get_contents($root . '/templates/pn_natuna_2026/index.php');
$hero = file_get_contents($root . '/templates/pn_natuna_2026/hero-slider.php');
$js = file_get_contents($root . '/templates/pn_natuna_2026/js/template.js');
// Komentar dibuang: penjelasan di CSS menyebut selektor slider lama, dan itu
// akan salah terbaca sebagai kontrol slider yang kembali hidup.
$css = preg_replace('#/\*.*?\*/#s', '', file_get_contents($root . '/templates/pn_natuna_2026/css/template.css'));
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$expect(str_contains($js, "slide.toggleAttribute('inert', !active)"), 'Hidden carousel slides must become inert.');
$expect(str_contains($js, 'searchReturnFocus'), 'Search dialog must retain its trigger for focus restoration.');
$expect(str_contains($js, 'trapFocus(event, overlay'), 'Search dialog must trap Tab and Shift+Tab.');
$expect(str_contains($js, 'searchBackground'), 'Search dialog must make background content inert.');
$expect(str_contains($index, 'autocomplete="off"'), 'Search input must disable irrelevant autocomplete.');
$expect(str_contains($index, 'enterkeyhint="search"'), 'Search input must request a search keyboard action.');
$expect(str_contains($index, 'posbakum…'), 'Search placeholder must use an ellipsis.');

// Tablist hero dibongkar bersama slider (25 Jul 2026). Yang tersisa untuk
// diuji: tablist instansi, dan jaminan bahwa slider hero tidak diam-diam balik.
foreach (["'ArrowLeft'", "'ArrowRight'", "'Home'", "'End'"] as $key) {
    $expect(str_contains($js, $key), "Tablist instansi harus mendukung {$key}.");
}
$expect(str_contains($js, "t.tabIndex = active ? 0 : -1"), 'Tablists must use roving tabindex.');

foreach (['.hero-slider-dots', '.hero-nav', '.hero-slide'] as $selector) {
    $expect(!str_contains($css, $selector), "Kontrol slider hero tidak boleh kembali: {$selector}");
}
$expect(!str_contains($hero, 'data-hero-slide='), 'Hero harus tetap satu komposisi statis, tanpa dot.');

$expect(str_contains($hero, 'gedung-pn-natuna-2026-graded-480.webp 480w'), 'Hero backdrop needs responsive 480w source.');
$expect(str_contains($hero, 'gedung-pn-natuna-2026-graded-768.webp 768w'), 'Hero backdrop needs responsive 768w source.');
$expect(str_contains($hero, 'integritas-tolak-gratifikasi-pungli-2026-480.webp'), 'Pratinjau poster Zona Integritas harus memakai varian 480w.');
$expect(!str_contains($js, 'prefetchIntegrityPoster'), 'Secondary hero poster must not be idle-prefetched.');
$expect(!str_contains($js, 'setupMobileHeroHeight'), 'Hero must not measure every slide at runtime.');
$expect(!str_contains($css, '--hero-mobile-slide-height'), 'Mobile hero must use intrinsic CSS geometry.');

$expect(str_contains($hero, 'fetchpriority="high"'), 'Active hero backdrop must retain high fetch priority.');
$posterTag = preg_match('/<img[^>]*integritas-tolak-gratifikasi-pungli-2026-480\.webp[^>]*>/s', $hero, $posterMatch) ? $posterMatch[0] : '';
$expect($posterTag !== '' && str_contains($posterTag, 'loading="lazy"'), 'Pratinjau poster Zona Integritas harus tetap lazy loaded.');
$expect(str_contains($index, 'id="theme-color-meta"'), 'Theme-color meta needs a stable hook.');
$expect(str_contains($js, 'syncBrowserTheme'), 'Theme changes must synchronize browser chrome.');
$expect(str_contains($js, 'document.documentElement.style.colorScheme'), 'Theme changes must synchronize native controls.');

foreach ([
    'gedung-pn-natuna-2026-graded-480.webp',
    'gedung-pn-natuna-2026-graded-768.webp',
    'gedung-pn-natuna-2026-graded-1200.webp',
    'integritas-tolak-gratifikasi-pungli-2026-480.webp',
    'integritas-tolak-gratifikasi-pungli-2026-768.webp',
    'integritas-tolak-gratifikasi-pungli-2026-1200.webp',
] as $image) {
    $expect(is_file($root . '/images/hero/' . $image), "Responsive hero image missing: {$image}");
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "accessibility performance hardening contract: ok\n";
