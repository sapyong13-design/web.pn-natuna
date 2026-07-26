<?php
$root = dirname(__DIR__);
$index = file_get_contents($root . '/templates/pn_natuna_2026/index.php');
$hero = file_get_contents($root . '/templates/pn_natuna_2026/hero-slider.php');
$js = file_get_contents($root . '/templates/pn_natuna_2026/js/template.js');
$css = file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');
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

foreach (['aria-controls="hero-panel-berita"', 'aria-controls="hero-panel-pengumuman"', 'role="tabpanel"', 'aria-labelledby="hero-tab-'] as $needle) {
    $expect(str_contains($hero, $needle), "Hero tabs missing APG relationship: {$needle}");
}
foreach (["'ArrowLeft'", "'ArrowRight'", "'Home'", "'End'"] as $key) {
    $expect(substr_count($js, $key) >= 2, "Hero and instansi tabs must support {$key}.");
}
$expect(str_contains($js, "t.tabIndex = active ? 0 : -1"), 'Tablists must use roving tabindex.');

$expect((bool) preg_match('/\.hero-slider-dots button[^}]*min-width:\s*44px[^}]*min-height:\s*44px/s', $css), 'Hero dots need 44px hit areas.');
$expect(str_contains($css, '.hero-slider-dots button::before'), 'Hero dots need a separate visual indicator.');
$expect((bool) preg_match('/\.hero-slider-dots button\s*\{[^}]*background:\s*transparent/s', $css), 'Hero dot hit areas must stay visually transparent.');

$expect(str_contains($hero, 'gedung-pn-natuna-2026-graded-480.webp 480w'), 'Hero backdrop needs responsive 480w source.');
$expect(str_contains($hero, 'gedung-pn-natuna-2026-graded-768.webp 768w'), 'Hero backdrop needs responsive 768w source.');
$expect(str_contains($hero, 'integritas-tolak-gratifikasi-pungli-2026-480.webp 480w'), 'Integrity poster needs responsive 480w source.');
$expect(!str_contains($js, 'prefetchIntegrityPoster'), 'Secondary hero poster must not be idle-prefetched.');
$expect(!str_contains($js, 'setupMobileHeroHeight'), 'Hero must not measure every slide at runtime.');
$expect(!str_contains($css, '--hero-mobile-slide-height'), 'Mobile hero must use intrinsic CSS geometry.');

$expect(str_contains($hero, 'fetchpriority="high"'), 'Active hero backdrop must retain high fetch priority.');
$posterTag = preg_match('/<img[^>]*data-integrity-poster[^>]*>/s', $hero, $posterMatch) ? $posterMatch[0] : '';
$expect($posterTag !== '' && str_contains($posterTag, 'loading="lazy"'), 'Secondary integrity poster must remain lazy loaded.');
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

// --- Kendali rotasi hero (25 Jul 2026) -------------------------------------
// WCAG 2.2.2: konten yang bergerak otomatis lebih dari 5 detik wajib punya
// kontrol jeda. Jeda saat hover dan prefers-reduced-motion sudah ada, tapi
// keduanya tidak terlihat dan tidak bisa dioperasikan dengan sengaja.
$expect(str_contains($hero, 'data-hero-pause'), 'Slider hero wajib punya tombol jeda.');
$expect((bool) preg_match('/class="hero-pause"[^>]*aria-pressed="false"/s', $hero), 'Tombol jeda wajib menyatakan status aria-pressed.');
$expect(str_contains($js, "pause: '[data-hero-pause]'"), 'Tombol jeda wajib disambungkan ke initCarousel.');
$expect(str_contains($js, 'let userPaused = false;'), 'Jeda pengguna harus dibedakan dari jeda sementara akibat hover/fokus.');
$expect(str_contains($js, 'pauseButton.hidden = reducedMotion;'), 'Tombol jeda disembunyikan saat rotasi memang tidak pernah jalan.');
$expect((bool) preg_match('/\.home-slider \.hero-pause\s*\{[^}]*width:\s*44px;[^}]*height:\s*44px;/s', $css), 'Tombol jeda wajib 44px.');

// aria-live "off" selama rotasi otomatis supaya pembaca layar tidak
// diinterupsi tiap 7 detik, "polite" begitu kendali berpindah ke pengguna.
$expect((bool) preg_match('/class="hero-slides"[^>]*aria-live="off"/s', $hero), 'Wadah slide wajib punya hook aria-live.');
$expect(str_contains($js, "liveRegion.setAttribute('aria-live', autoRunning ? 'off' : 'polite')"), 'aria-live wajib mengikuti status rotasi.');

// --- Kartu berita hero -----------------------------------------------------
$expect((bool) preg_match('/\.home-slider \.hero-tabs button\s*\{[^}]*min-height:\s*44px/s', $css), 'Tab Berita/Pengumuman wajib 44px.');
$expect(str_contains($hero, 'class="hero-item-thumb"'), 'Tiap item berita wajib punya thumbnail untuk mobile.');
$expect((bool) preg_match('/@media \(max-width: 900px\).*?\.home-slider \.hero-tab-list \.hero-item-thumb\s*\{[^}]*display:\s*block/s', $css), 'Thumbnail wajib tampil di mobile, tempat panel pratinjau besar disembunyikan.');
$expect(!str_contains($hero, 'hero-kicker'), 'Kicker "Informasi Terkini" dihapus: h2 di bawahnya menyatakan hal yang sama.');
$expect(str_contains($hero, 'mb_strrpos($cut'), 'Kutipan wajib dipotong di batas kata, bukan di tengah kata.');

// --- Beranda: perbaikan editorial (26 Jul 2026) ----------------------------
// Sembilan perbaikan hasil audit beranda. Yang dikunci di sini adalah hal yang
// bisa mundur diam-diam: kontras yang gagal di satu tema saja, target sentuh
// yang lolos karena selektornya tidak pernah diuji, dan jadwal basi yang
// kembali menyebut dirinya "Hari Ini".
$sipp = file_get_contents($root . '/templates/pn_natuna_2026/sipp-schedule.php');

// Tanpa reset ini, kontrol form jatuh ke huruf UA: 32 string di beranda
// dirender Arial, termasuk seluruh tab periode DIPA dan tombol Cari.
$expect((bool) preg_match('/button,\s*input,\s*select,\s*textarea,\s*optgroup\s*\{[^}]*font:\s*inherit/s', $css), 'Kontrol form wajib mewarisi huruf brand.');

foreach (['--step--2', '--step-0', '--step-4', '--space-4', '--space-8'] as $token) {
    $expect(str_contains($css, $token . ':'), "Token skala hilang: {$token}");
}

// Tiga tingkat seksi. Kalau ketiganya kembali sama, halaman kehilangan satu-
// satunya sinyal spasial bahwa ada seksi yang lebih penting dari seksi lain.
$gaps = [];
foreach (['feature', 'standard', 'ancillary'] as $tier) {
    $found = (bool) preg_match('/--section-gap-' . $tier . ':\s*(\d+)px/', $css, $tierMatch);
    $expect($found, "Jarak seksi {$tier} hilang.");
    $gaps[] = $found ? (int) $tierMatch[1] : 0;
}
$expect($gaps[0] > $gaps[1] && $gaps[1] > $gaps[2], 'Tingkat seksi wajib menurun: feature > standard > ancillary.');

// Jadwal basi tidak boleh menyebut dirinya hari ini.
$expect(str_contains($sipp, 'function pn_natuna_sipp_day_status'), 'Status basi per hari wajib punya helper sendiri.');
$expect(str_contains($sipp, 'function pn_natuna_sipp_label_date'), 'Label tanggal SIPP wajib bisa diurai untuk dibandingkan.');
$expect(str_contains($sipp, "DateTimeZone('Asia/Jakarta')"), 'Perbandingan tanggal wajib memakai zona waktu Jakarta.');
$expect(str_contains($sipp, 'sipp-tab-stale'), 'Tab basi wajib ditandai.');
$expect(str_contains($sipp, 'sipp-stale-notice'), 'Panel basi wajib menyatakan tanggal aslinya.');
$expect(str_contains($sipp, '$status[\'stale\'] ? ($schedule[\'date_label\']'), 'Judul tab wajib mengikuti status basi, bukan selalu "Hari Ini".');
$expect(str_contains($sipp, 'aria-label="Detil perkara'), 'Tiap tautan detil wajib menyebut nomor perkaranya.');
$expect(str_contains($sipp, 'tel:'), 'Keadaan kosong wajib menawarkan jalan keluar, bukan menyuruh menunggu.');
$expect(str_contains($hero, "function_exists('pn_natuna_sipp_day_status')"), 'Ribbon hero wajib memeriksa kebasian sebelum mencetak angka agenda.');

// Kontras: kalimat "apakah pengadilan buka?" gagal di kedua tema sebelum ini.
$expect((bool) preg_match('/\.hero-welcome-copy \.hero-status\.is-closed\s*\{[^}]*color:\s*#ffe3da/s', $css), 'Pil status tutup wajib punya warna depan eksplisit.');
$expect((bool) preg_match('/\.hero-welcome-copy \.hero-status\.is-open\s*\{[^}]*color:\s*#d6f8e8/s', $css), 'Pil status buka wajib punya warna depan eksplisit.');
// Selektornya muncul lebih dari sekali, jadi keberadaan selektor saja tidak
// membuktikan apa pun. Yang dijaga adalah tiga anak tab benar-benar mendapat
// warna depan: strong, span, dan b. Sebelum ini span dan b mewarisi maroon ke
// atas permukaan gelap dan turun ke 1,2:1.
foreach (['true', 'false'] as $terpilih) {
    $anak = 0;
    foreach (['strong', 'span', 'b'] as $bagian) {
        $pola = '/body\.is-dark \.sipp-day-tabs button\[aria-selected="' . $terpilih . '"\] ' . $bagian . '[^{}]*\{[^}]*color:\s*#[0-9a-f]{3,6}/s';
        if (preg_match($pola, $css)) {
            $anak++;
        }
    }
    $expect($anak === 3, "Mode gelap: ketiga baris tab hari (aria-selected={$terpilih}) wajib punya warna depan eksplisit, baru {$anak}/3.");
}

// Kartu jadwal mobile: tombol menimpa nomor perkara di 12 dari 12 kartu.
$expect((bool) preg_match('/\.sipp-card\s*\{[^}]*grid-template-columns:\s*auto minmax\(0, 1fr\);/s', $css), 'Di mobile tombol Detil wajib turun ke barisnya sendiri.');
$expect((bool) preg_match('/\.sipp-chip:not\(\.sipp-chip-room\):not\(\.sipp-chip-circuit\)\s*\{[^}]*border-radius:\s*10px/s', $css), 'Chip agenda multibaris tidak boleh memakai radius pil.');

// Target sentuh 44px adalah kontrak pemilik, bukan ambang 24px WCAG 2.5.8.
foreach (['.dipa-tab', '.section-action', '.instansi-tabbar button', '.footer-link-section a', '.skip-link'] as $target) {
    $expect((bool) preg_match('/' . preg_quote($target, '/') . '[^{}]*\{[^}]*min-height:\s*44px/s', $css), "Target sentuh di bawah 44px: {$target}");
}
$expect((bool) preg_match('/\.footer-social \.social-link\s*\{[^}]*min-width:\s*44px;\s*min-height:\s*44px/s', $css), 'Tombol sosial footer wajib 44px di kedua sumbu.');

// --- Hero: ribbon operasional + kontras komposit (26 Jul 2026) -------------
// Hero tidak boleh menjadi dashboard mini. IKM/IPAK tetap tersedia di modul
// Kinerja & Akuntabilitas; ribbon hanya memuat dua fakta yang mengubah tindakan
// saat ini: status PTSP dan kondisi jadwal SIPP.
$expect(str_contains($hero, 'aria-label="Status operasional pengadilan"'), 'Ribbon hero wajib menyatakan tujuan operasionalnya.');
$expect(substr_count($hero, 'class="hero-ribbon-status') === 2, 'Ribbon hero wajib memuat tepat dua status operasional.');
$expect(str_contains($hero, 'data-service-status hidden'), 'Status PTSP wajib diisi dari waktu Jakarta, bukan dibekukan di PHP.');
$expect(!str_contains($hero, 'pn_natuna_hero_survey_scores'), 'Helper survei hero yang tidak dipakai wajib dihapus.');
$ribbonStart = strpos($hero, '<div class="hero-service-ribbon"');
$ribbonEnd = $ribbonStart === false ? false : strpos($hero, '</div>', $ribbonStart);
$ribbonMarkup = $ribbonStart === false || $ribbonEnd === false ? '' : substr($hero, $ribbonStart, $ribbonEnd - $ribbonStart);
$expect(!str_contains($ribbonMarkup, 'IKM') && !str_contains($ribbonMarkup, 'IPAK'), 'IKM/IPAK tidak boleh diduplikasi di ribbon hero.');

// Hitung WCAG contrast ratio dari token. Ini menjaga hasil, bukan nama warna:
// token boleh diganti kelak selama tiga pasangannya tetap lulus 4,5:1.
$hexRgb = static function (string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
};
$luminance = static function (array $rgb): float {
    $linear = array_map(static function (int $channel): float {
        $value = $channel / 255;
        return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
    }, $rgb);
    return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
};
$contrast = static function (string $foreground, string $background) use ($hexRgb, $luminance): float {
    $a = $luminance($hexRgb($foreground));
    $b = $luminance($hexRgb($background));
    return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
};
$tokens = [];
foreach (['bg', 'fg', 'muted'] as $token) {
    if (preg_match('/--hero-ribbon-' . $token . ':\s*(#[0-9a-fA-F]{6})/', $css, $match)) {
        $tokens[$token] = $match[1];
    }
}
$expect(count($tokens) === 3, 'Ketiga token warna ribbon hero wajib berupa warna opak enam digit.');
if (count($tokens) === 3) {
    $expect($contrast($tokens['fg'], $tokens['bg']) >= 4.5, 'Teks utama ribbon hero gagal kontras 4,5:1.');
    $expect($contrast($tokens['muted'], $tokens['bg']) >= 4.5, 'Label/meta ribbon hero gagal kontras 4,5:1.');
    $expect($contrast('#ffd6ca', $tokens['bg']) >= 4.5, 'Status SIPP basi gagal kontras 4,5:1.');
}
$expect((bool) preg_match('/\.hero-service-ribbon\s*\{[^}]*background:\s*var\(--hero-ribbon-bg\)/s', $css), 'Ribbon harus memakai permukaan opak yang terukur, bukan alpha di atas foto.');
$expect((bool) preg_match('/\.hero-cinema \.hero-backdrop::after\s*\{[^}]*linear-gradient\(92deg,\s*rgba\(28, 12, 10, 0\.9\)/s', $css), 'Scrim teks hero wajib mempertahankan lapisan opak minimum pada kolom kiri.');
$expect((bool) preg_match('/\.hero-cinema \.hero-welcome-copy::before\s*\{[^}]*background:\s*#29130f/s', $css), 'Kolom copy hero wajib memiliki bidang tinta opak yang sama dengan ribbon.');
$expect(str_contains($hero, 'class="hero-welcome-label">Selamat Datang di'), 'Hero wajib membuka sambutan dengan label kecil, bukan menambah baris headline besar.');
$expect(str_contains($hero, 'seluruh wilayah hukum Pengadilan Negeri Natuna'), 'Copy hero wajib mencakup seluruh wilayah hukum, bukan hanya Kabupaten Natuna.');
$expect(!str_contains($hero, 'Melayani masyarakat pencari keadilan di Kabupaten Natuna'), 'Copy hero lama yang mempersempit wilayah wajib dihapus.');
$expect((bool) preg_match('/@media \(max-width:\s*560px\).*?\.hero-intro-desktop\s*\{\s*display:\s*none\s*!important/s', $css), 'Mobile wajib menampilkan satu varian intro, bukan desktop dan mobile sekaligus.');
// Proporsi masthead desktop. Headline 71,7px dengan label 14px menghasilkan
// rasio 1:5,1 dan memenuhi bidang tinta seperti poster. Putusan editorial:
// label 16px, headline maksimum 64px, bobot 700, line-height 1.
$expect((bool) preg_match('/@media \(min-width:\s*901px\).*?\.hero-welcome-label\s*\{[^}]*font-size:\s*var\(--step-0\)/s', $css), 'Label sambutan desktop wajib satu langkah lebih besar dari meta biasa.');
$expect((bool) preg_match('/@media \(min-width:\s*901px\).*?\.hero-welcome-copy h2\s*\{[^}]*font-size:\s*clamp\(3\.25rem,\s*4\.5vw,\s*4rem\)[^}]*font-weight:\s*700[^}]*line-height:\s*1/s', $css), 'Masthead desktop wajib memakai skala editorial 52-64px, bobot 700, line-height 1.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "accessibility performance hardening contract: ok\n";
