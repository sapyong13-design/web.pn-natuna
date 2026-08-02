<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_finder
 *
 * Daftar hasil. Kalimat inti Joomla berbunyi "Assuming posbakum is required , the
 * following 12 results were found." - mesin, berbahasa Inggris, dan menyisipkan spasi
 * sebelum koma. Di sini diganti satu kalimat Indonesia yang menyebut kata kunci dan
 * jumlahnya, plus keadaan kosong yang menawarkan jalan keluar, bukan jalan buntu.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

/** @var \Joomla\Component\Finder\Site\View\Search\HtmlView $this */
$kataKunci = trim((string) $this->query->input);
// Nomor perkara dapat membawa warga ke SIPP dan e-Court meski tidak ada kata kunci layanan.
$nomorPerkara = '~\b\d+\s*/\s*[A-Za-z.]+\s*/\s*(?:19|20)\d{2}\b~i';
$normalisasiKueri = static fn(string $nilai): string => (string) preg_replace('/\s+/u', ' ', mb_strtolower(trim($nilai), 'UTF-8'));
$kueriTernormalisasi = $normalisasiKueri($kataKunci);
$dataSistem = @file_get_contents(__DIR__ . '/../../../data/sistem-daring.json');
$daftarSistem = is_string($dataSistem) ? json_decode($dataSistem, true) : null;
$sistemResmi = is_array($daftarSistem['sistem'] ?? null) ? $daftarSistem['sistem'] : [];
$nomorPerkaraCocok = preg_match($nomorPerkara, $kataKunci) === 1;
$caseSystems = [];
foreach ($sistemResmi as $sistem) {
    if (!is_array($sistem) || !isset($sistem['id'], $sistem['nama'], $sistem['url'], $sistem['keterangan']) || !is_array($sistem['kataKunci'] ?? null)) {
        continue;
    }
    $kataKunciCocok = false;
    foreach ($sistem['kataKunci'] as $frasa) {
        $frasaTernormalisasi = $normalisasiKueri((string) $frasa);
        if ($frasaTernormalisasi !== '' && str_contains(' ' . $kueriTernormalisasi . ' ', ' ' . $frasaTernormalisasi . ' ')) {
            $kataKunciCocok = true;
            break;
        }
    }
    if ($kataKunciCocok || ($nomorPerkaraCocok && in_array($sistem['id'], ['sipp-perkara', 'ecourt'], true))) {
        $caseSystems[] = $sistem;
    }
}
$caseSystems = array_slice($caseSystems, 0, 2);
?>
<?php if ($caseSystems !== []) : ?>
    <?php require __DIR__ . '/default_caseroute.php'; ?>
<?php endif; ?>
<?php if (($this->total === 0) || ($this->total === null)) : ?>
    <div id="search-result-empty" class="site-search__empty">
        <h2>Tidak ada hasil untuk &ldquo;<?php echo $this->escape($kataKunci); ?>&rdquo;</h2>
        <p>Coba kata kunci yang lebih umum, atau langsung buka halaman yang paling sering dicari:</p>
        <ul class="site-search__suggestions">
            <li><a href="/berita-dan-pengumuman/berita">Berita dan pengumuman</a></li>
            <li><a href="/#sipp-schedule-title">Jadwal sidang</a></li>
            <li><a href="/layanan-publik/layanan-ptsp">Layanan PTSP</a></li>
            <li><a href="/kontak">Kontak dan jam layanan</a></li>
        </ul>
    </div>
<?php else : ?>

<p class="site-search__count" role="status">
    <strong><?php echo (int) $this->total; ?></strong> hasil untuk &ldquo;<?php echo $this->escape($kataKunci); ?>&rdquo;
</p>

<?php if (!empty($this->query->highlight) && $this->params->get('highlight_terms', 1)) : ?>
    <?php
        $this->getDocument()->getWebAssetManager()->useScript('highlight');
        $this->getDocument()->addScriptOptions('highlight', [[
            'class'     => 'js-highlight',
            'highLight' => array_slice($this->query->highlight, 0, 10),
        ]]);
    ?>
<?php endif; ?>

<ul id="search-result-list" class="js-highlight site-search__list">
    <?php $this->baseUrl = Uri::getInstance()->toString(['scheme', 'host', 'port']); ?>
    <?php foreach ($this->results as $i => $result) : ?>
        <?php $this->result = &$result; ?>
        <?php $this->result->counter = $i + 1; ?>
        <?php echo $this->loadTemplate($this->getLayoutFile($this->result->layout)); ?>
    <?php endforeach; ?>
</ul>

<?php if ($this->pagination->pagesTotal > 1) : ?>
    <nav class="site-search__pagination" aria-label="Halaman hasil pencarian">
        <?php echo $this->pagination->getPagesLinks(); ?>
    </nav>
<?php endif; ?>
<?php endif; ?>
