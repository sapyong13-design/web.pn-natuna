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
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'title', 'alias', 'created', 'images', 'introtext']))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('state') . ' = 1')
            ->where($db->quoteName('catid') . ' = ' . (int) $catId)
            ->where($db->quoteName('alias') . ' NOT LIKE ' . $db->quote('%berita-dan-pengumuman%'))
            ->order($db->quoteName('created') . ' DESC')
            ->setLimit($limit);
        $db->setQuery($query);
        return $db->loadObjectList() ?: [];
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
    $fallback = '/images/sejarah/sejarah-pn-natuna.jpg';
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
              <?php if ($excerpt !== '') : ?>
                <em class="hero-item-excerpt"><?php echo $excerpt; ?></em>
              <?php endif; ?>
            </span>
          </a>
        </li>
      <?php endforeach; else : ?>
        <li class="hero-tab-empty"><a href="/berita-dan-pengumuman">Belum ada data terbaru &mdash; lihat arsip</a></li>
      <?php endif; ?>
    </ul>
    <?php
}

function pn_natuna_render_latest_news(): void
{
    $articles = pn_natuna_hero_latest_articles(12, 3);
    if (!$articles) {
        return;
    }
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    ?>
    <div class="module-card news-cards-board">
      <div class="news-cards-head">
        <h2>Berita Terbaru</h2>
        <a class="section-action" href="/berita-dan-pengumuman">Semua Berita &rarr;</a>
      </div>
      <div class="news-cards-grid">
        <?php foreach ($articles as $article) :
            $ts = strtotime($article->created);
            $dateLabel = $ts ? date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts) : '';
        ?>
          <a class="news-card" href="<?php echo htmlspecialchars(pn_natuna_hero_article_url($article, 12), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="news-card-media">
              <img src="<?php echo htmlspecialchars(pn_natuna_hero_article_image($article), ENT_QUOTES, 'UTF-8'); ?>" alt="" loading="lazy">
              <?php if (pn_natuna_hero_is_new($article->created)) : ?><span class="news-card-new">Baru</span><?php endif; ?>
            </span>
            <time><?php echo htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8'); ?></time>
            <strong><?php echo htmlspecialchars($article->title, ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php $excerpt = pn_natuna_hero_excerpt($article->introtext ?? '', 110); ?>
            <?php if ($excerpt !== '') : ?><span class="news-card-excerpt"><?php echo $excerpt; ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}

function pn_natuna_render_hero_slider(): void
{
    $berita = pn_natuna_hero_latest_articles(12, 4);
    $pengumuman = pn_natuna_hero_latest_articles(13, 4);

    $previewImg = $berita ? pn_natuna_hero_article_image($berita[0]) : '/images/sejarah/sejarah-pn-natuna.jpg';
    $previewCaption = $berita ? $berita[0]->title : 'Berita Pengadilan Negeri Natuna';
    ?>
    <div class="hero-slider hero-cinema" data-interval="7000">
      <div class="hero-backdrop" aria-hidden="true">
        <img src="/images/sejarah/sejarah-pn-natuna.jpg" alt="" fetchpriority="high" decoding="async">
      </div>
      <span class="hero-photo-chip">Gedung Pengadilan Negeri Natuna</span>

      <div class="hero-slides">

        <div class="hero-slide is-active" role="group" aria-label="Selamat datang">
          <div class="hero-copy">
            <p class="hero-kicker"><span id="hero-greeting">Portal Resmi Pengadilan Negeri Natuna</span></p>
            <h2>Selamat Datang di<br>Pengadilan Negeri Natuna</h2>
            <p>Melayani masyarakat pencari keadilan di Kabupaten Natuna dengan pelayanan yang cepat, transparan, dan mudah diakses. Informasi layanan PTSP, perkara, dan kanal resmi pengadilan tersedia di beranda ini.</p>
            <div class="hero-actions">
              <a class="is-primary" href="/layanan-publik">Layanan PTSP</a>
              <a href="/informasi-perkara">Informasi Perkara</a>
              <a href="/zona-integritas">Zona Integritas</a>
              <a href="https://sipp.pn-natuna.go.id/" target="_blank" rel="noopener">SIPP</a>
            </div>
            <p class="hero-status" id="hero-service-status" hidden></p>
          </div>
        </div>

        <div class="hero-slide hero-slide-news" role="group" aria-label="Berita dan pengumuman terbaru">
          <div class="hero-copy hero-news-panel">
            <p class="hero-kicker">Informasi Terkini</p>
            <h2>Berita &amp; Pengumuman</h2>
            <div class="hero-tabs" role="tablist" aria-label="Pilih jenis informasi">
              <button type="button" class="is-active" data-hero-tab="berita" role="tab" aria-selected="true">Berita</button>
              <button type="button" data-hero-tab="pengumuman" role="tab" aria-selected="false">Pengumuman</button>
            </div>
            <?php pn_natuna_hero_render_tab_list($berita, 12, 'berita', true); ?>
            <?php pn_natuna_hero_render_tab_list($pengumuman, 13, 'pengumuman', false); ?>
            <div class="hero-actions">
              <a class="is-primary" href="/berita-dan-pengumuman">Lihat Semua Berita &amp; Pengumuman</a>
            </div>
          </div>
          <figure class="hero-media hero-news-media">
            <img id="hero-news-preview" src="<?php echo htmlspecialchars($previewImg, ENT_QUOTES, 'UTF-8'); ?>" alt="Pratinjau berita" loading="lazy">
            <figcaption id="hero-news-caption"><?php echo htmlspecialchars($previewCaption, ENT_QUOTES, 'UTF-8'); ?></figcaption>
          </figure>
        </div>

      </div>

      <button type="button" class="hero-nav hero-nav-prev" data-hero-nav="-1" aria-label="Slide sebelumnya">&#8249;</button>
      <button type="button" class="hero-nav hero-nav-next" data-hero-nav="1" aria-label="Slide berikutnya">&#8250;</button>

      <div class="hero-slider-dots">
        <button type="button" data-hero-slide="0" class="is-active" aria-label="Slide selamat datang" aria-pressed="true"></button>
        <button type="button" data-hero-slide="1" aria-label="Slide berita dan pengumuman" aria-pressed="false"></button>
      </div>
    </div>
    <?php
}
