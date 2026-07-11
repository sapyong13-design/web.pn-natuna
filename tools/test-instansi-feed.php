<?php
declare(strict_types=1);

define('_JEXEC', true);

require dirname(__DIR__) . '/templates/pn_natuna_2026/instansi-feed.php';

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

$html = '<article><a href="/kepri/pengadilan-tinggi-kepulauan-riau-gelar-sosialisasi-internal-aplikasi-inovasi-jurist-e-lid-dan-satya-monev">Pengadilan Tinggi Kepulauan Riau Gelar Sosialisasi Inte...</a></article>';
$items = pn_natuna_instansi_parse_items($html, 'https://pt-kepri.go.id/', ['/kepri/']);
assertSameValue('Pengadilan Tinggi Kepulauan Riau Gelar Sosialisasi Internal Aplikasi Inovasi Jurist E Lid Dan Satya Monev', $items[0]['title'] ?? null, 'PT Kepri title must be recovered from URL slug.');

$fallback = pn_natuna_instansi_fallback();
assertSameValue('/images/brand/logo-badilum.png', $fallback['badilum']['logo'] ?? null, 'Badilum fallback must use the Badilum logo.');

fwrite(STDOUT, "2 institution feed tests passed.\n");
