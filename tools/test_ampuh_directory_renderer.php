<?php
/** Focused contract check for AMPUH directory renderer. */
$dispatcher = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/com_content/article/default.php');
$source = (string) @file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/com_content/article/ampuh-directory.php');
$migration = (string) @file_get_contents(__DIR__ . '/../database/migrations/20260716_ampuh_directory.sql');
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$expect(str_contains($source, 'data-ampuh-directory'), 'Missing AMPUH root hook.');
$expect(str_contains($source, 'Buka Folder Utama AMPUH 2026'), 'Missing main Drive action.');
$expect(str_contains($source, 'aria-expanded="false"'), 'Disclosures must start closed.');
$expect(str_contains($source, 'rel="noopener noreferrer"'), 'Drive links must be isolated.');
$expect(str_contains($source, 'type="search"'), 'Missing search input.');
$expect(str_contains($source, 'data-ampuh-filter'), 'Missing GOBI filter hook.');
$expect(str_contains($source, 'aria-live="polite"'), 'Missing live result status.');
$expect(str_contains($source, 'data-ampuh-close-all'), 'Missing close-all control.');
$expect(str_contains($source, 'Buka folder checklist'), 'Missing checklist folder action.');
$expect(str_contains($source, 'Buka folder sub-checklist'), 'Missing sub-checklist folder action.');
$expect(str_contains($source, 'Tautan belum tersedia'), 'Missing unavailable Drive label.');
$expect(str_contains($source, 'htmlspecialchars($value, ENT_QUOTES, \'UTF-8\')'), 'Workbook values must be escaped.');
$expect(str_contains($source, 'JSON_THROW_ON_ERROR'), 'Dataset JSON parsing must reject invalid data.');
$expect(str_contains($source, 'data-search-text'), 'Missing server-normalized search text.');
$expect(str_contains($dispatcher, "require __DIR__ . '/ampuh-directory.php'"), 'Missing dispatcher.');
$expect(str_contains($migration, 'ampuh-2026'), 'Migration missing canonical article alias.');
$expect(str_contains($migration, "'ampuh'"), 'Migration missing canonical menu alias.');
$expect(str_contains($migration, 'WHERE NOT EXISTS'), 'Migration must be idempotent.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "AMPUH directory renderer contract: ok\n";
