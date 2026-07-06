<?php
defined('_JEXEC') or die;

function pn_natuna_sipp_text(string $html): string
{
    return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function pn_natuna_sipp_fetch(string $url): string
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 4,
            'header' => "User-Agent: PN-Natuna-Website/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $html = @file_get_contents($url, false, $context);
    return is_string($html) ? $html : '';
}

function pn_natuna_sipp_parse_schedule(string $html): array
{
    $data = [
        'date_label' => '',
        'updated' => 'tanggal gagal dimuat',
        'total' => '0',
        'rows' => [],
    ];

    if (preg_match('/JADWAL\s+SIDANG\s*-\s*([^<]+)/i', $html, $match)) {
        $data['date_label'] = pn_natuna_sipp_text($match[1]);
    }

    if (preg_match('/Pembaharuan\s+Data\s*:\s*(.*?)\s*,\s*Total\s*:\s*(\d+)\s*Perkara/is', $html, $match)) {
        $data['updated'] = pn_natuna_sipp_text($match[1]);
        $data['total'] = $match[2];
    }

    if (preg_match_all('/<tr><td>(\d+)<\/td><td[^>]*>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td[^>]*>(.*?)<\/td><\/tr>/is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $row) {
            $detail = '';
            if (preg_match("/detilSidang\('([^']+)'\)/", $row[7], $detailMatch)) {
                $detail = 'https://sipp.pn-natuna.go.id/detil_jadwal_sidang/' . $detailMatch[1];
            }

            $data['rows'][] = [
                'no' => pn_natuna_sipp_text($row[1]),
                'date' => pn_natuna_sipp_text($row[2]),
                'case' => pn_natuna_sipp_text($row[3]),
                'circuit' => pn_natuna_sipp_text($row[4]),
                'room' => pn_natuna_sipp_text($row[5]),
                'agenda' => pn_natuna_sipp_text($row[6]),
                'detail' => $detail,
            ];
        }
    }

    return $data;
}

function pn_natuna_sipp_render_schedule(): void
{
    $url = 'https://sipp.pn-natuna.go.id/list_jadwal_sidang';
    $html = pn_natuna_sipp_fetch($url);
    $schedule = pn_natuna_sipp_parse_schedule($html);
    $hasRows = count($schedule['rows']) > 0;
    $dateLabel = $schedule['date_label'] !== '' ? $schedule['date_label'] : 'Hari ini';
    ?>
    <section class="sipp-schedule-board" aria-labelledby="sipp-schedule-title">
      <div class="sipp-schedule-head sipp-schedule-head-simple">
        <div>
          <h2 id="sipp-schedule-title">Jadwal Sidang Hari Ini</h2>
          <span><?php echo htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <a class="section-action" href="<?php echo $url; ?>" target="_blank" rel="noopener">Buka jadwal lengkap di SIPP</a>
      </div>
      <?php if ($hasRows) : ?>
        <div class="sipp-cards">
          <?php foreach ($schedule['rows'] as $row) : ?>
            <article class="sipp-card">
              <div class="sipp-card-main">
                <strong class="sipp-card-case"><?php echo htmlspecialchars($row['case'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <div class="sipp-card-meta">
                  <span class="sipp-chip sipp-chip-room">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z" fill="currentColor"/></svg>
                    <?php echo htmlspecialchars($row['room'], ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                  <span class="sipp-chip"><?php echo htmlspecialchars($row['agenda'], ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php if (strtoupper(trim($row['circuit'])) !== 'TIDAK') : ?>
                    <span class="sipp-chip sipp-chip-circuit">Sidang Keliling</span>
                  <?php endif; ?>
                </div>
              </div>
              <a class="sipp-card-link" href="<?php echo htmlspecialchars($row['detail'] !== '' ? $row['detail'] : $url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Detil &rarr;</a>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <div class="sipp-empty">
          <span class="sipp-empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="30" height="30"><path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 16H5V10h14zM5 8V6h14v2zm4.53 10.47-2.5-2.5 1.06-1.06 1.44 1.43 3.88-3.87 1.06 1.06z" fill="currentColor"/></svg>
          </span>
          <strong>Tidak ada sidang hari ini</strong>
          <span>Jadwal diperbarui otomatis dari SIPP Pengadilan Negeri Natuna.</span>
          <a href="<?php echo $url; ?>" target="_blank" rel="noopener">Lihat arsip jadwal di SIPP &rarr;</a>
        </div>
      <?php endif; ?>
    </section>
    <?php
}
