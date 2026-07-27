<?php
defined('_JEXEC') or die;

function pn_natuna_sipp_text(string $html): string
{
    return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function pn_natuna_sipp_fetch(string $url, string $referer = ''): string
{
    static $cookieFile = null;
    if ($cookieFile === null) {
        $cookieFile = tempnam(sys_get_temp_dir(), 'pn-sipp-');
        if (is_string($cookieFile)) {
            register_shutdown_function(static function () use ($cookieFile): void {
                @unlink($cookieFile);
            });
        }
    }
    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        $headers = ['User-Agent: PN-Natuna-Website/1.0'];
        if ($referer !== '') {
            $headers[] = 'Referer: ' . $referer;
            $headers[] = 'X-Requested-With: XMLHttpRequest';
        }
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_COOKIEFILE => $cookieFile ?: '',
            CURLOPT_COOKIEJAR => $cookieFile ?: '',
        ]);
        $html = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        return is_string($html) && $status >= 200 && $status < 300 ? $html : '';
    }

    $headers = "User-Agent: PN-Natuna-Website/1.0\r\n";
    if ($referer !== '') {
        $headers .= "Referer: {$referer}\r\nX-Requested-With: XMLHttpRequest\r\n";
    }
    $context = stream_context_create([
        'http' => ['timeout' => 8, 'header' => $headers],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $html = @file_get_contents($url, false, $context);
    return is_string($html) ? $html : '';
}

function pn_natuna_sipp_cache_file(): string
{
    return (defined('JPATH_ROOT') ? JPATH_ROOT : dirname(__DIR__, 2)) . '/cache/pn_natuna_sipp_schedule.json';
}

function pn_natuna_sipp_load_cache(): array
{
    $empty = ['date_label' => '', 'updated' => 'tanggal gagal dimuat', 'total' => '0', 'rows' => []];
    $file = pn_natuna_sipp_cache_file();
    if (!is_file($file)) {
        return ['days' => ['today' => $empty, 'tomorrow' => $empty]];
    }
    $data = json_decode((string) @file_get_contents($file), true);
    if (!is_array($data)) {
        return ['days' => ['today' => $empty, 'tomorrow' => $empty]];
    }
    if (is_array($data['days']['today']['rows'] ?? null) && is_array($data['days']['tomorrow']['rows'] ?? null)) {
        return $data;
    }
    if (is_array($data['rows'] ?? null)) {
        return ['days' => ['today' => $data, 'tomorrow' => $empty], '_cached_at' => $data['_cached_at'] ?? null];
    }
    return ['days' => ['today' => $empty, 'tomorrow' => $empty]];
}
function pn_natuna_sipp_case_category(string $caseNumber): array
{
    $normalized = strtolower(trim($caseNumber));

    if (preg_match('/(?:pid\.sus-(?:prk|perikanan)|perikanan)/i', $caseNumber)) {
        return ['key' => 'fishery', 'label' => 'Perikanan'];
    }
    if (preg_match('/(?:pdt\.[^\/]*eks|\beks(?:ekusi)?\b)/i', $caseNumber)) {
        return ['key' => 'execution', 'label' => 'Eksekusi'];
    }
    if (preg_match('/(?:\/phi\/|\/niaga(?:\.|\/)|pdt\.sus-(?:phi|niaga))/i', $caseNumber)) {
        return ['key' => 'special-civil', 'label' => 'Perdata Khusus'];
    }
    if (preg_match('/\/pdt\.(?:g|p|g\.s|bth|plw|kons)[^\/]*\//i', $caseNumber)) {
        return ['key' => 'civil', 'label' => 'Perdata'];
    }
    if (str_contains($normalized, '/pid.') || preg_match('/\/(?:tipikor)\//i', $caseNumber)) {
        return ['key' => 'criminal', 'label' => 'Pidana'];
    }

    return ['key' => 'other', 'label' => 'Perkara'];
}

function pn_natuna_sipp_may_fetch_party(array $row): bool
{
    return ($row['detail'] ?? '') !== '' && stripos((string) ($row['case'] ?? ''), 'Pid.Sus-Anak') === false;
}


function pn_natuna_sipp_refresh_cache(): array
{
    $baseUrl = 'https://sipp.pn-natuna.go.id/list_jadwal_sidang';
    $timezone = new DateTimeZone('Asia/Jakarta');
    $today = new DateTimeImmutable('today', $timezone);
    $days = [
        'today' => pn_natuna_sipp_parse_schedule(pn_natuna_sipp_fetch($baseUrl)),
        'tomorrow' => pn_natuna_sipp_parse_schedule(pn_natuna_sipp_fetch(
            $baseUrl . '/search/1/' . $today->modify('+1 day')->format('d/m/Y')
        )),
    ];

    if ($days['today']['date_label'] === '' && !$days['today']['rows']) {
        return pn_natuna_sipp_load_cache();
    }

    foreach ($days as &$schedule) {
        foreach ($schedule['rows'] as &$row) {
            $row['party_label'] = '';
            $row['party'] = '';
            if (!pn_natuna_sipp_may_fetch_party($row)) {
                continue;
            }
            $party = pn_natuna_sipp_parse_party(pn_natuna_sipp_fetch($row['detail'], $baseUrl));
            $row['party_label'] = $party['label'];
            $row['party'] = $party['name'];
        }
        unset($row);
    }
    unset($schedule);

    $cache = [
        'days' => $days,
        '_cached_at' => gmdate(DATE_ATOM),
    ];
    $file = pn_natuna_sipp_cache_file();
    @mkdir(dirname($file), 0775, true);
    $temporary = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';
    if (@file_put_contents($temporary, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false) {
        @rename($temporary, $file);
    }
    @unlink($temporary);
    return $cache;
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

function pn_natuna_sipp_parse_party(string $html): array
{
    if (preg_match('/<td[^>]*>\s*(Terdakwa|Pemohon|Penggugat|Pihak)\s*<\/td>\s*<td[^>]*>(.*?)<td\b/is', $html, $match)) {
        return [
            'label' => pn_natuna_sipp_text($match[1]),
            'name' => pn_natuna_sipp_text($match[2]),
        ];
    }
    return ['label' => '', 'name' => ''];
}
function pn_natuna_sipp_label_date(string $label): string
{
    if (!preg_match('/^\s*(?:[^,]+,\s*)?(\d{1,2})\s+([[:alpha:]]+)\.?\s+(\d{4})\s*$/u', $label, $match)) {
        return '';
    }

    $months = [
        'jan' => 1, 'januari' => 1, 'feb' => 2, 'februari' => 2,
        'mar' => 3, 'maret' => 3, 'apr' => 4, 'april' => 4,
        'mei' => 5, 'jun' => 6, 'juni' => 6, 'jul' => 7, 'juli' => 7,
        'agu' => 8, 'ags' => 8, 'agustus' => 8, 'sep' => 9, 'september' => 9,
        'okt' => 10, 'oktober' => 10, 'nov' => 11, 'november' => 11,
        'des' => 12, 'desember' => 12,
    ];
    $month = strtolower($match[2]);
    $day = (int) $match[1];
    $year = (int) $match[3];

    if (!isset($months[$month]) || !checkdate($months[$month], $day, $year)) {
        return '';
    }

    return sprintf('%04d-%02d-%02d', $year, $months[$month], $day);
}

function pn_natuna_sipp_day_status(string $key): array
{
    $timezone = new DateTimeZone('Asia/Jakarta');
    $expected = (new DateTimeImmutable('today', $timezone))
        ->modify($key === 'tomorrow' ? '+1 day' : 'today')
        ->format('Y-m-d');
    $schedule = pn_natuna_sipp_load_cache()['days'][$key] ?? [];
    $labelDate = pn_natuna_sipp_label_date((string) ($schedule['date_label'] ?? ''));
    $updated = (string) ($schedule['updated'] ?? '');

    return [
        'stale' => $labelDate === '' || $labelDate !== $expected,
        'label_date' => $labelDate,
        'expected' => $expected,
        'updated' => $updated === 'tanggal gagal dimuat' ? '' : $updated,
    ];
}

function pn_natuna_sipp_render_schedule(): void
{
    $url = 'https://sipp.pn-natuna.go.id/list_jadwal_sidang';
    $cache = pn_natuna_sipp_load_cache();
    $statuses = [
        'today' => pn_natuna_sipp_day_status('today'),
        'tomorrow' => pn_natuna_sipp_day_status('tomorrow'),
    ];
    $days = [
        'today' => ['title' => 'Hari Ini', 'empty' => 'Tidak ada sidang hari ini'],
        'tomorrow' => ['title' => 'Besok', 'empty' => 'Tidak ada sidang besok'],
    ];
    ?>
    <section class="sipp-schedule-board" aria-labelledby="sipp-schedule-title">
      <div class="sipp-schedule-head sipp-schedule-head-simple">
        <div>
          <p class="section-kicker">Informasi Perkara</p>
          <h2 id="sipp-schedule-title">Jadwal Sidang Hari Ini &amp; Besok</h2>
          <p class="section-desc"><?php if ($statuses['today']['stale']) : ?>Jadwal hari ini belum termuat dari SIPP.<?php if ($statuses['today']['updated'] !== '') : ?> Data terakhir yang berhasil dimuat: <?php echo htmlspecialchars($statuses['today']['updated'], ENT_QUOTES, 'UTF-8'); ?>.<?php endif; ?> Hubungi PTSP 0773-3211203 atau buka SIPP langsung.<?php else : ?>Agenda persidangan resmi yang diperbarui otomatis dari SIPP.<?php endif; ?></p>
        </div>
        <a class="section-action" href="<?php echo $url; ?>" target="_blank" rel="noopener">Buka jadwal lengkap di SIPP</a>
      </div>
      <div class="sipp-day-tabs" role="tablist" aria-label="Pilih hari jadwal sidang">
        <?php
        foreach ($days as $key => $day) :
            $schedule = $cache['days'][$key];
            $status = $statuses[$key];
            // Saat basi, judul tab jadi tanggal literalnya. Barisan kedua tidak
            // boleh mengulang tanggal yang sama; ia menyatakan peran yang gagal
            // dipenuhi hari itu, supaya pembaca tahu apa yang seharusnya ada.
            $tabTitle = $status['stale'] ? ($schedule['date_label'] ?: $status['expected']) : $day['title'];
            $tabSub = $status['stale']
                ? 'bukan ' . mb_strtolower($day['title'], 'UTF-8')
                : ($schedule['date_label'] ?: $status['expected']);
        ?>
          <button type="button" role="tab" id="sipp-tab-<?php echo $key; ?>" aria-controls="sipp-panel-<?php echo $key; ?>" aria-selected="<?php echo $key === 'today' ? 'true' : 'false'; ?>" tabindex="<?php echo $key === 'today' ? '0' : '-1'; ?>"<?php echo $status['stale'] ? ' class="sipp-tab-stale" data-stale="true"' : ''; ?>>
            <strong><?php echo htmlspecialchars($tabTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
            <span><?php echo htmlspecialchars($tabSub, ENT_QUOTES, 'UTF-8'); ?></span>
            <b><?php echo count($schedule['rows']); ?> perkara</b>
          </button>
        <?php endforeach; ?>
      </div>
      <?php foreach ($days as $key => $day) : $schedule = $cache['days'][$key]; $status = $statuses[$key]; ?>
        <div class="sipp-day-panel" role="tabpanel" id="sipp-panel-<?php echo $key; ?>" aria-labelledby="sipp-tab-<?php echo $key; ?>"<?php echo $key === 'today' ? '' : ' hidden'; ?>>
          <?php if ($status['stale']) : ?>
            <p class="sipp-stale-notice" role="status">Daftar di bawah ini adalah agenda <?php echo htmlspecialchars($schedule['date_label'] ?: $status['expected'], ENT_QUOTES, 'UTF-8'); ?> &mdash; bukan <?php echo mb_strtolower($day['title'], 'UTF-8'); ?>.</p>
          <?php endif; ?>
          <?php if ($schedule['rows']) : ?>
            <div class="sipp-cards">
              <?php foreach ($schedule['rows'] as $index => $row) : ?>
                <?php $category = pn_natuna_sipp_case_category((string) ($row['case'] ?? '')); ?>
                <article class="sipp-card sipp-card--<?php echo htmlspecialchars($category['key'], ENT_QUOTES, 'UTF-8'); ?>" data-case-category="<?php echo htmlspecialchars($category['key'], ENT_QUOTES, 'UTF-8'); ?>">
                  <span class="sipp-card-number" aria-label="Nomor <?php echo $index + 1; ?>, kategori <?php echo htmlspecialchars($category['label'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT); ?></span>
                  <div class="sipp-card-main">
                    <span class="sipp-card-category"><?php echo htmlspecialchars($category['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <strong class="sipp-card-case"><?php echo htmlspecialchars($row['case'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if (($row['party'] ?? '') !== '') : ?>
                      <p class="sipp-card-party"><span><?php echo htmlspecialchars($row['party_label'], ENT_QUOTES, 'UTF-8'); ?></span><?php echo htmlspecialchars($row['party'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <div class="sipp-card-footer">
                    <div class="sipp-card-meta">
                      <span class="sipp-chip sipp-chip-room"><?php echo htmlspecialchars($row['room'], ENT_QUOTES, 'UTF-8'); ?></span>
                      <span class="sipp-chip"><?php echo htmlspecialchars($row['agenda'], ENT_QUOTES, 'UTF-8'); ?></span>
                      <?php if (strtoupper(trim($row['circuit'])) !== 'TIDAK') : ?><span class="sipp-chip sipp-chip-circuit">Sidang Keliling</span><?php endif; ?>
                    </div>
                      <a class="sipp-card-link" href="<?php echo htmlspecialchars($row['detail'] ?: $url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" aria-label="Lihat detail perkara <?php echo htmlspecialchars($row['case'], ENT_QUOTES, 'UTF-8'); ?> — buka SIPP di tab baru">Lihat detail <span aria-hidden="true">&rarr;</span></a>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else : ?>
            <div class="sipp-empty">
              <span class="sipp-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="30" height="30"><path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14h18V6a2 2 0 0 0-2-2zm0 16H5V10h14z" fill="currentColor"/></svg></span>
              <strong><?php echo $day['empty']; ?></strong>
              <span>Hubungi <a href="tel:07733211203">0773-3211203</a> atau <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">buka jadwal SIPP</a>.</span>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
    <script>
    (() => {
      const tabs = Array.from(document.querySelectorAll('.sipp-day-tabs [role="tab"]'));
      const activate = (tab) => tabs.forEach((item) => {
        const active = item === tab;
        item.setAttribute('aria-selected', active ? 'true' : 'false');
        item.tabIndex = active ? 0 : -1;
        document.getElementById(item.getAttribute('aria-controls')).hidden = !active;
      });
      tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activate(tab));
        tab.addEventListener('keydown', (event) => {
          if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
          event.preventDefault();
          const offset = event.key === 'ArrowRight' ? 1 : tabs.length - 1;
          const next = tabs[(index + offset) % tabs.length];
          activate(next);
          next.focus();
        });
      });
    })();
    </script>
    <?php
}
