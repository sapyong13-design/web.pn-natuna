<?php
/** Focused contract check for BMN announcements. */
$root = dirname(__DIR__);
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};
$assets = [
    'bmn-penetapan-pemenang-lelang-2026.webp',
    'bmn-pengumuman-lelang-2026.webp',
];
$hashes = [];
foreach ($assets as $name) {
    $path = $root . '/images/pengumuman/' . $name;
    $expect(is_file($path), "Missing BMN thumbnail {$name}.");
    if (is_file($path)) {
        $info = getimagesize($path);
        $expect(($info['mime'] ?? '') === 'image/webp', "{$name} must be WebP.");
        $expect(($info[0] ?? 0) >= 900, "{$name} must be at least 900px wide.");
        $expect(filesize($path) < 500 * 1024, "{$name} must stay below 500KB.");
        $hashes[] = hash_file('sha256', $path);
    }
}
if (count($hashes) === 2) $expect($hashes[0] !== $hashes[1], 'BMN thumbnails must be visually distinct files.');
if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "BMN announcement contract: ok\n";
