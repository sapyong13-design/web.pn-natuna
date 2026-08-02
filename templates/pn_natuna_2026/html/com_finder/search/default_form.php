<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_finder
 *
 * Formulir pencarian. Versi inti memasang legend "Search Form" dan label "Search Terms:"
 * dalam bahasa Inggris, serta menu lanjutan yang tidak dipakai situs ini.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Finder\Site\View\Search\HtmlView $this */
?>
<form id="finder-search" class="site-search__form" action="<?php echo Route::_($this->query->toUri()); ?>" method="get" role="search">
    <label for="q">Kata kunci pencarian</label>
    <div class="site-search__field">
        <input type="search" name="q" id="q" value="<?php echo $this->escape($this->query->input); ?>" enterkeyhint="search" autocomplete="off" placeholder="Contoh: biaya perkara, jadwal sidang, posbakum">
        <button type="submit">Cari</button>
    </div>
    <?php echo $this->getFields(); ?>
</form>
