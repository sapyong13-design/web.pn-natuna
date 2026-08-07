<?php
/** Focused contract check for latest announcements showcase. */
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
require_once JPATH_BASE . '/templates/pn_natuna_2026/youtube-feed.php';
require_once JPATH_BASE . '/templates/pn_natuna_2026/hero-slider.php';

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$expect(function_exists('pn_natuna_render_latest_announcements'), 'Announcement renderer is missing.');

if (function_exists('pn_natuna_render_latest_announcements')) {
    $fixture = static function (int $id, string $title, string $date, array $images = [], string $intro = ''): object {
        return (object) [
            'id' => $id,
            'title' => $title,
            'alias' => 'pengumuman-' . $id,
            'catid' => 13,
            'created' => $date,
            'publish_up' => $date,
            'images' => json_encode($images, JSON_UNESCAPED_SLASHES),
            'introtext' => $intro,
        ];
    };
    $fixtures = [
        $fixture(901, 'Pengumuman 901', '2026-07-16 09:00:00', ['image_fulltext' => 'images/full-901.webp', 'image_intro' => 'images/intro-901.webp'], 'Ringkasan pertama.'),
        $fixture(902, 'Pengumuman 902', '2026-07-15 09:00:00', ['image_fulltext' => '', 'image_intro' => 'images/intro-902.webp']),
    ];
    $videos = array_merge(pn_natuna_youtube_pinned(), [
        ['id' => 'abcdefghijk', 'title' => 'Sidang keliling Natuna', 'published' => '2026-07-15T09:00:00+00:00', 'url' => 'https://www.youtube.com/watch?v=abcdefghijk', 'thumbnail' => 'https://i.ytimg.com/vi/abcdefghijk/hqdefault.jpg', 'source' => 'terbaru'],
        ['id' => 'lmnopqrstuv', 'title' => 'Pelayanan PTSP', 'published' => '2026-07-14T09:00:00+00:00', 'url' => 'https://www.youtube.com/watch?v=lmnopqrstuv', 'thumbnail' => 'https://i.ytimg.com/vi/lmnopqrstuv/hqdefault.jpg', 'source' => 'terbaru'],
        ['id' => 'wxyzABCDEFG', 'title' => 'Kegiatan pengadilan', 'published' => '2026-07-13T09:00:00+00:00', 'url' => 'https://www.youtube.com/watch?v=wxyzABCDEFG', 'thumbnail' => 'https://i.ytimg.com/vi/wxyzABCDEFG/hqdefault.jpg', 'source' => 'terbaru'],
        ['id' => 'HIJKLMNOPQR', 'title' => 'Video keenam', 'published' => '2026-07-12T09:00:00+00:00', 'url' => 'https://www.youtube.com/watch?v=HIJKLMNOPQR', 'thumbnail' => 'https://i.ytimg.com/vi/HIJKLMNOPQR/hqdefault.jpg', 'source' => 'terbaru'],
    ]);
    $render = static function (array $items, array $videoItems) : string {
        ob_start();
        pn_natuna_render_latest_announcements($items, $videoItems);
        return (string) ob_get_clean();
    };

    $html = $render($fixtures, $videos);
    $expect(str_contains($html, 'Informasi Resmi &amp; Dokumentasi'), 'Showcase kicker is missing.');
    $expect(str_contains($html, '<h2 id="announcement-showcase-title">Pengumuman &amp; Video Terbaru</h2>'), 'Showcase heading is missing.');
    $expect(str_contains($html, 'href="/pengumuman"'), 'Archive action must target /pengumuman.');
    $expect(str_contains($html, 'youtube.com/channel/UCuPb35OggK2PKdW7Ed0qszA'), 'Official channel action is missing.');
    $expect(substr_count($html, 'class="announcement-feature"') === 1, 'Exactly one feature item is required.');
    $expect(!str_contains($html, 'announcement-compact'), 'Compact announcements must be removed.');
    $expect(substr_count($html, 'class="youtube-showcase-player"') === 1, 'Exactly one player shell is required.');
    $expect(substr_count($html, 'data-youtube-item') === 5, 'Video rail must contain at most five items.');
    $expect(substr_count($html, 'data-video-source="wajib"') === 2, 'Two pinned videos must preserve their editorial source metadata.');
    $expect(str_contains($html, 'data-video-id="-Di2t-yUZ1I"'), 'Rail video ID is missing.');
    $expect(str_contains($html, 'data-video-title="Video Profile Pengadilan Negeri / Perikanan Ranai"'), 'Rail video title is missing.');
    $expect(str_contains($html, 'data-video-thumbnail="https://i.ytimg.com/vi/-Di2t-yUZ1I/hqdefault.jpg"'), 'Rail thumbnail is missing.');
    $expect(substr_count($html, 'aria-current="true"') === 1, 'Exactly one video must be current.');
    $expect(str_contains($html, 'Tonton di YouTube'), 'Fallback YouTube link is missing.');
    $expect(str_contains($html, 'aria-live="polite"'), 'Player status live region is missing.');
    $expect(!str_contains($html, '<iframe'), 'Initial markup must not contain an iframe.');
    $expect(str_contains($html, '<ul class="youtube-showcase-rail" aria-label="Pilih video">'), 'Video rail must use a native unordered list.');
    $expect(preg_match('/<li>\s*<button\b/s', $html) === 1, 'Each video rail item must contain a native button.');
    $expect(!str_contains($html, 'role="listitem"'), 'Video buttons must not override native button semantics with listitem role.');
    $expect(str_contains($html, 'announcement-feature__eyebrow'), 'Announcement editorial label is missing.');
    $expect(str_contains($html, 'youtube-showcase__eyebrow'), 'Video editorial label is missing.');
    $expect(str_contains($html, 'announcement-showcase__channel-link'), 'Channel action must use quieter text-link styling.');
    $expect(str_contains($html, 'youtube-showcase-player__meta'), 'Player channel context is missing.');
    $expect(str_contains($html, 'youtube-showcase-play__label'), 'Player must separate icon and accessible play label.');
    $expect(str_contains($html, 'data-youtube-source'), 'Player source context hook is missing.');
    $expect(str_contains($html, 'youtube-showcase-rail__viewport'), 'Video rail needs a dedicated scroll viewport.');
    $expect(str_contains($html, 'youtube-showcase-item__state'), 'Video items need an explicit interaction state label.');
    $expect($render([], $videos) === '', 'Zero announcements must produce no markup.');

    $expect(pn_natuna_announcement_image($fixtures[0]) === '/images/full-901.webp', 'Showcase image must prefer fulltext.');
    $expect(pn_natuna_announcement_image($fixtures[1]) === '/images/intro-902.webp', 'Showcase image must fall back to intro.');
    $expect(pn_natuna_hero_article_image($fixtures[0]) === '/images/intro-901.webp', 'Hero image precedence must remain intro-first.');
    $bodyOnly = (object) [
        'catid' => 12,
        'images' => '{}',
        'introtext' => '<p><img src="images/berita/foto-pertama.jpg" alt=""></p>',
        'fulltext' => '',
    ];
    $expect(pn_natuna_hero_article_image($bodyOnly) === '/images/berita/foto-pertama.jpg', 'Hero must fall back to the first body image when Joomla image fields are empty.');
}

$source = (string) file_get_contents(JPATH_BASE . '/templates/pn_natuna_2026/hero-slider.php');
$index = (string) file_get_contents(JPATH_BASE . '/templates/pn_natuna_2026/index.php');
$css = (string) file_get_contents(JPATH_BASE . '/templates/pn_natuna_2026/css/template.css');
$expect(str_contains($css, 'grid-template-columns: minmax(0, 45fr) minmax(0, 55fr)'), 'Desktop showcase grid must use a 45:55 composition.');
$expect(str_contains($css, '@media (max-width: 1180px)'), 'Tablet stack breakpoint is missing.');
$expect(str_contains($css, 'scroll-snap-type: x mandatory'), 'Mobile rail snap is missing.');
$expect(str_contains($css, 'body.is-dark .youtube-showcase'), 'Dark player style is missing.');
$expect(str_contains($css, '@media (prefers-reduced-motion: reduce)'), 'Reduced motion support is missing.');
$expect(str_contains($css, 'grid-auto-columns: minmax(190px, calc((100% - 16px) / 3))'), 'Desktop rail must expose three readable cards through horizontal navigation.');
$expect(str_contains($css, 'order: -1'), 'Mobile layout must place video before announcement.');
$expect(str_contains($css, 'object-position: center top'), 'Announcement document preview must prioritize the document header.');
$expect((bool) preg_match('/@media \(min-width:\s*1181px\)\s*\{.*?\.announcement-showcase__grid\s*\{[^}]*align-items:\s*stretch;[^}]*\}.*?\.announcement-feature\s*\{[^}]*grid-template-rows:\s*auto minmax\(0, 1fr\) auto;[^}]*\}.*?\.announcement-feature__media\s*\{[^}]*aspect-ratio:\s*auto;[^}]*\}/s', $css), 'Desktop cards must share height by growing the document media area.');
$expect(str_contains($css, '.youtube-showcase-player__copy {') && str_contains($css, 'pointer-events: none;'), 'Player copy overlay must not intercept mobile taps.');
$expect(str_contains($css, '.youtube-showcase-player__copy a {') && str_contains($css, 'pointer-events: auto;'), 'YouTube fallback link must remain independently tappable.');
$expect(str_contains($css, 'touch-action: manipulation;'), 'Play button must declare direct touch manipulation.');
$expect(str_contains($source, 'pn_natuna_hero_latest_articles(13, 1)'), 'Renderer default must request one category 13 article.');
$expect(str_contains($index, "'/youtube-feed.php'"), 'Template bootstrap must include youtube-feed.php.');
if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "latest announcements showcase contract: ok\n";
