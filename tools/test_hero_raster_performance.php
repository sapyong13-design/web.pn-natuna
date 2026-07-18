<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$markup = file_get_contents($root . '/templates/pn_natuna_2026/hero-slider.php');
$css = file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');
$asset = $root . '/images/hero/gedung-pn-natuna-2026-graded.webp';

if ($markup === false || $css === false) {
    fwrite(STDERR, "FAIL: hero sources cannot be read.\n");
    exit(1);
}

$checks = [
    [$markup, 'class="hero-backdrop-image"', 'static feather wrapper is missing'],
    [$markup, '/images/hero/gedung-pn-natuna-2026-graded.webp', 'pre-graded hero asset is not used'],
    [$css, '.hero-cinema .hero-backdrop-image {', 'static feather wrapper CSS is missing'],
    [$css, '.hero-cinema .hero-backdrop-image > img {', 'inner animated image CSS is missing'],
    [$css, 'filter: none;', 'runtime image filter is not removed'],
    [$css, 'mask-image: linear-gradient', 'static feather mask is missing'],
];
foreach ($checks as [$source, $needle, $message]) {
    if (!str_contains($source, $needle)) {
        fwrite(STDERR, "FAIL: {$message}.\n");
        exit(1);
    }
}
if (!is_file($asset)) {
    fwrite(STDERR, "FAIL: pre-graded hero asset is missing.\n");
    exit(1);
}
$size = filesize($asset);
if ($size === false || $size > 350000) {
    fwrite(STDERR, "FAIL: pre-graded hero asset exceeds 350 KB.\n");
    exit(1);
}

fwrite(STDOUT, "hero raster performance contract: ok ({$size} bytes)\n");
