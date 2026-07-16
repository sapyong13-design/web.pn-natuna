<?php
/** Focused contract check for latest announcements showcase. */
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
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
        $fixture(903, 'Pengumuman 903', '2026-07-14 09:00:00'),
    ];
    $render = static function (array $items): string {
        ob_start();
        pn_natuna_render_latest_announcements($items);
        return (string) ob_get_clean();
    };

    $html = $render($fixtures);
    $expect(str_contains($html, 'Informasi Resmi'), 'Showcase kicker is missing.');
    $expect(str_contains($html, '<h2 id="announcement-showcase-title">Pengumuman Baru</h2>'), 'Showcase heading is missing.');
    $expect(str_contains($html, 'href="/pengumuman"'), 'Archive action must target /pengumuman.');
    $expect(substr_count($html, 'class="announcement-feature"') === 1, 'Exactly one feature item is required.');
    $expect(substr_count($html, 'class="announcement-compact"') === 2, 'Exactly two compact items are required.');
    $expect(str_contains($html, 'Baca Pengumuman'), 'Feature CTA is missing.');
    $expect(strpos($html, 'Pengumuman 901') < strpos($html, 'Pengumuman 902'), 'DOM order must preserve newest-first order.');
    $expect($render([]) === '', 'Zero announcements must produce no markup.');
    $expect(substr_count($render([$fixtures[0]]), 'class="announcement-feature"') === 1, 'One announcement must render one feature.');
    $expect(!str_contains($render([$fixtures[0]]), 'announcement-compact-list'), 'One announcement must not render compact list.');
    $expect(substr_count($render(array_slice($fixtures, 0, 2)), 'class="announcement-compact"') === 1, 'Two announcements must render one compact item.');

    $expect(pn_natuna_announcement_image($fixtures[0]) === '/images/full-901.webp', 'Showcase image must prefer fulltext.');
    $expect(pn_natuna_announcement_image($fixtures[1]) === '/images/intro-902.webp', 'Showcase image must fall back to intro.');
    $expect(pn_natuna_announcement_image($fixtures[2]) === '/images/brand/pengumuman-resmi-pn-natuna.webp', 'Showcase image fallback is wrong.');
    $expect(pn_natuna_hero_article_image($fixtures[0]) === '/images/intro-901.webp', 'Hero image precedence must remain intro-first.');
}

$source = (string) file_get_contents(JPATH_BASE . '/templates/pn_natuna_2026/hero-slider.php');
$expect(str_contains($source, 'pn_natuna_hero_latest_articles(13, 3)'), 'Renderer default must request three category 13 articles.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo "latest announcements showcase contract: ok\n";
