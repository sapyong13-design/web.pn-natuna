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
    : '';
$items = array_merge($this->lead_items ?? [], $this->intro_items ?? []);

// Arsip kanal ini 84 berita yang terbagi 14 halaman berisi enam. Tanpa penanda tahun,
// warga yang mencari pengumuman Desember 2025 hanya punya sepuluh tombol bernomor dan
// harus menebak. Indeks ini menghitung di posisi ke berapa tiap tahun dimulai, memakai
// urutan yang sama persis dengan yang dipakai daftar - diuji terhadap seluruh 84 artikel,
// nol posisi berbeda - lalu menautkannya sebagai `?start=` supaya tahun itu mendarat di
// puncak halaman, bukan di tengah.
$db = Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class);
$nowSql = Factory::getDate()->toSql();
$effectiveDate = 'CASE WHEN ' . $db->quoteName('a.publish_up') . ' > ' . $db->quote('2000-01-02 00:00:00')
    . ' THEN ' . $db->quoteName('a.publish_up') . ' ELSE ' . $db->quoteName('a.created') . ' END';
$archiveQuery = $db->getQuery(true)
    ->select('YEAR(' . $effectiveDate . ') AS terbit')
    ->from($db->quoteName('#__content', 'a'))
    ->where($db->quoteName('a.catid') . ' = :archiveCategory')
    ->where($db->quoteName('a.state') . ' = 1')
    ->whereIn($db->quoteName('a.access'), array_map('intval', Factory::getUser()->getAuthorisedViewLevels()))
    ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' <= :archiveUp)')
    ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' = ' . $db->quote($db->getNullDate()) . ' OR ' . $db->quoteName('a.publish_down') . ' >= :archiveDown)')
    ->bind(':archiveCategory', $this->category->id, Joomla\Database\ParameterType::INTEGER)
    ->bind(':archiveUp', $nowSql)
    ->bind(':archiveDown', $nowSql)
    ->order($effectiveDate . ' DESC');
$archiveYears = [];
foreach ($db->setQuery($archiveQuery)->loadColumn() as $position => $year) {
    $year = (int) $year;
    if (!isset($archiveYears[$year])) {
        $archiveYears[$year] = ['mulai' => $position, 'jumlah' => 0];
    }
    $archiveYears[$year]['jumlah']++;
}
// Tahun yang sedang dibaca ditandai supaya pembaca tahu posisinya di dalam arsip.
// `aria-current` harus tunggal: satu halaman berisi enam kartu bisa membentang tiga
// tahun - halaman pertama pengumuman melakukannya - dan menandai ketiganya berarti
// mengatakan pembaca berada di tiga tempat sekaligus. Yang ditandai tahun kartu
// teratas, karena itulah tahun yang mengawali halaman ini.
$currentArchiveYear = null;
if ($items !== []) {
    $firstItem = reset($items);
    $raw = !empty($firstItem->publish_up) && $firstItem->publish_up > '2000-01-02 00:00:00' ? $firstItem->publish_up : $firstItem->created;
    $currentArchiveYear = (int) Factory::getDate($raw)->format('Y');
}
$archiveNoun = $isAnnouncement ? 'pengumuman' : 'berita';
$archiveBase = $isAnnouncement ? '/berita-dan-pengumuman/pengumuman' : '/berita-dan-pengumuman/berita';
?>
<div class="com-content-category-blog blog news-channel <?php echo $channelClass; ?>">
    <header class="news-channel-hero">
        <?php // Pita hero cuma setinggi 122px di ponsel, tetapi berkas aslinya 1536px/229 KB
              // dan ber-fetchpriority tinggi - ia merebut jalur unduh sebelum satu judul
              // pun tampil. Varian responsifnya dibuat `tools/make-image-variants.php`. ?>
        <img src="/images/hero/gedung-pn-natuna-2026.webp" srcset="/images/hero/gedung-pn-natuna-2026-400.webp 400w, /images/hero/gedung-pn-natuna-2026-800.webp 800w, /images/hero/gedung-pn-natuna-2026-1200.webp 1200w" sizes="(max-width: 760px) 100vw, 76vw" alt="Gedung Pengadilan Negeri Natuna" width="1536" height="1024" fetchpriority="high">
        <div class="news-channel-hero__overlay">
            <p class="section-kicker"><?php echo $channelKicker; ?></p>
            <h1><?php echo $this->escape($channelTitle); ?></h1>
            <?php if ($channelIntro) : ?>
                <p><?php echo $this->escape($channelIntro); ?></p>
            <?php endif; ?>
        </div>
    </header>
    <nav class="news-channel-tabs" aria-label="Pilih kanal informasi">
        <a class="<?php echo $isAnnouncement ? '' : 'is-active'; ?>"<?php echo $isAnnouncement ? '' : ' aria-current="page"'; ?> href="/berita-dan-pengumuman/berita">Berita Terkini</a>
        <a class="<?php echo $isAnnouncement ? 'is-active' : ''; ?>"<?php echo $isAnnouncement ? ' aria-current="page"' : ''; ?> href="/berita-dan-pengumuman/pengumuman">Pengumuman Resmi</a>
    </nav>

    <?php if (count($archiveYears) > 1) : ?>
        <nav class="news-channel-archive" aria-label="Loncat ke tahun terbit">
            <span class="news-channel-archive__label">Arsip</span>
            <ul>
                <?php foreach ($archiveYears as $year => $entry) : ?>
                    <li><a href="<?php echo $this->escape($archiveBase . ($entry['mulai'] > 0 ? '?start=' . $entry['mulai'] : '')); ?>"<?php echo $year === $currentArchiveYear ? ' aria-current="true"' : ''; ?>><?php echo $year; ?><span><?php echo $entry['jumlah']; ?><i class="visually-hidden"> <?php echo $archiveNoun; ?></i></span></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <?php if (empty($items) && empty($this->link_items)) : ?>
        <?php if ($this->params->get('show_no_articles', 1)) : ?>
            <div class="alert alert-info">
                <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                Belum ada artikel pada halaman ini. <a href="<?php echo $isAnnouncement ? '/berita-dan-pengumuman/pengumuman' : '/berita-dan-pengumuman/berita'; ?>">Kembali ke halaman pertama <?php echo $isAnnouncement ? 'pengumuman' : 'berita'; ?></a>.
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

    <?php // Halaman di luar jangkauan (`?start=996`) dulu tetap mencetak "Page 14 of 14"
          // di atas nol artikel - penghitung yang mengklaim pembaca berada di halaman yang
          // sebenarnya tidak ia lihat. Kalau tidak ada kartu, tidak ada yang dinavigasikan. ?>
    <?php if (!empty($items) && ($this->params->def('show_pagination', 1) == 1 || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
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
