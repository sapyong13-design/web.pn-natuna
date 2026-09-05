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
$youtubeFeedHelper = __DIR__ . '/youtube-feed.php';
$instagramFeedHelper = __DIR__ . '/instagram-feed.php';

if (is_file($sippScheduleHelper)) {
    require_once $sippScheduleHelper;
}
if (is_file($statsCounterHelper)) {
    require_once $statsCounterHelper;
}
if (is_file($instansiFeedHelper)) {
    require_once $instansiFeedHelper;
}
if (is_file($youtubeFeedHelper)) {
    require_once $youtubeFeedHelper;
}
if (is_file($heroSliderHelper)) {
    require_once $heroSliderHelper;
}
if (is_file($instagramFeedHelper)) {
    require_once $instagramFeedHelper;
}

$siteUrl = rtrim(Joomla\CMS\Uri\Uri::root(), '/');
if (trim((string) $this->getDescription()) === '') {
    $this->setDescription('Website resmi Pengadilan Negeri Natuna Kelas II — informasi layanan PTSP, jadwal sidang, perkara, berita, dan transparansi peradilan di Kabupaten Natuna, Kepulauan Riau.');
}

$canonicalPath = Joomla\CMS\Uri\Uri::getInstance()->getPath();
$canonicalPath = preg_replace('#^/index\.php(?:/|$)#', '/', $canonicalPath) ?: '/';
$canonicalPath = '/' . ltrim($canonicalPath, '/');
$canonicalUrl = $siteUrl . ($canonicalPath === '/' ? '/' : rtrim($canonicalPath, '/'));
$this->addHeadLink(htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'), 'canonical');
?>
<!doctype html>
<html lang="id-ID" dir="<?php echo $this->direction; ?>">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <jdoc:include type="metas" />
  <jdoc:include type="styles" />
  <meta id="theme-color-meta" name="theme-color" content="#8f1f0b">
  <meta property="og:site_name" content="Pengadilan Negeri Natuna Kelas II">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?php echo htmlspecialchars($this->getTitle() ?: 'Pengadilan Negeri Natuna Kelas II', ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($this->getDescription(), ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:image" content="<?php echo $siteUrl; ?>/images/brand/og-image.jpg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="icon" type="image/png" sizes="32x32" href="/images/brand/favicon-32.png?v=20260804" />
  <link rel="icon" type="image/png" sizes="512x512" href="/images/brand/favicon-512.png?v=20260804" />
  <link rel="apple-touch-icon" sizes="180x180" href="/images/brand/apple-touch-icon.png?v=20260804" />
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "GovernmentOrganization",
    "inLanguage": "id-ID",
    "name": "Pengadilan Negeri Natuna Kelas II",
    "url": "<?php echo $siteUrl; ?>/",
    "logo": "<?php echo $siteUrl; ?>/images/brand/logo-pn-natuna.webp",
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
  <link rel="stylesheet" href="/templates/<?php echo $this->template; ?>/css/template-4b123344.css" />
  <jdoc:include type="scripts" />
  <script src="/templates/<?php echo $this->template; ?>/js/template-4b123344.js" defer></script>
</head>
<body class="site <?php echo $isHome ? 'is-home' : 'is-inner'; ?>">
  <script>
    (function () {
      try {
        var dark = localStorage.getItem('pnNatunaDark') === '1';
        document.body.classList.toggle('is-dark', dark);
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        var theme = document.getElementById('theme-color-meta');
        if (theme) theme.content = dark ? '#151015' : '#8f1f0b';
      } catch (e) { /* private mode */ }
    })();
  </script>
  <a class="skip-link" href="#content">Lewati ke konten utama</a>

  <header class="site-header">
    <div class="topbar">
      <jdoc:include type="modules" name="topbar" style="none" />
      <?php
      // Tanggal dan jam dirender server-side supaya kotaknya sudah berukuran benar
      // sejak paint pertama. Sebelumnya kedua span kosong lalu diisi JS, membuat
      // topbar melompat 40px -> 69px dan menggeser seluruh halaman (CLS 1,109).
      $clockNow = Factory::getDate('now', 'Asia/Jakarta');
      $clockDays = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
      $clockMonths = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
          'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
      $clockDate = $clockDays[(int) $clockNow->format('w')] . ', ' . $clockNow->format('j')
          . ' ' . $clockMonths[(int) $clockNow->format('n')] . ' ' . $clockNow->format('Y');
      $isAugustCommemoration = (int) $clockNow->format('n') === 8;
      $riCompactVersion = @filemtime(JPATH_ROOT . '/images/brand/commemorative/hut-ri-2026-header-128.webp') ?: '1';
      $maCompactVersion = @filemtime(JPATH_ROOT . '/images/brand/commemorative/hut-ma-ri-2026-header-128.webp') ?: '1';
      $logoCompactVersion = @filemtime(JPATH_ROOT . '/images/brand/logo-pn-natuna-96.webp') ?: '1';
      ?>
      <div class="topbar-clock" aria-label="Tanggal dan waktu saat ini">
        <span id="live-clock-date"><?php echo htmlspecialchars($clockDate, ENT_QUOTES, 'UTF-8'); ?></span>
        <span id="live-clock-time"><?php echo $clockNow->format('H:i:s'); ?> WIB</span>
      </div>
    </div>
    <div class="header-brand">
      <jdoc:include type="modules" name="header-brand" style="none" />
      <?php if ($isAugustCommemoration) : ?>
        <aside class="august-lockup" aria-label="Peringatan Hari Ulang Tahun Republik Indonesia dan Mahkamah Agung Republik Indonesia">
          <svg class="august-lockup__flag" viewBox="0 0 320 96" preserveAspectRatio="none" aria-hidden="true">
            <path class="august-lockup__flag-red" d="M0,22 C42,2 76,38 120,18 C164,-2 202,36 246,16 C278,2 300,8 320,18 L320,47 C286,31 266,58 224,43 C182,28 148,57 105,42 C63,27 30,51 0,40 Z">
              <animate attributeName="d" dur="6s" repeatCount="indefinite" values="M0,22 C42,2 76,38 120,18 C164,-2 202,36 246,16 C278,2 300,8 320,18 L320,47 C286,31 266,58 224,43 C182,28 148,57 105,42 C63,27 30,51 0,40 Z;M0,12 C38,32 77,-2 119,20 C161,42 202,5 244,25 C276,40 300,32 320,18 L320,48 C286,64 264,34 224,52 C183,70 147,34 105,54 C63,74 29,38 0,50 Z;M0,22 C42,2 76,38 120,18 C164,-2 202,36 246,16 C278,2 300,8 320,18 L320,47 C286,31 266,58 224,43 C182,28 148,57 105,42 C63,27 30,51 0,40 Z" />
            </path>
            <path class="august-lockup__flag-white" d="M0,40 C30,51 63,27 105,42 C148,57 182,28 224,43 C266,58 286,31 320,47 L320,76 C280,60 257,89 215,72 C173,55 138,86 96,69 C55,52 25,78 0,66 Z">
              <animate attributeName="d" dur="6s" repeatCount="indefinite" values="M0,40 C30,51 63,27 105,42 C148,57 182,28 224,43 C266,58 286,31 320,47 L320,76 C280,60 257,89 215,72 C173,55 138,86 96,69 C55,52 25,78 0,66 Z;M0,50 C29,38 63,74 105,54 C147,34 183,70 224,52 C264,34 286,64 320,48 L320,77 C282,93 255,62 214,80 C172,98 137,62 95,82 C53,102 24,67 0,78 Z;M0,40 C30,51 63,27 105,42 C148,57 182,28 224,43 C266,58 286,31 320,47 L320,76 C280,60 257,89 215,72 C173,55 138,86 96,69 C55,52 25,78 0,66 Z" />
            </path>
          </svg>
          <span class="august-lockup__marks">
            <span class="august-lockup__mark august-lockup__mark--ri"><img src="<?php echo $this->baseurl; ?>/images/brand/commemorative/hut-ri-2026-header.webp" srcset="<?php echo $this->baseurl; ?>/images/brand/commemorative/hut-ri-2026-header-128.webp?v=<?php echo $riCompactVersion; ?> 128w, <?php echo $this->baseurl; ?>/images/brand/commemorative/hut-ri-2026-header.webp 640w" sizes="64px" alt="Hari Ulang Tahun ke-81 Republik Indonesia" width="640" height="389" decoding="async"></span>
            <span class="august-lockup__divider" aria-hidden="true"></span>
            <span class="august-lockup__mark august-lockup__mark--ma"><img src="<?php echo $this->baseurl; ?>/images/brand/commemorative/hut-ma-ri-2026-header.webp" srcset="<?php echo $this->baseurl; ?>/images/brand/commemorative/hut-ma-ri-2026-header-128.webp?v=<?php echo $maCompactVersion; ?> 128w, <?php echo $this->baseurl; ?>/images/brand/commemorative/hut-ma-ri-2026-header.webp 760w" sizes="64px" alt="Hari Ulang Tahun ke-81 Mahkamah Agung Republik Indonesia" width="760" height="330" decoding="async"></span>
          </span>
        </aside>
      <?php endif; ?>
      <jdoc:include type="modules" name="header-badges" style="none" />
    </div>
    <nav class="main-menu" aria-label="Navigasi utama">
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-menu-list"><svg class="menu-toggle__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg><span>Menu</span></button>
      <div class="menu-backdrop" hidden></div>
      <div id="main-menu-list" class="main-menu-list mobile-menu-panel" aria-label="Menu Navigasi">
        <div class="mobile-menu-heading">
          <div class="mobile-menu-brand" id="mobile-menu-title">
            <img src="<?php echo $this->baseurl; ?>/images/brand/logo-pn-natuna.webp" srcset="<?php echo $this->baseurl; ?>/images/brand/logo-pn-natuna-96.webp?v=<?php echo $logoCompactVersion; ?> 96w, <?php echo $this->baseurl; ?>/images/brand/logo-pn-natuna.webp 179w" sizes="40px" alt="" width="40" height="40" loading="lazy" decoding="async">
            <span><strong>PN Natuna</strong><small>Pengadilan Negeri Kelas II</small></span>
          </div>
          <button class="menu-close" type="button" aria-label="Tutup menu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
        </div>
        <div class="mobile-menu-scroll">
          <div class="mobile-menu-actions" aria-label="Layanan utama">
            <a href="/informasi-perkara"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v2H5a2 2 0 0 0-2 2v14h18V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2zm12 8v8H5v-8z" fill="currentColor"/></svg><small>Jadwal</small></a>
            <a href="https://sipp.pn-natuna.go.id/" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h14v18H5zm3 4v2h8V7zm0 4v2h8v-2zm0 4v2h5v-2z" fill="currentColor"/></svg><small>SIPP</small></a>
            <a href="/layanan-publik/regulasi-pengaduan"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1 5h2v7h-2zm0 9h2v2h-2z" fill="currentColor"/></svg><small>Pengaduan</small></a>
          </div>
          <button class="mobile-menu-search search-overlay-toggle" type="button" aria-expanded="false" aria-controls="site-search-overlay"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 19.6-4.5-4.5a7.5 7.5 0 1 0-1.4 1.4l4.5 4.5zM5 10.5a5.5 5.5 0 1 1 11 0 5.5 5.5 0 0 1-11 0" fill="currentColor"/></svg><span>Cari informasi</span></button>
          <div class="mobile-menu-filter-wrap">
            <label for="mobile-menu-filter">Filter menu</label>
            <div class="mobile-menu-filter-control"><input id="mobile-menu-filter" type="search" placeholder="Cari menu…" autocomplete="off" data-mobile-menu-filter><button type="button" aria-label="Hapus filter menu" data-mobile-menu-clear hidden>&times;</button></div>
            <p class="mobile-menu-filter-status visually-hidden" aria-live="polite" data-mobile-menu-filter-status></p>
            <p class="mobile-menu-empty" data-mobile-menu-empty hidden>Tidak ada menu yang cocok.</p>
          </div>
          <jdoc:include type="modules" name="main-menu" style="none" />
        </div>
        <div class="mobile-menu-footer">
          <button class="dark-toggle" type="button" aria-pressed="false" aria-label="Mode gelap" title="Aktifkan/matikan mode gelap">
            <svg class="dark-toggle-moon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            <svg class="dark-toggle-sun" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            <span class="dark-toggle-copy"><strong>Mode gelap</strong><small><span class="dark-status-off">Mati</span><span class="dark-status-on">Aktif</span></small></span>
          </button>
          <span class="mobile-menu-contact"><a href="tel:+627733211203" aria-label="Telepon PN Natuna"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15 15 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.2c1.2.4 2.4.6 3.6.6a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.2.2 2.4.6 3.6a1 1 0 0 1-.3 1z" fill="currentColor"/></svg></a><a href="https://wa.me/6281261256661" target="_blank" rel="noopener" aria-label="WhatsApp PN Natuna"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a9.5 9.5 0 0 0-8.2 14.3L2.5 21.5l5.3-1.3A9.5 9.5 0 1 0 12 2Zm0 17a7.4 7.4 0 0 1-3.8-1.1l-.4-.2-3.1.8.8-3-.2-.5A7.5 7.5 0 1 1 12 19Zm4.1-5.6c-.2-.1-1.3-.7-1.5-.7-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6 6 0 0 1-3-2.6c-.2-.3 0-.4.1-.5l.5-.6c.1-.2.2-.4.1-.6l-.7-1.7c-.2-.4-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.8.8-1.1 1.8-.7 2.9.7 2.1 2.3 3.8 4.3 4.8 1.6.8 3.4 1.1 4.4.3.5-.4.8-1 .9-1.6 0-.2-.1-.3-.3-.4z" fill="currentColor"/></svg></a></span>
        </div>
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
    <section class="mobile-start-here" aria-labelledby="mobile-start-title">
      <div class="mobile-section-heading">
        <p>Jalan pintas layanan</p>
        <h2 id="mobile-start-title">Mulai dari sini</h2>
      </div>
      <div class="mobile-start-grid">
        <a href="/informasi-perkara"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v2H5a2 2 0 0 0-2 2v14h18V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2zm12 8v8H5v-8z" fill="currentColor"/></svg><span><strong>Jadwal Sidang</strong><small>Lihat agenda hari ini</small></span></a>
        <a href="https://sipp.pn-natuna.go.id/" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 4.5-9 4.5-9-4.5zm-7 8.2 7 3.5 7-3.5V17l-7 4-7-4z" fill="currentColor"/></svg><span><strong>Telusuri Perkara</strong><small>Buka layanan SIPP</small></span></a>
        <a href="/layanan-publik/permohonan-informasi"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 3h16v18H4zm3 4v2h10V7zm0 4v2h10v-2zm0 4v2h7v-2z" fill="currentColor"/></svg><span><strong>Ajukan Informasi</strong><small>Layanan informasi publik</small></span></a>
        <a href="/layanan-publik/regulasi-pengaduan"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1 5h2v7h-2zm0 9h2v2h-2z" fill="currentColor"/></svg><span><strong>Buat Pengaduan</strong><small>Sampaikan secara aman</small></span></a>
      </div>
    </section>
  <?php endif; ?>

  <?php if ($isHome) : ?>
    <main id="content" class="home-juknis-layout">
      <div class="system-message-slot">
        <jdoc:include type="message" />
      </div>
      <?php // Kotak pencarian tidak lagi berada di dalam `<aside>`. Di ponsel rel
            // samping menumpuk sesudah seluruh isi utama dan berubah menjadi
            // korsel gulir mendatar, sehingga kartu pencarian mendarat di 91%
            // tinggi halaman - praktis tidak pernah ditemukan. Sebagai anak
            // langsung `.home-juknis-layout` ia berdiri tepat di bawah pesan
            // sistem: paling awal di ponsel, sementara di layar lebar CSS
            // menempatkannya kembali di puncak kolom rel. Modul tetap satu,
            // urutan rel (layanan, role model, survei, DIPA) tidak berubah, dan
            // `aria-label` pada `<aside>` tetap menaungi isi rel yang sebenarnya. ?>
      <div class="home-search-slot">
        <jdoc:include type="modules" name="home-search" style="card" />
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
        <div class="home-section-divider" aria-hidden="true"></div>
        <?php if (function_exists('pn_natuna_sipp_render_schedule')) : ?>
          <?php pn_natuna_sipp_render_schedule(); ?>
        <?php else : ?>
          <jdoc:include type="modules" name="home-schedule" style="card" />
        <?php endif; ?>
        <jdoc:include type="modules" name="home-facilities" style="card" />
        <div class="home-section-divider" aria-hidden="true"></div>
        <?php if (function_exists('pn_natuna_render_latest_announcements')) : ?>
          <?php pn_natuna_render_latest_announcements(); ?>
        <?php endif; ?>
        <?php if (function_exists('pn_natuna_render_instansi_feed')) : ?>
          <?php pn_natuna_render_instansi_feed(); ?>
        <?php else : ?>
          <jdoc:include type="modules" name="home-public-board" style="card" />
        <?php endif; ?>
        <div class="home-section-divider" aria-hidden="true"></div>
        <jdoc:include type="modules" name="home-video" style="card" />
        <jdoc:include type="modules" name="home-contact" style="card" />
      </div>
      <aside class="home-juknis-sidebar" aria-label="Informasi pendukung">
        <div class="mobile-rail-status" data-sidebar-rail-status><span class="mobile-rail-hint">Geser untuk melihat lainnya</span><output>1 dari 1</output></div>
        <jdoc:include type="modules" name="home-service-info" style="card" />
        <jdoc:include type="modules" name="home-role-model" style="card" />
        <jdoc:include type="modules" name="home-survey" style="card" />
        <jdoc:include type="modules" name="home-dipa" style="card" />
        <?php if (function_exists('pn_natuna_instagram_render_profile_embed')) : ?>
          <?php echo pn_natuna_instagram_render_profile_embed(); ?>
        <?php else : ?>
          <jdoc:include type="modules" name="home-instagram" style="card" />
        <?php endif; ?>
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


  <section class="access-panel" aria-label="Panel aksesibilitas" data-access-panel>
    <button class="access-panel-toggle" type="button" aria-expanded="false" aria-controls="access-panel-body" aria-label="Buka panel aksesibilitas">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5Zm8 6.5H4v2h6v3.2l-2.1 6.8 1.9.6L12 15l2.2 6.1 1.9-.6-2.1-6.8v-3.2h6v-2Z" fill="currentColor"/></svg>
    </button>
    <div id="access-panel-body" class="access-panel-body" hidden>
      <div class="access-panel-heading">
        <strong>Alat Aksesibilitas</strong>
        <button class="access-panel-close" type="button" aria-label="Tutup panel aksesibilitas">Tutup</button>
      </div>
      <div class="access-panel-group access-panel-stack access-panel-actions" aria-label="Accessibility options">
        <button class="access-panel-action" type="button" data-access-action="increaseText"><span aria-hidden="true">A+</span><span>Increase Text Size</span></button>
        <button class="access-panel-action" type="button" data-access-action="decreaseText"><span aria-hidden="true">A-</span><span>Decrease Text Size</span></button>
        <button class="access-panel-action" type="button" data-access-action="increaseTextSpacing"><span aria-hidden="true">&lt;&gt;</span><span>Increase Text Spacing</span></button>
        <button class="access-panel-action" type="button" data-access-action="decreaseTextSpacing"><span aria-hidden="true">&gt;&lt;</span><span>Decrease Text Spacing</span></button>
        <button class="access-panel-action" type="button" data-access-action="invertColors" aria-pressed="false"><span aria-hidden="true">◐</span><span>Invert Colours</span></button>
        <button class="access-panel-action" type="button" data-access-action="grayHues" aria-pressed="false"><span aria-hidden="true">◒</span><span>Grey Hues</span></button>
        <button class="access-panel-action" type="button" data-access-action="underlineLinks" aria-pressed="false"><span aria-hidden="true">U</span><span>Underline Links</span></button>
        <button class="access-panel-action" type="button" data-access-action="bigCursor" aria-pressed="false"><span aria-hidden="true">↖</span><span>Big Cursor</span></button>
        <button class="access-panel-action" type="button" data-access-action="readingGuide" aria-pressed="false"><span aria-hidden="true">━</span><span>Reading Guide</span></button>
        <button class="access-panel-action" type="button" data-access-action="reset"><span aria-hidden="true">↺</span><span>Reset</span></button>
        <button class="access-panel-dark" type="button" aria-pressed="false">Mode Gelap</button>
        <button class="access-panel-voice is-active" type="button" aria-pressed="true">Suara Aktif</button>
      </div>
      <label class="access-panel-voice-select-wrap" hidden>
        <span>Pilihan Suara</span>
        <select class="access-panel-voice-select"></select>
      </label>
      <p class="access-panel-voice-note" hidden>Suara Bahasa Indonesia mengikuti ketersediaan browser/perangkat.</p>
    </div>
  </section>

  <?php if ($isHome) : ?>
  <nav class="mobile-quick-actions" aria-label="Navigasi utama mobile">
    <a class="is-active" href="/" aria-current="page">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v11h-6v-7H9v7H3z" fill="currentColor"/></svg><span>Beranda</span>
    </a>
    <a href="/layanan-publik">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 4 6v6c0 5 3.4 9.4 8 10 4.6-.6 8-5 8-10V6zm0 4 5 2.5V12c0 3.4-2 6.4-5 7.5-3-1.1-5-4.1-5-7.5V8.5z" fill="currentColor"/></svg><span>Layanan</span>
    </a>
    <a href="https://sipp.pn-natuna.go.id/" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h14v18H5zm3 4v2h8V7zm0 4v2h8v-2zm0 4v2h5v-2z" fill="currentColor"/></svg><span>Perkara</span>
    </a>
    <a href="/regulasi-pengaduan">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1 5h2v7h-2zm0 9h2v2h-2z" fill="currentColor"/></svg><span>Pengaduan</span>
    </a>
    <a href="/kontak">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15 15 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.2c1.2.4 2.4.6 3.6.6a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.2.2 2.4.6 3.6a1 1 0 0 1-.3 1z" fill="currentColor"/></svg><span>Kontak</span>
    </a>
  </nav>
  <?php endif; ?>
  <div id="site-search-overlay" class="search-overlay" hidden>
    <div class="search-overlay-panel" role="dialog" aria-modal="true" aria-labelledby="search-overlay-title">
      <div class="search-overlay-heading">
        <strong id="search-overlay-title">Cari Informasi PN Natuna</strong>
        <button class="search-overlay-close" type="button">Tutup</button>
      </div>
      <?php // `com_search` dihapus Joomla sejak versi 4, jadi `/component/search/` selalu
            // 404 - kotak pencarian di seluruh situs menuju halaman yang tidak ada.
            // Penggantinya `com_finder`, dengan rute `/cari` yang didaftarkan migrasi
            // 20260902 di menu tersembunyi dan parameter kueri bernama `q`. ?>
      <form action="/cari" method="get" role="search">
        <label for="site-search-query">Kata kunci pencarian</label>
        <input id="site-search-query" name="q" type="search" autocomplete="off" enterkeyhint="search" placeholder="Contoh: biaya perkara, jadwal sidang, posbakum…">
        <button type="submit">Cari</button>
      </form>
    </div>
  </div>
  <button id="back-to-top" class="back-to-top" type="button" aria-label="Kembali ke atas" hidden>
    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 4l-8 8h5v8h6v-8h5z" fill="currentColor"/></svg>
  </button>
</body>
</html>
