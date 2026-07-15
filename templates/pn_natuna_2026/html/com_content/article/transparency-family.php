<?php
/** Central renderer for Transparency family articles. */
defined('_JEXEC') or die;

$transparencyCards = [
    'Akuntabilitas Kinerja' => [
        37 => ['/transparansi/ringkasan-lkjip', 'Laporan Kinerja', 'Ringkasan sasaran, indikator, dan capaian kinerja instansi.', 'chart', 'Lihat ringkasan'],
        38 => ['/transparansi/laporan-tahunan', 'Laporan Tahunan', 'Dokumentasi pelaksanaan program dan layanan pengadilan setiap tahun.', 'calendar', 'Buka laporan'],
        39 => ['/transparansi/sakip', 'SAKIP', 'Dokumen sistem akuntabilitas kinerja instansi pemerintah.', 'target', 'Lihat SAKIP'],
    ],
    'Keuangan' => [
        40 => ['/transparansi/laporan-realisasi-anggaran', 'Realisasi Anggaran', 'Laporan pelaksanaan dan penyerapan anggaran satuan kerja.', 'budget', 'Lihat realisasi'],
        86 => ['/transparansi/laporan-keuangan', 'Laporan Keuangan', 'Dokumen pertanggungjawaban dan pelaporan keuangan pengadilan.', 'ledger', 'Buka laporan'],
        41 => ['/transparansi/lhkpn', 'LHKPN / LHKASN', 'Informasi kepatuhan pelaporan harta kekayaan penyelenggara negara dan ASN.', 'assets', 'Lihat kepatuhan'],
    ],
    'Survei dan Integritas' => [
        42 => ['/transparansi/laporan-skm', 'SKM / IKM', 'Hasil pengukuran kepuasan masyarakat terhadap layanan pengadilan.', 'survey', 'Lihat hasil'],
        43 => ['/transparansi/laporan-spak', 'SPAK', 'Hasil survei persepsi antikorupsi dan integritas pelayanan.', 'shield', 'Lihat integritas'],
        85 => ['/transparansi/laporan-survei-harian', 'Survei Harian', 'Rekap umpan balik harian pengguna layanan Pengadilan Negeri Natuna.', 'pulse', 'Buka rekap'],
    ],
    'Informasi Publik' => [
        87 => ['/transparansi/e-brosur', 'E-Brosur', 'Materi publikasi digital mengenai layanan dan informasi pengadilan.', 'brochure', 'Lihat publikasi'],
        88 => ['/transparansi/peraturan-kebijakan', 'Peraturan dan Kebijakan', 'Rujukan kebijakan dan regulasi yang mendasari pelaksanaan layanan.', 'book', 'Buka regulasi'],
        115 => ['/transparansi/lelang-barang-jasa', 'Lelang Barang dan Jasa', 'Informasi pengadaan dan lelang barang atau jasa satuan kerja.', 'auction', 'Lihat lelang'],
        116 => ['/transparansi/laporan-pelayanan-informasi-publik', 'Layanan Informasi Publik', 'Laporan pelaksanaan pelayanan informasi publik dan PPID.', 'info', 'Buka laporan'],
    ],
];
$transparencyIds = [45];
foreach ($transparencyCards as $cards) { $transparencyIds = array_merge($transparencyIds, array_keys($cards)); }
if (!in_array((int) $item->id, $transparencyIds, true)) { return false; }
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$extractArchive = static function (string $html): string {
    if (!class_exists('DOMDocument')) { return $html; }
    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?><div id="transparency-source">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " transparency-back ") or contains(concat(" ", normalize-space(@class), " "), " transparency-hero ") or contains(concat(" ", normalize-space(@class), " "), " transparency-header ") or contains(concat(" ", normalize-space(@class), " "), " transparency-chips ") or contains(concat(" ", normalize-space(@class), " "), " transparency-local-nav ")]') as $node) { $node->parentNode?->removeChild($node); }
    $root = $dom->getElementById('transparency-source');
    $output = '';
    foreach ($root?->childNodes ?? [] as $node) { $output .= $dom->saveHTML($node); }
    return $output;
};
$icon = static function (string $name, bool $illustration = false): string {
    $paths = [
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'budget' => '<path d="M4 20V10M10 20V6M16 20v-9M22 20H2"/><path d="m4 7 6-4 6 4 5-3"/>',
        'ledger' => '<path d="M5 3h14v18H5zM9 7h6M9 11h6M9 15h4"/>',
        'assets' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8" cy="12" r="3"/><path d="M14 10h4M14 14h3"/>',
        'survey' => '<path d="M6 2h12v20H6zM9 7h6M9 11h6M9 15h3"/><path d="m14 16 2 2 4-5"/>',
        'shield' => '<path d="M12 2 20 6v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z"/><path d="m8 12 3 3 5-6"/>',
        'pulse' => '<path d="M3 12h4l2-5 4 10 2-5h6"/>',
        'brochure' => '<path d="M4 3h16v18H4zM8 3v18M12 7h5M12 11h5M12 15h3"/>',
        'book' => '<path d="M3 5a4 4 0 0 1 4-2h5v17H7a4 4 0 0 0-4 2zM21 5a4 4 0 0 0-4-2h-5v17h5a4 4 0 0 1 4 2z"/>',
        'auction' => '<path d="m14 5 5 5M12 7l5 5M4 20l9-9M15 3l6 6M2 22h8"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
    ];
    return '<svg' . ($illustration ? ' class="svc-illus"' : '') . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . ($paths[$name] ?? $paths['info']) . '</svg>';
};
$arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
$transparencyIllustration = '<svg class="svc-illus" viewBox="0 0 200 160" aria-hidden="true"><circle cx="100" cy="80" r="72" fill="#fbf2d8"/><rect x="48" y="34" width="104" height="94" rx="10" fill="#fff" stroke="#d9e0e7" stroke-width="2"/><rect x="62" y="48" width="48" height="8" rx="4" fill="#8f1f0b"/><rect x="62" y="64" width="76" height="5" rx="2.5" fill="#e9dfc4"/><rect x="62" y="76" width="76" height="5" rx="2.5" fill="#e9dfc4"/><path d="M66 111V96M86 111V86M106 111V91M126 111V73" stroke="#1f5b4b" stroke-width="8" stroke-linecap="round"/><circle cx="146" cy="38" r="12" fill="#b98f24"/><path d="m141 38 4 4 7-8" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$documentIllustration = '<svg class="svc-illus" viewBox="0 0 200 160" aria-hidden="true"><circle cx="100" cy="80" r="72" fill="#fbf2d8"/><path d="M60 26h58l28 28v80H60z" fill="#fff" stroke="#d9e0e7" stroke-width="2"/><path d="M118 26v28h28" fill="#eadfbe"/><rect x="76" y="70" width="54" height="7" rx="3.5" fill="#8f1f0b"/><rect x="76" y="86" width="54" height="5" rx="2.5" fill="#d8cba8"/><rect x="76" y="99" width="42" height="5" rx="2.5" fill="#d8cba8"/><circle cx="132" cy="116" r="20" fill="#1f5b4b"/><path d="m122 116 7 7 13-15" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$renderSubnav = static function () use ($transparencyCards, $item, $escape): void { ?><nav class="svc-subnav" aria-label="Navigasi Transparansi"><a href="/transparansi">Ringkasan</a><?php foreach ($transparencyCards as $cards) : foreach ($cards as $id => [$route, $label]) : ?><a href="<?php echo $escape($route); ?>"<?php echo (int) $item->id === $id ? ' aria-current="page"' : ''; ?>><?php echo $escape($label); ?></a><?php endforeach; endforeach; ?></nav><?php };
$isLanding = (int) $item->id === 45;
?>
<article class="transparency-family<?php echo $isLanding ? ' transparency-family--landing' : ''; ?>">
<?php if ($isLanding) : ?>
  <div class="svc-hero"><div><span class="svc-kicker">Portal Informasi Publik</span><h1>Transparansi dan Akuntabilitas</h1><p class="svc-lead">Akses dokumen kinerja, keuangan, survei integritas, serta pelayanan informasi publik Pengadilan Negeri Natuna.</p></div><?php echo $transparencyIllustration; ?></div>
  <?php foreach ($transparencyCards as $group => $cards) : ?><section class="transparency-service-group"><h2><?php echo $escape($group); ?></h2><div class="svc-grid"><?php foreach ($cards as [$route, $title, $description, $iconName, $action]) : ?><a class="svc-card" href="<?php echo $escape($route); ?>"><span class="svc-icon"><?php echo $icon($iconName); ?></span><h3><?php echo $escape($title); ?></h3><p><?php echo $escape($description); ?></p><span class="svc-more"><?php echo $escape($action); ?> <?php echo $arrow; ?></span></a><?php endforeach; ?></div></section><?php endforeach; ?>
<?php else : ?>
  <?php $renderSubnav(); ?>
  <div class="svc-hero"><div><span class="svc-kicker">Transparansi</span><h1><?php echo $escape($item->title); ?></h1><p class="svc-lead">Dokumen resmi <?php echo $escape($item->title); ?> Pengadilan Negeri Natuna.</p></div><?php echo $documentIllustration; ?></div>
  <div class="transparency-family__archive"><?php echo $extractArchive((string) $item->text); ?></div>
<?php endif; ?>
</article>
<?php
return true;
