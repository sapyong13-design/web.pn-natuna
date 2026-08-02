<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_finder
 *
 * Satu hasil pencarian. Versi inti mencetak URL mentah di bawah judul dan menempelkan
 * baris "Type: Article Category: Berita" berbahasa Inggris. Warga tidak membaca URL;
 * yang menolongnya adalah kanal, tanggal berbahasa Indonesia, dan cuplikan isinya.
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\String\StringHelper;

/** @var \Joomla\Component\Finder\Site\View\Search\HtmlView $this */
$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$tanggal = '';
if (!empty($this->result->start_date) && $this->result->start_date !== Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class)->getNullDate()) {
    $waktu = Factory::getDate($this->result->start_date);
    $tanggal = $waktu->format('j') . ' ' . ($bulan[(int) $waktu->format('n')] ?? '') . ' ' . $waktu->format('Y');
}

// Kanal ditulis apa adanya dari taksonomi Joomla, tanpa awalan "Category:".
$kanal = '';
if (!empty($this->result->taxonomy['Category'])) {
    $pertama = reset($this->result->taxonomy['Category']);
    $kanal = is_object($pertama) ? (string) $pertama->title : (string) $pertama;
}

$cuplikan = '';
if ($this->params->get('show_description', 1)) {
    $isi = $this->result->description;
    if (!empty($this->result->summary) && !empty($this->result->body)) {
        $isi = $this->result->summary . ' ' . $this->result->body;
    }
    $cuplikan = HTMLHelper::_('string.truncate', trim(strip_tags((string) $isi)), (int) $this->params->get('description_length', 220), true);
    $cuplikan = trim(preg_replace('/\s+/u', ' ', $cuplikan));
}
?>
<li class="site-search__item">
    <p class="site-search__item-title">
        <?php if ($this->result->route) : ?>
            <a href="<?php echo Route::_($this->result->route); ?>"><?php echo StringHelper::trim($this->result->title); ?></a>
        <?php else : ?>
            <?php echo StringHelper::trim($this->result->title); ?>
        <?php endif; ?>
    </p>
    <?php if ($kanal || $tanggal) : ?>
        <p class="site-search__item-meta">
            <?php if ($kanal) : ?><span><?php echo $this->escape($kanal); ?></span><?php endif; ?>
            <?php if ($tanggal) : ?><time datetime="<?php echo $this->escape(Factory::getDate($this->result->start_date)->format('Y-m-d')); ?>"><?php echo $tanggal; ?></time><?php endif; ?>
        </p>
    <?php endif; ?>
    <?php if ($cuplikan !== '') : ?>
        <p class="site-search__item-text"><?php echo $this->escape($cuplikan); ?></p>
    <?php endif; ?>
</li>
