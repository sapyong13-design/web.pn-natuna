<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_content
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;

/** @var \Joomla\Component\Content\Site\View\Category\HtmlView $this */
$item = $this->item;
$params = $item->params;
$canEdit = $params->get('access-edit');
$link = Route::_(RouteHelper::getArticleRoute($item->slug, $item->catid, $item->language));
$images = json_decode($item->images ?? '{}');
$image = $images->image_intro ?? '';
$imageAlt = $images->image_intro_alt ?? $item->title;
$categoryAlias = $this->category->alias ?? '';
$isAnnouncement = $categoryAlias === 'pengumuman';
$publishUp = $item->publish_up ?? '';
$dateSource = ($publishUp && $publishUp !== Factory::getDbo()->getNullDate() && $publishUp > '2000-01-02 00:00:00') ? $publishUp : $item->created;
$dateObject = Factory::getDate($dateSource);
$monthNames = [
    '01' => 'Jan',
    '02' => 'Feb',
    '03' => 'Mar',
    '04' => 'Apr',
    '05' => 'Mei',
    '06' => 'Jun',
    '07' => 'Jul',
    '08' => 'Agu',
    '09' => 'Sep',
    '10' => 'Okt',
    '11' => 'Nov',
    '12' => 'Des',
];
$dateLabel = $dateObject->format('d') . ' ' . $monthNames[$dateObject->format('m')] . ' ' . $dateObject->format('Y');
$dateMachine = $dateObject->format('Y-m-d');
$excerpt = trim(preg_replace('/\s+/', ' ', strip_tags($item->introtext ?? '')));
$excerpt = HTMLHelper::_('string.truncate', $excerpt, $isAnnouncement ? 220 : 150, true, false);

?>
<article class="news-listing-item <?php echo $isAnnouncement ? 'announcement-item' : 'news-card'; ?>">
    <?php if ($image && !$isAnnouncement) : ?>
        <a class="news-card-media" href="<?php echo $link; ?>" aria-label="<?php echo $this->escape($item->title); ?>">
            <img src="/<?php echo ltrim($this->escape($image), '/'); ?>" alt="<?php echo $this->escape($imageAlt); ?>" width="800" height="500" loading="lazy">
        </a>
    <?php elseif ($isAnnouncement) : ?>
        <div class="announcement-mark" aria-hidden="true">PN</div>
    <?php endif; ?>

    <div class="news-card-body">
        <div class="news-meta">
            <span><?php echo $isAnnouncement ? 'Pengumuman' : 'Berita'; ?></span>
            <time datetime="<?php echo $dateMachine; ?>"><?php echo $dateLabel; ?></time>
        </div>
        <h2>
            <a href="<?php echo $link; ?>"><?php echo $this->escape($item->title); ?></a>
        </h2>
        <?php if ($excerpt) : ?>
            <p><?php echo $this->escape($excerpt); ?></p>
        <?php endif; ?>
        <a class="read-more-link" href="<?php echo $link; ?>">
            <?php echo $isAnnouncement ? 'Baca pengumuman' : 'Baca berita'; ?>
        </a>
        <?php if ($canEdit) : ?>
            <?php echo HTMLHelper::_('contenticon.edit', $item, $params); ?>
        <?php endif; ?>
    </div>
</article>
