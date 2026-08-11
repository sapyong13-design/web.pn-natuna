<?php
/** Focused behavioral contract for AMPUH directory renderer. */
$root = dirname(__DIR__);
$renderer = $root . '/templates/pn_natuna_2026/html/com_content/article/ampuh-directory.php';
$dispatcher = (string) file_get_contents($root . '/templates/pn_natuna_2026/html/com_content/article/default.php');
$migration = (string) file_get_contents($root . '/database/migrations/20260716_ampuh_directory.sql');
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};
$fixtureRoot = sys_get_temp_dir() . '/ampuh-renderer-' . bin2hex(random_bytes(6));
$fixturePath = $fixtureRoot . '/pn_natuna_2026/data';
mkdir($fixturePath, 0777, true);
$fixture = [
    'title' => 'AMPUH <script>alert(1)</script>',
    'main_drive_url' => 'https://drive.google.com/drive/folders/main',
    'summary' => 'Ringkasan <b>berbahaya</b>',
    'gobis' => [
    [
        'number' => 1, 'name' => '1.0 <img>',
        'checklists' => [[
            'number' => 1, 'title' => 'Checklist <svg>', 'drive_url' => 'javascript:alert(1)',
            'subchecklists' => [[
                'number' => '13.2', 'title' => '2. Sudah <i>',
                'document_count' => 1, 'drive_url' => 'https://example.com/not-drive',
                'files' => ['Bukti <script>alert(1)</script>.pdf', 'Rekap.xlsx', 'Berita.docx', 'Foto.png', 'Catatan.txt'],
            ]],
        ]],
    ],
    [
        'number' => 2, 'name' => 'Kedua',
        'checklists' => [[
            'number' => 1, 'title' => 'Checklist <svg>', 'drive_url' => 'javascript:alert(1)',
            'subchecklists' => [[
                'number' => '1.2', 'title' => 'Sub kedua',
                'document_count' => 0, 'drive_url' => 'https://drive.google.com/drive/folders/sub-valid', 'files' => [],
            ]],
        ]],
    ],
    ],
];
file_put_contents($fixturePath . '/ampuh-2026.json', json_encode($fixture, JSON_THROW_ON_ERROR));

define('_JEXEC', 1);
define('JPATH_THEMES', $fixtureRoot);
$item = (object) ['alias' => 'ampuh-2026', 'catid' => 9];
ob_start();
$returned = require $renderer;
$html = ob_get_clean();

$expect($returned === true, 'Canonical AMPUH alias must dispatch.');
$nonCanonicalItem = (object) ['alias' => 'other'];
$item = $nonCanonicalItem;
$expect((require $renderer) === false, 'Non-canonical aliases must not dispatch.');
$item = (object) ['alias' => 'ampuh-2026', 'catid' => 99];
$expect((require $renderer) === false, 'Canonical alias in another category must not dispatch.');
$expect(str_contains($dispatcher, "require __DIR__ . '/ampuh-directory.php'"), 'Missing dispatcher.');
$expect(str_contains($html, 'data-ampuh-directory'), 'Missing AMPUH root hook.');
$expect(str_contains($html, 'Buka Folder Utama AMPUH 2026'), 'Missing main Drive action.');
$expect(str_contains($html, 'Tautan belum tersedia'), 'Missing unavailable Drive label.');
$expect(!str_contains($html, '<script>alert(1)</script>'), 'Hostile workbook content must be escaped.');
$expect(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;'), 'Escaped hostile filename missing.');
$expect((bool) preg_match('/href="https:\/\/drive\.google\.com\/[^\"]+" target="_blank" rel="noopener noreferrer">Buka Folder Utama AMPUH 2026/', $html), 'Main Drive action must be isolated.');
$expect(!str_contains($html, 'href="javascript:alert(1)"'), 'JavaScript Drive URL must never render as a link.');
$expect(!str_contains($html, 'href="https://example.com/not-drive"'), 'Non-Drive URL must never render as a link.');
$expect(substr_count($html, 'Tautan belum tersedia') === 2, 'Only unavailable checklist URLs may render fallback labels; empty sub-checklist URLs stay silent.');
$expect(str_contains($html, 'class="ampuh-directory__sub-drive"><a class="ampuh-directory__drive" href="https://drive.google.com/drive/folders/sub-valid" target="_blank" rel="noopener noreferrer">Buka folder sub-checklist</a>'), 'Valid sub-checklist Drive URL must render its conditional action.');
$expect(!str_contains($html, 'https://example.com/not-drive">Buka folder sub-checklist'), 'Invalid sub-checklist Drive URL must remain silent.');
$expect(str_contains($html, '<dd>1</dd>'), 'Global summary must count split checklist number once.');
$expect(str_contains($html, '>GOBI 1 · &lt;img&gt;</button>'), 'Integral GOBI filter and disclosure labels must be informative and omit decimal suffixes.');
$expect(!str_contains($html, '>1.0 &lt;img&gt;</button>'), 'Bare dataset GOBI names must not become control labels.');
$expect((bool) preg_match('/data-ampuh-filter-value="1" aria-pressed="false"/', $html), 'Rendered GOBI filters must expose an initial unpressed state.');
$expect(str_contains($html, '1 checklist · 1 sub-checklist · 5 dokumen'), 'GOBI header must expose its scoped inventory count.');
$expect(str_contains($html, '1 sub-checklist · 5 dokumen'), 'Checklist header must expose its scoped inventory count.');
$expect((bool) preg_match('/<li data-ampuh-file-result data-file-type="PDF"><span class="ampuh-directory__file-name">Bukti &lt;script&gt;alert\(1\)&lt;\/script&gt;\.pdf<\/span><\/li>/', $html), 'PDF files need a generated type marker and filename hook.');
$expect(str_contains($html, 'data-file-type="SHEET"><span class="ampuh-directory__file-name">Rekap.xlsx</span>'), 'Spreadsheet files need deterministic type markers.');
$expect(str_contains($html, 'data-file-type="WORD"><span class="ampuh-directory__file-name">Berita.docx</span>'), 'Word files need deterministic type markers.');
$expect(str_contains($html, 'data-file-type="IMAGE"><span class="ampuh-directory__file-name">Foto.png</span>'), 'Image files need deterministic type markers.');
$expect(str_contains($html, 'data-file-type="FILE"><span class="ampuh-directory__file-name">Catatan.txt</span>'), 'Unknown extensions need generic type markers.');
$expect(str_contains($html, 'ampuh-directory__hero-secondary'), 'Hero needs institutional secondary field.');
$expect(str_contains($html, 'ampuh-directory__watermark') && str_contains($html, 'aria-hidden="true">2026'), 'Hero needs decorative 2026 watermark.');
$expect(str_contains($html, 'Indeks Koleksi'), 'Inventory needs an explicit collection-index label.');
$expect((bool) preg_match('/<select[^>]*data-ampuh-gobi-select[^>]*>.*?<option value="">Semua GOBI<\/option>.*?<option value="1">GOBI 1/s', $html), 'Mobile GOBI select needs all-GOBI and dataset options.');
$expect(substr_count($html, '<option value=') === 3, 'Fixture mobile select needs one all-GOBI option plus every GOBI.');
$expect(str_contains($html, 'ampuh-directory__gobi-number') && str_contains($html, 'ampuh-directory__gobi-title'), 'GOBI row needs separate number and title hooks.');
$expect(str_contains($html, 'ampuh-directory__sub-number') && str_contains($html, 'ampuh-directory__sub-title'), 'Sub-checklist row needs separate contents-style number and title hooks.');
$expect(!str_contains($html, '1.1. 1.1.'), 'Sub-checklist numbering must never duplicate visually.');
$expect(str_contains($html, 'class="ampuh-directory__sub-title">Sudah &lt;i&gt;</span>'), 'Compound sub-checklist number must strip source ordinal suffix without weakening escaping.');
$expect(!str_contains($html, '<h5>') && !str_contains($html, '-files"'), 'Document list must not add a fourth disclosure level.');
$expect((bool) preg_match('/<div id="ampuh-gobi-1-checklist-1-sub-13-2" data-ampuh-panel hidden><h5 class="ampuh-directory__files-heading">Daftar dokumen \(5\)<\/h5><ul class="ampuh-directory__files">/', $html), 'Opening a sub-checklist must expose its document heading and list immediately.');
$expect(str_contains($html, 'ampuh-directory__files'), 'Attachments need a file-list hook.');
$expect(str_contains($html, 'data-ampuh-file-result'), 'Document files need a dedicated result hook.');
$expect(!preg_match('/<li[^>]*data-ampuh-file-result[^>]*data-search-text=/', $html), 'Document filenames must not be duplicated into per-file search attributes.');
$expect(str_contains($html, 'data-ampuh-clear-search hidden'), 'Search tools need a hidden clear-search control.');
$expect(str_contains($html, 'class="ampuh-directory__file-name"'), 'Document names need a dedicated highlight-safe hook.');
$expect(!str_contains($html, '<section class="ampuh-directory__subchecklist" data-ampuh-result'), 'Branch nodes must not be document result nodes.');
$expect(str_contains($html, 'class="ampuh-directory__check-number"'), 'Checklist number needs a dedicated hook.');
$expect(str_contains($html, 'class="ampuh-directory__check-title"'), 'Checklist title needs a dedicated hook.');
$expect(str_contains($html, 'data-ampuh-checklist="1"'), 'Checklist nodes need a dedicated hierarchy-aware search hook.');
$expect(str_contains($html, 'data-search-text="checklist 1'), 'Checklist search text must include its semantic number.');
$expect(str_contains($html, 'data-ampuh-subchecklist="13.2"'), 'Sub-checklist nodes need a dedicated numbered search hook.');
$expect(str_contains($html, 'data-search-text="sub-checklist 13.2'), 'Sub-checklist search text must include its compound number.');
$expect(str_contains($html, 'name="q"'), 'Document search needs semantic query naming.');
$expect(str_contains($html, 'data-ampuh-filter-prev aria-label="Gulir GOBI ke kiri"'), 'Desktop GOBI rail needs a labeled previous control.');
$expect(str_contains($html, 'data-ampuh-filter-next aria-label="Gulir GOBI ke kanan"'), 'Desktop GOBI rail needs a labeled next control.');
$expect(str_contains($html, 'class="ampuh-directory__filter-window"'), 'GOBI rail needs a bounded fade viewport.');
$expect(!str_contains($html, '1 Checklist &lt;svg&gt;'), 'Checklist title must not duplicate its leading number.');
$expect(str_contains($html, 'placeholder="Cari GOBI, checklist, atau nama dokumen…"'), 'Search placeholder must end with an ellipsis.');

preg_match_all('/<button[^>]*aria-expanded="false"[^>]*aria-controls="([^"]+)"[^>]*>/', $html, $toggleMatches);
preg_match_all('/<div id="([^"]+)"[^>]* hidden>/', $html, $panelMatches);
$toggleIds = $toggleMatches[1];
$panelIds = $panelMatches[1];
$expect(count($toggleIds) === 6, 'Fixture must render only GOBI, checklist, and sub-checklist disclosure levels.');
$expect((bool) preg_match('/\sdata-ampuh-file-result(?:\s|>)/', $html), 'Searchable document nodes need behavior hooks.');
$expect(count($toggleIds) === count(array_unique($toggleIds)), 'Disclosure IDs must be unique.');
$expect(count($panelIds) === count(array_unique($panelIds)), 'Panel IDs must be unique.');
$expect($toggleIds === $panelIds, 'Every toggle aria-controls must match its hidden panel.');
$expect(str_contains($html, 'data-ampuh-panel'), 'Disclosure panels need behavior hooks.');
$expect(str_contains($html, 'data-ampuh-gobi-filter'), 'GOBI filter needs behavior hook.');
$css = (string) file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');
$ampuhStart = strpos($css, '/* AMPUH DIRECTORY 2026-07-13 */');
$ampuhEnd = strpos($css, '/* END AMPUH DIRECTORY 2026-07-13 */', $ampuhStart === false ? 0 : $ampuhStart);
$ampuhCss = $ampuhStart === false || $ampuhEnd === false
    ? ''
    : substr($css, $ampuhStart, $ampuhEnd - $ampuhStart);
$cssRule = static function (string $selector, string $declarations) use ($ampuhCss): bool {
    return (bool) preg_match('/' . preg_quote($selector, '/') . '\s*\{(?=[^}]*' . $declarations . ')[^}]*\}/s', $ampuhCss);
};
$expect(str_contains($css, 'AMPUH DIRECTORY 2026-07-13'), 'Missing AMPUH CSS section marker.');
foreach (['.ampuh-directory__header', '.ampuh-directory__gobi', '.ampuh-directory__checklist', '.ampuh-directory__subchecklist'] as $selector) {
    $expect($cssRule($selector, '[a-z-]+\s*:'), "Renderer selector {$selector} needs a CSS rule.");
}
$expect(!(bool) preg_match('/\.ampuh-directory__hero\s*[,\{]/', $css), 'Dead AMPUH hero alias must not remain.');
$expect($cssRule('.ampuh-directory [hidden]', 'display\s*:\s*none\s*!important'), 'Hidden panels must not occupy layout space.');
$expect($cssRule('.ampuh-directory__drive, .ampuh-directory__tools button, .ampuh-directory [data-ampuh-toggle]', 'min-height\s*:\s*44px'), 'All primary AMPUH interactive controls need 44px minimum targets.');
$expect($cssRule('.ampuh-directory__subchecklist li', 'overflow-wrap\s*:\s*anywhere'), 'File names must wrap anywhere.');
$expect((bool) preg_match('/\.ampuh-directory__tools\s*\{[^}]*max-height\s*:\s*220px/s', $ampuhCss), 'Desktop command toolbar needs a 220px height ceiling.');
$expect((bool) preg_match('/\.ampuh-directory__header\s*\{[^}]*min-height\s*:\s*350px/s', $ampuhCss), 'Desktop command hero needs a stable 350px minimum composition.');
$expect((bool) preg_match('/\.ampuh-directory__gobi > h2 \[data-ampuh-toggle\]\s*\{[^}]*min-height\s*:\s*88px/s', $ampuhCss), 'Desktop dossier rows need a strong 88px minimum hierarchy.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\).*?\.ampuh-directory__tools\s*\{[^}]*max-height\s*:\s*220px.*?\.ampuh-directory__gobi > h2 \[data-ampuh-toggle\]\s*\{[^}]*min-height\s*:\s*80px/s', $ampuhCss), 'Mobile toolbar and dossier rows need scoped compact bounds.');
$expect($cssRule('body.nav-stuck .ampuh-directory__tools', 'top\s*:\s*var\(--nav-height,\s*56px\)'), 'Desktop sticky tools must clear the fixed navigation.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\).*?body\.nav-stuck \.ampuh-directory__tools\s*\{[^}]*top\s*:\s*56px/s', $ampuhCss), 'Mobile sticky tools must clear the fixed 56px header.');
$expect((bool) preg_match('/\.ampuh-directory__gobi-select\s*\{[^}]*display\s*:\s*none/s', $ampuhCss), 'Mobile GOBI select must remain hidden on desktop.');
$expect($cssRule('.ampuh-directory__gobi-title', 'font-size\s*:\s*1\.5rem'), 'Desktop GOBI title needs prominent 1.5rem hierarchy.');
// Ukuran kecil kini diambil dari skala tipografi, bukan angka lepas. Yang
// dijaga tetap maksudnya: tipe kecil, bobot ringan, jarak judul jelas.
$expect((bool) preg_match('/\.ampuh-directory__meta\s*\{[^}]*margin-top\s*:\s*7px[^}]*font-size\s*:\s*var\(--step--2\)[^}]*font-weight\s*:\s*600/s', $ampuhCss), 'GOBI metadata needs smaller, lighter type with clear title spacing.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\).*?\.ampuh-directory__gobi-title\s*\{[^}]*font-size\s*:\s*1\.22rem/s', $ampuhCss), 'Mobile GOBI title needs prominent 1.22rem hierarchy.');
$expect($cssRule('.ampuh-directory__checklist', 'grid-template-columns\s*:\s*72px\s+minmax\(0,\s*1fr\)\s+190px'), 'Desktop checklist rows need a strong number, content, and action hierarchy.');
$expect($cssRule('.ampuh-directory__subchecklist', 'grid-template-columns\s*:\s*62px\s+minmax\(0,\s*1fr\)'), 'Desktop sub-checklist rows must use number and content columns without empty-link space.');
$expect($cssRule('.ampuh-directory__check-title', 'font-size\s*:\s*1\.12rem') && $cssRule('.ampuh-directory__check-title', 'line-height\s*:\s*1\.45'), 'Checklist titles need readable desktop type.');
$expect($cssRule('.ampuh-directory__sub-title', 'font-size\s*:\s*1rem') && $cssRule('.ampuh-directory__sub-title', 'line-height\s*:\s*1\.52'), 'Sub-checklist titles need readable desktop type.');
$expect(str_contains($ampuhCss, '.ampuh-directory__checklist > [data-ampuh-panel], .ampuh-directory__subchecklist > [data-ampuh-panel] { grid-column: 1/-1;'), 'Checklist disclosure panel must span all row areas.');
$expect($cssRule('.ampuh-directory__files-heading', 'font-size\s*:\s*var\(--step--2\)'), 'Ordinary document-list heading needs compact styling.');
$expect(!str_contains($ampuhCss, '.ampuh-directory__subchecklist h5 [data-ampuh-toggle]'), 'Removed document disclosure selector must not remain.');
$expect($cssRule('.ampuh-directory__tools', 'position\s*:\s*sticky'), 'AMPUH search tools need contextual sticky positioning.');
$expect($cssRule('.ampuh-directory__match', 'background\s*:\s*var\(--color-accent-soft\)'), 'Search matches need token-based highlighting.');
$expect($cssRule('.ampuh-directory__subchecklist li', 'transition\s*:\s*(?:opacity|transform)'), 'Document rows need restrained state motion.');
$expect((bool) preg_match('/@media \(prefers-reduced-motion:\s*reduce\).*?\.ampuh-directory \*[^}]*transition\s*:\s*none\s*!important/s', $ampuhCss), 'Reduced-motion mode must disable every AMPUH transition.');
$expect($cssRule('.ampuh-directory__filter-window', 'overflow\s*:\s*hidden'), 'GOBI filter window must hide native scrollbar overflow.');
$expect((bool) preg_match('/\.ampuh-directory__filter-nav\s*\{[^}]*display\s*:\s*grid/s', $ampuhCss), 'Desktop GOBI rail needs visible arrow navigation.');
$expect((bool) preg_match('/\.ampuh-directory__hero-main\s*>\s*\*\s*\{[^}]*animation\s*:\s*ampuh-command-enter/s', $ampuhCss), 'Command header content needs restrained entrance choreography.');
$expect((bool) preg_match('/@media \(prefers-reduced-motion:\s*reduce\).*?\.ampuh-directory__hero-main\s*>\s*\*[^}]*animation\s*:\s*none\s*!important/s', $ampuhCss), 'Reduced motion must disable command-header entrance choreography.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\).*?\.ampuh-directory__summary dl\s*\{[^}]*grid-template-columns\s*:\s*repeat\(2,\s*minmax\(0,\s*1fr\)\)/s', $ampuhCss), 'Mobile collection index must use a compact 2x2 grid.');
$expect((bool) preg_match('/body\.is-dark \.ampuh-directory\s*\{[^}]*color\s*:\s*var\(--color-ink\)[^}]*\}/s', $css), 'Dark AMPUH root needs token-based foreground.');
$hexToLuminance = static function (string $hex): float {
    $channels = array_map(static fn (string $channel): float => hexdec($channel) / 255, str_split(ltrim($hex, '#'), 2));
    $linear = array_map(static fn (float $channel): float => $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4, $channels);
    return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
};
$contrast = static function (string $foreground, string $background) use ($hexToLuminance): float {
    $light = max($hexToLuminance($foreground), $hexToLuminance($background));
    $dark = min($hexToLuminance($foreground), $hexToLuminance($background));
    return ($light + 0.05) / ($dark + 0.05);
};
preg_match('/body\.is-dark\s*\{[^}]*--color-ink:\s*(#[0-9a-f]{6})[^}]*--color-surface:\s*(#[0-9a-f]{6})/is', $css, $darkTokens);
preg_match('/:root\s*\{[^}]*--color-primary:\s*(#[0-9a-f]{6})/is', $css, $primaryToken);
$expect(isset($darkTokens[1], $darkTokens[2], $primaryToken[1]), 'Required dark and primary color tokens must resolve to hex values.');
$expect($cssRule('body.is-dark .ampuh-directory__header h1', 'color\s*:\s*var\(--color-ink\)'), 'Dark hero heading must use light foreground token.');
preg_match('/:root\s*\{[^}]*--color-primary-dark:\s*(#[0-9a-f]{6})/is', $css, $primaryDarkToken);
$expect(isset($primaryDarkToken[1]), 'Primary hover color token must resolve to a hex value.');
$expect((bool) preg_match('/body\.is-dark \.ampuh-directory__hero-main > \.ampuh-directory__drive:hover\s*,\s*body\.is-dark \.ampuh-directory__hero-main > \.ampuh-directory__drive:focus-visible\s*\{[^}]*color\s*:\s*var\(--color-ink\)/s', $ampuhCss), 'Dark primary action hover and focus must retain light foreground token.');
$expect((bool) preg_match('/\.ampuh-directory__hero-main > \.ampuh-directory__drive:hover\s*,\s*\.ampuh-directory__hero-main > \.ampuh-directory__drive:focus-visible\s*\{[^}]*background\s*:\s*var\(--color-primary-dark\)/s', $ampuhCss), 'Primary action hover and focus must use the contrast-tested primary-dark background token.');
$expect($cssRule('body.is-dark .ampuh-directory__hero-main > .ampuh-directory__drive', 'color\s*:\s*var\(--color-ink\)'), 'Dark primary action must use light foreground token.');
if (isset($darkTokens[1], $darkTokens[2], $primaryToken[1])) {
    $expect($contrast($darkTokens[1], $darkTokens[2]) >= 4.5, 'Dark hero heading contrast must meet WCAG AA.');
    $expect($contrast($darkTokens[1], $primaryToken[1]) >= 4.5, 'Dark primary action contrast must meet WCAG AA.');
    if (isset($primaryDarkToken[1])) {
        $expect($contrast($darkTokens[1], $primaryDarkToken[1]) >= 4.5, 'Dark primary action hover contrast must meet WCAG AA.');
    }
}
$expect((bool) preg_match('/@media \(prefers-reduced-motion:\s*reduce\)\s*\{.*transition\s*:\s*none\s*!important.*transform\s*:\s*none/s', $ampuhCss), 'Reduced motion must remove transitions and transforms.');
$expect((bool) preg_match('/\.ampuh-directory \[data-ampuh-panel\]\.is-revealing\s*\{[^}]*opacity\s*:\s*0[^}]*transform\s*:\s*translateY\(6px\)[^}]*transition\s*:\s*opacity\s+220ms\s+cubic-bezier\(\.16,1,\.3,1\),\s*transform\s+220ms\s+cubic-bezier\(\.16,1,\.3,1\)/s', $ampuhCss), 'Panels need a 220ms ease-out-quart opacity and 6px reveal state.');
$expect((bool) preg_match('/@media \(prefers-reduced-motion:\s*reduce\).*?\.ampuh-directory \[data-ampuh-panel\]\.is-revealing\s*\{[^}]*opacity\s*:\s*1[^}]*transform\s*:\s*none/s', $ampuhCss), 'Reduced motion must disable panel reveal movement.');
$expect((bool) preg_match('/\.ampuh-directory \[data-ampuh-toggle\]::before\s*,\s*\.ampuh-directory \[data-ampuh-toggle\]::after\s*\{[^}]*width\s*:\s*14px[^}]*height\s*:\s*2px/s', $ampuhCss), 'Disclosure icon must use two CSS lines.');
$expect((bool) preg_match('/\.ampuh-directory \[data-ampuh-toggle\]::before\s*\{[^}]*transform\s*:\s*translateY\(-50%\) rotate\(90deg\)/s', $ampuhCss), 'Disclosure vertical line needs a closed-state rotation.');
$expect((bool) preg_match('/\.ampuh-directory \[data-ampuh-toggle\]\[aria-expanded="true"\]::before\s*\{[^}]*opacity\s*:\s*0[^}]*transform\s*:\s*translateY\(-50%\) rotate\(0deg\)/s', $ampuhCss), 'Opening disclosure must fade and rotate its vertical line.');
$expect((bool) preg_match('/\.ampuh-directory__gobi\.is-expanded \.ampuh-directory__gobi-number\s*\{[^}]*transform\s*:\s*translateY\(-2px\)/s', $ampuhCss), 'Expanded GOBI number needs restrained lift feedback.');
$expect((bool) preg_match('/\.ampuh-directory \[data-ampuh-toggle\]:active[^}]*transform\s*:\s*scale\(\.985\)/s', $ampuhCss), 'Disclosure buttons need compact press feedback.');
$expect((bool) preg_match('/\.back-to-top\s*\{[^}]*transform\s*:\s*translateY\(8px\)[^}]*transition\s*:\s*opacity\s+180ms\s+cubic-bezier\(\.16,\s*1,\s*\.3,\s*1\),\s*transform\s+180ms/s', $css), 'Back-to-top needs an 8px, 180ms exponential entrance.');
preg_match_all('/transition\s*:\s*([^;}{]+)/', $ampuhCss, $transitionMatches);
foreach ($transitionMatches[1] as $transition) {
    $expect(trim($transition) === 'none !important' || !preg_match('/(?:^|,)\s*(?!opacity\b|transform\b)[a-z-]+/i', $transition), "AMPUH transitions permit only opacity or transform: {$transition}.");
}
$expect((bool) preg_match('/\.ampuh-directory__checklist > \.ampuh-directory__drive:hover[^\{]*\.ampuh-directory__checklist > \.ampuh-directory__drive:focus-visible\s*\{[^}]+\}/s', $css), 'Checklist Drive links need hover and focus-visible states.');
$expect((bool) preg_match('/\.ampuh-directory__sub-drive \.ampuh-directory__drive:hover[^\{]*\.ampuh-directory__sub-drive \.ampuh-directory__drive:focus-visible\s*\{[^}]+\}/s', $css), 'Conditional sub-checklist Drive links need hover and focus-visible states.');

$expect(str_contains($migration, "alias = 'ampuh-2026' AND catid = 9"), 'Article migration must scope canonical alias to category.');
$expect(str_contains($migration, "menu.menutype = 'hidden'"), 'Menu migration must canonicalize hidden menu location.');
$expect(str_contains($migration, "menu.path = 'ampuh'"), 'Menu migration must target canonical path.');
$expect(str_contains($migration, "menutype = 'hidden' AND parent_id = 1 AND language = '*'"), 'Menu migration must scope canonical menu identity.');
$expect(str_contains($migration, 'WHERE NOT EXISTS'), 'Migration must remain idempotent.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "AMPUH directory renderer contract: ok\n";
