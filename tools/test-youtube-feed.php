<?php
/** Focused contract runner: php tools/test-youtube-feed.php */
require_once __DIR__ . '/../templates/pn_natuna_2026/youtube-feed.php';

function check(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

$atom = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
  <entry>
    <id>yt:video:AbCdEfGhI_j</id>
    <title>  Video &amp;   terbaru  </title>
    <published>2026-07-16T10:00:00+00:00</published>
    <media:group><media:thumbnail url="https://i.ytimg.com/vi/AbCdEfGhI_j/hqdefault.jpg" /></media:group>
  </entry>
  <entry>
    <id>yt:video:invalid</id><title>Ignored</title><published>2026-07-16T11:00:00+00:00</published>
  </entry>
  <entry>
    <id>yt:video:kQ0dMRp1W_g</id><title>Pinned duplicate</title><published>2026-07-16T12:00:00+00:00</published>
  </entry>
</feed>
XML;

$latest = pn_natuna_youtube_parse_atom($atom);
check(count($latest) === 2, 'Atom parser extracts only valid 11-character video IDs');
check($latest[0] === [
    'id' => 'AbCdEfGhI_j',
    'title' => 'Video & terbaru',
    'published' => '2026-07-16T10:00:00+00:00',
    'url' => 'https://www.youtube.com/watch?v=AbCdEfGhI_j',
    'thumbnail' => 'https://i.ytimg.com/vi/AbCdEfGhI_j/hqdefault.jpg',
    'source' => 'terbaru',
], 'Atom parser normalizes title, date, thumbnail, and canonical URL');

$pinned = pn_natuna_youtube_pinned();
check(array_column($pinned, 'id') === ['-Di2t-yUZ1I', 'kQ0dMRp1W_g'], 'Pinned metadata appears in required order');
$merged = pn_natuna_youtube_merge($pinned, $latest);
check(array_column($merged, 'id') === ['-Di2t-yUZ1I', 'kQ0dMRp1W_g', 'AbCdEfGhI_j'], 'Pinned videos lead and duplicate feed IDs are removed');

for ($i = 0; $i < 6; $i++) {
    $id = 'vid' . sprintf('%07d', $i) . 'X';
    $many[] = ['id' => $id, 'title' => "Video $i", 'published' => '2026-07-16T00:00:00+00:00', 'url' => "https://www.youtube.com/watch?v=$id", 'thumbnail' => "https://i.ytimg.com/vi/$id/hqdefault.jpg", 'source' => 'terbaru'];
}
check(count(pn_natuna_youtube_merge($pinned, $many)) === 5, 'Merge limits feed to five items');

$cache = sys_get_temp_dir() . '/pn-youtube-test-' . bin2hex(random_bytes(4)) . '.json';
file_put_contents($cache, '{invalid json');
check(pn_natuna_youtube_load_cache($cache) === $pinned, 'Invalid JSON cache falls back to pinned items');
$before = hash_file('sha256', $cache);
check(!pn_natuna_youtube_promote_cache($cache, ['items' => [['id' => 'bad']]]), 'Invalid cache payload is rejected');
check(hash_file('sha256', $cache) === $before, 'Old cache remains after rejected promotion');
$payload = ['updated_at' => '2026-07-16T12:00:00+00:00', 'items' => array_merge($pinned, $many, [['id' => 'bad']])];
check(pn_natuna_youtube_promote_cache($cache, $payload), 'Payload with required pinned items promotes');
$serialized = json_decode((string) file_get_contents($cache), true);
check($serialized['items'] === pn_natuna_youtube_cached_items($payload['items']), 'Promotion serializes only normalized maximum-five items');
check(pn_natuna_youtube_load_cache($cache) === $serialized['items'], 'Promoted cache loads serialized items');
@unlink($cache);

$refresherPath = __DIR__ . '/cron-refresh-youtube.php';
$refresher = @file_get_contents($refresherPath);
check(is_string($refresher), 'YouTube refresher exists');
check(str_contains($refresher, "PHP_SAPI !== 'cli'"), 'Refresher rejects non-CLI execution');
check(str_contains($refresher, 'https://www.youtube.com/feeds/videos.xml?channel_id=UCuPb35OggK2PKdW7Ed0qszA'), 'Refresher targets exact official HTTPS channel feed');
check(str_contains($refresher, 'CURLOPT_SSL_VERIFYPEER') && str_contains($refresher, 'CURLOPT_SSL_VERIFYHOST'), 'Refresher verifies TLS');
check(str_contains($refresher, 'CURLOPT_TIMEOUT') && str_contains($refresher, '15'), 'Refresher has a 15-second timeout');
check(str_contains($refresher, '2097152'), 'Refresher caps payload at 2 MiB');
check(str_contains($refresher, 'CURLOPT_USERAGENT'), 'Refresher identifies its user agent');
check(str_contains($refresher, 'pn_natuna_youtube_parse_atom') && str_contains($refresher, 'pn_natuna_youtube_merge'), 'Refresher parses then merges domain items');
check(str_contains($refresher, 'pn_natuna_youtube_promote_cache'), 'Refresher atomically promotes through domain function');
check(str_contains($refresher, 'cache lama dipertahankan'), 'Refresher reports old-cache retention on failure');

echo "youtube feed tests: OK\n";
