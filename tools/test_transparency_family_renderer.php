<?php
/** Focused contract check for transparency family renderer. */
$dispatcher = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/com_content/article/default.php');
$source = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/com_content/article/transparency-family.php');
$css = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/css/template.css');
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$ids = [45, 37, 38, 39, 40, 86, 41, 42, 43, 85, 87, 88, 115, 116];
foreach ($ids as $id) {
    $expect(str_contains($source, (string) $id), "Missing transparency article ID {$id}.");
}
foreach (['Akuntabilitas Kinerja', 'Keuangan', 'Survei dan Integritas', 'Informasi Publik'] as $group) {
    $expect(str_contains($source, $group), "Missing group {$group}.");
}
foreach ([
    '/transparansi/ringkasan-lkjip', '/transparansi/laporan-tahunan', '/transparansi/sakip',
    '/transparansi/laporan-realisasi-anggaran', '/transparansi/laporan-keuangan', '/transparansi/lhkpn',
    '/transparansi/lelang-barang-jasa', '/transparansi/laporan-skm', '/transparansi/laporan-spak',
    '/transparansi/laporan-survei-harian', '/transparansi/e-brosur', '/transparansi/peraturan-kebijakan',
    '/transparansi/laporan-pelayanan-informasi-publik',
] as $route) {
    $expect(str_contains($source, $route), "Missing exact route {$route}.");
}
$expect(str_contains($dispatcher, "require __DIR__ . '/transparency-family.php'"), 'Transparency dispatch must happen from article override.');
$expect(str_contains($source, 'transparency-family'), 'Missing centralized transparency family renderer.');
$expect(str_contains($source, 'DOMDocument'), 'Content sanitizer must parse legacy markup.');
$expect(str_contains($source, 'aria-current="page"'), 'Current detail link must expose aria-current.');
$expect(!str_contains($source, '<details'), 'Transparency navigation must not use disclosure markup.');
$expect(str_contains($source, 'class="svc-subnav"'), 'Transparency detail pages must reuse service subnavigation.');
$expect(str_contains($source, 'class="svc-hero"'), 'Transparency landing must reuse service hero.');
$expect(str_contains($source, 'class="svc-grid"'), 'Transparency landing must reuse service grid.');
$expect(str_contains($source, 'class="svc-card"'), 'Transparency landing must reuse service cards.');
$expect(str_contains($source, 'class="svc-icon"'), 'Transparency cards need tailored icons.');
$expect(str_contains($source, 'class="svc-more"'), 'Transparency cards need contextual actions.');
$expect(str_contains($source, '$transparencyCards'), 'Transparency landing needs tailored card metadata.');
$expect(str_contains($source, "'Laporan Kinerja'"), 'Transparency submenu needs concise labels.');
$expect(str_contains($source, 'Portal Informasi Publik'), 'Transparency landing kicker must differ from the h1.');
$expect(str_contains($source, '$transparencyIllustration'), 'Transparency hero needs a dedicated multicolor service illustration.');
$expect(str_contains($source, 'class="svc-illus"'), 'Transparency hero illustration must use service illustration sizing.');
$expect(str_contains($source, '<h1>Transparansi dan Akuntabilitas</h1>'), 'Landing hero must own the page h1.');
$expect(str_contains($source, '<h1><?php echo $escape($item->title); ?></h1>'), 'Detail hero must own the page h1.');
$expect(!str_contains($source, 'transparency-family__gateway'), 'Landing must not duplicate navigation links.');
$expect(str_contains($css, 'body.is-dark .transparency-family'), 'Missing dark-mode styling.');
$expect(!str_contains($source, 'Details</'), 'Renderer must not emit Joomla Details metadata.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "transparency family renderer contract: ok\n";
