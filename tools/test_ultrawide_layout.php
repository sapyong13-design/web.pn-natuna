<?php
declare(strict_types=1);

$cssPath = dirname(__DIR__) . '/templates/pn_natuna_2026/css/template.css';
$css = file_get_contents($cssPath);

if ($css === false) {
    fwrite(STDERR, "FAIL: template stylesheet cannot be read.\n");
    exit(1);
}

$checks = [
    '@media (min-width: 1921px)' => 'ultra-wide breakpoint is missing',
    'max-width: 1920px' => 'shell maximum width is missing',
    'margin-inline: auto' => 'shell centering is missing',
    'body.nav-stuck .main-menu' => 'sticky navigation containment is missing',
    'left: 50%' => 'sticky navigation centered anchor is missing',
    'margin-left: -960px' => 'sticky navigation centered offset is missing',
];

foreach ($checks as $needle => $message) {
    if (!str_contains($css, $needle)) {
        fwrite(STDERR, "FAIL: {$message}.\n");
        exit(1);
    }
}

fwrite(STDOUT, "ultrawide layout contract: ok\n");
