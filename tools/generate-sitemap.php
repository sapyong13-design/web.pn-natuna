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
$sql = "SELECT path FROM {$prefix}menu WHERE client_id=0 AND published=1 AND type IN ('component','url') AND home IN (0,1) AND language IN ('*','id-ID','en-GB') ORDER BY home DESC, lft";
$result = $db->query($sql);
if (!$result) {
    fwrite(STDERR, "Query sitemap gagal.\n");
    exit(4);
}
$base = 'https://pn-natuna.go.id';
$urls = ['/'=>gmdate('Y-m-d')];
while ($row = $result->fetch_assoc()) {
    $path = trim((string) $row['path'], '/');
    if ($path === '' || str_starts_with($path, 'component/')) continue;
    $loc = '/' . $path;
    $urls[$loc] = gmdate('Y-m-d');
}
$xml = ['<?xml version="1.0" encoding="UTF-8"?>','<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
foreach ($urls as $path => $date) {
    $loc = htmlspecialchars($base . $path, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $priority = $path === '/' ? '1.0' : '0.7';
    $xml[] = "  <url><loc>{$loc}</loc><lastmod>{$date}</lastmod><changefreq>weekly</changefreq><priority>{$priority}</priority></url>";
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
