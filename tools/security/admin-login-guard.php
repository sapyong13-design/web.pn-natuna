<?php
declare(strict_types=1);

if (PHP_SAPI === 'cli' || strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
$option = (string) ($_POST['option'] ?? '');
$task = (string) ($_POST['task'] ?? '');
if ($option !== 'com_login' && $task !== 'login') return;

$window = 900;
$maxAttempts = 5;
$blockSeconds = 1800;
$ip = trim(explode(',', $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown')[0]);
if (!filter_var($ip, FILTER_VALIDATE_IP)) $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$dir = '/home/pnnatuna/private/login-throttle';
if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) return;
$file = $dir . '/' . hash('sha256', $ip) . '.json';
$handle = fopen($file, 'c+');
if (!$handle || !flock($handle, LOCK_EX)) return;
$raw = stream_get_contents($handle);
$state = json_decode(is_string($raw) ? $raw : '', true);
$state = is_array($state) ? $state : [];
$now = time();
$blockedUntil = (int) ($state['blocked_until'] ?? 0);
if ($blockedUntil > $now) {
    flock($handle, LOCK_UN); fclose($handle);
    header('Retry-After: ' . ($blockedUntil - $now));
    http_response_code(429);
    exit('Terlalu banyak percobaan login. Coba kembali dalam 30 menit.');
}
$attempts = array_values(array_filter($state['attempts'] ?? [], static fn($ts): bool => is_int($ts) && $ts >= $now - $window));
$attempts[] = $now;
$state = ['attempts' => $attempts, 'blocked_until' => count($attempts) >= $maxAttempts ? $now + $blockSeconds : 0];
rewind($handle); ftruncate($handle, 0); fwrite($handle, json_encode($state)); fflush($handle);
flock($handle, LOCK_UN); fclose($handle); chmod($file, 0600);
