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
    'gobis' => [[
        'number' => 1, 'name' => '1.0 <img>',
        'checklists' => [[
            'number' => 1, 'title' => 'Checklist <svg>', 'drive_url' => 'javascript:alert(1)',
            'subchecklists' => [[
                'number' => '1.1', 'title' => 'Sub <i>',
                'document_count' => 1, 'drive_url' => 'https://example.com/not-drive',
                'files' => ['Bukti <script>alert(1)</script>.pdf'],
            ]],
        ]],
    ]],
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
$expect(substr_count($html, 'Tautan belum tersedia') === 2, 'Invalid nonempty Drive URLs must yield unavailable labels.');
$expect(str_contains($html, '<dd>1</dd>'), 'Fixture aggregate counts must render.');
$expect(str_contains($html, '>GOBI 1 · &lt;img&gt;</button>'), 'Integral GOBI filter and disclosure labels must be informative and omit decimal suffixes.');
$expect(!str_contains($html, '>1.0 &lt;img&gt;</button>'), 'Bare dataset GOBI names must not become control labels.');

preg_match_all('/<button[^>]*aria-expanded="false"[^>]*aria-controls="([^"]+)"[^>]*>/', $html, $toggleMatches);
preg_match_all('/<div id="([^"]+)"[^>]* hidden>/', $html, $panelMatches);
$toggleIds = $toggleMatches[1];
$panelIds = $panelMatches[1];
$expect(count($toggleIds) === 4, 'Fixture must render four closed disclosure levels.');
$expect((bool) preg_match('/\sdata-ampuh-result(?:\s|>)/', $html), 'Searchable result nodes need behavior hooks.');
$expect(count($toggleIds) === count(array_unique($toggleIds)), 'Disclosure IDs must be unique.');
$expect(count($panelIds) === count(array_unique($panelIds)), 'Panel IDs must be unique.');
$expect($toggleIds === $panelIds, 'Every toggle aria-controls must match its hidden panel.');
$expect(str_contains($html, 'data-ampuh-panel'), 'Disclosure panels need behavior hooks.');
$expect(str_contains($html, 'data-ampuh-gobi-filter'), 'GOBI filter needs behavior hook.');
$css = (string) file_get_contents($root . '/templates/pn_natuna_2026/css/template.css');
$ampuhCss = substr($css, strpos($css, '/* AMPUH DIRECTORY 2026-07-13 */'));
$cssRule = static function (string $selector, string $declarations) use ($ampuhCss): bool {
    return (bool) preg_match('/' . preg_quote($selector, '/') . '\s*\{(?=[^}]*' . $declarations . ')[^}]*\}/s', $ampuhCss);
};
$expect(str_contains($css, 'AMPUH DIRECTORY 2026-07-13'), 'Missing AMPUH CSS section marker.');
foreach (['.ampuh-directory__header', '.ampuh-directory__gobi', '.ampuh-directory__checklist', '.ampuh-directory__subchecklist'] as $selector) {
    $expect($cssRule($selector, '[a-z-]+\s*:'), "Renderer selector {$selector} needs a CSS rule.");
}
$expect(!str_contains($css, '.ampuh-directory__hero'), 'Dead AMPUH hero alias must not remain.');
$expect($cssRule('.ampuh-directory [hidden]', 'display\s*:\s*none\s*!important'), 'Hidden panels must not occupy layout space.');
$expect((bool) preg_match('/\.ampuh-directory__drive\s*,\s*\.ampuh-directory__tools button\s*,\s*\.ampuh-directory \[data-ampuh-toggle\]\s*\{[^}]*min-height\s*:\s*44px/s', $css), 'All AMPUH interactive controls need 44px minimum targets.');
$expect($cssRule('.ampuh-directory__subchecklist li', 'overflow-wrap\s*:\s*anywhere'), 'File names must wrap anywhere.');
$expect((bool) preg_match('/@media \(max-width:\s*760px\)\s*\{.*?\.ampuh-directory__summary dl\s*\{[^}]*grid-template-columns\s*:\s*minmax\(0,\s*1fr\)/s', $ampuhCss), 'Mobile inventory must use one column.');
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
$expect((bool) preg_match('/body\.is-dark \.ampuh-directory__header > \.ampuh-directory__drive:hover\s*,\s*body\.is-dark \.ampuh-directory__header > \.ampuh-directory__drive:focus-visible\s*\{[^}]*color\s*:\s*var\(--color-ink\)/s', $ampuhCss), 'Dark primary action hover and focus must retain light foreground token.');
$expect((bool) preg_match('/\.ampuh-directory__header > \.ampuh-directory__drive:hover\s*,\s*\.ampuh-directory__header > \.ampuh-directory__drive:focus-visible\s*\{[^}]*background\s*:\s*var\(--color-primary-dark\)/s', $ampuhCss), 'Primary action hover and focus must use the contrast-tested primary-dark background token.');
$expect($cssRule('body.is-dark .ampuh-directory__header > .ampuh-directory__drive', 'color\s*:\s*var\(--color-ink\)'), 'Dark primary action must use light foreground token.');
if (isset($darkTokens[1], $darkTokens[2], $primaryToken[1])) {
    $expect($contrast($darkTokens[1], $darkTokens[2]) >= 4.5, 'Dark hero heading contrast must meet WCAG AA.');
    $expect($contrast($darkTokens[1], $primaryToken[1]) >= 4.5, 'Dark primary action contrast must meet WCAG AA.');
    if (isset($primaryDarkToken[1])) {
        $expect($contrast($darkTokens[1], $primaryDarkToken[1]) >= 4.5, 'Dark primary action hover contrast must meet WCAG AA.');
    }
}
$expect((bool) preg_match('/@media \(prefers-reduced-motion:\s*reduce\)\s*\{.*transition\s*:\s*none\s*!important.*transform\s*:\s*none/s', $ampuhCss), 'Reduced motion must remove transitions and transforms.');
preg_match_all('/transition\s*:\s*([^;}{]+)/', $ampuhCss, $transitionMatches);
foreach ($transitionMatches[1] as $transition) {
    $expect(trim($transition) === 'none !important' || !preg_match('/(?:^|,)\s*(?!opacity\b|transform\b)[a-z-]+/i', $transition), "AMPUH transitions permit only opacity or transform: {$transition}.");
}
$expect((bool) preg_match('/\.ampuh-directory__checklist > \.ampuh-directory__drive:hover[^\{]*\.ampuh-directory__subchecklist > \.ampuh-directory__drive:focus-visible\s*\{[^}]+\}/s', $css), 'Nested Drive links need hover and focus-visible states.');

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
