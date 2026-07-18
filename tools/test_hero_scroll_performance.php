<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$js = file_get_contents($root . '/templates/pn_natuna_2026/js/template.js');
$css = file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');

if ($js === false || $css === false) {
    fwrite(STDERR, "FAIL: hero runtime assets cannot be read.\n");
    exit(1);
}

$checks = [
    [$js, "hero.classList.add('is-scroll-active')", 'scroll-active state is not enabled'],
    [$js, "hero.classList.remove('is-scroll-active')", 'scroll-active state is not cleared'],
    [$js, 'window.requestAnimationFrame', 'scroll work is not frame-gated'],
    [$js, '120', 'scroll idle delay is missing'],
    [$css, '.hero.home-slider.is-scroll-active .hero-cinema .hero-backdrop img', 'backdrop pause selector is missing'],
    [$css, 'animation-play-state: paused', 'backdrop animation is not paused'],
];

foreach ($checks as [$haystack, $needle, $message]) {
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, "FAIL: {$message}.\n");
        exit(1);
    }
}

fwrite(STDOUT, "hero scroll performance contract: ok\n");
