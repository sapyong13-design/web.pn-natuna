<?php
/** Focused contract for the three-slide homepage hero. */
$root = dirname(__DIR__);
$php = (string) file_get_contents($root . '/templates/pn_natuna_2026/hero-slider.php');
$css = (string) file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');
$asset = $root . '/images/hero/integritas-tolak-gratifikasi-pungli-2026.webp';
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$expect(substr_count($php, 'class="hero-slide') >= 3, 'Hero must render at least three slides.');
$poster = strpos($php, 'hero-slide-integrity');
$news = strpos($php, 'hero-slide hero-slide-news');
$expect($poster !== false, 'Integrity poster slide is missing.');
$expect($poster !== false && $news !== false && $poster < $news, 'Integrity poster must be slide two before news.');
$expect(str_contains($php, 'href="/zona-integritas"'), 'Integrity poster must link to /zona-integritas.');
$expect(str_contains($php, '/images/hero/integritas-tolak-gratifikasi-pungli-2026.webp'), 'Integrity WebP asset reference is missing.');
$expect(str_contains($php, 'data-hero-slide="2"'), 'News dot must move to slide index 2.');
$expect(substr_count($php, 'data-hero-slide=') === 3, 'Hero must render exactly three navigation dots.');
$expect(str_contains($php, 'aria-label="Buka informasi Zona Integritas: Tolak Gratifikasi dan Pungutan Liar"'), 'Poster link needs a specific accessible label.');
$expect((bool) preg_match('/\.hero-slide-integrity__image\s*\{[^}]*object-fit:\s*contain;/s', $css), 'Poster artwork must use object-fit contain.');
$expect((bool) preg_match('/\.hero-slide-integrity__link\s*\{[^}]*display:\s*block;/s', $css), 'Poster must use a full-area link.');
// 820px, bukan 960px: tinggi baris grid hero sama dengan slide tertinggi, jadi
// rasio 1672:941 milik poster ikut menentukan apa yang jatuh di bawah lipatan
// pada slide sambutan. 820 x 941/1672 = 461,5px mengembalikan ribbon, caption,
// dan kendali ke dalam lipatan 768 tanpa memotong posternya.
$expect((bool) preg_match('/@media \(min-width:\s*761px\)\s*\{.*?\.home-slider \.hero-slide-integrity\s*\{[^}]*width:\s*min\(820px, 100%\);[^}]*margin-inline:\s*auto;[^}]*\}/s', $css), 'Desktop integrity slide must cap artwork width at 820px and stay centered.');
$expect(str_contains($php, 'hero-slider hero-cinema'), 'Hero root contract missing.');
$js = (string) file_get_contents($root . '/templates/pn_natuna_2026/js/template.js');
$expect(!str_contains($js, 'setupMobileHeroHeight'), 'Mobile hero must not measure every slide at runtime.');
$expect(!str_contains($css, '--hero-mobile-slide-height'), 'Mobile hero must not depend on a runtime height variable.');
$expect((bool) preg_match('/\.hero-cinema \.hero-slides\s*\{[^}]*min-height:\s*540px/s', $css), 'Mobile hero track must reserve a stable intrinsic height.');
$expect(is_file($asset), 'Optimized integrity WebP asset is missing.');
if (is_file($asset)) {
    $size = @getimagesize($asset);
    $expect(($size[0] ?? 0) === 1672 && ($size[1] ?? 0) === 941, 'Integrity asset must preserve 1672x941 dimensions.');
    $expect(($size['mime'] ?? '') === 'image/webp', 'Integrity asset must be WebP.');
    $expect(filesize($asset) <= 500 * 1024, 'Integrity asset must not exceed 500 KiB.');
}

// --- Poster dipajang, bukan ditempel (25 Jul 2026) -------------------------
$css = (string) preg_replace('#/\*.*?\*/#s', '', $css);
$expect((bool) preg_match('/\.home-slider \.hero-slide-integrity__link\s*\{[^}]*border-color:\s*rgba\(226, 185, 79/s', $css), 'Poster butuh bingkai emas, bukan garis putih tipis di atas foto.');
$expect((bool) preg_match('/@media \(min-width: 761px\).*?\.home-slider \.hero-slide-integrity__cta\s*\{/s', $css), 'Desktop butuh petunjuk bahwa poster bisa dibuka penuh; sebelumnya CTA-nya display:none tanpa pengganti.');

// --- Hierarki kartu berita -------------------------------------------------
// Sebelumnya nama section 36px sementara judul berita yang diklik cuma 14px.
$expect((bool) preg_match('/\.home-slider \.hero-tab-list \.hero-item-title\s*\{[^}]*font-size:\s*1rem/s', $css), 'Judul berita wajib lebih dominan daripada kutipannya.');
$expect((bool) preg_match('/\.home-slider \.hero-news-panel h2\s*\{[^}]*font-size:\s*clamp\(1\.35rem/s', $css), 'Nama section tidak boleh lebih besar daripada berita yang ditawarkannya.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "integrity hero slide contract: ok\n";
