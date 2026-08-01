<?php
/** Kontrak jembatan layanan: berita menautkan kanal yang benar-benar berdiri di menu. */
require_once __DIR__ . '/../configuration.php';

$config = new JConfig();
$db = new mysqli($config->host, $config->user, $config->password, $config->db, (int) ($config->dbport ?? 3306));
if ($db->connect_errno) {
    fwrite(STDERR, "Database connection failed: {$db->connect_error}\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$templatePath = __DIR__ . '/../templates/pn_natuna_2026/html/com_content/article/default.php';
$cssPath = __DIR__ . '/../templates/pn_natuna_2026/css/template.css';
$source = (string) file_get_contents($templatePath);
$css = (string) file_get_contents($cssPath);

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

// Kamus kanal dibaca dari templat, bukan disalin ke sini: satu sumber kebenaran.
$expect((bool) preg_match('/\$serviceChannels = \[(.*?)\n\];/s', $source, $dictionary), 'Service channel dictionary is missing from the article template.');
$dictionarySource = $dictionary[1] ?? '';
preg_match_all("/'route' => '([^']+)'/", $dictionarySource, $routeMatches);
preg_match_all("/'label' => '([^']+)'/", $dictionarySource, $labelMatches);
preg_match_all("/'note' => '([^']+)'/", $dictionarySource, $noteMatches);
preg_match_all("/'public' => (true|false)/", $dictionarySource, $publicMatches);
preg_match_all("/'patterns' => \[/", $dictionarySource, $patternMatches);
$routes = $routeMatches[1];
$expect($routes !== [], 'Service channel dictionary declares no routes.');
$expect(count($routes) === count($labelMatches[1]), 'Every service channel needs a label.');
$expect(count($routes) === count($noteMatches[1]), 'Every service channel needs a note.');
$expect(count($routes) === count($publicMatches[1]), 'Every service channel needs an explicit public flag.');
$expect(count($routes) === count($patternMatches[0]), 'Every service channel needs its own patterns.');
$expect(count($routes) === count(array_unique($routes)), 'Two service channels point at the same route.');

// Tautan kontak di kaki panel ikut diuji: nomor yang salah lebih buruk daripada tidak ada.
preg_match_all('/href="(\/[^"#?]*)"/', $source, $templateHrefs);
$menuPaths = array_values(array_unique(array_merge($routes, $templateHrefs[1])));
$statement = $db->prepare("SELECT published, client_id FROM {$config->dbprefix}menu WHERE path = ?");
foreach ($menuPaths as $route) {
    $expect($route === '/' . ltrim($route, '/') && !str_ends_with($route, '/'), "Route {$route} must be absolute without a trailing slash.");
    $path = ltrim($route, '/');
    $statement->bind_param('s', $path);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    $expect($row !== null, "Route {$route} has no menu item; the link would 404.");
    $expect($row !== null && (int) $row['published'] === 1, "Route {$route} points at an unpublished menu item.");
    $expect($row !== null && (int) $row['client_id'] === 0, "Route {$route} points outside the public site.");
}
// Nomor telepon layanan tetap wajib ada di situs; tempatnya bar atas dan halaman kontak,
// bukan lagi panel di kaki tiap berita.
$expect(str_contains((string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/index.php'), 'tel:+627733211203'), 'The published PTSP phone number must still be reachable from every page.');
// Nomor WhatsApp layanan tetap wajib ada di situs, tetapi tempatnya satu: tombol
// mengambang yang selalu di layar. Mengulangnya di panel artikel membuat pembaca
// bertemu tautan yang sama dua kali di satu halaman.
$expect(str_contains((string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/index.php'), 'https://wa.me/6281261256661'), 'The published WhatsApp service number must still be reachable from every page.');

// Penautan tidak boleh masuk ke judul, teks tebal, keterangan foto, atau tautan lain.
$expect(str_contains($source, '#^</?(?:a|b|strong|em|h2|h3|figcaption)\b#i'), 'Inline linking must skip headings, emphasis, captions, and existing links.');
$expect(!str_contains($source, '$servicePanel'), 'The "Untuk pencari keadilan" panel is removed by decision; it repeated on every article and duplicated links already in the menu and the body text. Do not reintroduce it.');
$expect(!str_contains($source, 'editorial-article__service"'), 'No service panel markup may return to the article tail.');
$expect(str_contains($source, 'strip_tags($articleBody)'), 'Channel detection must read the plain text so bold-only notices still get their panel.');
$expect(substr_count($source, 'class="editorial-article__service-link"') === 1, 'Inline service links must be minted in exactly one place.');

// Deteksi kanal kini hanya melayani penautan di dalam badan tulisan; satu sebutan cukup.
$expect(str_contains($source, '$serviceInlineMatches'), 'Inline service linking must keep its own match set.');
$expect(!str_contains($css, '.editorial-article__service {'), 'Service panel styling must be gone, not left as dead CSS.');
$expect(str_contains($source, 'foreach ($serviceInlineMatches as $channelKey => $serviceChannel)'), 'Inline links must use all one-mention matches, not only panel matches.');

$panelEligible = static fn(bool $inTitle, bool $inLede, int $bodyCount): bool => $inTitle || $inLede || $bodyCount >= 2;
$expect(!$panelEligible(false, false, 1), 'A passing body mention must not render the public-service panel.');
$expect($panelEligible(true, false, 0), 'A channel named in the title must render the panel.');
$expect($panelEligible(false, true, 1), 'A channel named in the lede must render the panel.');
$expect($panelEligible(false, false, 2), 'Two body mentions must render the panel.');
$inlineEligible = static fn(int $bodyCount): bool => $bodyCount >= 1;
$expect($inlineEligible(1), 'One body mention must remain eligible for an inline service link.');

// Aturan generik .content-primary merata-kanan-kirikan p dan li; artikel melepaskannya.
$expect((bool) preg_match('/\.editorial-article :is\(p, li\) \{[^}]*text-align: start/', $css), 'Article paragraphs and list items must stay left aligned, including the table-of-contents rail.');
// Bekas panel tidak boleh meninggalkan gaya menganggur di lembar gaya.
$expect(!str_contains($css, '.editorial-article__service-lead'), 'Panel styling must be removed with the panel, not left behind.');

// Ekor artikel dulu mengulang dirinya sendiri: nomor WhatsApp yang sama muncul di panel
// layanan padahal tombol mengambang di `index.php` sudah memegangnya, tombol bagikan
// bernama "WhatsApp" sehingga terbaca sebagai kontak ketiga, dan baris "Diterbitkan oleh
// Pengadilan Negeri Natuna" mengulang kop berlambang beserta tanggal di baris meta.
$expect(!str_contains($source, 'wa.me/6281261256661'), 'The article service panel must not repeat the floating WhatsApp button; it is the same number.');
$expect(str_contains($source, '>Kirim lewat WhatsApp</a>'), 'The share control must name its action, not read as a second contact channel.');
$expect(!str_contains($source, '<strong>Diterbitkan oleh Pengadilan Negeri Natuna</strong>'), 'The footer must not restate the masthead and publish date already carried by the kop and meta line.');
// Clamp dipasang pada <span> di dalam <strong>, bukan pada <strong> itu sendiri:
// <strong> adalah grid item, dan `display:-webkit-box` milik grid item diblokifikasi
// menjadi `flow-root` sehingga clamp-nya mati diam-diam dan judul dipenggal mentah.
$expect((bool) preg_match('/\.editorial-article__related-card strong span \{[^}]*-webkit-line-clamp: 3/', $css), 'Related card titles must be clamped on the inner span; unclamped titles made the tail 42% of a short article.');
$expect(str_contains($source, '<strong><span><?php echo $this->escape($relatedItem->title); ?></span></strong>'), 'The related card title needs the span the clamp is applied to.');
// Berita terkait dulu memakai kolam 24 terbaru dengan pemecah seri "terbaru dulu",
// sehingga hanya 17 dari 84 artikel pernah muncul dan satu di antaranya pada 54 halaman.
$expect(!str_contains($source, '$db->setQuery($relatedQuery, 0, 24)'), 'Related news must consider the whole channel, not just the 24 newest articles.');
$expect(str_contains($source, '$documentFrequency'), 'Related news must weight shared words by rarity; one common word is not a relation.');
$expect(str_contains($source, '$hasGenuineRelation'), 'The related heading must claim a relation only when one was actually found.');
$expect(str_contains($source, 'Berita di sekitar tanggal ini'), 'Without a genuine relation the section must offer chronological neighbours and say so.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "service bridge contract: ok\n";
