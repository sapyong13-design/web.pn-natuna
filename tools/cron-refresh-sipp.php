<?php
/** Refresh local SIPP schedule cache. Run from CLI cron only. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!defined('_JEXEC')) define('_JEXEC', true);
if (!defined('JPATH_ROOT')) define('JPATH_ROOT', dirname(__DIR__));
require JPATH_ROOT . '/templates/pn_natuna_2026/sipp-schedule.php';
$schedule = pn_natuna_sipp_refresh_cache();
printf("SIPP schedule cache refreshed: %d rows\n", count($schedule['rows'] ?? []));
