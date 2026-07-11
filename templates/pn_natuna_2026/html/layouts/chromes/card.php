<?php

/**
 * Module chrome "card" (Joomla 4/5 layout).
 * Membungkus semua module style="card" dengan wrapper .module-card
 * supaya seluruh card homepage konsisten (radius/border/shadow sama).
 */

defined('_JEXEC') or die;

$module  = $displayData['module'];
$params  = $displayData['params'];

if (trim((string) $module->content) === '') {
    return;
}

/*
 * EDITORIAL 2026-07-11 — hirarki seksi beranda (template-only, konten DB tidak diubah).
 * Peta posisi modul -> kicker label + deskripsi satu baris.
 * Modul dengan showtitle=0 (h2 berada di konten DB) hanya menerima kicker di atas konten.
 */
$sectionMeta = [
    'home-alerts'     => ['kicker' => 'Layanan Publik', 'desc' => 'Standar dan komitmen pelayanan kami bagi masyarakat pencari keadilan.'],
    'quick-links'     => ['kicker' => 'Layanan', 'desc' => 'Akses cepat aplikasi peradilan yang paling sering digunakan.'],
    'home-facilities' => ['kicker' => 'Fasilitas'],
    'home-survey'     => ['kicker' => 'Transparansi &amp; Kinerja'],
];
$meta = $sectionMeta[(string) $module->position] ?? null;
?>
<section class="module-card <?php echo htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($module->showtitle) : ?>
        <?php if ($meta !== null) : ?>
            <div class="section-head">
                <p class="section-kicker"><?php echo $meta['kicker']; ?></p>
                <h2><?php echo $module->title; ?></h2>
                <?php if (!empty($meta['desc'])) : ?>
                    <p class="section-desc"><?php echo $meta['desc']; ?></p>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <h2><?php echo $module->title; ?></h2>
        <?php endif; ?>
    <?php elseif ($meta !== null) : ?>
        <p class="section-kicker"><?php echo $meta['kicker']; ?></p>
    <?php endif; ?>
    <?php echo $module->content; ?>
</section>
