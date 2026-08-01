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
$expect(str_contains($source, 'href="tel:07733211203"'), 'Service panel must carry the published PTSP phone number.');
$expect(str_contains($source, 'https://wa.me/6281261256661'), 'Service panel must carry the published WhatsApp service number.');

// Penautan tidak boleh masuk ke judul, teks tebal, keterangan foto, atau tautan lain.
$expect(str_contains($source, '#^</?(?:a|b|strong|em|h2|h3|figcaption)\b#i'), 'Inline linking must skip headings, emphasis, captions, and existing links.');
$expect(str_contains($source, 'array_slice($serviceMatches, 0, 3, true)'), 'An article must offer at most three channels.');
$expect(str_contains($source, "\$serviceChannel['public']"), 'Internal channels must stay out of the reader-facing panel.');
$expect(str_contains($source, 'strip_tags($articleBody)'), 'Channel detection must read the plain text so bold-only notices still get their panel.');
$expect(substr_count($source, 'class="editorial-article__service-link"') === 1, 'Inline service links must be minted in exactly one place.');

// Aturan generik .content-primary merata-kanan-kirikan p dan li; artikel melepaskannya.
$expect((bool) preg_match('/\.editorial-article :is\(p, li\) \{[^}]*text-align: start/', $css), 'Article paragraphs and list items must stay left aligned, including the table-of-contents rail.');
$expect((bool) preg_match('/\.editorial-article__service \{[^}]*\}/', $css), 'Service panel needs its own styling.');
$expect((bool) preg_match('/body\.is-dark \.editorial-article__service \{[^}]*\}/', $css), 'Service panel needs a dark mode.');
$expect((bool) preg_match('/\.editorial-article__service li a \{[^}]*min-height: 44px/', $css), 'Service links must meet the 44px touch target.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "service bridge contract: ok\n";
