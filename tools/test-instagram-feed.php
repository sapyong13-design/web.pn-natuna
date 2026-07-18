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
check(!str_contains($carousel, 'instagram-gallery-head'), 'Renderer starts directly with Instagram posts');
check(!str_contains($carousel, '@pn.natuna') && !str_contains($carousel, 'Ikuti Instagram'), 'Renderer omits the redundant Instagram profile header');
check(substr_count($carousel, 'instagram-carousel-slide') === 2, 'Renderer creates one slide per valid cached item');
check(substr_count($carousel, 'data-instagram-carousel-dot') === 0, 'Renderer omits redundant carousel dots');
check(str_contains($carousel, 'instagram-gallery-caption'), 'Renderer places caption below the media');
check(!str_contains($carousel, 'instagram-gallery-overlay'), 'Renderer must not overlay caption on the image');
check(str_contains($carousel, 'data-instagram-carousel-prev') && str_contains($carousel, 'data-instagram-carousel-next'), 'Renderer includes previous and next controls');
check(str_contains($carousel, '>1 dari 2</output>'), 'Renderer uses a readable carousel count');
check(str_contains($carousel, 'instagram-carousel-status'), 'Renderer includes restrained status region');
check(!str_contains($html, 'instagram-carousel-controls'), 'Single-item carousel hides controls');
$css = (string) file_get_contents(__DIR__ . '/../templates/pn_natuna_2026/css/template.css');
check((bool) preg_match('/\.instagram-cache-post\s*\{[^}]*display:\s*grid;[^}]*grid-template-rows:\s*minmax\(0, 1fr\) auto/s', $css), 'Instagram post must separate stable media and caption rows');
check((bool) preg_match('/\.instagram-cache-post img\s*\{[^}]*height:\s*100%;[^}]*object-fit:\s*contain/s', $css), 'Instagram image must remain fully visible without cropping');
check((bool) preg_match('/\.instagram-carousel-viewport\s*\{[^}]*height:\s*clamp\(/s', $css), 'Instagram carousel needs a stable responsive height');
check((bool) preg_match('/body\.is-dark \.instagram-gallery-caption\s*\{[^}]*background:\s*var\(--dark-content-raised\)/s', $css), 'Instagram caption needs a dark-mode surface');
check((bool) preg_match('/\.instagram-carousel-control\s*\{[^}]*width:\s*44px;[^}]*height:\s*44px;[^}]*border:\s*0;[^}]*border-radius:\s*0;[^}]*font-size:\s*1\.2rem/s', $css), 'Instagram navigation needs minimal visuals with accessible touch targets');
check((bool) preg_match('/\.instagram-carousel-controls\s*\{[^}]*border-top:\s*1px solid var\(--home-rule/s', $css), 'Instagram controls need a clear footer separator');
check((bool) preg_match('/\.instagram-gallery-caption time\s*\{[^}]*text-transform:\s*none/s', $css), 'Instagram date must use calm sentence case');
check((bool) preg_match('/\.instagram-carousel-controls\s*\{[^}]*padding:\s*8px 4px 4px/s', $css), 'Instagram footer controls need compact balanced insets');
check(!str_contains($css, '.instagram-gallery-avatar'), 'Removed Instagram profile chrome must not leave dead avatar CSS');
check(!str_contains($css, '.instagram-gallery-profile'), 'Removed Instagram profile chrome must not leave dead profile CSS');
check(!str_contains($css, '.instagram-gallery-head'), 'Removed Instagram profile chrome must not leave dead header CSS');
echo "instagram feed tests: OK\n";
