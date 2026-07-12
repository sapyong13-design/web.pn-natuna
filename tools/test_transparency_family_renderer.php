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
$expect(str_contains($source, '<details'), 'Grouped navigation must use disclosure semantics.');
$expect(str_contains($source, "'mobile' : 'desktop'"), 'Navigation renderer must emit mobile and desktop variants.');
$expect(str_contains($source, '<h1>Transparansi dan Akuntabilitas</h1>'), 'Landing hero must own the page h1.');
$expect(str_contains($source, '<h1><?php echo $escape($item->title); ?></h1>'), 'Detail hero must own the page h1.');
$expect(str_contains($css, '.transparency-family__desktop-nav'), 'Missing grouped desktop navigation CSS.');
$expect(str_contains($css, '@media (max-width: 760px)'), 'Missing mobile disclosure breakpoint.');
$expect(str_contains($css, 'body.is-dark .transparency-family'), 'Missing dark-mode styling.');
$expect(!str_contains($source, 'Details</'), 'Renderer must not emit Joomla Details metadata.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "transparency family renderer contract: ok\n";
