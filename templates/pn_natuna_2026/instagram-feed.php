<?php
/** Local Instagram RSS cache parser and renderer. No network access from web requests. */
if (!function_exists('pn_natuna_instagram_cache_dir')) {
function pn_natuna_instagram_cache_dir(): string { return defined('JPATH_ROOT') ? JPATH_ROOT . '/cache/pn_natuna_instagram' : dirname(__DIR__, 2) . '/cache/pn_natuna_instagram'; }
function pn_natuna_instagram_clean_caption(string $text): string { return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'))); }
function pn_natuna_instagram_permalink(string $url): string { $parts = parse_url(trim($url)); if (($parts['scheme'] ?? '') !== 'https' || strtolower($parts['host'] ?? '') !== 'www.instagram.com' || !preg_match('#^/(?:p|reel|tv)/[A-Za-z0-9_-]+/?$#', $parts['path'] ?? '')) return ''; return 'https://www.instagram.com' . $parts['path']; }
function pn_natuna_instagram_embed_url(string $permalink): string
{
    $safe = pn_natuna_instagram_permalink($permalink);
    return $safe === '' ? '' : rtrim($safe, '/') . '/embed/captioned/';
}
function pn_natuna_instagram_media_url(string $url): string
{
    $url = trim($url);
    $parts = parse_url($url);
    $host = strtolower((string) ($parts['host'] ?? ''));
    if (strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || !preg_match('/(?:^|\.)(?:cdninstagram\.com|fbcdn\.net)$/D', $host)) return '';
    return $url;
}
function pn_natuna_instagram_parse_embed_image(string $html): string
{
    if ($html === '' || strlen($html) > 4194304) return '';
    $document = new DOMDocument();
    if (!@$document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) return '';
    foreach ($document->getElementsByTagName('img') as $image) {
        if (!$image instanceof DOMElement || stripos($image->getAttribute('alt'), 'Instagram post shared by') === false) continue;
        $url = pn_natuna_instagram_media_url(html_entity_decode($image->getAttribute('src'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($url !== '') return $url;
    }
    return '';
}

function pn_natuna_instagram_parse_rss(string $xml): array {
    if ($xml === '' || strlen($xml) > 2097152 || preg_match('/<\s*html\b/i', $xml)) return [];
    libxml_use_internal_errors(true);
    $feed = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    if (!$feed || strtolower($feed->getName()) !== 'rss' || !isset($feed->channel->item)) return [];
    $mediaUri = $feed->getNamespaces(true)['media'] ?? 'http://search.yahoo.com/mrss/';
    $out = [];
    foreach ($feed->channel->item as $entry) {
        $permalink = pn_natuna_instagram_permalink((string) $entry->link); $guid = trim((string) $entry->guid);
        $nodes = $entry->children($mediaUri); $thumbnail = '';
        foreach ($nodes->content as $content) { $url = trim((string) $content->attributes()->url); if (filter_var($url, FILTER_VALIDATE_URL) && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https') { $thumbnail = $url; break; } }
        if ($permalink === '' || $thumbnail === '') continue;
        $caption = pn_natuna_instagram_clean_caption((string) $entry->description); if ($caption === '') $caption = pn_natuna_instagram_clean_caption((string) $entry->title);
        $out[] = ['guid' => $guid !== '' ? $guid : hash('sha256', $permalink), 'permalink' => $permalink, 'caption' => $caption, 'thumbnail' => $thumbnail, 'date' => gmdate(DATE_ATOM, strtotime((string) $entry->pubDate) ?: time())];
        if (count($out) === 9) break;
    } return $out;
}
function pn_natuna_instagram_load_cache(?string $dir = null): ?array { $file = ($dir ?: pn_natuna_instagram_cache_dir()) . '/feed.json'; if (!is_file($file)) return null; $data = json_decode((string) @file_get_contents($file), true); if (!is_array($data) || !is_array($data['items'] ?? null) || !$data['items']) return null; foreach ($data['items'] as $item) if (!is_array($item) || pn_natuna_instagram_permalink((string) ($item['permalink'] ?? '')) === '' || !preg_match('#^/media/instagram/[a-f0-9]{64}\.webp$#', (string) ($item['image'] ?? ''))) return null; return $data; }
function pn_natuna_instagram_promote_cache(string $dir, array $data, ?string $stage = null): bool { if (!is_array($data['items'] ?? null) || !$data['items']) return false; if (!is_dir($dir) && !mkdir($dir, 0700, true)) return false; $tmp = $dir . '/feed.json.' . bin2hex(random_bytes(6)) . '.tmp'; if (file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) return false; if (!rename($tmp, $dir . '/feed.json')) { @unlink($tmp); return false; } return true; }
function pn_natuna_instagram_render_profile_embed(): string
{
    $profile = 'https://www.instagram.com/pn.natuna/';
    $embed = $profile . 'embed/';
    return '<section class="instagram-profile-embed module-card" aria-label="Instagram Pengadilan Negeri Natuna"><div class="instagram-profile-embed__head"><div><span>Media Sosial Resmi</span><strong>Instagram PN Natuna</strong></div><a href="' . $profile . '" target="_blank" rel="noopener noreferrer">Buka Instagram</a></div><div class="instagram-profile-embed__frame"><iframe title="Profil dan posting terbaru Instagram Pengadilan Negeri Natuna" src="' . $embed . '" loading="lazy" scrolling="no" referrerpolicy="strict-origin-when-cross-origin" allowtransparency="true"></iframe></div><noscript><a href="' . $profile . '" target="_blank" rel="noopener noreferrer">Lihat Instagram Pengadilan Negeri Natuna</a></noscript></section>';
}

function pn_natuna_instagram_render(array $cache): string
{
    $items = [];
    foreach (array_slice($cache['items'] ?? [], 0, 6) as $item) {
        $url = pn_natuna_instagram_permalink((string) ($item['permalink'] ?? ''));
        $image = (string) ($item['image'] ?? '');
        if ($url === '' || !preg_match('#^/media/instagram/[a-f0-9]{64}\.webp$#', $image)) continue;
        $caption = pn_natuna_instagram_clean_caption((string) ($item['caption'] ?? 'Posting Instagram'));
        $items[] = ['url' => $url, 'image' => $image, 'caption' => $caption, 'short_caption' => mb_strlen($caption) > 110 ? rtrim(mb_substr($caption, 0, 107)) . '…' : $caption, 'date' => ($timestamp = strtotime((string) ($item['date'] ?? ''))) ? date('d M Y', $timestamp) : 'Posting terbaru', 'width' => max(1, (int) ($item['width'] ?? 1)), 'height' => max(1, (int) ($item['height'] ?? 1))];
    }
    if (!$items) return '';
    $count = count($items);
    $html = '<section class="instagram-cache instagram-gallery" data-instagram-cache="1" data-instagram-carousel aria-label="Instagram Pengadilan Negeri Natuna"><div class="instagram-carousel-shell"><div class="instagram-carousel-viewport"><div class="instagram-carousel-track">';
    foreach ($items as $index => $item) {
        $active = $index === 0;
        $html .= '<article class="instagram-carousel-slide" aria-hidden="' . ($active ? 'false' : 'true') . '"' . ($active ? '' : ' inert') . '><a class="instagram-cache-post" href="' . htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer"' . ($active ? '' : ' tabindex="-1"') . '><span class="instagram-gallery-media"><img src="' . htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') . '" width="' . $item['width'] . '" height="' . $item['height'] . '" loading="lazy" decoding="async" alt="' . htmlspecialchars($item['caption'], ENT_QUOTES, 'UTF-8') . '">' . ($active ? '<span class="instagram-gallery-new">Terbaru</span>' : '') . '</span><span class="instagram-gallery-caption"><time>' . htmlspecialchars($item['date'], ENT_QUOTES, 'UTF-8') . '</time><strong>' . htmlspecialchars($item['short_caption'], ENT_QUOTES, 'UTF-8') . '</strong></span></a></article>';
    }
    $html .= '</div></div>';
    if ($count > 1) {
        $html .= '<div class="instagram-carousel-controls"><button class="instagram-carousel-control" type="button" data-instagram-carousel-prev aria-label="Posting sebelumnya">‹</button><output class="instagram-carousel-count" aria-label="Posisi posting">1 dari ' . $count . '</output><button class="instagram-carousel-control" type="button" data-instagram-carousel-next aria-label="Posting berikutnya">›</button></div>';
    }
    return $html . '<p class="instagram-carousel-status" aria-live="polite" aria-atomic="true"></p></div></section>';
}
}
