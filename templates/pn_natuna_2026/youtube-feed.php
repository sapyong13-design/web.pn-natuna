<?php
/** Local YouTube Atom cache domain. Web requests never contact YouTube. */
if (!function_exists('pn_natuna_youtube_pinned')) {
    function pn_natuna_youtube_pinned(): array
    {
        return [
            ['id' => '-Di2t-yUZ1I', 'title' => 'Video Profile Pengadilan Negeri / Perikanan Ranai', 'published' => '', 'url' => 'https://www.youtube.com/watch?v=-Di2t-yUZ1I', 'thumbnail' => 'https://i.ytimg.com/vi/-Di2t-yUZ1I/hqdefault.jpg', 'source' => 'wajib'],
            ['id' => 'kQ0dMRp1W_g', 'title' => 'Tata cara penggunaan e-Berpadu', 'published' => '', 'url' => 'https://www.youtube.com/watch?v=kQ0dMRp1W_g', 'thumbnail' => 'https://i.ytimg.com/vi/kQ0dMRp1W_g/hqdefault.jpg', 'source' => 'wajib'],
        ];
    }

    function pn_natuna_youtube_clean_text(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    function pn_natuna_youtube_item(array $item, string $source): ?array
    {
        $id = trim((string) ($item['id'] ?? ''));
        $title = pn_natuna_youtube_clean_text((string) ($item['title'] ?? ''));
        $published = trim((string) ($item['published'] ?? ''));
        $thumbnail = trim((string) ($item['thumbnail'] ?? ''));
        if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $id) || $title === '' || ($source !== 'wajib' && $published === '')) {
            return null;
        }
        if ($thumbnail === '') {
            $thumbnail = "https://i.ytimg.com/vi/$id/hqdefault.jpg";
        }
        if (filter_var($thumbnail, FILTER_VALIDATE_URL) === false || strtolower((string) parse_url($thumbnail, PHP_URL_SCHEME)) !== 'https') {
            return null;
        }
        return ['id' => $id, 'title' => $title, 'published' => $published, 'url' => "https://www.youtube.com/watch?v=$id", 'thumbnail' => $thumbnail, 'source' => $source];
    }

    function pn_natuna_youtube_parse_atom(string $xml): array
    {
        if ($xml === '' || strlen($xml) > 2097152 || preg_match('/<\s*html\b/i', $xml)) return [];
        $previous = libxml_use_internal_errors(true);
        $feed = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        if (!$feed || strtolower($feed->getName()) !== 'feed') return [];
        $namespaces = $feed->getNamespaces(true);
        $youtubeNs = $namespaces['yt'] ?? 'http://www.youtube.com/xml/schemas/2015';
        $mediaNs = $namespaces['media'] ?? 'http://search.yahoo.com/mrss/';
        $items = [];
        foreach ($feed->children() as $entry) {
            if (strtolower($entry->getName()) !== 'entry') continue;
            $id = trim((string) $entry->children($youtubeNs)->videoId);
            if ($id === '') $id = preg_replace('/^yt:video:/', '', trim((string) $entry->id));
            $thumbnail = '';
            foreach ($entry->children($mediaNs)->group as $group) {
                foreach ($group->children($mediaNs)->thumbnail as $node) {
                    $thumbnail = trim((string) $node->attributes()->url);
                    if ($thumbnail !== '') break 2;
                }
            }
            $item = pn_natuna_youtube_item(['id' => $id, 'title' => (string) $entry->title, 'published' => trim((string) $entry->published), 'thumbnail' => $thumbnail], 'terbaru');
            if ($item !== null) $items[] = $item;
        }
        return $items;
    }

    function pn_natuna_youtube_merge(array $pinned, array $latest, int $limit = 5): array
    {
        $merged = []; $seen = [];
        foreach ([[$pinned, 'wajib'], [$latest, 'terbaru']] as [$items, $source]) foreach ($items as $item) {
            $normalized = is_array($item) ? pn_natuna_youtube_item($item, $source) : null;
            if ($normalized === null || isset($seen[$normalized['id']])) continue;
            $seen[$normalized['id']] = true; $merged[] = $normalized;
            if (count($merged) >= max(0, $limit)) return $merged;
        }
        return $merged;
    }

    function pn_natuna_youtube_cached_items(array $items): array
    {
        $pinned = []; $latest = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            if (($item['source'] ?? '') === 'wajib') $pinned[] = $item; else $latest[] = $item;
        }
        return pn_natuna_youtube_merge($pinned, $latest);
    }

    function pn_natuna_youtube_load_cache(?string $path = null): array
    {
        $required = pn_natuna_youtube_pinned();
        $path ??= (defined('JPATH_ROOT') ? JPATH_ROOT : dirname(__DIR__, 2)) . '/cache/pn_natuna_youtube/feed.json';
        if (!is_file($path)) return $required;
        $payload = json_decode((string) @file_get_contents($path), true);
        if (!is_array($payload) || !is_array($payload['items'] ?? null)) return $required;
        $items = pn_natuna_youtube_cached_items($payload['items']);
        if (array_slice(array_column($items, 'id'), 0, 2) !== array_column($required, 'id')) return $required;
        return $items;
    }

    function pn_natuna_youtube_promote_cache(string $path, array $payload): bool
    {
        if (!is_array($payload['items'] ?? null)) return false;
        $items = pn_natuna_youtube_cached_items($payload['items']);
        if (array_slice(array_column($items, 'id'), 0, 2) !== array_column(pn_natuna_youtube_pinned(), 'id')) return false;
        $payload['items'] = $items;
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0700, true)) return false;
        try { $temporary = $path . '.' . bin2hex(random_bytes(6)) . '.tmp'; } catch (Throwable $exception) { return false; }
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || file_put_contents($temporary, $json, LOCK_EX) === false) { @unlink($temporary); return false; }
        if (!@rename($temporary, $path)) { @unlink($temporary); return false; }
        return true;
    }
}
