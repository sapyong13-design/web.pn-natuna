<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_finder
 *
 * Halaman hasil pencarian. Layout inti Joomla menyajikan seluruh halaman ini dalam
 * bahasa Inggris - "Search Form", "Search Terms:", dan kalimat mesin "Assuming posbakum
 * is required , the following 12 results were found." - lengkap dengan URL mentah
 * tercetak di bawah tiap judul. Situs ini berbahasa Indonesia dari kop sampai kaki, dan
 * halaman pencarian adalah tempat warga mendarat ketika sudah tidak menemukan jalan.
 */

defined('_JEXEC') or die;

/** @var \Joomla\Component\Finder\Site\View\Search\HtmlView $this */
?>
<div class="com-finder finder site-search">
    <header class="site-search__header">
        <h1><?php echo $this->escape($this->params->get('page_heading') ?: $this->params->get('page_title') ?: 'Cari Informasi'); ?></h1>
        <p class="site-search__lead">Telusuri berita, pengumuman, layanan, dan dokumen di situs Pengadilan Negeri Natuna.</p>
    </header>

    <div id="search-form" class="com-finder__form site-search__form-slot">
        <?php echo $this->loadTemplate('form'); ?>
    </div>

    <?php if ($this->query->search === true) : ?>
        <div id="search-results" class="com-finder__results site-search__results">
            <?php echo $this->loadTemplate('results'); ?>
        </div>
    <?php endif; ?>
</div>
