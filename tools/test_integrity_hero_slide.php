<?php
/**
 * Kontrak hero beranda setelah slider dibongkar (25 Jul 2026).
 *
 * Hero dulu memutar tiga slide tiap 7 detik, sehingga dua dari tiga komposisi
 * selalu tersembunyi - termasuk daftar berita, satu-satunya tempat berita
 * pengadilan muncul di beranda. Kontrak ini menjaga agar ketiganya tetap
 * tampil bersamaan tanpa rotasi, dan agar slider tidak diam-diam kembali.
 */
$root = dirname(__DIR__);
$php = (string) file_get_contents($root . '/templates/pn_natuna_2026/hero-slider.php');
// Komentar dibuang dulu: penjelasan di CSS menyebut selektor lama dan memuat
// tanda kurung, yang bikin pemindaian selektor mati dan regex `[^}]*` meleset.
$css = (string) preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents($root . '/templates/pn_natuna_2026/css/template.css'));
$js = (string) file_get_contents($root . '/templates/pn_natuna_2026/js/template.js');
$asset = $root . '/images/hero/integritas-tolak-gratifikasi-pungli-2026.webp';
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

// --- Rotasi tidak boleh kembali ---------------------------------------------
foreach ([
    'class="hero-slide' => 'Hero tidak boleh punya slide lagi.',
    'data-hero-slide=' => 'Dot slider hero tidak boleh kembali.',
    'data-hero-nav=' => 'Panah slider hero tidak boleh kembali.',
    'data-interval=' => 'Hero tidak boleh punya interval rotasi.',
] as $needle => $message) {
    $expect(!str_contains($php, $needle), $message);
}
$expect(!str_contains($js, "'.hero-slider'"), 'JS tidak boleh lagi menginisialisasi carousel hero.');
$expect(!str_contains($js, 'setupHeroNewsTabs'), 'Tab berita hero sudah dihapus bersama slider.');
foreach (['.hero-slide', '.hero-slider-dots', '.hero-nav', '.hero-tab-list'] as $selector) {
    $expect(!str_contains($css, $selector), "CSS slider mati tersisa: {$selector}");
}

// --- Tiga lapis melebar, bukan kartu melayang di atas foto ------------------
$expect(str_contains($php, 'class="hero-cinema hero-stack"'), 'Akar hero harus memakai kontrak hero-stack.');
$expect(str_contains($php, 'class="hero-stage"'), 'Panggung foto hilang.');
$expect(str_contains($php, 'class="hero-footbar"'), 'Alas pita hilang; tanpa alas, judul berita jatuh di atas dinding gedung yang terang.');
foreach (['hero-news-card', 'hero-integrity', 'hero-aside', 'hero-stack-grid'] as $stale) {
    $expect(!str_contains($php, $stale), "Kartu melayang versi lama tersisa: {$stale}");
    $expect(!str_contains($css, '.' . $stale), "CSS kartu melayang versi lama tersisa: .{$stale}");
}
$copy = strpos($php, 'hero-welcome-copy');
$news = strpos($php, 'hero-newsbar');
$poster = strpos($php, 'hero-pledge');
$expect($copy !== false, 'Kolom sambutan hilang.');
$expect($news !== false, 'Pita berita hilang dari hero.');
$expect($poster !== false, 'Baris Zona Integritas hilang dari hero.');
$expect($copy < $news && $news < $poster, 'Urutan hero harus sambutan, berita, lalu Zona Integritas.');
$expect((bool) preg_match('/\.hero-footbar\s*\{[^}]*background:\s*linear-gradient/s', $css), 'Alas pita wajib punya latar sendiri.');
$expect((bool) preg_match('/\.hero-newsbar__list\s*\{[^}]*grid-template-columns:\s*repeat\(3/s', $css), 'Pita berita harus tiga kolom setara di desktop.');

// --- Berita: satu-satunya tempat berita sendiri di beranda ------------------
$expect(str_contains($php, 'pn_natuna_hero_latest_articles(12, 3)'), 'Pita berita harus mengambil tiga artikel kategori 12.');
$expect(str_contains($php, 'href="/berita-dan-pengumuman"'), 'Pita berita butuh tautan ke arsip lengkap.');
$expect(str_contains($php, 'pn_natuna_hero_article_url('), 'Item berita harus memakai URL Joomla yang ter-route.');
$expect(str_contains($php, 'pn_natuna_hero_date('), 'Item berita harus menampilkan tanggal.');
$expect(str_contains($php, 'pn_natuna_hero_article_image('), 'Tiap item berita harus punya gambar; itu yang membuat pita layak dilihat.');

// --- Zona Integritas: pernyataan satu baris, poster jadi lampiran -----------
$expect(str_contains($php, 'href="/zona-integritas"'), 'Baris Zona Integritas harus menaut ke /zona-integritas.');
$expect(str_contains($php, 'data-maklumat-zoom="/images/hero/integritas-tolak-gratifikasi-pungli-2026.webp"'), 'Poster penuh harus dibuka lewat lightbox yang sudah ada.');
$expect(!str_contains($php, 'integritas-tolak-gratifikasi-pungli-2026-480.webp'), 'Pratinjau poster kecil dihapus: pada 92px posternya tidak terbaca sama sekali.');

// --- Kontras: teks hero tidak boleh mewarisi warna gelap bodi ---------------
$expect((bool) preg_match('/\.home-slider \.hero-welcome-copy h2\s*\{[^}]*color:\s*#fff;/s', $css), 'Headline hero harus menyatakan warnanya sendiri; `.hero h2` akan membuatnya gelap.');
$expect((bool) preg_match('/\.home-slider \.hero-welcome-copy \.hero-intro\s*\{[^}]*color:\s*rgba\(255, 244, 230/s', $css), 'Intro hero harus menyatakan warnanya sendiri; `.hero p { color: #34414c }` akan menang.');
$expect((bool) preg_match('/\.hero-cinema \.hero-welcome-copy::before\s*\{/s', $css), 'Kolom kiri butuh scrim lokal agar headline tetap kontras di atas atap yang terang.');
$expect(str_contains($css, '.home-slider .hero-newsbar__list a'), 'Tautan pita berita harus melawan gaya tautan global secara eksplisit.');

// --- Aset poster ------------------------------------------------------------
$expect(is_file($asset), 'Aset WebP Zona Integritas hilang.');
if (is_file($asset)) {
    $size = @getimagesize($asset);
    $expect(($size[0] ?? 0) === 1672 && ($size[1] ?? 0) === 941, 'Aset Zona Integritas harus tetap 1672x941.');
    $expect(($size['mime'] ?? '') === 'image/webp', 'Aset Zona Integritas harus WebP.');
    $expect(filesize($asset) <= 500 * 1024, 'Aset Zona Integritas tidak boleh melebihi 500 KiB.');
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "hero stack contract: ok\n";
