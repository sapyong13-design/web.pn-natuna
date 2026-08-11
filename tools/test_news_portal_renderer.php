<?php
/** Focused contract check for article ID 53 portal renderer. */
$source = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/com_content/article/default.php');
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$expect(str_contains($source, '(int) $item->id === 53'), 'Missing article ID 53 portal branch.');
$expect(str_contains($source, "trim((string) (\$images['image_fulltext'] ?? '')) ?: trim((string) (\$images['image_intro'] ?? ''))"), 'Images must prefer non-empty fulltext then intro.');
$expect(str_contains($source, "'a.images', 'a.introtext', 'a.fulltext'"), 'Portal query must fetch article bodies for automatic first-image fallback.');
$expect(str_contains($source, "(string) (\$portalItem->introtext ?? '')") && str_contains($source, "(string) (\$portalItem->fulltext ?? '')"), 'Portal cards must tolerate partial article objects without emitting undefined-property warnings.');
$expect(str_contains($source, "'2000-01-02 00:00:00'"), 'Dates must fall back from sentinel publish_up to created.');
$expect(str_contains($source, 'portalNews = $portalItems(12, 3, \'portalNewsCategory\')'), 'Missing category 12 query.');
$expect(str_contains($source, 'portalAnnouncements = $portalItems(13, 5, \'portalAnnouncementCategory\')'), 'Missing category 13 query.');
$expect(str_contains($source, 'news-portal__hero'), 'Missing semantic news portal hero.');
$expect(str_contains($source, '/images/hero/gedung-pn-natuna-2026.webp'), 'Portal must use courthouse hero WebP.');
$expect(str_contains($source, 'news-portal__news-card'), 'Missing news cards.');
$expect(str_contains($source, 'news-portal__announcement'), 'Missing announcement rows.');
$expect(str_contains($source, 'RouteHelper::getArticleRoute'), 'Portal links must use Joomla routes.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "news portal renderer contract: ok\n";
