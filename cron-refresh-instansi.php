<?php
/**
 * Cron Auto-Refresh Instansi Feed
 * --------------------------------
 * Refresh cache berita & pengumuman instansi.
 *   - Badilum     : fetch LIVE dari badilum.mahkamahagung.go.id
 *   - PT Kepri    : fetch LIVE dari pt-kepri.go.id
 *   - MA RI       : curated fallback (Cloudflare blokir fetch server-side)
 *
 * Salin script ini ke direktori private di luar public_html, lalu jalankan:
 *   PN_NATUNA_JPATH_ROOT=/home/USER/public_html php -f /home/USER/private/cron/cron-refresh-instansi.php
 * Cache tetap ditulis ke Joomla cache; output cron dikirim ke stdout/stderr.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (!defined('_JEXEC')) {
    define('_JEXEC', true);
}
if (!defined('JPATH_ROOT')) {
    $configuredRoot = getenv('PN_NATUNA_JPATH_ROOT') ?: dirname(__FILE__);
    define('JPATH_ROOT', rtrim($configuredRoot, '/\\'));
}
if (!is_file(JPATH_ROOT . '/templates/pn_natuna_2026/instansi-feed.php')) {
    fwrite(STDERR, "Joomla root tidak valid.\n");
    exit(2);
}
$cacheFile = JPATH_ROOT . '/cache/pn_natuna_instansi_feed.json';
$logFile = getenv('PN_NATUNA_LOG_FILE') ?: dirname(JPATH_ROOT) . '/private/logs/instansi-refresh.log';
if (!is_dir(dirname($logFile)) && !mkdir(dirname($logFile), 0750, true) && !is_dir(dirname($logFile))) {
    fwrite(STDERR, "Peringatan: log file private tidak tersedia; output hanya dikirim ke stdout/stderr.\n");
    $logFile = null;
}

@mkdir(dirname($cacheFile), 0755, true);

// Hapus cache lama supaya pn_natuna_instansi_load() wajib re-fetch.
@unlink($cacheFile);

require JPATH_ROOT . '/templates/pn_natuna_2026/instansi-feed.php';

$ts  = date('Y-m-d H:i:s');
$log = "[$ts] Mulai refresh instansi feed...\n";

try {
    $data = pn_natuna_instansi_load();

    $labels = [
        'ma'      => 'Mahkamah Agung RI',
        'badilum' => 'Badilum',
        'pt'      => 'PT Kepri',
    ];

    foreach ($labels as $key => $label) {
        $berita = count($data[$key]['news'] ?? []);
        $peng   = count($data[$key]['announcements'] ?? []);
        $log   .= "  - {$label}: {$berita} berita, {$peng} pengumuman\n";
    }

    $log .= "[$ts] SELESAI. Cache ditulis: {$cacheFile}\n";
} catch (Throwable $e) {
    $log .= "[ERROR] Refresh gagal.\n";
    fwrite(STDERR, "Refresh instansi gagal.\n");
    $exitCode = 1;
}

if ($logFile !== null) {
    @file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
}

$exitCode = $exitCode ?? 0;
echo $log;
exit($exitCode);
