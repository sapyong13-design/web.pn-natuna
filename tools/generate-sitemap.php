<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = rtrim((string) (getenv('PN_NATUNA_JPATH_ROOT') ?: dirname(__DIR__)), '/\\');
$configFile = $root . '/configuration.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "Joomla root tidak valid.\n");
    exit(2);
}
require_once $configFile;
$config = new JConfig();
$db = new mysqli($config->host, $config->user, $config->password, $config->db);
if ($db->connect_errno) {
    fwrite(STDERR, "Koneksi database sitemap gagal.\n");
    exit(3);
}
$db->set_charset('utf8mb4');
$prefix = $config->dbprefix;
$sql = "SELECT menu.path, content.modified, content.created, content.images, content.introtext, content.fulltext FROM {$prefix}menu AS menu"
    . " LEFT JOIN {$prefix}content AS content"
    . " ON menu.link = CONCAT('index.php?option=com_content&view=article&id=', content.id)"
    . " WHERE menu.client_id=0 AND menu.published=1"
    . " AND menu.menutype='mainmenu' AND menu.type IN ('component','url')"
    . " AND menu.home=0 AND menu.language IN ('*','id-ID','en-GB')"
    . " ORDER BY menu.home DESC, menu.lft";
$result = $db->query($sql);
if (!$result) {
    fwrite(STDERR, "Query sitemap gagal.\n");
    exit(4);
}
$base = 'https://pn-natuna.go.id';
$urls = ['/' => null];
$images = [];
$collectImages = static function (string $markup, array $sources = []) use ($root, $base): array {
    if ($markup !== '') {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $markup);
        foreach ($document->getElementsByTagName('img') as $image) {
            $sources[] = $image->getAttribute('src');
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
    $found = [];
    foreach ($sources as $source) {
        $parts = parse_url((string) $source);
        if ($parts === false || (isset($parts['host']) && !in_array(strtolower($parts['host']), ['pn-natuna.go.id', 'www.pn-natuna.go.id'], true))) continue;
        $path = '/' . ltrim((string) ($parts['path'] ?? ''), '/');
        if (!str_starts_with($path, '/images/')) continue;
        $file = realpath($root . rawurldecode($path));
        $imageRoot = realpath($root . '/images');
        if (!$file || !$imageRoot || !str_starts_with($file, $imageRoot . DIRECTORY_SEPARATOR) || !is_file($file)) continue;
        if (!preg_match('/\.(?:jpe?g|png|webp|gif|avif)$/i', $path)) continue;
        $found[$base . $path] = true;
    }
    return array_keys($found);
};
$homeTemplate = $root . '/templates/pn_natuna_2026/index.php';
$images['/'] = $collectImages((string) file_get_contents($homeTemplate));
while ($row = $result->fetch_assoc()) {
    $path = trim((string) $row['path'], '/');
    if ($path === '' || str_starts_with($path, 'component/')) continue;
    $changed = (string) ($row['modified'] && $row['modified'] > '2000-01-02 00:00:00' ? $row['modified'] : ($row['created'] ?? ''));
    $timestamp = $changed !== '' ? strtotime($changed . ' UTC') : false;
    $urls['/' . $path] = $timestamp && $timestamp > 946684800 ? gmdate('Y-m-d', $timestamp) : null;
    $articleImages = json_decode((string) ($row['images'] ?? ''), true) ?: [];
    $images['/' . $path] = $collectImages((string) ($row['introtext'] ?? '') . (string) ($row['fulltext'] ?? ''), [$articleImages['image_intro'] ?? '', $articleImages['image_fulltext'] ?? '']);
}
$xml = ['<?xml version="1.0" encoding="UTF-8"?>','<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'];
foreach ($urls as $path => $date) {
    $loc = htmlspecialchars($base . $path, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $lastmod = $date ? "<lastmod>{$date}</lastmod>" : '';
    $imageXml = '';
    foreach (array_slice($images[$path] ?? [], 0, 1000) as $image) {
        $imageLoc = htmlspecialchars($image, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $imageXml .= "<image:image><image:loc>{$imageLoc}</image:loc></image:image>";
    }
    $xml[] = "  <url><loc>{$loc}</loc>{$lastmod}{$imageXml}</url>";
}
$xml[] = '</urlset>';
$output = $root . '/sitemap.xml';
$temp = $output . '.tmp';
if (file_put_contents($temp, implode("\n", $xml) . "\n", LOCK_EX) === false || !rename($temp, $output)) {
    fwrite(STDERR, "Sitemap gagal ditulis.\n");
    exit(5);
}
chmod($output, 0644);
printf("Sitemap ditulis: %s (%d URL)\n", $output, count($urls));
