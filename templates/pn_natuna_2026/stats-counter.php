<?php
defined('_JEXEC') or die;


/**
 * Lalu lintas mesin tidak boleh ikut dihitung. Angka di kaki situs mengklaim "kunjungan
 * halaman oleh pembaca", dan perayap indeks, pemantau uptime, serta peramban otomatis
 * bisa melipatgandakannya tanpa satu pun manusia membuka halaman. Daftarnya sengaja
 * konservatif: hanya penanda yang jelas menyebut dirinya mesin. Permintaan tanpa
 * User-Agent juga dilewati - peramban sungguhan selalu mengirimnya.
 */
function pn_natuna_visitor_is_machine(): bool
{
    $agent = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($agent === '') {
        return true;
    }

    return (bool) preg_match(
        '#bot\b|crawl|spider|slurp|scrape|headless|phantomjs|puppeteer|playwright|lighthouse|'
        . 'curl/|wget|python-requests|okhttp|java/|go-http-client|libwww|httpclient|'
        . 'uptime|pingdom|monitor|preview|facebookexternalhit|embedly#i',
        $agent
    );
}

function pn_natuna_track_visitor(): void
{
    if (pn_natuna_visitor_is_machine()) {
        return;
    }

    $db = Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
    $aggregates = $db->quoteName('#__visitor_aggregates');
    $totals = $db->quoteName('#__visitor_totals');

    try {
        // One row per five-minute UTC bucket bounds growth regardless of source-IP churn.
        // The per-bucket cap limits bot inflation while preserving ordinary counters.
        $db->setQuery(
            "INSERT INTO $aggregates (bucket_start, hits) "
            . "VALUES (FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - MOD(UNIX_TIMESTAMP(UTC_TIMESTAMP()), 300)), 1) "
            . "ON DUPLICATE KEY UPDATE hits = LEAST(hits + 1, 100), updated_at = UTC_TIMESTAMP()"
        );
        $db->execute();

        // Lifetime total has one fixed row and receives the same capped bucket contribution.
        $db->setQuery(
            "INSERT INTO $totals (counter_id, total_hits, current_bucket, bucket_hits) "
            . "VALUES (1, 1, FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - MOD(UNIX_TIMESTAMP(UTC_TIMESTAMP()), 300)), 1) "
            . "ON DUPLICATE KEY UPDATE "
            . "total_hits = total_hits + IF(current_bucket = VALUES(current_bucket), IF(bucket_hits < 100, 1, 0), 1), "
            . "bucket_hits = IF(current_bucket = VALUES(current_bucket), LEAST(bucket_hits + 1, 100), 1), "
            . "current_bucket = VALUES(current_bucket)"
        );
        $db->execute();

        // Deterministic retention: detailed buckets expire after 32 days on every write.
        $db->setQuery("DELETE FROM $aggregates WHERE bucket_start < UTC_TIMESTAMP() - INTERVAL 32 DAY");
        $db->execute();
    } catch (Throwable $error) {
        // Conservative fallback for pre-migration/read-only DBs: render zeroes, write nothing.
    }
}

function pn_natuna_get_visitor_stats(): array
{
    $stats = ['today' => 0, 'month' => 0, 'total' => 0, 'since' => null];
    $db = Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
    $aggregates = $db->quoteName('#__visitor_aggregates');
    $totals = $db->quoteName('#__visitor_totals');

    try {
        $db->setQuery("SELECT COALESCE(SUM(hits), 0) FROM $aggregates WHERE bucket_start >= UTC_DATE()");
        $stats['today'] = (int) $db->loadResult();

        $db->setQuery("SELECT COALESCE(SUM(hits), 0) FROM $aggregates WHERE bucket_start >= DATE_FORMAT(UTC_DATE(), '%Y-%m-01')");
        $stats['month'] = (int) $db->loadResult();

        $db->setQuery("SELECT total_hits, counting_since FROM $totals WHERE counter_id = 1");
        $row = $db->loadAssoc();
        $stats['total'] = (int) ($row['total_hits'] ?? 0);
        $stats['since'] = $row['counting_since'] ?? null;
    } catch (Throwable $error) {
        // Keep the display contract when the migration is absent or the DB is unavailable.
    }

    return $stats;
}

/**
 * Pita penghitung di kaki situs.
 *
 * Yang dihitung adalah permintaan halaman, bukan orang: satu pembaca yang membuka lima
 * halaman terhitung lima. Karena itu labelnya berbunyi "kunjungan halaman" dan bukan
 * "pengunjung" - situs ini melarang angka yang tidak bisa ditelusuri ke klaimnya, dan
 * penghitung pengunjung unik butuh identifikasi per orang yang sengaja tidak dikumpulkan.
 * Tanggal mulai ikut dicetak supaya angka totalnya punya konteks.
 */
function pn_natuna_render_visitor_stats(): void
{
    $stats = pn_natuna_get_visitor_stats();
    if ($stats['total'] <= 0) {
        return;
    }

    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $sinceLabel = '';
    if (!empty($stats['since'])) {
        $date = Joomla\CMS\Factory::getDate($stats['since']);
        $sinceLabel = $date->format('j') . ' ' . ($months[(int) $date->format('n')] ?? '') . ' ' . $date->format('Y');
    }

    $angka = static fn(int $n): string => number_format($n, 0, ',', '.');
    ?>
    <div class="footer-stats">
      <p class="footer-stats__label">Kunjungan halaman</p>
      <dl>
        <div><dt>Hari ini</dt><dd><span class="stats-num" data-countup="<?php echo (int) $stats['today']; ?>"><?php echo $angka($stats['today']); ?></span></dd></div>
        <div><dt>Bulan ini</dt><dd><span class="stats-num" data-countup="<?php echo (int) $stats['month']; ?>"><?php echo $angka($stats['month']); ?></span></dd></div>
        <div><dt>Total</dt><dd><span class="stats-num" data-countup="<?php echo (int) $stats['total']; ?>"><?php echo $angka($stats['total']); ?></span></dd></div>
      </dl>
      <?php if ($sinceLabel !== '') : ?>
        <p class="footer-stats__note">Dihitung sejak <?php echo $sinceLabel; ?>. Angka ini menghitung permintaan halaman, bukan jumlah orang.</p>
      <?php endif; ?>
    </div>
    <?php
}
