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
    'body.nav-stuck .main-menu' => 'sticky navigation containment is missing',
    'margin-left: -960px' => 'sticky navigation centered offset is missing',
];

$ultrawide = [];
if (preg_match('/@media \(min-width: 1921px\) \{([\s\S]*)\n\}/', $css, $match)) {
    $ultrawide = $match[1];
}
if (!str_contains($ultrawide, '.site-header,') || !str_contains($ultrawide, '.site-footer {') || !str_contains($ultrawide, 'margin-inline: auto;')) {
    fwrite(STDERR, "FAIL: ultra-wide shell group must center header through footer.\n");
    exit(1);
}
if (!preg_match('/body\.nav-stuck \.main-menu \{[^}]*left:\s*50%;[^}]*width:\s*1920px;[^}]*margin-left:\s*-960px;/s', $ultrawide)) {
    fwrite(STDERR, "FAIL: ultra-wide sticky navigation must center its 1920px frame.\n");
    exit(1);
}

foreach ($checks as $needle => $message) {
    if (!str_contains($css, $needle)) {
        fwrite(STDERR, "FAIL: {$message}.\n");
        exit(1);
    }
}

fwrite(STDOUT, "ultrawide layout contract: ok\n");
