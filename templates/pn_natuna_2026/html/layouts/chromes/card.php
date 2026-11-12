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
    'home-dipa'       => ['kicker' => 'Transparansi &amp; Kinerja'],
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
    <?php
    /*
     * Kinerja & Akuntabilitas: tiap tile skor menyimpan dokumen publikasinya di
     * `data-maklumat-zoom`. Pratinjau halaman pertama ditempel di dalam tile supaya
     * pengunjung melihat dokumennya, bukan hanya angka. Sumbernya atribut yang sudah
     * diurus tools/refresh-survey.py, jadi periode TW berikutnya ikut sendiri tanpa
     * menyentuh template lagi. Varian `-thumb.webp` dipakai bila ada agar kartu tidak
     * mengunduh PNG halaman penuh; tanpa varian itu pratinjau tetap memakai PNG asli.
     */
    if ((string) $module->position === 'home-survey') {
        $rewritten = preg_replace_callback(
            '~(<button type="button" class="survey-score-tile"[^>]*data-maklumat-zoom="([^"]+)"[^>]*>.*?)(</button>)~s',
            static function (array $match): string {
                $document = html_entity_decode($match[2], ENT_QUOTES, 'UTF-8');
                // `-preview.webp` is the whole page with its white margins trimmed, so the
                // signature block and stamp at the bottom stay inside the preview. Its own
                // dimensions are read from the file: the crop makes the height vary per
                // document, and hard-coding it would letterbox the page.
                $preview_path = preg_replace('~\.(png|jpe?g)$~i', '-preview.webp', $document);
                $source = $document;
                $width = 400;
                $height = 566;
                if ($preview_path !== null && is_file(JPATH_ROOT . $preview_path)) {
                    $size = @getimagesize(JPATH_ROOT . $preview_path);
                    if ($size !== false) {
                        $source = $preview_path;
                        [$width, $height] = $size;
                    }
                }
                $preview = '<span class="survey-score-doc"><img src="' . htmlspecialchars($source, ENT_QUOTES, 'UTF-8')
                    . '" alt="" width="' . (int) $width . '" height="' . (int) $height . '" loading="lazy" decoding="async"></span>';

                return $match[1] . $preview . $match[3];
            },
            (string) $module->content
        );
        // A null return means the pattern engine failed; keep the raw content rather
        // than blanking the whole module.
        if (is_string($rewritten)) {
            $module->content = $rewritten;
        }
    }
    ?>
    <?php echo $module->content; ?>
</section>
