<?php
/** Kontrak blok Sistem resmi pada hasil Smart Search. */
$root = dirname(__DIR__);
$resultsPath = $root . '/templates/pn_natuna_2026/html/com_finder/search/default_results.php';
$cardPath = $root . '/templates/pn_natuna_2026/html/com_finder/search/default_caseroute.php';
$jsonPath = $root . '/templates/pn_natuna_2026/data/sistem-daring.json';
$cssPath = $root . '/templates/pn_natuna_2026/css/template.css';
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};
$results = is_file($resultsPath) ? (string) file_get_contents($resultsPath) : '';
$card = is_file($cardPath) ? (string) file_get_contents($cardPath) : '';
$css = is_file($cssPath) ? (string) file_get_contents($cssPath) : '';
$json = is_file($jsonPath) ? json_decode((string) file_get_contents($jsonPath), true) : null;

$expect($results !== '', 'Search results override is missing.');
$expect($card !== '', 'Official-systems partial is missing.');
$expect(is_array($json) && is_array($json['sistem'] ?? null), 'sistem-daring.json must be valid JSON with a sistem list.');
foreach (($json['sistem'] ?? []) as $index => $sistem) {
    $expect(is_array($sistem), "System entry {$index} must be an object.");
    foreach (['id', 'nama', 'url', 'keterangan', 'kataKunci'] as $key) {
        $expect(isset($sistem[$key]), "System entry {$index} is missing {$key}.");
    }
    $host = parse_url((string) ($sistem['url'] ?? ''), PHP_URL_HOST);
    $expect(is_string($host) && (str_ends_with($host, '.mahkamahagung.go.id') || str_ends_with($host, '.pn-natuna.go.id')), "System entry {$index} URL must remain on an official Mahkamah Agung or PN Natuna domain.");
}
$expect(str_contains($results, "json_decode(") && str_contains($results, 'sistem-daring.json'), 'Card data must be read from curated JSON, not a PHP literal.');
$expect(str_contains($results, 'mb_strtolower') && str_contains($results, "preg_replace('/\\s+/u'"), 'Queries and trigger phrases must be lowercased and whitespace-normalized.');
$expect(str_contains($results, "str_contains(' ' . \$kueriTernormalisasi . ' '") && str_contains($results, "' ' . \$frasaTernormalisasi . ' '"), 'System keywords must match complete normalized phrases, never loose substrings.');
$expect((bool) preg_match('/\$nomorPerkara\s*=\s*[\'\"]~\\\\b\\\\d\+/', $results), 'Case-number detection regex is missing.');
$expect(str_contains($results, "['sipp-perkara', 'ecourt']"), 'A case number must offer SIPP penelusuran and e-Court.');
$expect(str_contains($results, 'array_slice($caseSystems, 0, 2)'), 'Official-systems block may show at most two entries.');
$expect(str_contains($results, "require __DIR__ . '/default_caseroute.php'"), 'Matching queries must render the official-systems card.');
$expect(str_contains($results, '<?php else : ?>') && str_contains($results, 'site-search__list'), 'Official-systems card must not replace ordinary results.');
$expect(str_contains($card, 'Sistem resmi Mahkamah Agung'), 'Card title must identify official systems, not claim search results.');
$expect(str_contains($card, 'Pencarian situs tidak memeriksa data pada sistem daring berikut.'), 'Card must honestly state that site search does not inspect external-system data.');
$expect(str_contains($card, 'target="_blank" rel="noopener noreferrer"'), 'External system links must open safely in a new tab.');
$expect(!preg_match('/(?:hasil|ditemukan|menampilkan)\s+(?:SIPP|perkara)/iu', $card), 'Card must not claim SIPP search results or case data.');
$expect((bool) preg_match('/\.site-search__case-route-links a \{[^}]*min-height: 44px/', $css), 'Official-system links must retain 44px touch targets.');
$expect((bool) preg_match('/body\.is-dark \.site-search__case-route-links a \{[^}]*color: #fff4e8/', $css), 'Official-system links require an explicit dark-mode contrast color.');
if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "official systems card contract: ok\n";
