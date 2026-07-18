<?php
/** Refreshes the local YouTube Atom cache. CLI only. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../templates/pn_natuna_2026/youtube-feed.php';

const PN_NATUNA_YOUTUBE_FEED_URL = 'https://www.youtube.com/feeds/videos.xml?channel_id=UCuPb35OggK2PKdW7Ed0qszA';
const PN_NATUNA_YOUTUBE_MAX_BYTES = 2097152;

$root = dirname(__DIR__);
$cachePath = $root . '/cache/pn_natuna_youtube/feed.json';
$logPath = getenv('PN_NATUNA_YOUTUBE_LOG_FILE') ?: $root . '/logs/youtube-refresh.log';

function pn_natuna_youtube_refresh_log(string $path, string $message): void
{
    $directory = dirname($path);
    if (!is_dir($directory)) {
        @mkdir($directory, 0700, true);
    }
    @file_put_contents($path, gmdate('c') . ' ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function pn_natuna_youtube_refresh_fail(string $logPath, string $reason): never
{
    $message = "gagal refresh YouTube: $reason; cache lama dipertahankan";
    pn_natuna_youtube_refresh_log($logPath, $message);
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

$curl = curl_init(PN_NATUNA_YOUTUBE_FEED_URL);
if ($curl === false) {
    pn_natuna_youtube_refresh_fail($logPath, 'cURL tidak dapat diinisialisasi');
}

$body = '';
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_USERAGENT => 'PN-Natuna-YouTube-Refresher/1.0',
    CURLOPT_HTTPHEADER => ['Accept: application/atom+xml, application/xml;q=0.9'],
    CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
        if (strlen($body) + strlen($chunk) > PN_NATUNA_YOUTUBE_MAX_BYTES) {
            return 0;
        }
        $body .= $chunk;
        return strlen($chunk);
    },
]);

$transferred = curl_exec($curl);
$status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$error = curl_error($curl);
curl_close($curl);
if ($transferred !== true || $status !== 200 || $body === '') {
    pn_natuna_youtube_refresh_fail($logPath, $error !== '' ? 'fetch gagal' : "HTTP $status");
}

$latest = pn_natuna_youtube_parse_atom($body);
if ($latest === []) {
    pn_natuna_youtube_refresh_fail($logPath, 'Atom tidak valid atau tanpa video valid');
}

$items = pn_natuna_youtube_merge(pn_natuna_youtube_pinned(), $latest);
$pinnedIds = array_column(pn_natuna_youtube_pinned(), 'id');
if (array_slice(array_column($items, 'id'), 0, 2) !== $pinnedIds) {
    pn_natuna_youtube_refresh_fail($logPath, 'hasil tidak memuat dua video wajib');
}

$sourceUpdatedAt = '';
foreach ($latest as $item) {
    if (($item['published'] ?? '') > $sourceUpdatedAt) {
        $sourceUpdatedAt = $item['published'];
    }
}
$payload = [
    'updated_at' => gmdate('c'),
    'source_updated_at' => $sourceUpdatedAt,
    'items' => $items,
];
if (!pn_natuna_youtube_promote_cache($cachePath, $payload)) {
    pn_natuna_youtube_refresh_fail($logPath, 'promosi cache gagal');
}

pn_natuna_youtube_refresh_log($logPath, 'refresh YouTube berhasil');
fwrite(STDOUT, "YouTube cache refreshed\n");
