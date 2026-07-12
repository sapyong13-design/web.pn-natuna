<?php
/**
 * Editorial article override for Berita and Pengumuman.
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Joomla\Component\Content\Site\Helper\RouteHelper;

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */
$item = $this->item;
$params = $item->params;
$app = Factory::getApplication();
$db = Factory::getContainer()->get('DatabaseDriver');
// Transparency family must dispatch before curated/news article rendering.
if (require __DIR__ . '/transparency-family.php') {
    return;
}
// Article 53 is the curated Berita dan Pengumuman portal, not an editorial detail page.
if ((int) $item->id === 53) {
    $nowSql = Factory::getDate()->format('Y-m-d H:i:s');
    $levels = array_map('intval', $this->getCurrentUser()->getAuthorisedViewLevels());
    $language = $app->getLanguage()->getTag();
    $portalItems = static function (int $categoryId, int $limit, string $parameter) use ($db, $levels, $language, $nowSql): array {
        $query = $db->getQuery(true)
            ->select($db->quoteName(['a.id', 'a.title', 'a.alias', 'a.catid', 'a.language', 'a.publish_up', 'a.created', 'a.images']))
            ->from($db->quoteName('#__content', 'a'))
            ->where($db->quoteName('a.catid') . ' = :' . $parameter)
            ->where($db->quoteName('a.state') . ' = 1')
            ->whereIn($db->quoteName('a.access'), $levels)
            ->whereIn($db->quoteName('a.language'), ['*', $language], Joomla\Database\ParameterType::STRING)
            ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' <= :portalNowUp)')
            ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' = ' . $db->quote($db->getNullDate()) . ' OR ' . $db->quoteName('a.publish_down') . ' >= :portalNowDown)')
            ->where($db->quoteName('a.alias') . ' NOT LIKE ' . $db->quote('berita-dan-pengumuman%'))
            ->bind(':' . $parameter, $categoryId, Joomla\Database\ParameterType::INTEGER)
            ->bind(':portalNowUp', $nowSql)
            ->bind(':portalNowDown', $nowSql)
            ->order('CASE WHEN ' . $db->quoteName('a.publish_up') . ' > ' . $db->quote('2000-01-02 00:00:00') . ' THEN ' . $db->quoteName('a.publish_up') . ' ELSE ' . $db->quoteName('a.created') . ' END DESC');
        return $db->setQuery($query, 0, $limit)->loadObjectList();
    };
    $portalNews = $portalItems(12, 3, 'portalNewsCategory');
    $portalAnnouncements = $portalItems(13, 5, 'portalAnnouncementCategory');
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $formatPortalDate = static function (string $publishUp, string $created) use ($months): array {
        $raw = !empty($publishUp) && $publishUp > '2000-01-02 00:00:00' ? $publishUp : $created;
        $date = Factory::getDate($raw);
        return [$date->format(DATE_ATOM), (int) $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y')];
    };
    $portalImage = static function (string $images): string {
        $decoded = json_decode($images, true) ?: [];
        $image = trim((string) ($decoded['image_fulltext'] ?? '')) ?: trim((string) ($decoded['image_intro'] ?? ''));
        return $image !== '' && !preg_match('#^(?:https?:)?//#i', $image) ? '/' . ltrim($image, '/') : $image;
    };
    ?>
    <section class="news-portal" aria-labelledby="news-portal-title">
      <section class="news-portal__hero">
        <img src="/images/hero/gedung-pn-natuna-2026.webp" alt="Gedung Pengadilan Negeri Natuna" width="1536" height="1024" fetchpriority="high">
        <div class="news-portal__hero-overlay"><p>Informasi resmi</p><h1 id="news-portal-title">Berita dan Pengumuman</h1><span>Informasi terkini Pengadilan Negeri Natuna untuk masyarakat dan para pencari keadilan.</span></div>
      </section>
      <nav class="news-portal__channels" aria-label="Kanal informasi"><a href="<?php echo Route::_('/berita'); ?>" aria-label="Buka semua berita">Berita</a><a href="<?php echo Route::_('/pengumuman'); ?>" aria-label="Buka semua pengumuman">Pengumuman</a></nav>
      <section class="news-portal__section" aria-labelledby="portal-news-title"><header class="news-portal__heading"><div><p>Berita</p><h2 id="portal-news-title">Kabar dari pengadilan</h2></div><a href="<?php echo Route::_('/berita'); ?>">Semua berita</a></header>
        <div class="news-portal__news-grid">
        <?php foreach ($portalNews as $portalItem) : $portalItemImage = $portalImage((string) $portalItem->images); [$portalDateTime, $portalDate] = $formatPortalDate((string) $portalItem->publish_up, (string) $portalItem->created); ?>
          <article class="news-portal__news-card"><a href="<?php echo Route::_(RouteHelper::getArticleRoute($portalItem->id . ':' . $portalItem->alias, $portalItem->catid, $portalItem->language)); ?>"><span class="news-portal__news-image"><?php if ($portalItemImage) : ?><img src="<?php echo $this->escape($portalItemImage); ?>" alt="" width="640" height="400" loading="lazy" decoding="async"><?php else : ?><span aria-hidden="true">PN</span><?php endif; ?></span><span class="news-portal__news-copy"><time datetime="<?php echo $portalDateTime; ?>"><?php echo $portalDate; ?></time><strong><?php echo $this->escape($portalItem->title); ?></strong></span></a></article>
        <?php endforeach; ?>
        </div>
      </section>
      <section class="news-portal__section news-portal__section--announcements" aria-labelledby="portal-announcement-title"><header class="news-portal__heading"><div><p>Pengumuman</p><h2 id="portal-announcement-title">Pemberitahuan resmi</h2></div><a href="<?php echo Route::_('/pengumuman'); ?>">Semua pengumuman</a></header>
        <ol class="news-portal__announcements"><?php foreach ($portalAnnouncements as $portalItem) : [$portalDateTime, $portalDate] = $formatPortalDate((string) $portalItem->publish_up, (string) $portalItem->created); ?><li><a href="<?php echo Route::_(RouteHelper::getArticleRoute($portalItem->id . ':' . $portalItem->alias, $portalItem->catid, $portalItem->language)); ?>"><time datetime="<?php echo $portalDateTime; ?>"><?php echo $portalDate; ?></time><span><?php echo $this->escape($portalItem->title); ?></span></a></li><?php endforeach; ?></ol>
      </section>
      <aside class="news-portal__trust" aria-label="Informasi resmi dan kontak"><div><strong>Periksa sebelum membagikan</strong><span>Waspadai informasi yang mengatasnamakan PN Natuna. Pastikan sumbernya kanal resmi.</span></div><a href="<?php echo Route::_('/kontak'); ?>">Konfirmasi ke PN Natuna</a></aside>
      <nav class="news-portal__social" aria-label="Media sosial resmi"><a href="https://www.instagram.com/pn.natuna/" target="_blank" rel="noopener noreferrer">Instagram</a><a href="https://www.facebook.com/pengadilannegerinatuna" target="_blank" rel="noopener noreferrer">Facebook</a><a href="https://www.youtube.com/@PengadilanNegeriNatuna" target="_blank" rel="noopener noreferrer">YouTube</a></nav>
    </section>
    <?php
    return;
}


// Resolve the complete category branch in one query. This also handles direct children.
$aliases = [];
$categoryId = (int) $item->catid;
if ($categoryId > 0) {
    $query = $db->getQuery(true)
        ->select($db->quoteName(['node.alias', 'node.id']))
        ->from($db->quoteName('#__categories', 'node'))
        ->innerJoin($db->quoteName('#__categories', 'current') . ' ON node.lft <= current.lft AND node.rgt >= current.rgt')
        ->where($db->quoteName('current.id') . ' = :categoryId')
        ->bind(':categoryId', $categoryId, Joomla\Database\ParameterType::INTEGER)
        ->order($db->quoteName('node.lft'));
    $aliases = array_column($db->setQuery($query)->loadAssocList(), 'alias');
}

$channel = in_array('pengumuman', $aliases, true) ? 'announcement' : (in_array('berita', $aliases, true) ? 'news' : null);
if ($channel === null) {
    require JPATH_BASE . '/components/com_content/tmpl/article/default.php';
    return;
}

$user = $this->getCurrentUser();
$canEdit = $params->get('access-edit');
$hasAccess = $params->get('access-view');
$nowSql = Factory::getDate()->format('Y-m-d H:i:s');
$isNotPublishedYet = $item->publish_up > $nowSql;
$isExpired = !empty($item->publish_down) && $item->publish_down < $nowSql;
$publishedRaw = !empty($item->publish_up) && $item->publish_up !== $db->getNullDate() && $item->publish_up > '2000-01-02 00:00:00' ? $item->publish_up : $item->created;
$modifiedRaw = !empty($item->modified) && $item->modified !== $db->getNullDate() ? $item->modified : null;
$published = Factory::getDate($publishedRaw);
$modified = $modifiedRaw ? Factory::getDate($modifiedRaw) : null;
$months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$formatIdDate = static function ($date) use ($months): string {
    return (int) $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
};
$publishedLabel = $formatIdDate($published);
$modifiedLabel = $modified ? $formatIdDate($modified) : '';
$isMateriallyModified = $modified && abs($modified->toUnix() - $published->toUnix()) >= 86400;
$wordCount = str_word_count(strip_tags((string) ($item->text ?: $item->introtext)));
$readingMinutes = max(1, (int) ceil($wordCount / 200));
$basePath = $channel === 'news' ? '/berita' : '/pengumuman';
$channelLabel = $channel === 'news' ? 'Berita PN Natuna' : 'Pengumuman Resmi';
$schemaType = $channel === 'news' ? 'NewsArticle' : 'Article';

$images = json_decode((string) $item->images, true) ?: [];
$image = trim((string) ($images['image_fulltext'] ?? '')) ?: trim((string) ($images['image_intro'] ?? ''));
$imageAlt = trim((string) ($images['image_fulltext_alt'] ?? '')) ?: trim((string) ($images['image_intro_alt'] ?? ''));
$imageCaption = trim((string) ($images['image_fulltext_caption'] ?? '')) ?: trim((string) ($images['image_intro_caption'] ?? ''));
$imageUrl = $image && !preg_match('#^(?:https?:)?//#i', $image) ? '/' . ltrim($image, '/') : $image;

// Related items: same current category, one bounded query, all Joomla visibility windows respected.
$levels = array_map('intval', $user->getAuthorisedViewLevels());
$relatedQuery = $db->getQuery(true)
    ->select($db->quoteName(['a.id', 'a.title', 'a.alias', 'a.catid', 'a.language', 'a.publish_up', 'a.created', 'a.images']))
    ->from($db->quoteName('#__content', 'a'))
    ->where($db->quoteName('a.catid') . ' = :relatedCategory')
    ->where($db->quoteName('a.id') . ' <> :currentId')
    ->where($db->quoteName('a.state') . ' = 1')
    ->whereIn($db->quoteName('a.access'), $levels)
    ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' <= :nowUp)')
    ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' = ' . $db->quote($db->getNullDate()) . ' OR ' . $db->quoteName('a.publish_down') . ' >= :nowDown)')
    ->bind(':relatedCategory', $categoryId, Joomla\Database\ParameterType::INTEGER)
    ->bind(':currentId', $item->id, Joomla\Database\ParameterType::INTEGER)
    ->bind(':nowUp', $nowSql)
    ->bind(':nowDown', $nowSql)
    ->order('CASE WHEN ' . $db->quoteName('a.publish_up') . ' > ' . $db->quote('2000-01-02 00:00:00') . ' THEN ' . $db->quoteName('a.publish_up') . ' ELSE ' . $db->quoteName('a.created') . ' END DESC');
$relatedLanguage = $item->language === '*' ? $app->getLanguage()->getTag() : $item->language;
$relatedQuery->whereIn($db->quoteName('a.language'), ['*', $relatedLanguage], Joomla\Database\ParameterType::STRING);
$related = $db->setQuery($relatedQuery, 0, 3)->loadObjectList();
$currentUrl = Uri::getInstance()->toString(['scheme', 'host', 'port', 'path', 'query']);
?>
<article class="editorial-article editorial-article--<?php echo $channel; ?>" itemscope itemtype="https://schema.org/<?php echo $schemaType; ?>">
  <meta itemprop="inLanguage" content="<?php echo $this->escape($item->language === '*' ? $app->get('language') : $item->language); ?>">
  <header class="editorial-article__header">
    <a class="editorial-article__back" href="<?php echo Route::_($basePath); ?>">← <?php echo $channelLabel; ?></a>
    <p class="editorial-article__kicker"><?php echo $channelLabel; ?></p>
    <?php if ($params->get('show_title', 1)) : ?><h1 class="editorial-article__title" itemprop="headline"><?php echo $this->escape($item->title); ?></h1><?php endif; ?>
    <div class="editorial-article__meta">
      <time itemprop="datePublished" datetime="<?php echo $published->format(DATE_ATOM); ?>"><?php echo $publishedLabel; ?></time>
      <span><?php echo $this->escape($item->category_title); ?></span>
      <span><?php echo $readingMinutes; ?> menit baca</span>
    </div>
    <?php if ($modified) : ?><meta itemprop="dateModified" content="<?php echo $modified->format(DATE_ATOM); ?>"><?php endif; ?>
    <?php if ($item->state == ContentComponent::CONDITION_UNPUBLISHED || $isNotPublishedYet || $isExpired) : ?>
      <div class="editorial-article__status" role="status"><?php echo $item->state == ContentComponent::CONDITION_UNPUBLISHED ? 'Belum diterbitkan' : ($isNotPublishedYet ? 'Terjadwal' : 'Kedaluwarsa'); ?></div>
    <?php endif; ?>
    <?php if ($canEdit) echo LayoutHelper::render('joomla.content.icons', ['params' => $params, 'item' => $item]); ?>
    <?php echo $item->event->afterDisplayTitle; ?>
  </header>
  <?php if (!empty($item->pagination) && !$item->paginationposition && $item->paginationrelative) echo $item->pagination; ?>

  <?php if ($channel === 'news' && $image) : ?>
    <figure class="editorial-article__hero" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
      <img src="<?php echo $this->escape($imageUrl); ?>" alt="<?php echo $this->escape($imageAlt); ?>" width="1200" height="675" itemprop="contentUrl">
      <?php if ($imageCaption) : ?><figcaption><?php echo $this->escape($imageCaption); ?></figcaption><?php endif; ?>
    </figure>
  <?php elseif ($channel === 'announcement' && $image) : ?>
    <figure class="editorial-article__hero editorial-article__hero--document"><img src="<?php echo $this->escape($imageUrl); ?>" alt="<?php echo $this->escape($imageAlt); ?>" width="1200" height="675"><?php if ($imageCaption) : ?><figcaption><?php echo $this->escape($imageCaption); ?></figcaption><?php endif; ?></figure>
  <?php elseif ($channel === 'announcement') : ?>
    <figure class="editorial-article__hero editorial-article__hero--document"><img src="/images/brand/pengumuman-resmi-pn-natuna.webp" alt="Pengumuman Resmi Pengadilan Negeri Natuna" width="1200" height="675"></figure>
  <?php else : ?>
    <div class="editorial-article__hero-fallback" role="img" aria-label="<?php echo $this->escape($channelLabel); ?>"><span>PN</span><strong>Pengadilan Negeri Natuna</strong><small><?php echo $channelLabel; ?></small></div>
  <?php endif; ?>

  <?php echo $item->event->beforeDisplayContent; ?>
  <?php if ($hasAccess) : ?>
    <?php if ((int) $params->get('urls_position', 0) === 0) echo $this->loadTemplate('links'); ?>
    <?php if (!empty($item->pagination) && !$item->paginationposition && !$item->paginationrelative) echo $item->pagination; ?>
    <?php if (isset($item->toc)) echo $item->toc; ?>
    <div class="editorial-article__body" itemprop="articleBody"><?php echo $item->text; ?></div>
    <?php if ($params->get('show_tags', 1) && !empty($item->tags->itemTags)) : $item->tagLayout = new FileLayout('joomla.content.tags'); echo $item->tagLayout->render($item->tags->itemTags); endif; ?>
    <?php if (!empty($item->pagination) && $item->paginationposition && !$item->paginationrelative) echo $item->pagination; ?>
    <?php if ((int) $params->get('urls_position', 0) === 1) echo $this->loadTemplate('links'); ?>
  <?php elseif ($params->get('show_noauth') && $user->guest) : ?>
    <?php echo LayoutHelper::render('joomla.content.intro_image', $item); ?>
    <?php echo HTMLHelper::_('content.prepare', $item->introtext); ?>
    <?php if ($params->get('show_readmore') && $item->fulltext != null) : ?>
      <?php $active = $app->getMenu()->getActive(); $itemId = $active ? $active->id : 0; $loginLink = new Uri(Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId, false)); $loginLink->setVar('return', base64_encode(RouteHelper::getArticleRoute($item->slug, $item->catid, $item->language))); ?>
      <?php echo LayoutHelper::render('joomla.content.readmore', ['item' => $item, 'params' => $params, 'link' => $loginLink]); ?>
    <?php endif; ?>
  <?php endif; ?>
  <?php if (!empty($item->pagination) && $item->paginationposition && $item->paginationrelative) echo $item->pagination; ?>
  <?php echo $item->event->afterDisplayContent; ?>

  <footer class="editorial-article__footer">
    <div class="editorial-article__publication"><strong>Diterbitkan oleh Pengadilan Negeri Natuna</strong><span><?php echo $publishedLabel; ?><?php echo $isMateriallyModified ? ' · Diperbarui ' . $modifiedLabel : ''; ?></span></div>
    <div class="editorial-article__share" data-editorial-share data-title="<?php echo $this->escape($item->title); ?>" data-url="<?php echo $this->escape($currentUrl); ?>">
      <span>Bagikan</span>
      <button type="button" data-share-native>Bagikan artikel</button>
      <a href="https://wa.me/?text=<?php echo rawurlencode($item->title . ' ' . $currentUrl); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
      <button type="button" data-share-copy>Salin tautan</button>
      <span class="editorial-article__share-status" data-share-status aria-live="polite"></span>
    </div>
  </footer>

  <?php if ($related) : ?>
  <section class="editorial-article__related" aria-labelledby="related-heading"><h2 id="related-heading"><?php echo $channel === 'news' ? 'Berita terkait' : 'Pengumuman lainnya'; ?></h2><div class="editorial-article__related-grid">
    <?php foreach ($related as $relatedItem) : $relatedImages = json_decode((string) $relatedItem->images, true) ?: []; $relatedImage = trim((string) ($relatedImages['image_intro'] ?? '')) ?: trim((string) ($relatedImages['image_fulltext'] ?? '')); $relatedImageUrl = $relatedImage && !preg_match('#^(?:https?:)?//#i', $relatedImage) ? '/' . ltrim($relatedImage, '/') : $relatedImage; $relatedDateRaw = !empty($relatedItem->publish_up) && $relatedItem->publish_up > '2000-01-02 00:00:00' ? $relatedItem->publish_up : $relatedItem->created; $relatedDate = Factory::getDate($relatedDateRaw); ?>
      <a class="editorial-article__related-card" href="<?php echo Route::_(RouteHelper::getArticleRoute($relatedItem->id . ':' . $relatedItem->alias, $relatedItem->catid, $relatedItem->language)); ?>">
        <?php if ($relatedImage) : ?><img src="<?php echo $this->escape($relatedImageUrl); ?>" alt="" width="480" height="270" loading="lazy"><?php elseif ($channel === 'announcement') : ?><img src="/images/brand/pengumuman-resmi-pn-natuna.webp" alt="" width="480" height="270" loading="lazy"><?php else : ?><span class="editorial-article__related-fallback" aria-hidden="true">PN</span><?php endif; ?>
        <time datetime="<?php echo $relatedDate->format(DATE_ATOM); ?>"><?php echo $formatIdDate($relatedDate); ?></time><strong><?php echo $this->escape($relatedItem->title); ?></strong>
      </a>
    <?php endforeach; ?>
  </div></section>
  <?php endif; ?>
  <a class="editorial-article__return" href="<?php echo Route::_($basePath); ?>">Kembali ke <?php echo $channel === 'news' ? 'berita' : 'pengumuman'; ?></a>
</article>
