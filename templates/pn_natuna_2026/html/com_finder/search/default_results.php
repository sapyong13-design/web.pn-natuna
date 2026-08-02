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
?>
<?php if (($this->total === 0) || ($this->total === null)) : ?>
    <div id="search-result-empty" class="site-search__empty">
        <h2>Tidak ada hasil untuk &ldquo;<?php echo $this->escape($kataKunci); ?>&rdquo;</h2>
        <p>Coba kata kunci yang lebih umum, atau langsung buka halaman yang paling sering dicari:</p>
        <ul class="site-search__suggestions">
            <li><a href="/berita-dan-pengumuman/berita">Berita dan pengumuman</a></li>
            <li><a href="/informasi-perkara/jadwal-sidang">Jadwal sidang</a></li>
            <li><a href="/layanan-publik/layanan-ptsp">Layanan PTSP</a></li>
            <li><a href="/kontak">Kontak dan jam layanan</a></li>
        </ul>
    </div>
    <?php return; ?>
<?php endif; ?>

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
