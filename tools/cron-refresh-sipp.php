<?php
/** Refresh local SIPP schedule cache. Run from CLI cron only. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!defined('_JEXEC')) define('_JEXEC', true);
$configuredRoot = getenv('PN_NATUNA_JPATH_ROOT') ?: dirname(__DIR__);
if (!defined('JPATH_ROOT')) define('JPATH_ROOT', rtrim($configuredRoot, '/\\'));
if (!is_file(JPATH_ROOT . '/templates/pn_natuna_2026/sipp-schedule.php')) {
    fwrite(STDERR, "Joomla root tidak valid.\n");
    exit(2);
}
require JPATH_ROOT . '/templates/pn_natuna_2026/sipp-schedule.php';
$schedule = pn_natuna_sipp_refresh_cache();
$today = count($schedule['days']['today']['rows'] ?? []);
$tomorrow = count($schedule['days']['tomorrow']['rows'] ?? []);
$source = (string) ($schedule['_source'] ?? 'cache');
printf("SIPP schedule cache refreshed from %s: %d today, %d tomorrow\n", $source, $today, $tomorrow);

// Cache basi adalah kegagalan, bukan keberhasilan: beranda menyembunyikan angka
// basi, dan log cron harus menunjukkan gangguan alih-alih "berhasil" palsu.
$status = pn_natuna_sipp_day_status('today');
if ($status['stale']) {
    fwrite(STDERR, sprintf(
        "SIPP cache basi: label %s, seharusnya %s. Periksa WAF sipp.pn-natuna.go.id atau PN_NATUNA_SIPP_DB_CONFIG.\n",
        $status['label_date'] !== '' ? $status['label_date'] : '(kosong)',
        $status['expected']
    ));
    exit(3);
}
