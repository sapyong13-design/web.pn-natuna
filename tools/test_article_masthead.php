<?php
/** Kontrak kop siaran dan dateline: identitas kedinasan tampil, dan tidak ada kata yang hilang. */
require_once __DIR__ . '/../configuration.php';
defined('_JEXEC') or define('_JEXEC', 1);
require_once __DIR__ . '/../templates/pn_natuna_2026/article-dateline.php';

$config = new JConfig();
$db = new mysqli($config->host, $config->user, $config->password, $config->db, (int) ($config->dbport ?? 3306));
if ($db->connect_errno) {
    fwrite(STDERR, "Database connection failed: {$db->connect_error}\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$root = dirname(__DIR__);
$template = (string) file_get_contents($root . '/templates/pn_natuna_2026/html/com_content/article/default.php');
$css = (string) file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};
$plain = static function (string $html): string {
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string) preg_replace('/\s+/u', ' ', $text));
};

// Kop siaran menggantikan kicker yang hanya mengulang teks tautan kembali.
$expect(str_contains($template, 'class="editorial-article__masthead"'), 'Article header must carry the official masthead.');
$expect(!str_contains($template, 'editorial-article__kicker'), 'The duplicated kicker must be gone.');
$expect(str_contains($template, '<span>Pengadilan Negeri Natuna Kelas II</span>'), 'Masthead must name the court exactly as the site does.');
$expect(!str_contains($template, "'Berita' : 'Pengumuman Resmi'"), 'Masthead must not repeat the channel the back link and meta row already state.');
$expect(str_contains($template, '/images/brand/logo-pn-natuna.webp'), 'Masthead must show the court emblem.');
$expect(is_file($root . '/images/brand/logo-pn-natuna.webp'), 'Court emblem file is missing.');
// Berkas lambangnya potret 179x232. Kotak bujur sangkar meletterboxingnya sampai
// terbaca sebagai cakram, jadi yang dikontrak adalah rasio dan tinggi minimalnya.
$emblemFile = $root . '/images/brand/logo-pn-natuna.webp';
$expect((bool) preg_match('/logo-pn-natuna\.webp" alt="" width="(\d+)" height="(\d+)"/', $template, $emblemBox), 'Emblem must be decorative and dimensioned to stop layout shift.');
if (isset($emblemBox[1]) && is_file($emblemFile) && ($emblemSize = @getimagesize($emblemFile))) {
    $declaredRatio = (int) $emblemBox[1] / (int) $emblemBox[2];
    $intrinsicRatio = $emblemSize[0] / $emblemSize[1];
    $expect(abs($declaredRatio - $intrinsicRatio) / $intrinsicRatio < 0.03, sprintf('Emblem box %sx%s does not match the artwork ratio %dx%d; it would letterbox into a coloured disc.', $emblemBox[1], $emblemBox[2], $emblemSize[0], $emblemSize[1]));
    $expect((int) $emblemBox[2] >= 36, 'Emblem must stay at least 36px tall to read as a seal.');
    $expect($emblemSize[1] >= 2 * (int) $emblemBox[2], 'Emblem artwork must carry enough pixels for a 2x display.');
}
$expect((bool) preg_match('/\.editorial-article__masthead img \{[^}]*width:\s*' . preg_quote($emblemBox[1] ?? '', '/') . 'px;\s*height:\s*' . preg_quote($emblemBox[2] ?? '', '/') . 'px/', $css), 'Emblem CSS box must match the attributes it reserves.');

// Fraunces bertinggi-x 0,45em; pada bobot 650 dan lebar 22ch judulnya terbaca lebih
// kecil daripada judul Medium 42px meski angkanya lebih besar.
$expect((bool) preg_match('/\.editorial-article \.editorial-article__title \{[^}]*font-weight:\s*(7|8|9)00/', $css), 'Article headline must stay at weight 700 or heavier to hold its own against a sans headline of the same size.');
$expect(!preg_match('/\.editorial-article \.editorial-article__title \{[^}]*max-width:\s*\d+ch/', $css), 'Article headline must use the full reading column, not a 22ch stack.');
// Judul memakai lebar kolom penuh, jadi tiap baris wajib mengisi kolomnya. Diukur pada
// seluruh 84 judul: `balance` rata-rata 89% dengan 12 judul di bawah 80%, `pretty` masih
// menarik kata turun (judul BPJS 72%), pembungkusan serakah 91% - dan jumlah barisnya
// sama persis di ketiga mode, jadi tidak ada tinggi yang ditukar. Kata yatim yang timbul
// ditangani templat lewat spasi tanpa-putus, bukan dengan memendekkan baris.
$expect(!preg_match('/\.editorial-article \.editorial-article__title \{[^}]*text-wrap:\s*(balance|pretty)/', $css), 'Article headline must fill its column; balance and pretty both leave a gutter beside the full-width photo.');
$expect(str_contains($template, '$headlineWords') && str_contains($template, '\u{00a0}'), 'A headline ending in a very short word must glue it to the previous word; greedy wrapping otherwise strands "II" and "RI" alone on the last line.');
$expect((bool) preg_match('/\.editorial-article \.editorial-article__masthead \{[^}]*border-bottom: 2px solid/', $css), 'Masthead needs its gold rule.');
$expect((bool) preg_match('/body\.is-dark \.editorial-article__masthead \{[^}]*\}/', $css), 'Masthead needs a dark mode.');
$expect((bool) preg_match('/\.editorial-article__dateline \{[^}]*font-weight: 800/', $css), 'Dateline needs its own weight.');
$expect((bool) preg_match('/:is\(\s*\.section-kicker,\s*\.editorial-article__masthead,/', $css), 'Masthead must stay inside the synthetic-weight guard.');

// Dateline dijalankan terhadap konten asli: tidak boleh ada kata yang hilang.
$places = 'natuna|ranai|tarempa|anambas|midai|serasan|bunguran|batam|tanjungpinang|jakarta';
$days = 'senin|selasa|rabu|kamis|jumat|sabtu|minggu';
$months = 'januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember';
$result = $db->query("SELECT id, alias, introtext, `fulltext` FROM {$config->dbprefix}content WHERE state = 1");
if (!$result) {
    fwrite(STDERR, "Content query failed: {$db->error}\n");
    exit(1);
}
$rewritten = 0;
while ($row = $result->fetch_assoc()) {
    $body = (string) $row['introtext'] . (string) $row['fulltext'];
    $after = pn_natuna_article_dateline($body);
    if ($after === $body) {
        continue;
    }
    $rewritten++;
    $label = '';
    if (preg_match('#<span class="editorial-article__dateline">(.*?)</span>#s', $after, $span)) {
        $label = $plain($span[1]);
    }
    $expect(substr_count($after, 'editorial-article__dateline') === 1, "Article {$row['alias']} must carry exactly one dateline.");
    $expect($label !== '', "Article {$row['alias']} produced an empty dateline.");

    $before = $plain($body);
    $tail = trim((string) mb_substr($plain($after), mb_strlen($label)));
    $expect($tail !== '' && str_ends_with(mb_strtolower($before), mb_strtolower($tail)), "Article {$row['alias']}: text after the dateline must survive untouched.");
    $removed = mb_substr($before, 0, max(0, mb_strlen($before) - mb_strlen($tail)));

    // Hanya tempat, hari, tanggal, dan tanda bacanya yang boleh hilang dari kalimat.
    foreach (preg_split('/[^\p{L}]+/u', $removed, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
        $expect((bool) preg_match('/^(?:' . $places . '|' . $days . '|' . $months . ')$/u', mb_strtolower($word)), "Article {$row['alias']}: dateline swallowed the word \"{$word}\".");
    }
    // Angka pada dateline harus benar-benar berasal dari kalimatnya, bukan dikarang.
    preg_match_all('/\d+/', $removed, $sourceNumbers);
    $sourceValues = array_map('intval', $sourceNumbers[0]);
    preg_match_all('/\d+/', $label, $labelNumbers);
    foreach (array_map('intval', $labelNumbers[0]) as $number) {
        $expect(in_array($number, $sourceValues, true), "Article {$row['alias']}: dateline invented the number {$number}.");
    }
    $expect((bool) preg_match('/^(?:' . $places . ')/u', mb_strtolower($label)), "Article {$row['alias']}: dateline must open with the place named in the text.");
}
$expect($rewritten >= 5, "Dateline must still cover the five datelined articles; it rewrote {$rewritten}.");

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "article masthead contract: ok ({$rewritten} datelines)\n";
