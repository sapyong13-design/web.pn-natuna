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
            ->select($db->quoteName(['id', 'title', 'alias', 'catid', 'created', 'publish_up', 'images', 'introtext']))
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
    $featureExcerpt = pn_natuna_hero_excerpt($feature->introtext ?? '', 140);
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

function pn_natuna_hero_render_tab_list(array $items, int $catId, string $panel, bool $active): void
{
    ?>
    <ul class="hero-tab-list<?php echo $active ? ' is-active' : ''; ?>" data-hero-panel="<?php echo $panel; ?>">
      <?php if ($items) : foreach ($items as $item) : ?>
        <li>
          <a href="<?php echo htmlspecialchars(pn_natuna_hero_article_url($item, $catId), ENT_QUOTES, 'UTF-8'); ?>"
             data-image="<?php echo htmlspecialchars(pn_natuna_hero_article_image($item), ENT_QUOTES, 'UTF-8'); ?>"
             data-caption="<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>">
            <time><?php echo pn_natuna_hero_date($item->created); ?></time>
            <span class="hero-item-body">
              <span class="hero-item-title">
                <?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
                <?php if (pn_natuna_hero_is_new($item->created)) : ?><span class="hero-badge-new">Baru</span><?php endif; ?>
              </span>
              <?php $excerpt = pn_natuna_hero_excerpt($item->introtext ?? ''); ?>
              <em class="hero-item-excerpt"><?php echo $excerpt; ?></em>
            </span>
          </a>
        </li>
      <?php endforeach; else : ?>
        <li class="hero-tab-empty"><a href="/berita-dan-pengumuman">Belum ada data terbaru &mdash; lihat arsip</a></li>
      <?php endif; ?>
    </ul>
    <?php
}

function pn_natuna_render_hero_slider(): void
{
    $berita = pn_natuna_hero_latest_articles(12, 4);
    $pengumuman = pn_natuna_hero_latest_articles(13, 4);

    $today = Factory::getDate('now', 'Asia/Jakarta');
    $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $monthNames = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $todayLabel = $dayNames[(int) $today->format('w')] . ', ' . $today->format('j') . ' ' . $monthNames[(int) $today->format('n')] . ' ' . $today->format('Y');
    $previewImg = $berita ? pn_natuna_hero_article_image($berita[0]) : '/images/sejarah/sejarah-pn-natuna.jpg';
    $previewCaption = $berita ? $berita[0]->title : 'Berita Pengadilan Negeri Natuna';
    ?>
    <div class="hero-slider hero-cinema" data-interval="7000">
      <div class="hero-backdrop" aria-hidden="true">
        <span class="hero-backdrop-image"><img src="/images/hero/gedung-pn-natuna-2026-graded.webp" alt="" width="1536" height="1024" fetchpriority="high" decoding="async"></span>
      </div>
      <span class="hero-photo-chip">Gedung Pengadilan Negeri Natuna &middot; Ranai, Kepulauan Riau</span>

      <div class="hero-slides">

        <div class="hero-slide is-active" role="group" aria-label="Selamat datang">
          <div class="hero-copy hero-welcome-copy">
            <p class="hero-institution">Pengadilan Negeri Natuna <span>Kelas II</span></p>
            <p class="hero-kicker"><span id="hero-greeting">Portal Resmi Pengadilan Negeri Natuna</span></p>
            <h2>Selamat Datang di<br>Pengadilan Negeri Natuna</h2>
            <p class="hero-intro hero-intro-desktop">Melayani masyarakat pencari keadilan di Kabupaten Natuna dengan pelayanan cepat, transparan, dan mudah diakses.</p>
            <p class="hero-intro hero-intro-mobile">Layanan pengadilan yang jelas dan mudah diakses.<br>Temukan kebutuhan Anda dalam dua langkah.</p>
            <div class="hero-service-ribbon" aria-label="Informasi layanan pengadilan">
              <p class="hero-status" id="hero-service-status" hidden></p>
              <p><span>Jam layanan</span><strong class="js-service-hours">08.00-16.30 WIB</strong></p>
              <p><span>Hari ini</span><strong><?php echo htmlspecialchars($todayLabel, ENT_QUOTES, 'UTF-8'); ?></strong></p>
              <p><span>Lokasi</span><strong>Ranai, Kepulauan Riau</strong></p>
            </div>
            <div class="hero-actions hero-actions-primary">
              <a class="is-primary" href="/layanan-publik">Layanan Pengadilan</a>
              <a href="https://sipp.pn-natuna.go.id/" target="_blank" rel="noopener">Telusuri Perkara</a>
            </div>
            <nav class="hero-actions-secondary" aria-label="Tautan layanan lainnya">
              <a href="/zona-integritas">Zona Integritas</a>
              <a href="https://sipp.pn-natuna.go.id/" target="_blank" rel="noopener">SIPP</a>
            </nav>
          </div>
        </div>

        <div class="hero-slide hero-slide-integrity" role="group" aria-label="Tolak Gratifikasi dan Pungutan Liar">
          <a class="hero-slide-integrity__link" href="/zona-integritas" aria-label="Buka informasi Zona Integritas: Tolak Gratifikasi dan Pungutan Liar">
            <img class="hero-slide-integrity__image" src="/images/hero/integritas-tolak-gratifikasi-pungli-2026.webp" alt="Pengadilan Negeri Natuna Kelas II secara tegas menolak segala bentuk gratifikasi dan pungutan liar" width="1672" height="941" loading="lazy" decoding="async" data-integrity-poster>
            <span class="hero-slide-integrity__cta">Lihat poster penuh <span aria-hidden="true">↗</span></span>
          </a>
        </div>

        <div class="hero-slide hero-slide-news" role="group" aria-label="Berita dan pengumuman terbaru">
          <div class="hero-copy hero-news-panel">
            <p class="hero-kicker">Informasi Terkini</p>
            <h2>Berita &amp; Pengumuman</h2>
            <div class="hero-tabs" role="tablist" aria-label="Pilih jenis informasi">
              <button type="button" class="is-active" data-hero-tab="berita" role="tab" aria-selected="true">Berita</button>
              <button type="button" data-hero-tab="pengumuman" role="tab" aria-selected="false">Pengumuman</button>
            </div>
            <div class="hero-tab-panels">
            <?php pn_natuna_hero_render_tab_list($berita, 12, 'berita', true); ?>
            <?php pn_natuna_hero_render_tab_list($pengumuman, 13, 'pengumuman', false); ?>
            </div>
            <div class="hero-actions">
              <a class="is-primary" href="/berita-dan-pengumuman">Lihat Semua Berita &amp; Pengumuman</a>
            </div>
          </div>
          <figure class="hero-media hero-news-media">
            <img id="hero-news-preview" src="<?php echo htmlspecialchars($previewImg, ENT_QUOTES, 'UTF-8'); ?>" alt="Pratinjau berita" width="800" height="600" loading="lazy" decoding="async">
            <figcaption id="hero-news-caption"><?php echo htmlspecialchars($previewCaption, ENT_QUOTES, 'UTF-8'); ?></figcaption>
          </figure>
        </div>

      </div>

      <button type="button" class="hero-nav hero-nav-prev" data-hero-nav="-1" aria-label="Slide sebelumnya">&#8249;</button>
      <button type="button" class="hero-nav hero-nav-next" data-hero-nav="1" aria-label="Slide berikutnya">&#8250;</button>

      <div class="hero-slider-dots">
        <button type="button" data-hero-slide="0" class="is-active" aria-label="Slide selamat datang" aria-pressed="true"></button>
        <button type="button" data-hero-slide="1" aria-label="Slide Tolak Gratifikasi dan Pungutan Liar" aria-pressed="false"></button>
        <button type="button" data-hero-slide="2" aria-label="Slide berita dan pengumuman" aria-pressed="false"></button>
      </div>
    </div>
    <?php
}
