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
$categoryAlias = $this->category->alias ?? '';

if (!in_array($categoryAlias, ['berita', 'pengumuman'], true)) {
    require JPATH_SITE . '/components/com_content/tmpl/category/blog_item.php';

    return;
}

// Kartu daftar dulu menyajikan berkas asli: di 390px thumbnail 126px mengunduh foto
// 5 MB apa adanya. Varian yang sama dengan artikel dipakai ulang di sini.
$variantHelper = JPATH_SITE . '/plugins/content/pnnatunaimagevariants/src/Helper/VariantMaker.php';
if (is_file($variantHelper)) {
    require_once $variantHelper;
}
$cardSrcset = static function (string $src): string {
    return class_exists(\Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::class)
        ? \Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::srcset(JPATH_SITE, $src)
        : '';
};
$cardSizes = '(max-width: 760px) calc(100vw - 36px), 533px';

$params = $item->params;
$canEdit = $params->get('access-edit');
$link = Route::_(RouteHelper::getArticleRoute($item->slug, $item->catid, $item->language));
$images = json_decode($item->images ?? '{}');
$image = trim((string) ($images->image_fulltext ?? '')) ?: trim((string) ($images->image_intro ?? ''));
if ($image === '' && class_exists(\Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::class)) {
    $image = \Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::firstImage(
        (string) ($item->introtext ?? ''),
        (string) ($item->fulltext ?? '')
    );
}
// Media Manager menyimpan `foo.jpg#joomlaImage://local-images/foo.jpg?width=...`;
// fragmen itu tidak boleh ikut tercetak ke atribut src kartu.
$image = strtok($image, '#');
$imageAlt = trim((string) ($images->image_fulltext_alt ?? '')) ?: trim((string) ($images->image_intro_alt ?? '')) ?: $item->title;
$isAnnouncement = $categoryAlias === 'pengumuman';
$itemChannelAlias = str_starts_with((string) ($item->alias ?? ''), 'legacy-pengumuman-')
    ? 'pengumuman'
    : ($item->category_alias ?? '');
$showChannelLabel = $itemChannelAlias !== '' && $itemChannelAlias !== $categoryAlias;
$itemChannelLabel = $itemChannelAlias === 'pengumuman' ? 'Pengumuman resmi' : 'Berita';
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
<article class="news-listing-item news-card<?php echo $isAnnouncement ? ' news-card--announcement' : ''; ?>">
    <div class="news-card-media<?php echo $image ? '' : ' news-card-media--fallback'; ?>">
        <?php if ($image) : $cardCandidates = $cardSrcset('/' . ltrim($image, '/')); ?>
            <img src="/<?php echo ltrim($this->escape($image), '/'); ?>"<?php echo $cardCandidates ? ' srcset="' . $this->escape($cardCandidates) . '" sizes="' . $this->escape($cardSizes) . '"' : ''; ?> alt="<?php echo $this->escape($imageAlt); ?>" width="800" height="500" loading="lazy" decoding="async">
        <?php elseif ($isAnnouncement) : ?>
            <img src="/images/brand/pengumuman-resmi-pn-natuna.webp" alt="Pengumuman Resmi Pengadilan Negeri Natuna" width="800" height="500" loading="lazy" decoding="async">
        <?php else : ?>
            <span aria-hidden="true">PN</span>
        <?php endif; ?>
    </div>

    <div class="news-card-body">
        <div class="news-meta">
            <time datetime="<?php echo $dateMachine; ?>"><?php echo $dateLabel; ?></time>
            <?php if ($showChannelLabel) : ?>
                <span><?php echo $itemChannelLabel; ?></span>
            <?php endif; ?>
        </div>
        <h2>
            <a href="<?php echo $link; ?>"><?php echo $this->escape($item->title); ?></a>
        </h2>
        <?php if ($excerpt) : ?>
            <p><?php echo $this->escape($excerpt); ?></p>
        <?php endif; ?>
    </div>
    <?php if ($canEdit) : ?>
        <?php echo HTMLHelper::_('contenticon.edit', $item, $params); ?>
    <?php endif; ?>
</article>
