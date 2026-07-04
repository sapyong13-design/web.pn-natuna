<?php
/**
 * Cron Auto-Refresh Instansi Feed
 * --------------------------------
 * Refresh cache berita & pengumuman instansi.
 *   - Badilum     : fetch LIVE dari badilum.mahkamahagung.go.id
 *   - PT Kepri    : fetch LIVE dari pt-kepri.go.id
 *   - MA RI       : curated fallback (Cloudflare blokir fetch server-side)
 *
 * Jalankan via cPanel Cron Job sekali sehari.
 *   php -f /home/USER/public_html/cron-refresh-instansi.php
 *
 * Log ditulis ke cache/instansi-refresh.log dan stdout (email cron).
 */

if (!defined('_JEXEC')) {
    define('_JEXEC', true);
}
if (!defined('JPATH_ROOT')) {
    define('JPATH_ROOT', dirname(__FILE__));
}

$cacheFile = JPATH_ROOT . '/cache/pn_natuna_instansi_feed.json';
$logFile   = JPATH_ROOT . '/cache/instansi-refresh.log';

@mkdir(dirname($cacheFile), 0775, true);

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
    $log .= '[ERROR] ' . $e->getMessage() . "\n";
}

@file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);

echo $log;
