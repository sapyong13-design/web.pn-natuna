<?php
namespace Joomla\Plugin\System\Pnnatunacloudflare\Helper;
\defined('_JEXEC') or die;

final class CloudflarePurgeQueue
{
    public static function articlePaths(object $item): array
    {
        $alias = trim((string) ($item->alias ?? ''));
        $category = (int) ($item->catid ?? 0);
        $channel = $category === 12 ? 'berita' : ($category === 13 ? 'pengumuman' : '');
        return $alias !== '' && $channel !== ''
            ? ['/', '/' . $channel, '/berita-dan-pengumuman', '/' . $channel . '/' . rawurlencode($alias)]
            : ['/'];
    }

    public static function enqueue(array $paths, ?string $privateRoot = null): bool
    {
        $privateRoot ??= (string) getenv('PN_NATUNA_PRIVATE_ROOT');
        if ($privateRoot === '') return false;
        $directory = rtrim($privateRoot, '/\\') . '/cloudflare';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) return false;
        $urls = array_map(static fn(string $path): string => 'https://pn-natuna.go.id' . $path, $paths);
        $record = json_encode(['queued_at' => gmdate(DATE_ATOM), 'urls' => array_values(array_unique($urls))], JSON_UNESCAPED_SLASHES);
        if ($record === false) return false;
        $queue = $directory . '/purge-queue.jsonl';
        $handle = @fopen($queue, 'ab');
        if ($handle === false) return false;
        try {
            if (!flock($handle, LOCK_EX)) return false;
            $written = fwrite($handle, $record . "\n");
            fflush($handle); flock($handle, LOCK_UN); @chmod($queue, 0600);
            return $written === strlen($record) + 1;
        } finally { fclose($handle); }
    }
}
