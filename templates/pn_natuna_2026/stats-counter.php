<?php
defined('_JEXEC') or die;


function pn_natuna_track_visitor(): void
{
    $db = Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');

    try {
        // One row per five-minute UTC bucket bounds growth regardless of source-IP churn.
        // The per-bucket cap limits bot inflation while preserving ordinary counters.
        $db->setQuery(
            "INSERT INTO pnn_visitor_aggregates (bucket_start, hits) "
            . "VALUES (FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - MOD(UNIX_TIMESTAMP(UTC_TIMESTAMP()), 300)), 1) "
            . "ON DUPLICATE KEY UPDATE hits = LEAST(hits + 1, 100), updated_at = UTC_TIMESTAMP()"
        );
        $db->execute();

        // Lifetime total has one fixed row and receives the same capped bucket contribution.
        $db->setQuery(
            "INSERT INTO pnn_visitor_totals (counter_id, total_hits, current_bucket, bucket_hits) "
            . "VALUES (1, 1, FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - MOD(UNIX_TIMESTAMP(UTC_TIMESTAMP()), 300)), 1) "
            . "ON DUPLICATE KEY UPDATE "
            . "total_hits = total_hits + IF(current_bucket = VALUES(current_bucket), IF(bucket_hits < 100, 1, 0), 1), "
            . "bucket_hits = IF(current_bucket = VALUES(current_bucket), LEAST(bucket_hits + 1, 100), 1), "
            . "current_bucket = VALUES(current_bucket)"
        );
        $db->execute();

        // Deterministic retention: detailed buckets expire after 32 days on every write.
        $db->setQuery("DELETE FROM pnn_visitor_aggregates WHERE bucket_start < UTC_TIMESTAMP() - INTERVAL 32 DAY");
        $db->execute();
    } catch (Throwable $error) {
        // Conservative fallback for pre-migration/read-only DBs: render zeroes, write nothing.
    }
}

function pn_natuna_get_visitor_stats(): array
{
    $stats = ['online' => 0, 'today' => 0, 'month' => 0, 'total' => 0];
    $db = Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');

    try {
        $db->setQuery("SELECT COALESCE(SUM(hits), 0) FROM pnn_visitor_aggregates WHERE bucket_start >= UTC_TIMESTAMP() - INTERVAL 5 MINUTE");
        $stats['online'] = (int) $db->loadResult();

        $db->setQuery("SELECT COALESCE(SUM(hits), 0) FROM pnn_visitor_aggregates WHERE bucket_start >= UTC_DATE()");
        $stats['today'] = (int) $db->loadResult();

        $db->setQuery("SELECT COALESCE(SUM(hits), 0) FROM pnn_visitor_aggregates WHERE bucket_start >= DATE_FORMAT(UTC_DATE(), '%Y-%m-01')");
        $stats['month'] = (int) $db->loadResult();

        $db->setQuery("SELECT total_hits FROM pnn_visitor_totals WHERE counter_id = 1");
        $stats['total'] = (int) $db->loadResult();
    } catch (Throwable $error) {
        // Keep the four-key display contract when migration is absent or DB is unavailable.
    }

    return $stats;
}
