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

assertSameValue('', pn_natuna_instansi_google_title('Mahkamah Agung Republik Indonesia - mahkamahagung.go.id'), 'MA site-homepage result must be discarded.');
assertSameValue('Ketua Mahkamah Agung Lantik Panmud Pidana dan Panmud Perdata Khusus MA', pn_natuna_instansi_google_title('KETUA MAHKAMAH AGUNG LANTIK PANMUD PIDANA DAN PANMUD PERDATA KHUSUS MA - mahkamahagung.go.id'), 'MA ALL CAPS title must become readable while preserving acronym MA.');
assertSameValue('Mahkamah Agung Hadiri RDPU dengan Komisi XI DPR RI', pn_natuna_instansi_google_title('MAHKAMAH AGUNG HADIRI RDPU DENGAN KOMISI XI DPR RI - mahkamahagung.go.id'), 'MA title must preserve Roman numeral and institutional acronyms.');
$fresh = pn_natuna_instansi_recent_items([
    ['title' => 'Lama', 'pub' => strtotime('2023-01-01')],
    ['title' => 'Terbaru kedua', 'pub' => strtotime('2026-07-09')],
    ['title' => 'Terbaru pertama', 'pub' => strtotime('2026-07-13')],
], 60, strtotime('2026-07-16'));
assertSameValue(['Terbaru pertama', 'Terbaru kedua'], array_column($fresh, 'title'), 'MA feed must sort newest first and drop stale results.');
assertSameValue([], pn_natuna_instansi_recent_items([['title' => 'RSS basi', 'pub' => strtotime('2026-04-27')]], 60, strtotime('2026-07-16')), 'Entirely stale RSS must fall back to curated data.');

$ptHtml = '<main><article><a href="/kepri/ketua-pengadilan-tinggi-kepulauan-riau-hadiri-penutupan-mtq-xii-tingkat-provinsi-kepulauan-riau-tahun-2026">Ketua Pengadilan Tinggi Kepulauan Riau Hadiri Penutupan MTQ XII Tingkat Provinsi Kepulauan Riau Tahun 2026</a><time>Jul 09, 2026</time></article></main>';
$ptLatest = pn_natuna_instansi_parse_items($ptHtml, 'https://pt-kepri.go.id/', ['/kepri/'], ['pengumuman', 'pengantar', 'visi', 'struktur', 'wilayah', 'yurisdiksi', 'sejarah', 'tugas', 'fungsi', 'kepaniteraan', 'pegawai', 'role-model']);
assertSameValue('Ketua Pengadilan Tinggi Kepulauan Riau Hadiri Penutupan MTQ XII Tingkat Provinsi Kepulauan Riau Tahun 2026', $ptLatest[0]['title'] ?? null, 'PT Kepri news titles containing Ketua must not be excluded.');

$source = (string) file_get_contents(dirname(__DIR__) . '/templates/pn_natuna_2026/instansi-feed.php');
if (!str_contains($source, "pn_natuna_instansi_fetch_google_news('site:mahkamahagung.go.id/id/berita')")) {
    fwrite(STDERR, "FAIL: MA Google News RSS must be used by the loader.\n");
    exit(1);
}
if (!str_contains($source, 'array_intersect_key($data, $meta)')) {
    fwrite(STDERR, "FAIL: renderer must exclude internal status metadata.\n");
    exit(1);
}
$fallback = pn_natuna_instansi_fallback();
assertSameValue('/images/brand/logo-badilum.png', $fallback['badilum']['logo'] ?? null, 'Badilum fallback must use the Badilum logo.');

fwrite(STDOUT, "2 institution feed tests passed.\n");
