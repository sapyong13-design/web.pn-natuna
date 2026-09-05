<?php

/**
 * @package     Joomla.Site
 * @subpackage  Templates.pn_natuna_2026
 *
 * Halaman galat. Sebelum berkas ini ada, Joomla menyajikan halaman bawaannya: berbahasa
 * Inggris, tanpa lambang, tanpa satu pun tautan keluar - "You may not be able to visit
 * this page because of: an out-of-date bookmark/favourite ...". Tautan berita pengadilan
 * beredar lewat WhatsApp dan tetap diklik berbulan-bulan setelah alamatnya berubah, jadi
 * halaman inilah yang menyambut sebagian warga. Ia harus mengaku salah dalam bahasa yang
 * mereka pakai, dan menunjukkan jalan ke urusan yang sedang mereka cari.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\CMS\Document\ErrorDocument $this */
$app = Factory::getApplication();
require_once JPATH_ROOT . '/includes/pn-csp.php';
$app->setHeader(
    'Content-Security-Policy',
    pnNatunaContentSecurityPolicy('', Uri::getInstance()->isSsl()),
    true
);
$baseUrl = Uri::base(true);
$code = (int) ($this->error->getCode() ?: 500);
$isMissing = $code === 404;

$title = $isMissing ? 'Halaman tidak ditemukan' : 'Terjadi gangguan pada layanan';
$lead = $isMissing
    ? 'Alamat yang Anda buka tidak ada di situs ini. Biasanya karena tautannya sudah lama, halamannya dipindahkan, atau alamatnya kurang satu huruf.'
    : 'Situs sedang tidak dapat menampilkan halaman ini. Gangguan ini tercatat dan akan ditangani; silakan coba beberapa saat lagi.';

// Tujuan yang benar-benar dicari orang saat tersesat di situs pengadilan.
$routes = [
    ['/', 'Beranda', 'Titik awal seluruh layanan.'],
    ['/berita-dan-pengumuman/berita', 'Berita dan pengumuman', 'Kegiatan, pengumuman resmi, dan informasi terbaru.'],
    ['/informasi-perkara/jadwal-sidang', 'Jadwal sidang', 'Daftar sidang hari ini dan berikutnya.'],
    ['/layanan-publik/layanan-ptsp', 'Layanan PTSP', 'Seluruh permohonan layanan cukup lewat satu meja.'],
    ['/kontak', 'Kontak dan jam layanan', 'Alamat kantor, telepon, dan jam buka.'],
];
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">
    <title><?php echo $code; ?> - <?php echo $title; ?> | Pengadilan Negeri Natuna Kelas II</title>
    <link href="<?php echo $baseUrl; ?>/images/favicon-pn-natuna.png" rel="icon" type="image/png">
    <link href="<?php echo $baseUrl; ?>/templates/pn_natuna_2026/css/template-4b123344.css" rel="stylesheet">
</head>
<body class="site site-error">
    <?php // Halaman ini tidak memuat template.js, jadi kelas mode gelap dipasang sendiri
          // sebelum cat pertama - pembaca yang sudah memilih mode gelap tidak boleh
          // disilaukan halaman terang hanya karena ia tersesat. Sama dengan index.php. ?>
    <script src="<?php echo $baseUrl; ?>/templates/pn_natuna_2026/js/theme-boot.js"></script>
    <main class="error-page">
        <div class="error-page__inner">
            <p class="error-page__masthead">
                <img src="<?php echo $baseUrl; ?>/images/brand/logo-pn-natuna.webp" alt="" width="31" height="40" decoding="async">
                <span>Pengadilan Negeri Natuna Kelas II</span>
            </p>
            <p class="error-page__code"><?php echo $code; ?></p>
            <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="error-page__lead"><?php echo htmlspecialchars($lead, ENT_QUOTES, 'UTF-8'); ?></p>

            <form class="error-page__search" action="<?php echo $baseUrl; ?>/cari" method="get" role="search">
                <label for="error-search">Cari informasi di situs ini</label>
                <input id="error-search" name="q" type="search" placeholder="Contoh: jadwal sidang, biaya perkara, posbakum" enterkeyhint="search">
                <button type="submit">Cari</button>
            </form>

            <h2>Atau langsung ke halaman yang sering dicari</h2>
            <ul class="error-page__routes">
                <?php foreach ($routes as [$href, $label, $note]) : ?>
                    <li>
                        <a href="<?php echo $baseUrl . $href; ?>">
                            <strong><?php echo $label; ?></strong>
                            <span><?php echo $note; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="error-page__help">Masih tidak menemukan yang Anda cari? Hubungi <a href="tel:07733211203">0773-3211203</a> pada jam layanan, atau lihat <a href="<?php echo $baseUrl; ?>/kontak">alamat dan jam layanan kantor</a>.</p>

            <?php if ($this->debug) : ?>
                <div class="error-page__debug">
                    <?php echo $this->renderBacktrace(); ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
