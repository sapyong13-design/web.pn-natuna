<?php
declare(strict_types=1);

define('_JEXEC', true);
require dirname(__DIR__) . '/plugins/content/pnnatunaimagevariants/src/Helper/CloudflarePurgeQueue.php';

use Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\CloudflarePurgeQueue;

$news = (object) ['alias' => 'berita-baru', 'catid' => 12];
$announcement = (object) ['alias' => 'pengumuman-baru', 'catid' => 13];
if (CloudflarePurgeQueue::articlePaths($news) !== ['/', '/berita', '/berita-dan-pengumuman', '/berita/berita-baru']) exit(1);
if (CloudflarePurgeQueue::articlePaths($announcement) !== ['/', '/pengumuman', '/berita-dan-pengumuman', '/pengumuman/pengumuman-baru']) exit(1);

$root = sys_get_temp_dir() . '/pn-cf-queue-' . bin2hex(random_bytes(6));
if (!CloudflarePurgeQueue::enqueue(CloudflarePurgeQueue::articlePaths($news), $root)) exit(1);
$queue = $root . '/cloudflare/purge-queue.jsonl';
$record = json_decode((string) file_get_contents($queue), true);
if (($record['urls'] ?? []) !== [
    'https://pn-natuna.go.id/',
    'https://pn-natuna.go.id/berita',
    'https://pn-natuna.go.id/berita-dan-pengumuman',
    'https://pn-natuna.go.id/berita/berita-baru',
]) exit(1);
@unlink($queue); @rmdir(dirname($queue)); @rmdir($root);
$plugin = (string) file_get_contents(dirname(__DIR__) . '/plugins/content/pnnatunaimagevariants/src/Extension/PnNatunaImageVariants.php');
if (!str_contains($plugin, 'CloudflarePurgeQueue::enqueue(CloudflarePurgeQueue::articlePaths($item))')) exit(1);
printf("Cloudflare purge queue contract: ok\n");
