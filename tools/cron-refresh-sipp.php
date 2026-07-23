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
printf("SIPP schedule cache refreshed: %d today, %d tomorrow\n", $today, $tomorrow);
