<?php
/** Focused test runner: php tools/test-instagram-feed.php */
require_once __DIR__ . '/../templates/pn_natuna_2026/instagram-feed.php';

function check(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
$fixture = file_get_contents(__DIR__ . '/fixtures/instagram-rss.xml');
$items = pn_natuna_instagram_parse_rss($fixture);
check(count($items) === 2, 'RSS parser keeps valid Instagram posts');
check($items[0]['permalink'] === 'https://www.instagram.com/p/Dam_Dx1hlc4/', 'Instagram permalink parsed');
check($items[0]['caption'] === 'Caption lengkap untuk post Dam.', 'Description replaces truncated title');
check($items[0]['thumbnail'] === 'https://cdn.rss.app/images/dam.jpg', 'media:content thumbnail parsed');
check(pn_natuna_instagram_parse_rss('<html><body>no</body></html>') === [], 'HTML rejected');
check(pn_natuna_instagram_parse_rss('<rss><channel><item>') === [], 'Invalid XML rejected');
check(pn_natuna_instagram_clean_caption("<b>Hello</b> &amp;  dunia\nbaru") === 'Hello & dunia baru', 'Caption cleaned');

$cache = sys_get_temp_dir() . '/pn-instagram-test-' . bin2hex(random_bytes(4));
mkdir($cache, 0700, true);
file_put_contents($cache . '/feed.json', '{"updated_at":"old","items":[{"permalink":"https://www.instagram.com/p/old/"}]}');
$before = hash_file('sha256', $cache . '/feed.json');
check(!pn_natuna_instagram_promote_cache($cache, []), 'Empty refresh not promoted');
check($before === hash_file('sha256', $cache . '/feed.json'), 'Old cache preserved on failed refresh');
$html = pn_natuna_instagram_render(['items' => [['permalink' => 'https://www.instagram.com/p/Dam_Dx1hlc4/', 'caption' => '<script>x</script>', 'image' => '/media/instagram/' . str_repeat('a', 64) . '.webp', 'width' => 1, 'height' => 1]]]);
check(str_contains($html, 'data-instagram-cache="1"'), 'Manual fallback marker distinguishes cache');
check(!str_contains($html, '<script>'), 'Renderer escapes caption');
check(str_contains($html, 'loading="lazy"'), 'Renderer uses lazy local image');
$carouselItems = [];
for ($i = 0; $i < 2; $i++) {
    $carouselItems[] = ['permalink' => 'https://www.instagram.com/p/post' . $i . '/', 'caption' => 'Caption ' . $i, 'image' => '/media/instagram/' . str_repeat((string) ($i + 1), 64) . '.webp', 'width' => 1080, 'height' => 1080, 'date' => '2026-07-11T00:00:00+00:00'];
}
$carousel = pn_natuna_instagram_render(['items' => $carouselItems]);
check(str_contains($carousel, 'data-instagram-carousel'), 'Renderer marks carousel for setup');
check(str_contains($carousel, 'instagram-carousel-viewport') && str_contains($carousel, 'instagram-carousel-track'), 'Renderer includes viewport and track');
check(substr_count($carousel, 'instagram-carousel-slide') === 2, 'Renderer creates one slide per valid cached item');
check(substr_count($carousel, 'data-instagram-carousel-dot') === 2, 'Renderer creates carousel dots');
check(str_contains($carousel, 'data-instagram-carousel-prev') && str_contains($carousel, 'data-instagram-carousel-next'), 'Renderer includes previous and next controls');
check(str_contains($carousel, 'instagram-carousel-status'), 'Renderer includes restrained status region');
echo "instagram feed tests: OK\n";
