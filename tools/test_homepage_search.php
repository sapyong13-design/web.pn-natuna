<?php
/**
 * Kontrak kotak pencarian beranda.
 *
 * Modul 807 (`mod_custom`, posisi `home-search`) selama bertahun-tahun mengirim
 * ke `index.php?option=com_search&view=search`. Joomla menghapus `com_search`
 * pada versi 4, jadi setiap warga yang mengetik kata kunci di beranda mendarat
 * di 404 tanpa satu pun pesan galat yang menjelaskan. Migrasi 20260906
 * mengarahkannya ke `/cari` dengan parameter `q`.
 *
 * Dua hal lain ikut dikunci di sini karena keduanya rusak tanpa suara:
 *
 *   1. Setiap saran di `<datalist>` harus benar-benar menghasilkan sesuatu.
 *      "JDIH Peraturan" dulu nol hasil - saran yang menuntun ke halaman kosong
 *      lebih buruk daripada tidak ada saran, dan tidak ada yang memberi tahu
 *      kalau sebuah saran mati. Pemeriksaan menanyai tabel Smart Search
 *      langsung, bukan lewat HTTP, supaya kontrak ini tidak butuh peladen hidup.
 *
 *   2. Kartunya tidak boleh kembali ke dalam `<aside class="home-juknis-sidebar">`.
 *      Di ponsel rel samping ditumpuk sesudah seluruh isi utama lalu diubah
 *      menjadi korsel gulir mendatar; di dalamnya kartu pencarian berdiri di 91%
 *      tinggi halaman. Sebagai anak langsung `.home-juknis-layout` ia berada di
 *      12% - dan penempatan grid di >=1181px mengembalikannya ke puncak kolom rel.
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
$prefix = $config->dbprefix;
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

// --- Modul 807 ---------------------------------------------------------------
$module = $db->query(
    "SELECT content, module, position, published FROM {$prefix}modules WHERE id = 807"
)->fetch_assoc();

if ($module === null) {
    fwrite(STDERR, "Module 807 (the homepage search box) is missing.\n");
    exit(1);
}

$content = (string) $module['content'];
$expect($module['module'] === 'mod_custom', 'Module 807 must stay a mod_custom instance; the migration guards on it.');
$expect($module['position'] === 'home-search', 'Module 807 must stay in the home-search position.');
$expect((int) $module['published'] === 1, 'Module 807 must be published; an unpublished search box is an invisible one.');

$expect(!str_contains($content, 'com_search'), 'Module 807 still references com_search, which Joomla removed in version 4; every search from the homepage would land on a 404.');
$expect(!str_contains($content, 'searchword'), 'Module 807 still uses the com_search parameter name; com_finder reads `q`.');

$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML('<?xml encoding="utf-8" ?><body>' . $content . '</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
libxml_clear_errors();
$xpath = new DOMXPath($doc);

$form = $xpath->query('//form')->item(0);
$expect($form !== null, 'Module 807 must contain a form.');

if ($form instanceof DOMElement) {
    $expect($form->getAttribute('action') === '/cari', 'The homepage search form must post to the /cari route registered by migration 20260902.');
    $expect(strtolower($form->getAttribute('method')) === 'get', 'The homepage search form must use method="get"; /cari reads the query string.');
    $expect($form->getAttribute('role') === 'search', 'The homepage search form must expose role="search" so it is announced as a search landmark.');
    $expect(str_contains($form->getAttribute('class'), 'search-box'), 'The `search-box` class is the styling hook for this form; dropping it strips the card of its field and button styling.');

    $hidden = $xpath->query('.//input[translate(@type,"HIDEN","hiden")="hidden"]', $form);
    $expect($hidden->length === 0, "The form carries {$hidden->length} hidden field(s); the com_search `option`/`view` pair must not come back.");
}

$fields = $xpath->query('//input[translate(@type,"SEARCH","search")="search"]');
$expect($fields->length === 1, "The form must carry exactly one search field, found {$fields->length}.");

$field = $fields->item(0);
if ($field instanceof DOMElement) {
    $expect($field->getAttribute('name') === 'q', 'The homepage search field must be named `q`; com_finder ignores any other name.');

    // Judul modul berbunyi "Pencarian", jadi labelnya tersembunyi secara visual -
    // tetapi ia harus tetap ada dan tetap menunjuk medan isian yang benar.
    $id = $field->getAttribute('id');
    $expect($id !== '', 'The search field needs an id so the label can point at it.');
    $label = $id === '' ? null : $xpath->query('//label[@for="' . $id . '"]')->item(0);
    $expect($label instanceof DOMElement, 'The search field must have a <label for="…">; a placeholder is not an accessible name.');
    $expect($label instanceof DOMElement && str_contains($label->getAttribute('class'), 'visually-hidden'), 'The label must carry the `visually-hidden` class, not be removed or hidden with display:none.');
    $expect($label instanceof DOMElement && trim($label->textContent) !== '', 'The visually hidden label must actually say something.');
}

// --- Setiap saran harus berisi ------------------------------------------------
// Penelusuran meniru com_finder pada tabel indeks: kata dipisah spasi dan
// di-AND-kan, sedangkan potongan di dalam satu kata bertanda hubung ("e-Court")
// di-OR-kan, persis seperti Query::processString memperlakukan kata bertanda
// hubung. Hasilnya cocok dengan jumlah nyata di /cari untuk sepuluh dari sebelas
// kata uji, dan pada satu sisanya ("e-Court") menghitung kurang, bukan lebih -
// arah galat yang aman untuk ambang "minimal satu hasil".
$common = [];
$rows = $db->query("SELECT term FROM {$prefix}finder_terms_common");
while ($row = $rows->fetch_row()) {
    $common[$row[0]] = true;
}

$linksFor = static function (string $token) use ($db, $prefix): array {
    $escaped = $db->real_escape_string($token);
    $result = $db->query(
        "SELECT DISTINCT lt.link_id"
        . " FROM {$prefix}finder_links_terms lt"
        . " JOIN {$prefix}finder_terms ft ON ft.term_id = lt.term_id"
        . " JOIN {$prefix}finder_links l ON l.link_id = lt.link_id"
        . " WHERE (ft.term = '{$escaped}' OR ft.stem = '{$escaped}')"
        . " AND l.published = 1 AND l.state = 1 AND l.access <= 1"
    );
    $ids = [];
    while ($row = $result->fetch_row()) {
        $ids[(int) $row[0]] = true;
    }

    return $ids;
};

$countHits = static function (string $phrase) use ($linksFor, $common): int {
    $words = preg_split('/\s+/u', mb_strtolower($phrase, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $matched = null;
    foreach ($words as $word) {
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_values(array_filter($parts, static fn(string $part): bool => !isset($common[$part])));
        if (!$parts) {
            continue;
        }
        $any = [];
        foreach ($parts as $part) {
            $any += $linksFor($part);
        }
        $matched = $matched === null ? $any : array_intersect_key($matched, $any);
    }

    return $matched === null ? 0 : count($matched);
};

$options = $xpath->query('//datalist/option');
$expect($options->length > 0, 'The search box must keep its suggestion list; it is the only hint a citizen gets about what this site can answer.');

$counts = [];
foreach ($options as $option) {
    if (!$option instanceof DOMElement) {
        continue;
    }
    $value = $option->getAttribute('value') !== '' ? $option->getAttribute('value') : trim($option->textContent);
    $hits = $countHits($value);
    $counts[$value] = $hits;
    $expect($hits > 0, "Suggestion \"{$value}\" resolves to zero indexed results; a suggestion that leads to an empty page is worse than no suggestion.");
}

// --- Penempatan ---------------------------------------------------------------
$index = (string) file_get_contents($root . '/templates/pn_natuna_2026/index.php');
$css = (string) file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');

$includes = preg_match_all('/<jdoc:include\s+type="modules"\s+name="home-search"/', $index);
$expect($includes === 1, "The home-search position is included {$includes} time(s); it must appear exactly once or the module renders twice.");

// Kartu pencarian harus berdiri di luar `<aside>`. Diperiksa dengan memotong
// sumber pada pembuka aside: include-nya wajib berada di potongan sebelumnya.
$asideAt = strpos($index, '<aside class="home-juknis-sidebar"');
$searchAt = strpos($index, 'name="home-search"');
$expect($asideAt !== false, 'The homepage sidebar <aside> is gone; the rail markup changed shape.');
$expect($searchAt !== false && $asideAt !== false && $searchAt < $asideAt, 'The home-search include is back inside the sidebar <aside>; on phones that rail stacks after all of the main content and turns into a horizontal carousel, burying the search card at 91% of the page.');
$expect(str_contains($index, 'aria-label="Informasi pendukung"'), 'The sidebar <aside> must keep its aria-label; moving the search card out must not cost the rail its landmark name.');
$expect(str_contains($index, '<div class="home-search-slot">'), 'The search card needs its `home-search-slot` wrapper; the desktop grid placement and the card styling both hang off it.');

// Urutan rel di layar lebar: layanan, role model, survei, DIPA.
$rail = ['home-service-info', 'home-role-model', 'home-survey', 'home-dipa'];
$previous = $asideAt === false ? false : $asideAt;
foreach ($rail as $position) {
    $at = $previous === false ? false : strpos($index, 'name="' . $position . '"', $previous);
    $expect($at !== false, "Rail position {$position} is missing or out of order; the desktop rail must stay search, service-info, role-model, survey, dipa.");
    $previous = $at === false ? false : $at;
}

// Penempatan grid: tanpa ini kartu melayang di atas kolom utama pada layar lebar.
$expect((bool) preg_match('/@media \(min-width: 1181px\)[^@]*\.home-juknis-layout > \.home-search-slot \{[^}]*grid-column: 2/s', $css), 'The desktop grid placement is gone; above 1180px the search card must sit at the top of the sidebar column, not above the main column.');
$expect((bool) preg_match('/\.home-search-slot \.search-box input \{[^}]*min-height: 44px/s', $css), 'The search field must keep its 44px touch target.');
$expect((bool) preg_match('/\.home-search-slot \.search-box button \{[^}]*min-height: 44px/s', $css), 'The submit button must keep its 44px touch target.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

$summary = [];
foreach ($counts as $value => $hits) {
    $summary[] = "{$value}={$hits}";
}
echo 'homepage search contract: ok (' . implode(', ', $summary) . ")\n";
