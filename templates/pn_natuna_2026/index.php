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

$siteUrl = rtrim(Joomla\CMS\Uri\Uri::root(), '/');
if (trim((string) $this->getDescription()) === '') {
    $this->setDescription('Website resmi Pengadilan Negeri Natuna Kelas II — informasi layanan PTSP, jadwal sidang, perkara, berita, dan transparansi peradilan di Kabupaten Natuna, Kepulauan Riau.');
}
?>
<!doctype html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <jdoc:include type="metas" />
  <jdoc:include type="styles" />
  <meta name="theme-color" content="#8f1f0b">
  <meta property="og:site_name" content="Pengadilan Negeri Natuna Kelas II">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?php echo htmlspecialchars($this->getTitle() ?: 'Pengadilan Negeri Natuna Kelas II', ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($this->getDescription(), ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:url" content="<?php echo htmlspecialchars(Joomla\CMS\Uri\Uri::current(), ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:image" content="<?php echo $siteUrl; ?>/images/brand/og-image.jpg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="icon" type="image/png" sizes="32x32" href="/images/brand/favicon-32.png" />
  <link rel="icon" type="image/png" sizes="512x512" href="/images/brand/favicon-512.png" />
  <link rel="apple-touch-icon" sizes="180x180" href="/images/brand/apple-touch-icon.png" />
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "GovernmentOrganization",
    "name": "Pengadilan Negeri Natuna Kelas II",
    "url": "<?php echo $siteUrl; ?>/",
    "logo": "<?php echo $siteUrl; ?>/images/brand/logo-pn-natuna.png",
    "image": "<?php echo $siteUrl; ?>/images/brand/og-image.jpg",
    "telephone": "+62-773-3211203",
    "email": "pn.natuna@gmail.com",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Jl. Batu Sisir, Sungai Ulu, Kecamatan Bunguran Timur",
      "addressLocality": "Kabupaten Natuna",
      "addressRegion": "Kepulauan Riau",
      "addressCountry": "ID"
    },
    "openingHoursSpecification": [
      { "@type": "OpeningHoursSpecification", "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday"], "opens": "08:00", "closes": "16:30" },
      { "@type": "OpeningHoursSpecification", "dayOfWeek": "Friday", "opens": "08:00", "closes": "17:00" }
    ],
    "sameAs": ["https://www.instagram.com/pn.natuna/", "https://sipp.pn-natuna.go.id/"]
  }
  </script>
  <link rel="preload" href="/templates/<?php echo $this->template; ?>/fonts/plus-jakarta-sans-var.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="/templates/<?php echo $this->template; ?>/fonts/fraunces-var.woff2" as="font" type="font/woff2" crossorigin>
  <?php $tplPath = JPATH_THEMES . '/' . $this->template; ?>
  <link rel="stylesheet" href="/templates/<?php echo $this->template; ?>/css/fonts.css?v=<?php echo @filemtime($tplPath . '/css/fonts.css') ?: '1'; ?>" />
  <link rel="stylesheet" href="/templates/<?php echo $this->template; ?>/css/template.css?v=<?php echo @filemtime($tplPath . '/css/template.css') ?: '1'; ?>" />
  <jdoc:include type="scripts" />
  <script src="/templates/<?php echo $this->template; ?>/js/template.js?v=<?php echo @filemtime($tplPath . '/js/template.js') ?: '1'; ?>" defer></script>
</head>
<body class="site <?php echo $isHome ? 'is-home' : 'is-inner'; ?>">
  <script>
    (function () {
      try {
        if (localStorage.getItem('pnNatunaDark') === '1') {
          document.body.classList.add('is-dark');
        }
      } catch (e) { /* private mode */ }
    })();
  </script>
  <a class="skip-link" href="#content">Lewati ke konten utama</a>

  <header class="site-header">
    <div class="topbar">
      <jdoc:include type="modules" name="topbar" style="none" />
      <div class="topbar-clock" aria-label="Tanggal dan waktu saat ini">
        <span id="live-clock-date"></span>
        <span id="live-clock-time"></span>
      </div>
      <button class="dark-toggle" type="button" aria-pressed="false" aria-label="Mode gelap" title="Aktifkan/matikan mode gelap">
        <svg class="dark-toggle-moon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg class="dark-toggle-sun" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      </button>
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
        <?php if (function_exists('pn_natuna_render_latest_news')) : ?>
          <?php pn_natuna_render_latest_news(); ?>
        <?php endif; ?>
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
    <a href="https://sipp.pn-natuna.go.id/" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2 8l10 5 8.36-4.18A12 12 0 0 1 21 12h2a14 14 0 0 0-1.06-5.36zM4 11.9V16c0 1.66 3.58 3 8 3s8-1.34 8-3v-4.1l-8 4z" fill="currentColor"/></svg>
      <span>SIPP</span>
    </a>
    <a href="/informasi-perkara">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 16H5V10h14zM5 8V6h14v2z" fill="currentColor"/></svg>
      <span>Jadwal</span>
    </a>
    <a href="/layanan-publik">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 4 6v6c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V6zm0 10h6c-.5 3.9-3 7.2-6 7.9V12H6V7.3l6-3z" fill="currentColor"/></svg>
      <span>PTSP</span>
    </a>
    <button class="search-overlay-toggle" type="button" aria-controls="site-search-overlay" aria-expanded="false">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27a6.5 6.5 0 1 0-.7.7l.27.28v.79l5 4.99L20.49 19zm-6 0A4.5 4.5 0 1 1 14 9.5 4.5 4.5 0 0 1 9.5 14z" fill="currentColor"/></svg>
      <span>Cari</span>
    </button>
    <a href="/kontak">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24 11.36 11.36 0 0 0 3.57.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.36 11.36 0 0 0 .57 3.57 1 1 0 0 1-.25 1.02z" fill="currentColor"/></svg>
      <span>Kontak</span>
    </a>
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
