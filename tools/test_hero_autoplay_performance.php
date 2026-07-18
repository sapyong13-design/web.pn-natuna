<?php
declare(strict_types=1);

$jsPath = dirname(__DIR__) . '/templates/pn_natuna_2026/js/template.js';
$js = file_get_contents($jsPath);

if ($js === false) {
    fwrite(STDERR, "FAIL: template JavaScript cannot be read.\n");
    exit(1);
}

$guard = "if (root.closest('.is-scroll-active')) return;";
if (!str_contains($js, $guard)) {
    fwrite(STDERR, "FAIL: hero autoplay is not guarded during active scroll.\n");
    exit(1);
}
if (!str_contains($js, 'window.setInterval')) {
    fwrite(STDERR, "FAIL: carousel autoplay interval is missing.\n");
    exit(1);
}

fwrite(STDOUT, "hero autoplay performance contract: ok\n");
