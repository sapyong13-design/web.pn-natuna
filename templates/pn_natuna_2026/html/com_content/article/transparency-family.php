<?php
/** Central renderer for Transparency family articles. */
defined('_JEXEC') or die;

$transparencyPages = [
    'Akuntabilitas Kinerja' => [
        37 => ['/transparansi/ringkasan-lkjip', 'Ringkasan LKjIP'],
        38 => ['/transparansi/laporan-tahunan', 'Laporan Tahunan'],
        39 => ['/transparansi/sakip', 'SAKIP'],
    ],
    'Keuangan' => [
        40 => ['/transparansi/laporan-realisasi-anggaran', 'Laporan Realisasi Anggaran'],
        86 => ['/transparansi/laporan-keuangan', 'Laporan Keuangan'],
        41 => ['/transparansi/lhkpn', 'LHKPN / LHKASN'],
    ],
    'Survei dan Integritas' => [
        42 => ['/transparansi/laporan-skm', 'Laporan SKM / IKM'],
        43 => ['/transparansi/laporan-spak', 'Laporan SPAK'],
        85 => ['/transparansi/laporan-survei-harian', 'Laporan Survei Harian'],
    ],
    'Informasi Publik' => [
        87 => ['/transparansi/e-brosur', 'E-Brosur'],
        88 => ['/transparansi/peraturan-kebijakan', 'Peraturan dan Kebijakan'],
        115 => ['/transparansi/lelang-barang-jasa', 'Lelang Barang dan Jasa'],
        116 => ['/transparansi/laporan-pelayanan-informasi-publik', 'Laporan Pelayanan Informasi Publik'],
    ],
];
$transparencyIds = [45, 37, 38, 39, 40, 86, 41, 42, 43, 85, 87, 88, 115, 116];
if (!in_array((int) $item->id, $transparencyIds, true)) {
    return false;
}
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$extractArchive = static function (string $html): string {
    if (!class_exists('DOMDocument')) {
        return $html;
    }
    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?><div id="transparency-source">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " transparency-back ") or contains(concat(" ", normalize-space(@class), " "), " transparency-hero ") or contains(concat(" ", normalize-space(@class), " "), " transparency-header ") or contains(concat(" ", normalize-space(@class), " "), " transparency-chips ") or contains(concat(" ", normalize-space(@class), " "), " transparency-local-nav ")]') as $node) {
        $node->parentNode?->removeChild($node);
    }
    $root = $dom->getElementById('transparency-source');
    $output = '';
    foreach ($root?->childNodes ?? [] as $node) {
        $output .= $dom->saveHTML($node);
    }
    return $output;
};
$renderNavigation = static function () use ($transparencyPages, $item, $escape): void { ?>
  <nav class="transparency-family__nav" aria-label="Navigasi informasi transparansi">
    <?php foreach ($transparencyPages as $group => $pages) : ?>
      <section class="transparency-family__nav-group">
        <h2><?php echo $escape($group); ?></h2>
        <div><?php foreach ($pages as $id => [$route, $title]) : ?><a href="<?php echo $escape($route); ?>"<?php echo (int) $item->id === $id ? ' aria-current="page"' : ''; ?>><?php echo $escape($title); ?></a><?php endforeach; ?></div>
      </section>
    <?php endforeach; ?>
  </nav>
<?php };
$isLanding = (int) $item->id === 45;
?>
<article class="transparency-family<?php echo $isLanding ? ' transparency-family--landing' : ''; ?>">
  <?php if ($isLanding) : ?>
    <header class="transparency-family__hero"><p>Portal informasi publik</p><h1>Transparansi dan Akuntabilitas</h1><span>Dokumen kinerja, keuangan, survei, integritas, serta informasi publik Pengadilan Negeri Natuna.</span></header>
    <?php $renderNavigation(); ?>
  <?php else : ?>
    <nav class="transparency-family__breadcrumb" aria-label="Breadcrumb"><a href="/transparansi">Transparansi</a><span aria-hidden="true">/</span><span><?php echo $escape($item->title); ?></span></nav>
    <header class="transparency-family__hero"><p>Informasi publik</p><h1><?php echo $escape($item->title); ?></h1></header>
    <?php $renderNavigation(); ?>
    <div class="transparency-family__archive"><?php echo $extractArchive((string) $item->text); ?></div>
  <?php endif; ?>
</article>
<?php
return true;
