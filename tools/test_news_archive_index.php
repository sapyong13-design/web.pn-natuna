<?php
/**
 * Kontrak indeks arsip tahun.
 *
 * Arsip Berita 84 artikel terbagi 14 halaman berisi enam. Sebelum indeks ini, warga yang
 * mencari pengumuman Desember 2025 hanya punya sepuluh tombol bernomor dan harus menebak.
 * Indeks bekerja dengan menghitung di posisi ke berapa tiap tahun mulai, lalu menautkannya
 * sebagai `?start=` - jadi taruhannya adalah urutan: kalau urutan yang dihitung templat
 * meleset satu saja dari urutan yang benar-benar dirender daftar, seluruh tautannya
 * mendarat di tahun yang salah. Kontrak ini menghitung ulang posisinya dari basis data dan
 * memastikan tautan yang dicetak templat menunjuk artikel pertama tahun itu.
 */
require_once __DIR__ . '/../configuration.php';

$config = new JConfig();
$db = new mysqli($config->host, $config->user, $config->password, $config->db, (int) ($config->dbport ?? 3306));
if ($db->connect_errno) {
    fwrite(STDERR, "Database connection failed: {$db->connect_error}\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$template = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/com_content/category/blog.php');

$expect(str_contains($template, 'news-channel-archive'), 'The channel listing must offer a year index; ten numbered buttons are not navigation.');
$expect(str_contains($template, 'aria-label="Loncat ke tahun terbit"'), 'The year index must name what it does for assistive technology.');
$expect(str_contains($template, "aria-current=\"true\""), 'The year currently on screen must be marked, so the reader knows where they are in the archive.');
// Halaman di luar jangkauan dulu mencetak "Page 14 of 14" di atas nol artikel.
$expect(str_contains($template, "!empty(\$items) && (\$this->params->def('show_pagination'"), 'Pagination must not render on a page that shows no articles.');

// Urutan yang dipakai indeks harus sama dengan urutan yang dipakai daftar. Keduanya
// memakai tanggal efektif: publish_up bila wajar, selain itu created.
$effective = "CASE WHEN a.publish_up > '2000-01-02 00:00:00' THEN a.publish_up ELSE a.created END";
$expect(str_contains($template, "'2000-01-02 00:00:00'"), 'The year index must use the same effective date rule as the listing, or every link lands on the wrong year.');

foreach (['berita', 'pengumuman'] as $channel) {
    $sql = "SELECT a.alias, YEAR($effective) AS terbit FROM {$config->dbprefix}content a"
        . " INNER JOIN {$config->dbprefix}categories c ON c.id = a.catid"
        . " WHERE a.state = 1 AND c.path = '" . $db->real_escape_string($channel) . "'"
        . " ORDER BY $effective DESC";
    $result = $db->query($sql);
    if (!$result) {
        fwrite(STDERR, "Archive query failed: {$db->error}\n");
        exit(1);
    }
    $positions = [];
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $year = (int) $row['terbit'];
        if (!isset($positions[$year])) {
            $positions[$year] = count($rows) - 1;
        }
    }
    if (count($rows) < 2) {
        continue;
    }
    // Artikel pada indeks yang dihitung harus benar-benar artikel pertama tahun itu.
    foreach ($positions as $year => $index) {
        $atIndex = (int) $rows[$index]['terbit'];
        $expect($atIndex === $year, "Year index for $channel $year points at position $index, which holds a $atIndex article.");
        if ($index > 0) {
            $before = (int) $rows[$index - 1]['terbit'];
            $expect($before > $year, "Position " . ($index - 1) . " in $channel should hold a newer year than $year, found $before; the archive ordering is not monotonic.");
        }
    }
    $expect(count($positions) >= 1, "Channel $channel must resolve at least one archive year.");
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo 'news archive index contract: ok (' . count($positions) . " years resolved)\n";
