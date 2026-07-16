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

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "public facility photo contract: ok\n";
