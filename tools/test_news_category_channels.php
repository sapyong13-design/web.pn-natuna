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
$expect(str_contains($item, "str_starts_with((string) (\$item->alias ?? ''), 'legacy-pengumuman-')"), 'Legacy announcement aliases must retain their announcement channel identity.');
$expect(str_contains($item, "\$showChannelLabel = \$itemChannelAlias !== '' && \$itemChannelAlias !== \$categoryAlias;"), 'Card channel label must be suppressed when it matches the viewed channel.');
$expect(!str_contains($blog, "Publikasi kegiatan pengadilan, pelayanan publik, pembinaan aparatur"), 'Berita channel must not repeat its purpose beneath the title.');
$expect(str_contains($item, "\$itemChannelAlias === 'pengumuman' ? 'Pengumuman resmi' : 'Berita'"), 'A differing card channel must retain its own label.');
$expect(str_contains($blog, 'Belum ada artikel pada halaman ini.'), 'Empty state must explain that the requested page has no articles in Indonesian.');
$expect(str_contains($blog, 'Kembali ke halaman pertama'), 'Empty state must offer a route back to the first channel page.');
$expect(str_contains($css, '.news-listing--cards { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));'), 'News listing must have two equal desktop columns; three made 349px cards that broke long court headlines into five lines.');
$expect(str_contains($css, '.news-card-media { display: block; overflow: hidden; aspect-ratio: 3 / 2;'), 'Card thumbnails must use the same 3:2 ratio as the article hero.');
$expect((bool) preg_match('/\.news-channel :is\(p, li\) \{[^}]*text-align: start/', $css), 'Channel pages must drop the inherited justify; card excerpts sit in 313px columns and Chrome has no Indonesian hyphenation.');
$expect(str_contains($css, '.news-listing--announcement'), 'Announcement row styles missing.');
$anchorCount = preg_match_all('/<a\b[^>]*>/', $item);
$expect($anchorCount === 1, 'Each news card template must contain exactly one anchor: the article title link.');
$expect((bool) preg_match('/<h2>\s*<a\b[^>]*href="<\?php echo \$link; \?>"[^>]*>/', $item), 'The card title h2 must contain the sole article link.');
$expect(!str_contains($item, 'read-more-link'), 'Cards must not contain a read-more-link anchor.');
$expect((bool) preg_match('/\.news-card\s*\{[^}]*position\s*:\s*relative/', $css), 'News cards require position: relative for the stretched title link.');
$expect(str_contains($css, '.news-card h2 a::after'), 'News card title link requires a stretched-link pseudo-element hook.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "news category channels contract: ok\n";
