<?php
/** Focused contract check for public facility documentary photos. */
$root = dirname(__DIR__);
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$asset = $root . '/images/layanan/gallery/ruang-ptsp-2026.webp';
$expect(is_file($asset), 'Cinematic PTSP WebP is missing.');
if (is_file($asset)) {
    $info = getimagesize($asset);
    $expect(($info['mime'] ?? '') === 'image/webp', 'PTSP asset must be WebP.');
    $expect(($info[0] ?? 0) >= 1400, 'PTSP asset must be at least 1400px wide.');
    $ratio = ($info[1] ?? 0) > 0 ? $info[0] / $info[1] : 0;
    $expect($ratio >= 1.65 && $ratio <= 1.9, 'PTSP asset must use a cinematic landscape ratio.');
    $expect(filesize($asset) < 500 * 1024, 'PTSP asset must stay below 500KB.');
}

foreach (['akses-disabilitas-2026.webp', 'posbakum-2026.webp'] as $name) {
    $expect(is_file($root . '/images/layanan/gallery/' . $name), "Required facility asset {$name} is missing.");
}
$migrationPath = $root . '/database/migrations/20260716_public_facility_documentary_photos.sql';
$expect(is_file($migrationPath), 'Facility content migration is missing.');
if (is_file($migrationPath)) {
    $sql = (string) file_get_contents($migrationPath);
    $containsPayload = static function (string $needle) use ($sql): bool {
        return str_contains($sql, $needle) || str_contains($sql, strtoupper(bin2hex($needle)));
    };
    foreach (['ruang-ptsp-2026.webp', 'akses-disabilitas-2026.webp', 'posbakum-2026.webp'] as $name) {
        $expect($containsPayload($name), "Migration must reference {$name}.");
    }
    foreach (['Petugas Pelayanan Terpadu Satu Pintu Pengadilan Negeri Natuna', 'Kursi roda dan alat bantu mobilitas di Pengadilan Negeri Natuna', 'Meja layanan Pos Bantuan Hukum Pengadilan Negeri Natuna'] as $alt) {
        $expect($containsPayload($alt), "Migration is missing approved alt: {$alt}.");
    }
    $expect(str_contains($sql, 'id = 480') && str_contains($sql, "title = 'Galeri Fasilitas Publik'"), 'Gallery update must be narrowly guarded.');
    $expect($containsPayload('data-maklumat-zoom'), 'Documentary photos must use existing lightbox trigger.');
    $expect($containsPayload('loading="lazy" decoding="async"'), 'Documentary photos must load lazily and decode asynchronously.');
    foreach (['jenis-layanan-pada-ptsp-pengadilan-negeri-natuna', 'layanan-disabilitas', 'pos-bantuan-hukum'] as $alias) {
        $expect(str_contains($sql, "alias = '{$alias}'"), "Migration must guard exact alias {$alias}.");
    }
    $expect(substr_count($sql, 'SHA2(introtext, 256) = ') === 3, 'Each full article snapshot must guard its prior content hash.');
    foreach (['Petugas Pelayanan Terpadu Satu Pintu Pengadilan Negeri Natuna siap melayani masyarakat.', 'Kursi roda dan alat bantu mobilitas yang tersedia untuk pengguna layanan prioritas.', 'Meja layanan Pos Bantuan Hukum di area PTSP Pengadilan Negeri Natuna.'] as $caption) {
        $expect($containsPayload($caption), "Migration is missing approved caption: {$caption}.");
    }
    foreach (['biaya-jenis-layanan.png', 'waktu-layanan.png', 'Layanan per Kepaniteraan', 'Prinsip Layanan', 'Apa yang Bisa Anda Peroleh?'] as $preserved) {
        $expect($containsPayload($preserved), "Migration must preserve {$preserved}.");
    }
    foreach (['akses-disabilitas.jpg', 'posbakum.jpg', '2026-briefing-ptsp-1.jpeg'] as $legacy) {
        $expect(!$containsPayload($legacy), "Migration must remove legacy reference {$legacy}.");
    }
    $expect($containsPayload('facility-documentary facility-documentary--ptsp'), 'PTSP documentary needs a mobile-safe variant class.');
}

$variantMigrationPath = $root . '/database/migrations/20260720_facility_panel_size_variants.sql';
$expect(is_file($variantMigrationPath), 'Facility panel variant migration is missing.');
if (is_file($variantMigrationPath)) {
    $variantSql = (string) file_get_contents($variantMigrationPath);
    $expect(str_contains($variantSql, "alias = 'layanan-disabilitas'") && str_contains($variantSql, "alias = 'pos-bantuan-hukum'"), 'Panel variants must target exact aliases.');
    $expect(str_contains($variantSql, 'facility-documentary--disability') && str_contains($variantSql, 'facility-documentary--posbakum'), 'Panel variant classes are missing.');
    $expect(!str_contains($variantSql, 'facility-thumb') && !str_contains($variantSql, 'id = 480'), 'Homepage facility gallery must remain untouched.');
}
$css = (string) file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');
foreach (['.facility-documentary {', '.facility-documentary__media:focus-visible', 'body.is-dark .facility-documentary', '@media (max-width: 760px)', '@media (prefers-reduced-motion: reduce)'] as $selector) {
    $expect(str_contains($css, $selector), "Facility documentary CSS is missing {$selector}.");
}
$expect(str_contains($css, '.facility-documentary--ptsp .facility-documentary__media img') && str_contains($css, 'object-fit: contain;'), 'PTSP mobile crop must preserve all staff.');
$expect(str_contains($css, 'height: clamp(260px, 28vw, 320px);'), 'Desktop documentary height must be capped at 320px.');
$expect(str_contains($css, 'height: 200px;'), 'Mobile documentary height must be capped at 200px.');
foreach (['height: 380px;', 'height: 350px;', 'height: 360px;', 'height: 230px;', 'height: 220px;'] as $height) {
    $expect(str_contains($css, $height), "Facility variant CSS is missing {$height}.");
}


if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "public facility photo contract: ok\n";
