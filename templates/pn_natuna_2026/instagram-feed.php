<?php
/** Local Instagram RSS cache parser and renderer. No network access from web requests. */
if (!function_exists('pn_natuna_instagram_cache_dir')) {
function pn_natuna_instagram_cache_dir(): string { return defined('JPATH_ROOT') ? JPATH_ROOT . '/cache/pn_natuna_instagram' : dirname(__DIR__, 2) . '/cache/pn_natuna_instagram'; }
function pn_natuna_instagram_clean_caption(string $text): string { return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'))); }
function pn_natuna_instagram_permalink(string $url): string { $parts = parse_url(trim($url)); if (($parts['scheme'] ?? '') !== 'https' || strtolower($parts['host'] ?? '') !== 'www.instagram.com' || !preg_match('#^/(?:p|reel|tv)/[A-Za-z0-9_-]+/?$#', $parts['path'] ?? '')) return ''; return 'https://www.instagram.com' . $parts['path']; }
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
    $html = '<section class="instagram-cache instagram-gallery" data-instagram-cache="1" data-instagram-carousel aria-label="Instagram Pengadilan Negeri Natuna"><header class="instagram-gallery-head"><span class="instagram-gallery-avatar" aria-hidden="true"><img src="/images/brand/logo-pn-natuna.png" alt="" width="36" height="36"></span><span class="instagram-gallery-profile"><strong>@pn.natuna</strong></span><a href="https://www.instagram.com/pn.natuna/" target="_blank" rel="noopener noreferrer">Ikuti Instagram</a></header><div class="instagram-carousel-shell"><div class="instagram-carousel-viewport"><div class="instagram-carousel-track">';
    foreach ($items as $index => $item) {
        $active = $index === 0;
        $html .= '<article class="instagram-carousel-slide" aria-hidden="' . ($active ? 'false' : 'true') . '"' . ($active ? '' : ' inert') . '><a class="instagram-cache-post" href="' . htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer"' . ($active ? '' : ' tabindex="-1"') . '><img src="' . htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8') . '" width="' . $item['width'] . '" height="' . $item['height'] . '" loading="lazy" decoding="async" alt="' . htmlspecialchars($item['caption'], ENT_QUOTES, 'UTF-8') . '"><span class="instagram-gallery-overlay"><time>' . htmlspecialchars($item['date'], ENT_QUOTES, 'UTF-8') . '</time><strong>' . htmlspecialchars($item['short_caption'], ENT_QUOTES, 'UTF-8') . '</strong></span>' . ($active ? '<span class="instagram-gallery-new">Terbaru</span>' : '') . '</a></article>';
    }
    $html .= '</div></div>';
    if ($count > 1) {
        $html .= '<div class="instagram-carousel-controls"><button class="instagram-carousel-control" type="button" data-instagram-carousel-prev aria-label="Posting sebelumnya">‹</button><div class="instagram-carousel-dots" role="group" aria-label="Pilih posting Instagram">';
        foreach ($items as $index => $_item) $html .= '<button class="instagram-carousel-dot' . ($index === 0 ? ' is-active' : '') . '" type="button" data-instagram-carousel-dot="' . $index . '" aria-label="Tampilkan posting ' . ($index + 1) . ' dari ' . $count . '" aria-current="' . ($index === 0 ? 'true' : 'false') . '"></button>';
        $html .= '</div><button class="instagram-carousel-control" type="button" data-instagram-carousel-next aria-label="Posting berikutnya">›</button><output class="instagram-carousel-count" aria-label="Posisi posting">1/' . $count . '</output></div>';
    }
    return $html . '<p class="instagram-carousel-status" aria-live="polite" aria-atomic="true"></p></div></section>';
}
}
