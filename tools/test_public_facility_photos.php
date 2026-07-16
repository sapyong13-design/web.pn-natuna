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
}

$css = (string) file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');
foreach (['.facility-documentary {', '.facility-documentary__media:focus-visible', 'body.is-dark .facility-documentary', '@media (max-width: 760px)', '@media (prefers-reduced-motion: reduce)'] as $selector) {
    $expect(str_contains($css, $selector), "Facility documentary CSS is missing {$selector}.");
}


if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "public facility photo contract: ok\n";
