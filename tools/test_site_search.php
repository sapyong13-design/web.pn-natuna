<?php
/**
 * Kontrak pencarian situs.
 *
 * Kotak pencarian ada di setiap halaman, tetapi selama ini formulirnya menunjuk
 * `/component/search/` - `com_search` dihapus Joomla sejak versi 4, jadi setiap warga
 * yang mengetik kata kunci mendarat di 404. Penggantinya `com_finder` aktif tetapi
 * indeksnya hanya berisi 6 tautan dan tidak punya item menu situs, sehingga router
 * jatuh ke Beranda dan keluaran komponennya dibuang templat.
 *
 * Kontrak ini menjaga ketiga syarat itu tetap terpenuhi sekaligus: rutenya ada,
 * formulirnya menunjuk ke sana, dan indeksnya benar-benar berisi.
 */
require_once __DIR__ . '/../configuration.php';

$config = new JConfig();
$db = new mysqli($config->host, $config->user, $config->password, $config->db, (int) ($config->dbport ?? 3306));
if ($db->connect_errno) {
    fwrite(STDERR, "Database connection failed: {$db->connect_error}\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$root = dirname(__DIR__);
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$index = (string) file_get_contents($root . '/templates/pn_natuna_2026/index.php');
$error = (string) file_get_contents($root . '/templates/pn_natuna_2026/error.php');
$css = (string) file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');

// Komentar templat mengutip rute lama sebagai catatan sejarah, jadi pemeriksaan hanya
// membaca kode yang benar-benar dipancarkan - bukan komentarnya.
$tanpaKomentar = static fn(string $s): string => (string) preg_replace(['#/\*.*?\*/#s', '#<\?php\s*//.*?\?>#s', '#^\s*//.*$#m'], '', $s);
// Tidak boleh ada satu pun formulir yang masih menunjuk komponen yang sudah dihapus.
foreach (['index.php' => $tanpaKomentar($index), 'error.php' => $tanpaKomentar($error)] as $name => $source) {
    $expect(!str_contains($source, '/component/search'), "{$name} still posts to com_search, which Joomla removed in version 4; every search would land on a 404.");
    $expect(!str_contains($source, 'name="searchword"'), "{$name} still uses the com_search parameter name; com_finder reads `q`.");
    $expect(str_contains($source, 'name="q"'), "{$name} search field must be named `q`.");
    $expect(str_contains($source, 'action="/cari"') || str_contains($source, '/cari" method="get"'), "{$name} search form must post to the /cari route.");
}

// Rute hasil wajib ada sebagai item menu situs, kalau tidak router jatuh ke Beranda
// dan templat membuang keluaran komponen - halaman beranda tidak memuat jdoc component.
$menu = $db->query("SELECT link, published, client_id, level FROM {$config->dbprefix}menu WHERE path = 'cari' AND client_id = 0");
$row = $menu ? $menu->fetch_assoc() : null;
$expect($row !== null, 'The /cari route needs a site menu item; without one Joomla falls back to the home menu item and the component output is discarded.');
$expect($row !== null && str_contains((string) $row['link'], 'option=com_finder'), 'The /cari menu item must point at com_finder.');
$expect($row !== null && (int) $row['published'] === 1, 'The /cari menu item must be published.');

// Nested set menu wajib tetap konsisten sesudah migrasi menyisipkan item baru.
$bad = (int) $db->query("SELECT COUNT(*) c FROM {$config->dbprefix}menu WHERE lft >= rgt")->fetch_assoc()['c'];
$expect($bad === 0, "Menu nested set is broken: {$bad} rows have lft >= rgt.");

// Indeks yang kosong membuat pencarian tampak hidup tetapi tidak pernah menemukan apa pun.
$links = (int) $db->query("SELECT COUNT(*) c FROM {$config->dbprefix}finder_links")->fetch_assoc()['c'];
$published = (int) $db->query("SELECT COUNT(*) c FROM {$config->dbprefix}content WHERE state = 1")->fetch_assoc()['c'];
$expect($links >= (int) ($published * 0.8), "Smart Search index holds only {$links} links for {$published} published articles; run `php cli/joomla.php finder:index`.");

// Halaman hasil wajib berbahasa Indonesia dan punya jalan keluar saat kosong.
$layoutDir = $root . '/templates/pn_natuna_2026/html/com_finder/search';
foreach (['default.php', 'default_form.php', 'default_results.php', 'default_result.php'] as $file) {
    $expect(is_file($layoutDir . '/' . $file), "Search layout override {$file} is missing; the core layout renders the whole page in English.");
}
if (is_file($layoutDir . '/default_results.php')) {
    $results = (string) file_get_contents($layoutDir . '/default_results.php');
    $expect(str_contains($results, 'hasil untuk'), 'The result count must be stated in Indonesian.');
    $expect(str_contains($results, 'site-search__suggestions'), 'An empty search must offer routes out, not a dead end.');
}
if (is_file($layoutDir . '/default_result.php')) {
    $result = (string) file_get_contents($layoutDir . '/default_result.php');
    $expect(!str_contains($result, 'result__title-url'), 'Search results must not print raw URLs under each title.');
    $expect(str_contains($result, 'Desember'), 'Result dates must be written in Indonesian.');
}
// Judul dan cuplikan berasal dari isi apa adanya; satu kata panjang tanpa spasi pernah
// melebarkan halaman 17px di layar 390.
$expect((bool) preg_match('/\.site-search__item \{[^}]*overflow-wrap: anywhere/', $css), 'Search results must break long unspaced words; one legacy title overflowed the page at 390px.');
$expect((bool) preg_match('/\.site-search :is\(p, li\) \{[^}]*text-align: start/', $css), 'The search page must drop the inherited justify, like the article and channel pages.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "site search contract: ok ({$links} indexed links)\n";
