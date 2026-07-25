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

// --- Ketiga komposisi tampil bersamaan --------------------------------------
$expect(str_contains($php, 'class="hero-cinema hero-stack"'), 'Akar hero harus memakai kontrak hero-stack.');
$expect(str_contains($php, 'class="hero-stack-grid"'), 'Hero harus memakai grid dua kolom statis.');
$copy = strpos($php, 'hero-welcome-copy');
$news = strpos($php, 'hero-news-card');
$poster = strpos($php, 'hero-integrity');
$expect($copy !== false, 'Kolom sambutan hilang.');
$expect($news !== false, 'Kartu berita hilang dari hero.');
$expect($poster !== false, 'Pita Zona Integritas hilang dari hero.');
$expect($copy < $news && $news < $poster, 'Urutan hero harus sambutan, berita, lalu Zona Integritas.');
$expect((bool) preg_match('/\.hero-stack-grid\s*\{[^}]*display:\s*grid;/s', $css), 'hero-stack-grid harus berupa grid.');

// --- Berita: satu-satunya tempat berita sendiri di atas fold ----------------
$expect(str_contains($php, 'pn_natuna_hero_latest_articles(12, 3)'), 'Kartu berita harus mengambil tiga artikel kategori 12.');
$expect(str_contains($php, 'href="/berita-dan-pengumuman"'), 'Kartu berita butuh tautan ke arsip lengkap.');
$expect(str_contains($php, 'pn_natuna_hero_article_url('), 'Item berita harus memakai URL Joomla yang ter-route.');
$expect(str_contains($php, 'pn_natuna_hero_date('), 'Item berita harus menampilkan tanggal.');

// --- Zona Integritas: janji jadi teks, poster jadi lampiran -----------------
$expect(str_contains($php, 'href="/zona-integritas"'), 'Pita Zona Integritas harus menaut ke /zona-integritas.');
$expect(str_contains($php, 'data-maklumat-zoom="/images/hero/integritas-tolak-gratifikasi-pungli-2026.webp"'), 'Poster penuh harus dibuka lewat lightbox yang sudah ada.');
$expect(str_contains($php, 'aria-label="Lihat poster Zona Integritas ukuran penuh"'), 'Tombol poster butuh label aksesibel yang spesifik.');
$expect(str_contains($php, 'integritas-tolak-gratifikasi-pungli-2026-480.webp'), 'Pratinjau poster harus memakai varian 480w, bukan berkas penuh.');
$expect((bool) preg_match('/class="hero-integrity__poster"[^>]*>\s*<img[^>]*loading="lazy"/s', $php), 'Pratinjau poster harus lazy-loaded.');

// --- Kontras: teks hero tidak boleh mewarisi warna gelap bodi ---------------
$expect((bool) preg_match('/\.home-slider \.hero-welcome-copy h2\s*\{[^}]*color:\s*#fff;/s', $css), 'Headline hero harus menyatakan warnanya sendiri; `.hero h2` akan membuatnya gelap.');
$expect((bool) preg_match('/\.home-slider \.hero-welcome-copy \.hero-intro\s*\{[^}]*color:\s*rgba\(255, 244, 230/s', $css), 'Intro hero harus menyatakan warnanya sendiri; `.hero p { color: #34414c }` akan menang.');
$expect((bool) preg_match('/\.hero-cinema \.hero-welcome-copy::before\s*\{/s', $css), 'Kolom kiri butuh scrim lokal agar headline tetap kontras di atas atap yang terang.');
$expect(str_contains($css, '.home-slider .hero-news-card__lead'), 'Tautan kartu berita harus melawan gaya tautan global secara eksplisit.');

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
