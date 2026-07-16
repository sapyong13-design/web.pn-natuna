<?php
/** Focused contract check for BMN announcements. */
$root = dirname(__DIR__);
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};
$assets = [
    'bmn-penetapan-pemenang-lelang-2026.webp',
    'bmn-pengumuman-lelang-2026.webp',
];
$hashes = [];
foreach ($assets as $name) {
    $path = $root . '/images/pengumuman/' . $name;
    $expect(is_file($path), "Missing BMN thumbnail {$name}.");
    if (is_file($path)) {
        $info = getimagesize($path);
        $expect(($info['mime'] ?? '') === 'image/webp', "{$name} must be WebP.");
        $expect(($info[0] ?? 0) >= 900, "{$name} must be at least 900px wide.");
        $expect(filesize($path) < 500 * 1024, "{$name} must stay below 500KB.");
        $hashes[] = hash_file('sha256', $path);
    }
}
$migrationPath = $root . '/database/migrations/20260716_import_bmn_announcements.sql';
$expect(is_file($migrationPath), 'BMN announcement migration is missing.');
if (is_file($migrationPath)) {
    $sql = (string) file_get_contents($migrationPath);
    $contains = static fn(string $value): bool => str_contains($sql, $value) || str_contains($sql, strtoupper(bin2hex($value)));
    foreach (['Pengumuman Penetapan Pemenang Lelang BMN', 'Pengumuman Lelang BMN Pada Pengadilan Negeri Natuna', 'pengumuman-penetapan-pemenang-lelang-bmn', 'pengumuman-lelang-bmn-pengadilan-negeri-natuna', '1KDGdzwbuK0Wbqu_3MlbjHTrpDgpl3Td6', '1E4v21cQPCrXDP6F3rXNZWCWeobzmRB-s'] as $value) {
        $expect($contains($value), "Migration is missing {$value}.");
    }
    $expect(substr_count($sql, 'catid,created') === 2, 'Migration must insert exactly two category-bound articles.');
    $expect(substr_count($sql, ',1,13,') === 2, 'Both BMN articles must be published in category 13.');
    $expect(str_contains($sql, 'LOWER(TRIM(title))'), 'Migration must deduplicate normalized titles.');
    $expect(str_contains($sql, 'JSON_UNQUOTE(JSON_EXTRACT(metadata'), 'Migration must deduplicate document URLs.');
    $expect(str_contains($sql, 'alias COLLATE utf8mb4_unicode_ci IN'), 'Migration must deduplicate aliases.');
    $expect(str_contains($sql, 'COLLATE utf8mb4_unicode_ci'), 'BMN identity predicates must use Joomla column collation.');
    $expect($contains('target="_blank" rel="noopener noreferrer"'), 'Document links must open safely.');
    $expect($contains('4 Juni 2026') && $contains('11 Juni 2026') && $contains('16 Juli 2026'), 'Articles must preserve official document dates and retrieval provenance.');
    $winnerPayload = implode('|', ['Pengumuman Penetapan Pemenang Lelang BMN', '11 Juni 2026', '1KDGdzwbuK0Wbqu_3MlbjHTrpDgpl3Td6', 'bmn-penetapan-pemenang-lelang-2026.webp']);
    $auctionPayload = implode('|', ['Pengumuman Lelang BMN Pada Pengadilan Negeri Natuna', '4 Juni 2026', '1E4v21cQPCrXDP6F3rXNZWCWeobzmRB-s', 'bmn-pengumuman-lelang-2026.webp']);
    foreach ([$winnerPayload, $auctionPayload] as $payload) {
        foreach (explode('|', $payload) as $value) $expect($contains($value), "Paired BMN payload is missing {$value}.");
    }
}
$reconcilePath = $root . '/database/migrations/20260721_reconcile_bmn_announcements.sql';
$expect(is_file($reconcilePath), 'BMN reconciliation migration is missing.');
if (is_file($reconcilePath)) {
    $reconcile = (string) file_get_contents($reconcilePath);
    $expect(str_contains($reconcile, 'id <> @bmn_id') && str_contains($reconcile, 'state = -2'), 'Reconciliation must trash additional identity matches.');
    $expect(substr_count($reconcile, 'created = ') === 2 && substr_count($reconcile, 'publish_up = ') === 2, 'Canonical rows must use official chronology.');
}

$blocks = preg_split('/-- payload [12]:/', $sql ?? '');
if (count($blocks) === 3) {
    foreach ([
        [$blocks[1], 'Pengumuman Penetapan Pemenang Lelang BMN', '11 Juni 2026', '1KDGdzwbuK0Wbqu_3MlbjHTrpDgpl3Td6', 'bmn-penetapan-pemenang-lelang-2026.webp'],
        [$blocks[2], 'Pengumuman Lelang BMN Pada Pengadilan Negeri Natuna', '4 Juni 2026', '1E4v21cQPCrXDP6F3rXNZWCWeobzmRB-s', 'bmn-pengumuman-lelang-2026.webp'],
    ] as [$block, $title, $date, $driveId, $image]) {
        foreach ([$title, $date, $driveId, $image] as $value) {
            $expect(str_contains($block, $value) || str_contains($block, strtoupper(bin2hex($value))), "Bounded BMN payload is missing {$value}.");
        }
    }
} else {
    $expect(false, 'Migration must contain exactly two bounded payload blocks.');
}

if (count($hashes) === 2) $expect($hashes[0] !== $hashes[1], 'BMN thumbnails must be visually distinct files.');
if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "BMN announcement contract: ok\n";
