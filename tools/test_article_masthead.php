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
$expect((bool) preg_match('/logo-pn-natuna\.webp" alt="" width="28" height="28"/', $template), 'Emblem must be decorative and dimensioned to stop layout shift.');

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
