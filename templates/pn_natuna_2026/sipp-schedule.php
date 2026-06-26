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
      <div class="sipp-table-wrap">
        <table class="sipp-schedule-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal Sidang</th>
              <th>Nomor Perkara</th>
              <th>Sidang Keliling</th>
              <th>Ruangan</th>
              <th>Agenda</th>
              <th>Detil</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($hasRows) : ?>
              <?php foreach ($schedule['rows'] as $row) : ?>
                <tr>
                  <td data-label="No"><?php echo htmlspecialchars($row['no'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="Tanggal Sidang"><?php echo htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="Nomor Perkara"><strong><?php echo htmlspecialchars($row['case'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                  <td data-label="Sidang Keliling">
                    <?php if (strtoupper(trim($row['circuit'])) === 'TIDAK') : ?>
                      <span class="sipp-badge sipp-badge-gray">TIDAK</span>
                    <?php else : ?>
                      <span class="sipp-badge sipp-badge-green"><?php echo htmlspecialchars($row['circuit'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                  </td>
                  <td data-label="Ruangan"><?php echo htmlspecialchars($row['room'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="Agenda"><?php echo htmlspecialchars($row['agenda'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="Detil">
                    <?php if ($row['detail'] !== '') : ?>
                      <a href="<?php echo htmlspecialchars($row['detail'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Detil</a>
                    <?php else : ?>
                      <a href="<?php echo $url; ?>" target="_blank" rel="noopener">SIPP</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else : ?>
              <tr><td colspan="7" class="sipp-empty-state">Data Tidak diTemukan</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php
}
