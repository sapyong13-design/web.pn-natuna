<?php

/**
 * Hero Slider PN Natuna.
 * Slide 1: Selamat datang (sapaan dinamis + status layanan) + foto gedung.
 * Slide 2: Berita & Pengumuman interaktif (tab + preview gambar + excerpt).
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

function pn_natuna_hero_latest_articles(int $catId, int $limit = 4): array
{
    try {
        $db = Factory::getDbo();
        $now = Factory::getDate()->toSql();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title', 'alias', 'catid', 'created', 'publish_up', 'images', 'introtext', 'fulltext', 'metadesc']))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('state') . ' = 1')
            ->where($db->quoteName('catid') . ' = ' . (int) $catId)
            ->where($db->quoteName('alias') . ' NOT LIKE ' . $db->quote('%berita-dan-pengumuman%'))
            ->where('(' . $db->quoteName('publish_up') . ' IS NULL OR ' . $db->quoteName('publish_up') . ' = ' . $db->quote($db->getNullDate()) . ' OR ' . $db->quoteName('publish_up') . ' <= ' . $db->quote($now) . ')')
            ->where('(' . $db->quoteName('publish_down') . ' IS NULL OR ' . $db->quoteName('publish_down') . ' = ' . $db->quote($db->getNullDate()) . ' OR ' . $db->quoteName('publish_down') . ' >= ' . $db->quote($now) . ')')
            ->order('CASE WHEN ' . $db->quoteName('publish_up') . ' > ' . $db->quote('2000-01-02 00:00:00') . ' THEN ' . $db->quoteName('publish_up') . ' ELSE ' . $db->quoteName('created') . ' END DESC')
            ->setLimit($limit);
        $db->setQuery($query);
        $items = $db->loadObjectList() ?: [];
        foreach ($items as $item) {
            if (!empty($item->publish_up) && $item->publish_up > '2000-01-02 00:00:00') {
                $item->created = $item->publish_up;
            }
        }
        return $items;
    } catch (Throwable $e) {
        return [];
    }
}

function pn_natuna_hero_date(string $created): string
{
    $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($created);
    if (!$ts) {
        return '';
    }
    return date('j', $ts) . ' ' . $months[(int) date('n', $ts)];
}

function pn_natuna_hero_is_new(string $created): bool
{
    $ts = strtotime($created);
    return $ts && (time() - $ts) < 7 * 86400;
}

function pn_natuna_hero_excerpt(?string $introtext, int $length = 90): string
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $introtext)));
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) > $length) {
        $text = rtrim(mb_substr($text, 0, $length), ' .,;:') . '&hellip;';
    }
    return $text;
}

function pn_natuna_hero_article_url(object $article, int $catId): string
{
    try {
        return Route::_('index.php?option=com_content&view=article&id=' . (int) $article->id . ':' . $article->alias . '&catid=' . (int) $catId);
    } catch (Throwable $e) {
        return '/berita-dan-pengumuman';
    }
}

function pn_natuna_hero_article_image(object $article): string
{
    $fallback = (int) ($article->catid ?? 0) === 13
        ? '/images/brand/pengumuman-resmi-pn-natuna.webp'
        : '/images/sejarah/sejarah-pn-natuna.jpg';
    if (empty($article->images)) {
        return $fallback;
    }
    $decoded = json_decode((string) $article->images, true);
    $img = $decoded['image_intro'] ?? '';
    if ($img === '') {
        $img = $decoded['image_fulltext'] ?? '';
    }
    if ($img === '') {
        return $fallback;
    }
    $img = explode('#', $img)[0];
    return '/' . ltrim($img, '/');
}

function pn_natuna_announcement_image(object $article): string
{
    $decoded = json_decode((string) ($article->images ?? ''), true) ?: [];
    $img = trim((string) ($decoded['image_fulltext'] ?? ''));
    if ($img === '') {
        $img = trim((string) ($decoded['image_intro'] ?? ''));
    }
    if ($img === '') {
        return '/images/brand/pengumuman-resmi-pn-natuna.webp';
    }
    return '/' . ltrim(explode('#', $img)[0], '/');
}

function pn_natuna_announcement_date(string $created): string
{
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($created);
    return $ts ? date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts) : '';
}
function pn_natuna_render_latest_announcements(?array $articles = null, ?array $videos = null): void
{
    $articles ??= pn_natuna_hero_latest_articles(13, 1);
    if (!$articles) {
        return;
    }
    $videos ??= function_exists('pn_natuna_youtube_load_cache') ? pn_natuna_youtube_load_cache() : [];
    $videos = array_slice(array_values($videos), 0, 5);
    if (!$videos && function_exists('pn_natuna_youtube_pinned')) {
        $videos = pn_natuna_youtube_pinned();
    }
    $feature = array_values($articles)[0];
    $featureExcerpt = pn_natuna_hero_excerpt($feature->introtext ?: ($feature->metadesc ?: $feature->fulltext), 150);
    if ((int) ($feature->id ?? 0) === 208) {
        $featureExcerpt = 'Penetapan resmi pemenang lelang Barang Milik Negara Pengadilan Negeri Natuna tanggal 11 Juni 2026.';
    } elseif ((int) ($feature->id ?? 0) === 209) {
        $featureExcerpt = 'Informasi resmi lelang Barang Milik Negara Pengadilan Negeri Natuna tanggal 4 Juni 2026.';
    }
    $activeVideo = $videos[0] ?? null;
    ?>
    <section class="module-card announcement-showcase" aria-labelledby="announcement-showcase-title">
      <div class="news-cards-head announcement-showcase__head">
        <div class="section-head">
          <p class="section-kicker">Informasi Resmi &amp; Dokumentasi</p>
          <h2 id="announcement-showcase-title">Pengumuman &amp; Video Terbaru</h2>
          <p class="section-desc">Pengumuman resmi dan dokumentasi terbaru Pengadilan Negeri Natuna.</p>
        </div>
        <div class="announcement-showcase__actions">
          <a class="section-action" href="/pengumuman">Semua Pengumuman <span aria-hidden="true">&rarr;</span></a>
          <a class="announcement-showcase__channel-link" href="https://www.youtube.com/channel/UCuPb35OggK2PKdW7Ed0qszA" target="_blank" rel="noopener noreferrer"><span aria-hidden="true">▶</span> Kunjungi channel</a>
        </div>
      </div>
      <div class="announcement-showcase__grid">
        <a class="announcement-feature" href="<?php echo htmlspecialchars(pn_natuna_hero_article_url($feature, 13), ENT_QUOTES, 'UTF-8'); ?>">
          <span class="announcement-feature__eyebrow">Pengumuman terbaru</span>
          <span class="announcement-feature__media">
            <img src="<?php echo htmlspecialchars(pn_natuna_announcement_image($feature), ENT_QUOTES, 'UTF-8'); ?>" alt="" width="760" height="460" loading="lazy" decoding="async">
            <span class="announcement-feature__badge">Terbaru</span>
          </span>
          <span class="announcement-feature__copy">
            <time><?php echo htmlspecialchars(pn_natuna_announcement_date($feature->created), ENT_QUOTES, 'UTF-8'); ?></time>
            <strong><?php echo htmlspecialchars($feature->title, ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if ($featureExcerpt !== '') : ?><span class="announcement-feature__excerpt"><?php echo $featureExcerpt; ?></span><?php endif; ?>
            <span class="announcement-feature__cta">Baca Pengumuman <span aria-hidden="true">&rarr;</span></span>
          </span>
        </a>
        <?php if ($activeVideo) : ?>
          <div class="youtube-showcase" data-youtube-showcase>
            <span class="youtube-showcase__eyebrow">Video pilihan</span>
            <div class="youtube-showcase-player" data-youtube-player>
              <img src="<?php echo htmlspecialchars($activeVideo['thumbnail'], ENT_QUOTES, 'UTF-8'); ?>" alt="" width="1280" height="720" loading="lazy" decoding="async" data-youtube-preview>
              <span class="youtube-showcase-player__shade" aria-hidden="true"></span>
              <button class="youtube-showcase-play" type="button" data-youtube-play aria-label="Putar video: <?php echo htmlspecialchars($activeVideo['title'], ENT_QUOTES, 'UTF-8'); ?>"><span class="youtube-showcase-play__icon" aria-hidden="true">&#9654;</span><span class="youtube-showcase-play__label">Putar video</span></button>
              <div class="youtube-showcase-player__copy">
                <span class="youtube-showcase-player__meta">Channel Resmi PN Natuna <span aria-hidden="true">&middot;</span> <span data-youtube-source><?php echo ($activeVideo['source'] ?? '') === 'wajib' ? 'Video pilihan' : 'Video terbaru'; ?></span></span>
                <strong data-youtube-title><?php echo htmlspecialchars($activeVideo['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <a href="<?php echo htmlspecialchars($activeVideo['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" data-youtube-fallback>Tonton di YouTube</a>
              </div>
            </div>
            <div class="youtube-showcase-rail__viewport">
              <ul class="youtube-showcase-rail" aria-label="Pilih video">
                <?php foreach ($videos as $index => $video) : ?>
                  <li>
                    <button class="youtube-showcase-item<?php echo $index === 0 ? ' is-active' : ''; ?>" type="button" data-youtube-item data-video-id="<?php echo htmlspecialchars($video['id'], ENT_QUOTES, 'UTF-8'); ?>" data-video-title="<?php echo htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8'); ?>" data-video-thumbnail="<?php echo htmlspecialchars($video['thumbnail'], ENT_QUOTES, 'UTF-8'); ?>" data-video-source="<?php echo htmlspecialchars((string) ($video['source'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="Pilih video: <?php echo htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8'); ?>">
                      <span class="youtube-showcase-item__media"><img src="<?php echo htmlspecialchars($video['thumbnail'], ENT_QUOTES, 'UTF-8'); ?>" alt="" width="240" height="135" loading="lazy" decoding="async"><span class="youtube-showcase-item__play" aria-hidden="true">&#9654;</span></span>
                      <span class="youtube-showcase-item__copy"><strong><?php echo htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8'); ?></strong><span class="youtube-showcase-item__state"><?php echo $index === 0 ? 'Sedang dipilih' : (($video['source'] ?? '') === 'wajib' ? 'Pilihan' : 'Terbaru'); ?></span></span>
                    </button>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mobile-rail-status youtube-showcase-count" data-youtube-count><span class="mobile-rail-hint">Geser untuk memilih video</span><output>1 dari <?php echo count($videos); ?></output></div>
            <p class="youtube-showcase-status visually-hidden" aria-live="polite" aria-atomic="true" data-youtube-status></p>
          </div>
        <?php endif; ?>
      </div>
    </section>
    <?php
}

/** Jumlah agenda sidang hari ini dari cache SIPP; 0 bila cache belum tersedia. */
function pn_natuna_hero_agenda_today(): int
{
    if (!function_exists('pn_natuna_sipp_load_cache')) {
        return 0;
    }
    try {
        $rows = pn_natuna_sipp_load_cache()['days']['today']['rows'] ?? [];
        return is_array($rows) ? count($rows) : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Skor IKM dan IPAK terbaru beserta jumlah respondennya, dibaca dari modul 816
 * (sumbernya tools/refresh-survey.py). Array kosong bila widget belum terisi —
 * hero tidak boleh gagal hanya karena survei belum diperbarui.
 *
 * @return list<array{label:string, value:string, responden:string}>
 */
function pn_natuna_hero_survey_scores(): array
{
    try {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('content'))
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('id') . ' = 816');
        $db->setQuery($query);
        $content = (string) $db->loadResult();
    } catch (Throwable $e) {
        return [];
    }

    $pattern = '/survey-score-label">(?P<label>[^<]*)<\/span>\s*'
        . '<span class="survey-score-value">\s*(?P<score>[\d.,]+)\s*<em>\s*\/\s*(?P<max>[\d.,]+)\s*<\/em>.*?'
        . 'survey-score-meta">(?P<meta>[^<]*)</su';
    if (!preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
        return [];
    }

    $scores = [];
    foreach ($matches as $match) {
        $label = trim(html_entity_decode($match['label'], ENT_QUOTES, 'UTF-8'));
        // "SKM / IKM" disingkat jadi "IKM" agar muat di baris data hero.
        if (stripos($label, 'IKM') !== false) {
            $label = 'IKM';
        }
        $meta = html_entity_decode($match['meta'], ENT_QUOTES, 'UTF-8');
        $responden = preg_match('/(\d+)\s*responden/ui', $meta, $r) ? $r[1] . ' responden' : '';
        $scores[] = [
            'label' => $label,
            'value' => $match['score'] . ' / ' . $match['max'],
            'responden' => $responden,
        ];
    }
    return $scores;
}

/**
 * Hero beranda: satu komposisi statis, tanpa rotasi.
 *
 * Susunannya tiga lapis vertikal, bukan kartu melayang di atas foto:
 * panggung foto dengan sambutan di kiri, lalu pita berita selebar layar, lalu
 * satu baris Zona Integritas. Kartu melayang membuat foto tertutup dari dua
 * sisi dan terlihat seperti panel admin yang ditempel; pita memberi berita
 * ruang selebar halaman dan membiarkan gedung tetap terlihat utuh.
 */
function pn_natuna_render_hero_slider(): void
{
    $berita = pn_natuna_hero_latest_articles(12, 3);

    $agendaToday = pn_natuna_hero_agenda_today();
    $surveyScores = pn_natuna_hero_survey_scores();

    ?>
    <div class="hero-cinema hero-stack">
      <div class="hero-backdrop" aria-hidden="true">
        <span class="hero-backdrop-image"><img src="/images/hero/gedung-pn-natuna-2026-graded.webp" srcset="/images/hero/gedung-pn-natuna-2026-graded-480.webp 480w, /images/hero/gedung-pn-natuna-2026-graded-768.webp 768w, /images/hero/gedung-pn-natuna-2026-graded-1200.webp 1200w, /images/hero/gedung-pn-natuna-2026-graded.webp 1536w" sizes="100vw" alt="" width="1536" height="1024" fetchpriority="high" decoding="async"></span>
      </div>

      <div class="hero-stage">
        <span class="hero-photo-chip">Gedung Pengadilan Negeri Natuna &middot; Ranai, Kepulauan Riau</span>
        <div class="hero-copy hero-welcome-copy">
          <p class="hero-status" id="hero-service-status"></p>
          <h2>Selamat Datang di<br>Pengadilan Negeri<br>Natuna Kelas II</h2>
          <p class="hero-intro hero-intro-desktop">Melayani masyarakat pencari keadilan di Kabupaten Natuna dengan pelayanan cepat, transparan, dan mudah diakses.</p>
          <p class="hero-intro hero-intro-mobile">Urusan Anda bisa diselesaikan daring.<br>Temukan kebutuhan dalam dua langkah.</p>
          <div class="hero-facts">
            <div class="hero-service-ribbon" aria-label="Ringkasan agenda dan indeks layanan">
              <p><span>Agenda hari ini</span><strong><?php echo (int) $agendaToday; ?></strong></p>
              <?php foreach ($surveyScores as $score) : ?>
                <p>
                  <span><?php echo htmlspecialchars($score['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                  <strong><?php echo htmlspecialchars($score['value'], ENT_QUOTES, 'UTF-8'); ?></strong>
                  <?php if ($score['responden'] !== '') : ?>
                    <small><?php echo htmlspecialchars($score['responden'], ENT_QUOTES, 'UTF-8'); ?></small>
                  <?php endif; ?>
                </p>
              <?php endforeach; ?>
            </div>
            <div class="hero-actions hero-actions-primary">
              <a class="is-primary" href="/layanan-publik">Layanan Pengadilan</a>
              <a href="https://sipp.pn-natuna.go.id/" target="_blank" rel="noopener">Telusuri Perkara</a>
            </div>
          </div>
        </div>
      </div>

      <div class="hero-footbar">
      <?php if ($berita !== []) : ?>
        <section class="hero-newsbar" aria-labelledby="hero-newsbar-title">
          <div class="hero-newsbar__head">
            <h3 id="hero-newsbar-title">Berita Terkini</h3>
            <a href="/berita-dan-pengumuman">Semua berita <span aria-hidden="true">&rarr;</span></a>
          </div>
          <ul class="hero-newsbar__list">
            <?php foreach ($berita as $item) : ?>
              <li>
                <a href="<?php echo htmlspecialchars(pn_natuna_hero_article_url($item, 12), ENT_QUOTES, 'UTF-8'); ?>">
                  <span class="hero-newsbar__thumb">
                    <img src="<?php echo htmlspecialchars(pn_natuna_hero_article_image($item), ENT_QUOTES, 'UTF-8'); ?>" alt="" width="800" height="600" loading="lazy" decoding="async">
                  </span>
                  <span class="hero-newsbar__copy">
                    <span class="hero-newsbar__when">
                      <time><?php echo htmlspecialchars(pn_natuna_hero_date($item->created), ENT_QUOTES, 'UTF-8'); ?></time>
                      <?php if (pn_natuna_hero_is_new($item->created)) : ?><em>Baru</em><?php endif; ?>
                    </span>
                    <span class="hero-newsbar__title"><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></span>
                  </span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <div class="hero-pledge">
        <a class="hero-pledge__body" href="/zona-integritas">
          <span class="hero-pledge__mark" aria-hidden="true"></span>
          <span class="hero-pledge__text"><strong>Zona Integritas</strong> &middot; Tolak Gratifikasi &amp; Pungutan Liar</span>
        </a>
        <button type="button" class="hero-pledge__poster" data-maklumat-zoom="/images/hero/integritas-tolak-gratifikasi-pungli-2026.webp" data-maklumat-label="Pengadilan Negeri Natuna Kelas II secara tegas menolak segala bentuk gratifikasi dan pungutan liar">
          Lihat poster <span aria-hidden="true">&#8599;</span>
        </button>
      </div>
      </div>
    </div>
    <?php
}
