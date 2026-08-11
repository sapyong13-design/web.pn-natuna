<?php

namespace Joomla\Plugin\Content\Pnnatunaimagevariants\Helper;

\defined('_JEXEC') or die;

final class CloudflarePurgeQueue
{
    /** @return list<string> */
    public static function articlePaths(object $item): array
    {
        $alias = trim((string) ($item->alias ?? ''));
        if ($alias === '') {
            return ['/'];
        }
        $category = (int) ($item->catid ?? 0);
        $channel = $category === 12 ? 'berita' : ($category === 13 ? 'pengumuman' : '');
        if ($channel === '') {
            return ['/'];
        }

        return ['/', '/' . $channel, '/berita-dan-pengumuman', '/' . $channel . '/' . rawurlencode($alias)];
    }

    /** @param list<string> $paths */
    public static function enqueue(array $paths, ?string $privateRoot = null): bool
    {
        $privateRoot ??= (string) getenv('PN_NATUNA_PRIVATE_ROOT');
        if ($privateRoot === '') {
            return false;
        }
        $directory = rtrim($privateRoot, '/\\') . '/cloudflare';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            return false;
        }
        $urls = [];
        foreach ($paths as $path) {
            if (!preg_match('#^/[A-Za-z0-9/_~.%-]*$#', $path)) {
                continue;
            }
            $urls[] = 'https://pn-natuna.go.id' . ($path === '' ? '/' : $path);
        }
        if (!$urls) {
            return false;
        }
        $record = json_encode(['queued_at' => gmdate(DATE_ATOM), 'urls' => array_values(array_unique($urls))], JSON_UNESCAPED_SLASHES);
        if ($record === false) {
            return false;
        }
        $queue = $directory . '/purge-queue.jsonl';
        $handle = fopen($queue, 'ab');
        if ($handle === false) {
            return false;
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }
            $written = fwrite($handle, $record . "\n");
            fflush($handle);
            flock($handle, LOCK_UN);
            @chmod($queue, 0600);
            return $written === strlen($record) + 1;
        } finally {
            fclose($handle);
        }
    }
}
