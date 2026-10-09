#!/usr/bin/env php
<?php
// Run with PHP 5.6: deploy-sipp-overrides.php --root=... --backup-dir=... [--check]
$options = getopt('', array('root:', 'backup-dir:', 'check'));
try {
    if (empty($options['root'])) throw new RuntimeException('--root is required');
    $root = realpath($options['root']);
    if (!$root || !is_file($root.'/system/core/CodeIgniter.php') || !is_file($root.'/application/config/routes.php') || is_file($root.'/configuration.php')) {
        throw new RuntimeException('Target must be SIPP, not Joomla');
    }
    $source = __DIR__.'/sipp-overrides';
    $manifest = json_decode(file_get_contents($source.'/manifest.json'), true);
    if (!isset($manifest['files']) || count($manifest['files']) !== 9) throw new RuntimeException('Invalid manifest');
    $lock = fopen($root.'/application/.overrides-deploy.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('Deployment already running');
    $pending = array();
    foreach ($manifest['files'] as $entry) {
        $path = $entry['path'];
        if (strpos($path, 'application/') !== 0 || strpos($path, '..') !== false) throw new RuntimeException('Invalid override path');
        $file = $source.'/'.$path;
        if (!is_file($file) || hash('sha256', str_replace("\r\n", "\n", file_get_contents($file))) !== $entry['after']) throw new RuntimeException('Source hash mismatch: '.$path);
        $target = $root.'/'.$path;
        if (is_link($target) || realpath(dirname($target)) !== $root.'/'.dirname($path)) throw new RuntimeException('Unsafe target path: '.$path);
        $hash = is_file($target) ? hash_file('sha256', $target) : null;
        if (is_file($target) && hash('sha256', str_replace("\r\n", "\n", file_get_contents($target))) === $entry['after']) $hash = $entry['after'];
        if ($hash !== $entry['after'] && $hash !== $entry['before']) throw new RuntimeException('Vendor file changed; review required: '.$path);
        if ($hash !== $entry['after']) $pending[] = $entry;
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1', $output, $status);
        if ($status !== 0) throw new RuntimeException('PHP lint failed: '.$path);
        $output = array();
    }
    if (isset($options['check'])) {
        echo 'Verified 9 overrides; pending '.count($pending).PHP_EOL;
        exit(count($pending) ? 2 : 0);
    }
    if (!$pending) { echo 'Already current; 9 overrides verified'.PHP_EOL; exit(0); }
    if (empty($options['backup-dir'])) throw new RuntimeException('--backup-dir outside webroot is required');
    $backupBase = realpath($options['backup-dir']);
    if (!$backupBase || $backupBase === $root || strpos($backupBase, $root.'/') === 0) throw new RuntimeException('Backup directory must be outside webroot');
    $backup = $backupBase.'/sipp-overrides-'.gmdate('Ymd\THis\Z').'-'.getmypid();
    if (!mkdir($backup, 0700)) throw new RuntimeException('Cannot create backup');
    $applied = array();
    try {
        foreach ($pending as $entry) {
            $path = $entry['path'];
            $target = $root.'/'.$path;
            $saved = $backup.'/'.str_replace('/', '_', $path);
            $exists = is_file($target);
            if ($exists && !copy($target, $saved)) throw new RuntimeException('Backup failed: '.$path);
            $temp = $target.'.override-'.getmypid();
            if (!copy($source.'/'.$path, $temp)) throw new RuntimeException('Copy failed: '.$path);
            chmod($temp, $exists ? fileperms($target) & 0777 : 0644);
            if (!rename($temp, $target)) { unlink($temp); throw new RuntimeException('Replace failed: '.$path); }
            $applied[] = array($target, $saved, $exists);
        }
    } catch (Exception $error) {
        foreach (array_reverse($applied) as $item) {
            if ($item[2]) copy($item[1], $item[0]); else unlink($item[0]);
        }
        throw $error;
    }
    echo 'Applied '.count($pending).' overrides; backup '.$backup.PHP_EOL;
} catch (Exception $error) {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
}
