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

preg_match_all('/<button[^>]*aria-expanded="false"[^>]*aria-controls="([^"]+)"[^>]*>/', $html, $toggleMatches);
preg_match_all('/<div id="([^"]+)"[^>]* hidden>/', $html, $panelMatches);
$toggleIds = $toggleMatches[1];
$panelIds = $panelMatches[1];
$expect(count($toggleIds) === 4, 'Fixture must render four closed disclosure levels.');
$expect(count($toggleIds) === count(array_unique($toggleIds)), 'Disclosure IDs must be unique.');
$expect(count($panelIds) === count(array_unique($panelIds)), 'Panel IDs must be unique.');
$expect($toggleIds === $panelIds, 'Every toggle aria-controls must match its hidden panel.');
$expect(str_contains($html, 'data-ampuh-panel'), 'Disclosure panels need behavior hooks.');
$expect(str_contains($html, 'data-ampuh-gobi-filter'), 'GOBI filter needs behavior hook.');

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
