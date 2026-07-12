<?php
/** Focused contract check for nested news channel category overrides. */
$blog = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/com_content/category/blog.php');
$item = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/html/com_content/category/blog_item.php');
$css = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/css/template.css');
$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$expect(str_contains($blog, "in_array(\$categoryAlias, ['berita', 'pengumuman'], true)"), 'Category override must limit channel rendering to berita/pengumuman.');
$expect(str_contains($blog, '/berita-dan-pengumuman/berita'), 'Berita tab must use nested canonical path.');
$expect(str_contains($blog, '/berita-dan-pengumuman/pengumuman'), 'Pengumuman tab must use nested canonical path.');
$expect(str_contains($blog, 'news-channel-hero'), 'Channel must render portal-aligned hero.');
$expect(str_contains($item, "trim((string) (\$images->image_fulltext ?? '')) ?: trim((string) (\$images->image_intro ?? ''))"), 'Items must prefer non-empty fulltext image then intro image.');
$expect(str_contains($item, "'2000-01-02 00:00:00'"), 'Dates must fall back from sentinel publish_up to created.');
$expect(!str_contains($item, 'announcement-mark'), 'Announcements must not render PN mark cards.');
$expect(str_contains($css, '.news-channel-hero'), 'Channel CSS missing.');
$expect(str_contains($item, "in_array(\$categoryAlias, ['berita', 'pengumuman'], true)"), 'Item override must limit channel rendering to berita/pengumuman.');
$expect(str_contains($css, '.news-listing--cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));'), 'News listing must have three equal desktop columns.');
$expect(str_contains($css, '.news-listing--announcement'), 'Announcement row styles missing.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "news category channels contract: ok\n";
