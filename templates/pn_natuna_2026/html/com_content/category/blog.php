<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_content
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;

$app = Factory::getApplication();

$categoryAlias = $this->category->alias ?? '';

if (!in_array($categoryAlias, ['berita', 'pengumuman'], true)) {
    require JPATH_SITE . '/components/com_content/tmpl/category/blog.php';

    return;
}

/** @var \Joomla\Component\Content\Site\View\Category\HtmlView $this */
$this->category->text = $this->category->description;
$app->triggerEvent('onContentPrepare', [$this->category->extension . '.categories', &$this->category, &$this->params, 0]);
$this->category->description = $this->category->text;

$results = $app->triggerEvent('onContentAfterTitle', [$this->category->extension . '.categories', &$this->category, &$this->params, 0]);
$afterDisplayTitle = trim(implode("\n", $results));

$results = $app->triggerEvent('onContentBeforeDisplay', [$this->category->extension . '.categories', &$this->category, &$this->params, 0]);
$beforeDisplayContent = trim(implode("\n", $results));

$results = $app->triggerEvent('onContentAfterDisplay', [$this->category->extension . '.categories', &$this->category, &$this->params, 0]);
$afterDisplayContent = trim(implode("\n", $results));

$isChannel = true;
$isAnnouncement = $categoryAlias === 'pengumuman';
$channelClass = $isAnnouncement ? 'news-channel--announcement' : 'news-channel--news';
$channelKicker = $isAnnouncement ? 'Pengumuman Resmi' : 'Berita Terkini';
$channelTitle = $isAnnouncement ? 'Pengumuman Resmi PN Natuna' : 'Berita Pengadilan Negeri Natuna';
$channelIntro = $isAnnouncement
    ? 'Pemberitahuan resmi satuan kerja, seleksi layanan, informasi Posbakum, dan pengumuman lain yang perlu diketahui masyarakat.'
    : 'Publikasi kegiatan pengadilan, pelayanan publik, pembinaan aparatur, dan informasi kelembagaan Pengadilan Negeri Natuna.';
$items = array_merge($this->lead_items ?? [], $this->intro_items ?? []);
?>
<div class="com-content-category-blog blog news-channel <?php echo $channelClass; ?>">
    <header class="news-channel-hero">
        <img src="/images/hero/gedung-pn-natuna-2026.webp" alt="Gedung Pengadilan Negeri Natuna" width="1536" height="1024" fetchpriority="high">
        <div class="news-channel-hero__overlay">
            <p class="section-kicker"><?php echo $channelKicker; ?></p>
            <h1><?php echo $this->escape($channelTitle); ?></h1>
            <p><?php echo $this->escape($channelIntro); ?></p>
        </div>
    </header>
    <nav class="news-channel-tabs" aria-label="Pilih kanal informasi">
        <a class="<?php echo $isAnnouncement ? '' : 'is-active'; ?>"<?php echo $isAnnouncement ? '' : ' aria-current="page"'; ?> href="/berita-dan-pengumuman/berita">Berita Terkini</a>
        <a class="<?php echo $isAnnouncement ? 'is-active' : ''; ?>"<?php echo $isAnnouncement ? ' aria-current="page"' : ''; ?> href="/berita-dan-pengumuman/pengumuman">Pengumuman Resmi</a>
    </nav>


    <?php if (empty($items) && empty($this->link_items)) : ?>
        <?php if ($this->params->get('show_no_articles', 1)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                <?php echo Text::_('COM_CONTENT_NO_ARTICLES'); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($items)) : ?>
        <div class="news-listing news-listing--cards<?php echo $isAnnouncement ? ' news-listing--announcement-cards' : ''; ?>">
            <?php foreach ($items as &$item) : ?>
                <?php $this->item = &$item; ?>
                <?php echo $this->loadTemplate('item'); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (($this->params->def('show_pagination', 1) == 1 || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
        <div class="com-content-category-blog__navigation news-channel-pagination">
            <?php if ($this->params->def('show_pagination_results', 1)) : ?>
                <p class="com-content-category-blog__counter counter">
                    <?php echo $this->pagination->getPagesCounter(); ?>
                </p>
            <?php endif; ?>
            <div class="com-content-category-blog__pagination">
                <?php echo $this->pagination->getPagesLinks(); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
