<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

$app = Factory::getApplication();
$menu = $app->getMenu()->getActive();
$isHome = $menu && $menu->home;
$hasSidebar = (bool) ($this->countModules('sidebar') || $this->countModules('sidebar-right'));
$sippScheduleHelper = __DIR__ . '/sipp-schedule.php';
$statsCounterHelper = __DIR__ . '/stats-counter.php';
$instansiFeedHelper = __DIR__ . '/instansi-feed.php';
$heroSliderHelper = __DIR__ . '/hero-slider.php';

if (is_file($sippScheduleHelper)) {
    require_once $sippScheduleHelper;
}
if (is_file($statsCounterHelper)) {
    require_once $statsCounterHelper;
    pn_natuna_track_visitor();
}
if (is_file($instansiFeedHelper)) {
    require_once $instansiFeedHelper;
}
if (is_file($heroSliderHelper)) {
    require_once $heroSliderHelper;
}
?>
<!doctype html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
  <jdoc:include type="metas" />
  <jdoc:include type="styles" />
  <link rel="icon" href="/images/brand/logo-pn-natuna.png" type="image/png" />
  <link rel="apple-touch-icon" href="/images/brand/logo-pn-natuna.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700;9..144,900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="/templates/<?php echo $this->template; ?>/css/template.css" />
  <jdoc:include type="scripts" />
  <script src="/templates/<?php echo $this->template; ?>/js/template.js" defer></script>
</head>
<body class="site <?php echo $isHome ? 'is-home' : 'is-inner'; ?>">
  <a class="skip-link" href="#content">Lewati ke konten utama</a>

  <header class="site-header">
    <div class="topbar">
      <jdoc:include type="modules" name="topbar" style="none" />
    </div>
    <div class="header-brand">
      <jdoc:include type="modules" name="header-brand" style="none" />
      <jdoc:include type="modules" name="header-badges" style="none" />
    </div>
    <nav class="main-menu" aria-label="Navigasi utama">
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-menu-list">Menu</button>
      <div class="menu-backdrop" hidden></div>
      <div id="main-menu-list" class="main-menu-list mobile-menu-panel" aria-label="Menu Navigasi">
        <div class="mobile-menu-heading">
          <strong>Menu Navigasi</strong>
          <button class="menu-close" type="button" aria-label="Tutup menu">Tutup</button>
        </div>
        <jdoc:include type="modules" name="main-menu" style="none" />
      </div>
      <div class="menu-live-clock">
        <span id="live-clock-date"></span>
        <span id="live-clock-time"></span>
      </div>
    </nav>
  </header>

  <?php if ($isHome) : ?>
    <section class="hero home-slider">
      <?php if (function_exists('pn_natuna_render_hero_slider')) : ?>
        <?php pn_natuna_render_hero_slider(); ?>
      <?php else : ?>
        <jdoc:include type="modules" name="hero" style="none" />
      <?php endif; ?>
    </section>
    <section class="quick-links app-strip">
      <jdoc:include type="modules" name="quick-links" style="card" />
    </section>
  <?php endif; ?>

  <?php if ($isHome) : ?>
    <main id="content" class="home-juknis-layout">
      <div class="system-message-slot">
        <jdoc:include type="message" />
      </div>
      <div class="home-juknis-main">
        <jdoc:include type="modules" name="home-alerts" style="card" />
        <jdoc:include type="modules" name="home-today" style="card" />
        <jdoc:include type="modules" name="home-service-spotlight" style="card" />
        <jdoc:include type="modules" name="home-public-access" style="card" />
        <div class="home-content-pair">
          <jdoc:include type="modules" name="home-news" style="card" />
          <jdoc:include type="modules" name="home-announcements" style="card" />
        </div>
        <div class="home-briefing-pair">
          <jdoc:include type="modules" name="home-transparency" style="card" />
          <jdoc:include type="modules" name="home-reform" style="card" />
        </div>
        <jdoc:include type="modules" name="home-integrity" style="card" />
        <jdoc:include type="modules" name="home-rss" style="card" />
        <?php if (function_exists('pn_natuna_sipp_render_schedule')) : ?>
          <?php pn_natuna_sipp_render_schedule(); ?>
        <?php else : ?>
          <jdoc:include type="modules" name="home-schedule" style="card" />
        <?php endif; ?>
        <jdoc:include type="modules" name="home-facilities" style="card" />
        <?php if (function_exists('pn_natuna_render_instansi_feed')) : ?>
          <?php pn_natuna_render_instansi_feed(); ?>
        <?php else : ?>
          <jdoc:include type="modules" name="home-public-board" style="card" />
        <?php endif; ?>
        <jdoc:include type="modules" name="home-video" style="card" />
        <jdoc:include type="modules" name="home-contact" style="card" />
      </div>
      <aside class="home-juknis-sidebar" aria-label="Informasi pendukung">
        <jdoc:include type="modules" name="home-search" style="card" />
        <jdoc:include type="modules" name="home-service-info" style="card" />
        <jdoc:include type="modules" name="home-role-model" style="card" />
        <jdoc:include type="modules" name="home-survey" style="card" />
        <jdoc:include type="modules" name="home-dipa" style="card" />
        <jdoc:include type="modules" name="home-instagram" style="card" />
        <jdoc:include type="modules" name="home-index" style="card" />
        <jdoc:include type="modules" name="home-web-links" style="card" />
        <jdoc:include type="modules" name="home-social" style="card" />
        <?php if (function_exists('pn_natuna_get_visitor_stats')) : 
            $stats = pn_natuna_get_visitor_stats();
        ?>
          <div class="module-card stats-card">
            <h2 class="module-title">Statistik Pengunjung</h2>
            <div class="stats-board-grid">
              <div class="stats-board-item">
                <span class="stats-num"><?php echo number_format($stats['online']); ?></span>
                <span class="stats-label">Pengunjung Aktif</span>
              </div>
              <div class="stats-board-item">
                <span class="stats-num"><?php echo number_format($stats['today']); ?></span>
                <span class="stats-label">Hari Ini</span>
              </div>
              <div class="stats-board-item">
                <span class="stats-num"><?php echo number_format($stats['month']); ?></span>
                <span class="stats-label">Bulan Ini</span>
              </div>
              <div class="stats-board-item">
                <span class="stats-num"><?php echo number_format($stats['total']); ?></span>
                <span class="stats-label">Total Kunjungan</span>
              </div>
            </div>
          </div>
        <?php else : ?>
          <jdoc:include type="modules" name="home-stats" style="card" />
        <?php endif; ?>
      </aside>
    </main>
  <?php else : ?>
    <main id="content" class="site-main">
      <jdoc:include type="modules" name="breadcrumb" style="none" />
      <div class="system-message-slot">
        <jdoc:include type="message" />
      </div>
      <jdoc:include type="modules" name="content-top" style="card" />
      <div class="content-layout <?php echo $hasSidebar ? 'has-sidebar' : 'no-sidebar'; ?>">
        <div class="content-primary">
          <jdoc:include type="component" />
        </div>
        <?php if ($hasSidebar) : ?>
          <aside class="content-sidebar">
            <jdoc:include type="modules" name="sidebar" style="card" />
            <jdoc:include type="modules" name="sidebar-right" style="card" />
          </aside>
        <?php endif; ?>
      </div>
      <jdoc:include type="modules" name="content-bottom" style="card" />
    </main>
  <?php endif; ?>

  <a href="https://wa.me/6281261256661" class="floating-whatsapp" target="_blank" rel="noopener" aria-label="Hubungi kami via WhatsApp">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.98 1.005-3.645-.235-.373a9.86 9.86 0 01-1.51-5.26c0-5.445 4.43-9.875 9.882-9.875 2.64 0 5.122 1.028 6.988 2.895a9.82 9.82 0 012.888 6.988c-.004 5.445-4.434 9.882-9.88 9.882m8.536-18.411A11.96 11.96 0 0012.052 0C5.394 0 .003 5.433.003 12.06c0 2.128.555 4.205 1.611 6.035L0 24l6.065-1.587a11.96 11.96 0 005.98 1.6h.005c6.657 0 12.052-5.434 12.052-12.065a11.96 11.96 0 00-3.516-8.52z"/>
    </svg>
  </a>

  <footer class="site-footer">
    <div class="footer-links">
      <jdoc:include type="modules" name="footer-links" style="none" />
    </div>
    <div class="footer-contact">
      <jdoc:include type="modules" name="footer-contact" style="none" />
    </div>
    <div class="footer-social">
      <jdoc:include type="modules" name="footer-social" style="none" />
    </div>
    <div class="footer-bottom">
      <jdoc:include type="modules" name="footer-bottom" style="none" />
    </div>
  </footer>


  <nav class="mobile-quick-actions" aria-label="Aksi cepat mobile">
    <a href="https://sipp.pn-natuna.go.id/" target="_blank" rel="noopener">SIPP</a>
    <a href="/informasi-perkara">Jadwal</a>
    <a href="/layanan-publik">PTSP</a>
    <button class="search-overlay-toggle" type="button" aria-controls="site-search-overlay" aria-expanded="false">Cari</button>
    <a href="/kontak">Kontak</a>
  </nav>
  <div id="site-search-overlay" class="search-overlay" hidden>
    <div class="search-overlay-panel" role="dialog" aria-modal="true" aria-labelledby="search-overlay-title">
      <div class="search-overlay-heading">
        <strong id="search-overlay-title">Cari Informasi PN Natuna</strong>
        <button class="search-overlay-close" type="button">Tutup</button>
      </div>
      <form action="/component/search/" method="get">
        <label for="site-search-query">Kata kunci pencarian</label>
        <input id="site-search-query" name="searchword" type="search" placeholder="Contoh: biaya perkara, jadwal sidang, posbakum">
        <button type="submit">Cari</button>
      </form>
    </div>
  </div>
  <button id="back-to-top" class="back-to-top" type="button" aria-label="Kembali ke atas" hidden>
    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 4l-8 8h5v8h6v-8h5z" fill="currentColor"/></svg>
  </button>
</body>
</html>
