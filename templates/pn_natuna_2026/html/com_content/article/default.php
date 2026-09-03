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
// AMPUH directory must dispatch before transparency/news article rendering.
if (require __DIR__ . '/ampuh-directory.php') {
    return;
}
// Transparency family must dispatch before curated/news article rendering.
if (require __DIR__ . '/transparency-family.php') {
    return;
}
// Article 53 is the curated Berita dan Pengumuman portal, not an editorial detail page.
$variantHelper = JPATH_SITE . '/plugins/content/pnnatunaimagevariants/src/Helper/VariantMaker.php';
if (is_file($variantHelper)) {
    require_once $variantHelper;
}
$articleImage = static function (string $images, string $introtext = '', string $fulltext = ''): string {
    $decoded = json_decode($images, true) ?: [];
    $image = trim((string) ($decoded['image_fulltext'] ?? '')) ?: trim((string) ($decoded['image_intro'] ?? ''));
    if ($image === '' && class_exists(\Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::class)) {
        $image = \Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::firstImage($introtext, $fulltext);
    }

    return $image !== '' && !preg_match('#^(?:https?:)?//#i', $image) ? '/' . ltrim($image, '/') : $image;
};
if ((int) $item->id === 53) {
    $nowSql = Factory::getDate()->format('Y-m-d H:i:s');
    $levels = array_map('intval', $this->getCurrentUser()->getAuthorisedViewLevels());
    $language = $app->getLanguage()->getTag();
    $portalItems = static function (int $categoryId, int $limit, string $parameter) use ($db, $levels, $language, $nowSql): array {
        $query = $db->getQuery(true)
            ->select($db->quoteName(['a.id', 'a.title', 'a.alias', 'a.catid', 'a.language', 'a.publish_up', 'a.created', 'a.images', 'a.introtext', 'a.fulltext']))
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

    ?>
    <section class="news-portal" aria-labelledby="news-portal-title">
      <section class="news-portal__hero">
        <img src="/images/hero/gedung-pn-natuna-2026.webp" alt="Gedung Pengadilan Negeri Natuna" width="1536" height="1024" fetchpriority="high">
        <div class="news-portal__hero-overlay"><p>Informasi resmi</p><h1 id="news-portal-title">Berita dan Pengumuman</h1><span>Informasi terkini Pengadilan Negeri Natuna untuk masyarakat dan para pencari keadilan.</span></div>
      </section>
      <nav class="news-portal__channels" aria-label="Kanal informasi"><a href="<?php echo Route::_('/berita'); ?>" aria-label="Buka semua berita">Berita</a><a href="<?php echo Route::_('/pengumuman'); ?>" aria-label="Buka semua pengumuman">Pengumuman</a></nav>
      <section class="news-portal__section" aria-labelledby="portal-news-title"><header class="news-portal__heading"><div><p>Berita</p><h2 id="portal-news-title">Kabar dari pengadilan</h2></div><a href="<?php echo Route::_('/berita'); ?>">Semua berita</a></header>
        <div class="news-portal__news-grid">
        <?php foreach ($portalNews as $portalItem) : $portalItemImage = $articleImage((string) ($portalItem->images ?? ''), (string) ($portalItem->introtext ?? ''), (string) ($portalItem->fulltext ?? '')); [$portalDateTime, $portalDate] = $formatPortalDate((string) ($portalItem->publish_up ?? ''), (string) ($portalItem->created ?? '')); ?>
          <article class="news-portal__news-card"><a href="<?php echo Route::_(RouteHelper::getArticleRoute($portalItem->id . ':' . $portalItem->alias, $portalItem->catid, $portalItem->language)); ?>"><span class="news-portal__news-image"><?php if ($portalItemImage) : ?><img src="<?php echo $this->escape($portalItemImage); ?>" alt="" width="640" height="400" loading="lazy" decoding="async"><?php else : ?><span aria-hidden="true">PN</span><?php endif; ?></span><span class="news-portal__news-copy"><time datetime="<?php echo $portalDateTime; ?>"><?php echo $portalDate; ?></time><strong><?php echo $this->escape($portalItem->title); ?></strong></span></a></article>
        <?php endforeach; ?>
        </div>
      </section>
      <section class="news-portal__section news-portal__section--announcements" aria-labelledby="portal-announcement-title"><header class="news-portal__heading"><div><p>Pengumuman</p><h2 id="portal-announcement-title">Pemberitahuan resmi</h2></div><a href="<?php echo Route::_('/pengumuman'); ?>">Semua pengumuman</a></header>
        <ol class="news-portal__announcements"><?php foreach ($portalAnnouncements as $portalItem) : [$portalDateTime, $portalDate] = $formatPortalDate((string) $portalItem->publish_up, (string) $portalItem->created); ?><li><a href="<?php echo Route::_(RouteHelper::getArticleRoute($portalItem->id . ':' . $portalItem->alias, $portalItem->catid, $portalItem->language)); ?>"><time datetime="<?php echo $portalDateTime; ?>"><?php echo $portalDate; ?></time><span><?php echo $this->escape($portalItem->title); ?></span></a></li><?php endforeach; ?></ol>
      </section>
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
$profilePath = rtrim(Uri::getInstance()->getPath(), '/');
$profilePages = [
    '/profil-pengadilan/kata-sambutan' => 'Sambutan Ketua Pengadilan',
    '/profil-pengadilan/sejarah-pengadilan' => 'Sejarah',
    '/profil-pengadilan/visi-misi' => 'Visi & Misi',
    '/profil-pengadilan/tugas-pokok-fungsi' => 'Tugas & Fungsi',
    '/profil-pengadilan/struktur-organisasi' => 'Struktur',
    '/profil-pengadilan/wilayah-yurisdiksi' => 'Wilayah Hukum',
    '/profil-pengadilan/profil-hakim' => 'Hakim',
    '/profil-pengadilan/profil-kepaniteraan' => 'Kepaniteraan',
    '/profil-pengadilan/profil-kesekretariatan' => 'Kesekretariatan',
    '/profil-pengadilan/profil-pppk' => 'PPPK',
];
$profileRegistryPaths = [
    '/profil-pengadilan/profil-kepaniteraan/kepaniteraan-pidana' => 'Pidana',
    '/profil-pengadilan/profil-kepaniteraan/kepaniteraan-perdata' => 'Perdata',
    '/profil-pengadilan/profil-kepaniteraan/kepaniteraan-hukum' => 'Hukum',
    '/profil-pengadilan/profil-kepaniteraan/kepaniteraan-khusus-perikanan' => 'Perikanan',
];
$profileSecretariatPaths = [
    '/profil-pengadilan/profil-kesekretariatan/subbagian-kepegawaian-ortala' => 'Kepegawaian, Organisasi, dan Tata Laksana',
    '/profil-pengadilan/profil-kesekretariatan/subbagian-ptip' => 'Perencanaan, Teknologi Informasi, dan Pelaporan (PTIP)',
    '/profil-pengadilan/profil-kesekretariatan/subbagian-umum-keuangan' => 'Umum dan Keuangan',
];
$profileRoutes = $profilePages + $profileRegistryPaths + $profileSecretariatPaths;
$profileUnitPaths = str_starts_with($profilePath, '/profil-pengadilan/profil-kepaniteraan')
    ? $profileRegistryPaths
    : (str_starts_with($profilePath, '/profil-pengadilan/profil-kesekretariatan') ? $profileSecretariatPaths : []);
$showProfileUnits = $profileUnitPaths !== [];
$profileUnitLabel = str_starts_with($profilePath, '/profil-pengadilan/profil-kepaniteraan')
    ? 'Unit Kepaniteraan'
    : 'Bagian Kesekretariatan';
if ($channel === null && str_starts_with($profilePath, '/profil-pengadilan/') && isset($profileRoutes[$profilePath])) {
    ?><nav class="svc-subnav" aria-label="Navigasi Tentang Pengadilan"><a href="/profil-pengadilan">Ringkasan</a><?php foreach ($profilePages as $route => $label) : ?><a href="<?php echo $this->escape($route); ?>"<?php echo $profilePath === $route ? ' aria-current="page"' : ''; ?>><?php echo $this->escape($label); ?></a><?php endforeach; ?></nav><?php if ($showProfileUnits) : ?><nav class="svc-subnav svc-subnav--unit" aria-label="<?php echo $this->escape($profileUnitLabel); ?>"><span class="svc-subnav__label"><?php echo $this->escape($profileUnitLabel); ?></span><?php foreach ($profileUnitPaths as $route => $label) : ?><a href="<?php echo $this->escape($route); ?>"<?php echo $profilePath === $route ? ' aria-current="page"' : ''; ?>><?php echo $this->escape($label); ?></a><?php endforeach; ?></nav><?php endif;
}
$isProfileRoute = $channel === null && str_starts_with($profilePath, '/profil-pengadilan/') && isset($profileRoutes[$profilePath]);
if ($isProfileRoute) {
    foreach (['show_author', 'show_category', 'show_parent_category', 'show_create_date', 'show_modify_date', 'show_publish_date', 'show_hits', 'show_tags', 'show_associations', 'show_item_navigation'] as $profileHiddenOption) {
        $item->params->set($profileHiddenOption, 0);
        $this->params->set($profileHiddenOption, 0);
    }
    $item->event->afterDisplayContent = '';
}
if ($channel === null) {
    if ($isProfileRoute) {
        ob_start();
        require JPATH_BASE . '/components/com_content/tmpl/article/default.php';
        $profileArticleHtml = (string) ob_get_clean();
        echo preg_replace('/<nav[^>]*class=["\'][^"\']*\bpagenavigation\b[^"\']*["\'][^>]*>[\s\S]*?<\/nav>/i', '', $profileArticleHtml);
    } else {
        require JPATH_BASE . '/components/com_content/tmpl/article/default.php';
    }
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
if ($image === '' && class_exists(\Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::class)) {
    $image = \Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::firstImage(
        (string) ($item->introtext ?? ''),
        (string) ($item->fulltext ?? ''),
        (string) ($item->text ?? '')
    );
}
// Media Manager menyimpan `foo.jpg#joomlaImage://local-images/foo.jpg?width=...`.
// Fragmen itu tidak boleh ikut tercetak ke atribut src.
$image = strtok($image, '#');
$imageAlt = trim((string) ($images['image_fulltext_alt'] ?? '')) ?: trim((string) ($images['image_intro_alt'] ?? ''));
$imageCaption = trim((string) ($images['image_fulltext_caption'] ?? '')) ?: trim((string) ($images['image_intro_caption'] ?? ''));
$imageUrl = $image && !preg_match('#^(?:https?:)?//#i', $image) ? '/' . ltrim($image, '/') : $image;

// Editorial photography: responsive candidates plus a caption derived from the article
// itself, so a photo never stands on the page without saying where it comes from.
// Aturan srcset-nya dibagi dengan kartu daftar lewat kelas milik plugin varian, supaya
$photoSrcset = static function (string $src): string {
    return class_exists(\Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::class)
        ? \Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::srcset(JPATH_SITE, $src)
        : '';
};
$photoSizes = '(max-width: 760px) 100vw, 900px';
// Kapsi foto adalah keterangan fotonya, bukan cap lembaga yang diulang. Deskripsi yang
// benar sudah ditulis redaksi di atribut `alt` - selama ini hanya dibacakan pembaca
// layar, sementara pembaca awas menerima "Dokumentasi ... <tanggal terbit>" yang bahkan
// bisa bertentangan dengan dateline artikelnya sendiri. Kredit lembaga tidak hilang: ia
// sudah berdiri satu kali di kaki artikel.
$photoCaption = static function (string $tag) use ($item): string {
    $alt = preg_match('/alt="([^"]*)"/', $tag, $m) ? trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')) : '';
    $judul = trim((string) $item->title);
    if ($alt !== '' && mb_strlen($alt) > 12 && mb_strtolower($alt) !== mb_strtolower($judul)) {
        return $alt;
    }

    return 'Dokumentasi Pengadilan Negeri Natuna';
};
$heroSrcset = $imageUrl ? $photoSrcset($imageUrl) : '';
$heroCaption = $imageCaption
    ?: ($imageAlt !== '' && mb_strlen($imageAlt) > 12 && mb_strtolower($imageAlt) !== mb_strtolower(trim((string) $item->title))
        ? $imageAlt
        : 'Dokumentasi Pengadilan Negeri Natuna');
// Inline figures live in the article body (Joomla content), so the responsive
// candidates and the fallback caption are attached at render time, not stored.
$articleBody = preg_replace_callback(
    '#<figure class="editorial-article__figure">(.*?)</figure>#s',
    static function (array $figure) use ($photoSrcset, $photoSizes, $photoCaption): string {
        $inner = preg_replace_callback(
            '#<img\b([^>]*?)src="([^"]+)"([^>]*)>#',
            static function (array $img) use ($photoSrcset, $photoSizes): string {
                if (str_contains($img[0], 'srcset=')) {
                    return $img[0];
                }
                $srcset = $photoSrcset($img[2]);
                return $srcset === ''
                    ? $img[0]
                    : '<img' . $img[1] . 'src="' . $img[2] . '"' . $img[3] . ' srcset="' . $srcset . '" sizes="' . $photoSizes . '">';
            },
            $figure[1]
        );
        if (!str_contains($inner, '<figcaption')) {
            $inner .= '<figcaption>' . htmlspecialchars($photoCaption($inner), ENT_QUOTES, 'UTF-8') . '</figcaption>';
        }
        return '<figure class="editorial-article__figure">' . $inner . '</figure>';
    },
    (string) $item->text
);

// Pada 75 dari 81 berita berhero, berkas foto yang sama dicetak sekali lagi di dalam
// badan artikel - tanpa bingkai, tanpa kapsi, beberapa ratus piksel di bawah yang
// pertama. Penyebabnya kebiasaan redaksi: satu foto diunggah ke kolom intro lalu
// ditempel lagi ke badan tulisan. Pembacanya bertemu foto yang sama dua kali dan
// mengira halamannya rusak. Salinan kedua dibuang di sini, bukan di database, supaya
// artikel lama tidak perlu disunting satu per satu dan kebiasaan itu boleh berlanjut.
$photoKey = static function (string $src): string {
    $name = basename(strtok($src, '?') ?: $src);
    $name = preg_replace('/-(?:400|800|1200)\.webp$/i', '', $name);

    return mb_strtolower(preg_replace('/\.[a-z0-9]+$/i', '', $name));
};
if ($imageUrl !== '') {
    $heroKey = $photoKey(strtok($imageUrl, '#') ?: $imageUrl);
    $articleBody = preg_replace_callback(
        '#<img\b[^>]*src="([^"]+)"[^>]*>#i',
        static function (array $img) use ($photoKey, $heroKey): string {
            return $photoKey(strtok($img[1], '#') ?: $img[1]) === $heroKey ? '' : $img[0];
        },
        $articleBody
    );
    // Pembungkus yang ditinggalkannya - `<p>`, `<figure>`, atau tautan pembesar - ikut
    // dibuang supaya tidak menyisakan jeda kosong di tengah alur baca.
    $articleBody = preg_replace(
        [
            '#<a\b[^>]*>\s*</a>#i',
            '#<figure\b[^>]*>\s*(?:<figcaption\b[^>]*>.*?</figcaption>)?\s*</figure>#is',
            '#<p\b[^>]*>(?:\s|&nbsp;|<br\s*/?>)*</p>#i',
        ],
        '',
        $articleBody
    );
}

// Paragraf pembuka kadang tersimpan di intro dan diulang lagi di badan tulisan, sehingga
// Joomla mencetaknya dua kali - sekali dengan dateline, sekali polos. Yang kedua dibuang.
$paragraphSeen = [];
$articleBody = preg_replace_callback(
    '#<p\b[^>]*>(.*?)</p>#is',
    static function (array $p) use (&$paragraphSeen): string {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($p[1]), ENT_QUOTES, 'UTF-8')));
        if (mb_strlen($text) < 60) {
            return $p[0];
        }
        $key = mb_strtolower($text);
        if (isset($paragraphSeen[$key])) {
            return '';
        }
        $paragraphSeen[$key] = true;

        return $p[0];
    },
    $articleBody
);

// Enam berita yang direstrukturisasi memakai bingkai editorial, tetapi puluhan berita
// warisan menaruh fotonya sebagai `<img>` telanjang di badan artikel. Tanpa pass ini
// mereka tidak pernah mendapat `srcset` sama sekali - foto 4032px seberat 1,5 MB
// dikirim utuh ke ponsel. Yang sudah punya srcset dari pass bingkai dilewati.
// Editor juga menuliskan fragmen `#joomlaImage://...` ke dalam tag; fragmen itu
// dibuang dari `src` supaya tidak ikut tercetak ke halaman.
// `width`/`height` ikut dipasang: tanpa keduanya peramban tidak tahu ruang yang harus
// disediakan, sehingga paragraf yang sedang dibaca melompat turun setiap satu foto tiba
// - persis kondisi pembaca di koneksi kepulauan yang jadi persona utama situs ini.
$photoBox = static function (string $src): string {
    if ($src === '' || preg_match('#^(?:https?:)?//#i', $src)) {
        return '';
    }
    $size = @getimagesize(JPATH_BASE . '/' . ltrim($src, '/'));

    return $size ? ' width="' . (int) $size[0] . '" height="' . (int) $size[1] . '"' : '';
};
$articleBody = preg_replace_callback(
    '#<img\b([^>]*?)src="([^"]+)"([^>]*)>#i',
    static function (array $img) use ($photoSrcset, $photoSizes, $photoBox): string {
        $clean = strtok($img[2], '#');
        $box = preg_match('/\swidth="/i', $img[0]) && preg_match('/\sheight="/i', $img[0]) ? '' : $photoBox($clean);
        if (str_contains($img[0], 'srcset=')) {
            $tag = $clean === $img[2]
                ? $img[0]
                : str_replace('src="' . $img[2] . '"', 'src="' . $clean . '"', $img[0]);

            return $box === '' ? $tag : substr($tag, 0, -1) . $box . '>';
        }
        $srcset = $photoSrcset($clean);
        if ($srcset === '') {
            return '<img' . $img[1] . 'src="' . $clean . '"' . $img[3] . $box . '>';
        }

        return '<img' . $img[1] . 'src="' . $clean . '"' . $img[3] . $box . ' srcset="' . $srcset . '" sizes="' . $photoSizes . '">';
    },
    $articleBody
);

// Foto yang berdiri berdampingan tanpa teks di antaranya dirangkai menjadi satu
// galeri. Urutan dan jumlah fotonya tidak berubah; hanya tampilannya dirapatkan
// supaya tiga bingkai satu momen tidak menumpuk setinggi tiga layar.
$articleBody = preg_replace_callback(
    '#(?:<figure class="editorial-article__figure">.*?</figure>\s*){2,}#s',
    static function (array $run): string {
        $count = substr_count($run[0], '<figure class="editorial-article__figure">');
        return '<div class="editorial-article__gallery" data-photos="' . $count . '">' . trim($run[0]) . '</div>';
    },
    $articleBody
);

// Dateline pembuka diangkat menjadi elemen kedinasan; logikanya dibagi dengan
// kontrak `tools/test_article_masthead.php` supaya bisa diuji terhadap konten asli.
$datelineHelper = dirname(__DIR__, 3) . '/article-dateline.php';
if (is_file($datelineHelper)) {
    require_once $datelineHelper;
    $articleBody = pn_natuna_article_dateline($articleBody);
}
// Artikel panjang mendapat daftar isi dari subjudulnya sendiri. Dibangun di server
// supaya anchor tetap bekerja tanpa JavaScript dan tidak menimbulkan pergeseran.
$tableOfContents = [];
$articleBody = preg_replace_callback(
    '#<h2(\s[^>]*)?>(.*?)</h2>#s',
    static function (array $heading) use (&$tableOfContents): string {
        $attributes = $heading[1] ?? '';
        $label = trim(html_entity_decode(strip_tags($heading[2]), ENT_QUOTES, 'UTF-8'));
        if ($label === '' || str_contains((string) $attributes, 'id=')) {
            return $heading[0];
        }
        $slug = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9]+/', '-', strtolower($label))), '-');
        $anchor = 'bagian-' . ($slug !== '' ? $slug : count($tableOfContents) + 1);
        $tableOfContents[] = ['anchor' => $anchor, 'label' => $label];
        return '<h2 id="' . $anchor . '"' . $attributes . '>' . $heading[2] . '</h2>';
    },
    $articleBody
);
// Satu subjudul tidak membentuk daftar; relnya hanya muncul bila benar-benar menavigasi.
$hasRail = count($tableOfContents) > 1;

// Enam berita menyebut layanan yang halamannya berdiri di navigasi yang sama,
// tetapi tidak satu pun menautinya: nol tautan di dalam badan teks. Istilah yang
// dikenali dijembatani sekali saja - artikel AMPUH menyebut namanya tujuh kali,
// dan tautan yang berulang berhenti menjadi bantuan lalu menjadi kebisingan.
// Keterangannya diringkas dari halaman kanal itu sendiri, bukan dikarang di sini.
$serviceChannels = [
    // Diuji lebih dulu daripada 'delegasi' supaya frasa "panggilan umum" utuh.
    'panggilan-umum' => [
        'route' => '/informasi-perkara/panggilan-umum',
        'label' => 'Panggilan Umum',
        'note' => 'Cara pengadilan memanggil pihak yang tidak diketahui alamatnya.',
        'patterns' => ['/panggilan umum/iu', '/tidak diketahui alamatnya/iu'],
        'public' => true,
    ],
    'delegasi' => [
        'route' => '/layanan-hukum/delegasi',
        'label' => 'Delegasi',
        'note' => 'Bantuan panggilan dan pemberitahuan antar pengadilan.',
        'patterns' => ['/panggilan dan pemberitahuan/iu', '/\bdelegasi\b/iu', '/\bpanggilan\b/iu', '/\bpemberitahuan\b/iu'],
        'public' => true,
    ],
    'zitting-plaats' => [
        'route' => '/layanan-hukum/zitting-plaats',
        'label' => 'Sidang di Luar Gedung',
        'note' => 'Zitting plaats: sidang yang didekatkan ke wilayah kepulauan.',
        'patterns' => ['/sidang keliling/iu', '/zitting plaats/iu', '/sidang di luar gedung/iu'],
        'public' => true,
    ],
    'posbakum' => [
        'route' => '/layanan-hukum/posbakum',
        'label' => 'Pos Bantuan Hukum',
        'note' => 'Bantuan hukum tanpa biaya bagi masyarakat tidak mampu.',
        'patterns' => ['/\bposbakum\b/iu', '/pos bantuan hukum/iu', '/bantuan hukum/iu'],
        'public' => true,
    ],
    'prodeo' => [
        'route' => '/layanan-hukum/prodeo',
        'label' => 'Pembebasan Biaya Perkara',
        'note' => 'Berperkara tanpa biaya bagi yang tidak mampu membayarnya.',
        'patterns' => ['/\bprodeo\b/iu', '/pembebasan biaya perkara/iu'],
        'public' => true,
    ],
    'ptsp' => [
        'route' => '/layanan-publik/jenis-layanan-ptsp',
        'label' => 'Layanan PTSP',
        'note' => 'Seluruh permohonan layanan cukup lewat satu meja.',
        'patterns' => ['/\bPTSP\b/u', '/pelayanan terpadu satu pintu/iu'],
        'public' => true,
    ],
    'pengaduan' => [
        'route' => '/layanan-publik/regulasi-pengaduan',
        'label' => 'Pengaduan',
        'note' => 'Kanal resmi pengaduan; identitas pelapor dirahasiakan.',
        'patterns' => ['/\bpengaduan\b/iu', '/whistleblowing/iu'],
        'public' => true,
    ],
    'zona-integritas' => [
        'route' => '/zona-integritas',
        'label' => 'Zona Integritas',
        'note' => 'Komitmen WBK dan WBBM beserta enam area perubahannya.',
        'patterns' => ['/zona integritas/iu', '/\bWBBM\b/u', '/\bWBK\b/u'],
        'public' => true,
    ],
    // Halaman bukti dukung untuk penilai, bukan layanan warga: ditautkan di dalam
    // teks sebagai rujukan, tetapi tidak ditawarkan sebagai layanan di kaki artikel.
    'ampuh' => [
        'route' => '/ampuh',
        'label' => 'AMPUH',
        'note' => 'Checklist dan bukti dukung AMPUH 2026.',
        'patterns' => ['/\bAMPUH\b/u'],
        'public' => false,
    ],
];
// Kanal layanan hanya dipakai untuk menautkan penyebutan di dalam badan tulisan. Panel
// "Untuk pencari keadilan" yang dulu berdiri di bawah artikel sudah dihapus: ia terbit
// di setiap berita, mengulang tautan yang sudah ada di menu dan di badan tulisan, dan
// menambah perancah pada ekor artikel yang memang sedang dirampingkan.
$servicePlainText = strip_tags($articleBody);
$serviceOccurrences = static function (array $patterns, string $text): array {
    $occurrences = [];
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $text, $hits, PREG_OFFSET_CAPTURE)) {
            foreach ($hits[0] as $hit) {
                $start = $hit[1];
                $end = $start + strlen($hit[0]);
                $overlaps = false;
                foreach ($occurrences as [$knownStart, $knownEnd]) {
                    if ($start < $knownEnd && $end > $knownStart) {
                        $overlaps = true;
                        break;
                    }
                }
                if (!$overlaps) {
                    $occurrences[] = [$start, $end];
                }
            }
        }
    }
    usort($occurrences, static fn(array $a, array $b): int => $a[0] <=> $b[0]);
    return $occurrences;
};
$serviceInlineMatches = [];
foreach ($serviceChannels as $channelKey => $serviceChannel) {
    $bodyOccurrences = $serviceOccurrences($serviceChannel['patterns'], $servicePlainText);
    if ($bodyOccurrences !== []) {
        $serviceInlineMatches[$channelKey] = $serviceChannel + ['at' => $bodyOccurrences[0][0]];
    }
}
uasort($serviceInlineMatches, static fn(array $a, array $b): int => $a['at'] <=> $b['at']);

// Penautannya lebih pemilih daripada deteksinya: satu tautan per simpul teks, dan
// tidak pernah di dalam judul, teks tebal, keterangan foto, atau tautan yang sudah
// ada. Legacy "RISALAH PANGGILAN/PEMBERITAHUAN UMUM" adalah judul risalah yang
// dicetak tebal - menautinya membuatnya terbaca seperti menu, bukan surat resmi.
$bodySegments = preg_split('#(<[^>]+>)#', $articleBody, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
$serviceSkipDepth = 0;
$serviceLinked = [];
foreach ($bodySegments as $segmentIndex => $segment) {
    if ($segment === '') {
        continue;
    }
    if ($segment[0] === '<') {
        if (preg_match('#^</?(?:a|b|strong|em|h2|h3|figcaption)\b#i', $segment)) {
            $serviceSkipDepth += $segment[1] === '/' ? -1 : 1;
            $serviceSkipDepth = max(0, $serviceSkipDepth);
        }
        continue;
    }
    if ($serviceSkipDepth > 0) {
        continue;
    }
    foreach ($serviceInlineMatches as $channelKey => $serviceChannel) {
        if (isset($serviceLinked[$channelKey])) {
            continue;
        }
        foreach ($serviceChannel['patterns'] as $servicePattern) {
            if (!preg_match($servicePattern, $segment, $hit, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            $serviceLinked[$channelKey] = true;
            $bodySegments[$segmentIndex] = substr_replace(
                $segment,
                '<a class="editorial-article__service-link" href="' . $serviceChannel['route'] . '">' . $hit[0][0] . '</a>',
                $hit[0][1],
                strlen($hit[0][0])
            );
            continue 3;
        }
    }
}
$articleBody = implode('', $bodySegments);

// Berita terkait. Versi sebelumnya mengambil 24 artikel terbaru lalu memeringkatnya
// dengan kata yang dibagi judul - tetapi hampir tidak ada judul peradilan yang berbagi
// kata, sehingga pemecah serinya ("terbaru dulu") yang selalu menang. Akibatnya di
// seluruh 84 artikel hanya **17 artikel** yang pernah muncul sebagai terkait, satu di
// antaranya pada 54 halaman - pembaca merasa membaca trio yang sama berulang kali.
// Sekarang: kolamnya seluruh kanal, dan bila tidak ada kaitan kata yang sungguhan,
// yang ditawarkan adalah tetangga kronologisnya - berita tepat sebelum dan sesudahnya.
// Itu kaitan yang benar-benar ada, berbeda untuk setiap artikel, dan judulnya jujur.
$levels = array_map('intval', $user->getAuthorisedViewLevels());
$relatedQuery = $db->getQuery(true)
    ->select($db->quoteName(['a.id', 'a.title', 'a.alias', 'a.catid', 'a.language', 'a.publish_up', 'a.created', 'a.images', 'a.introtext', 'a.fulltext']))
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
$relatedPool = $db->setQuery($relatedQuery)->loadObjectList();
// Kata yang muncul di hampir semua judul peradilan tidak membedakan apa pun.
$relatedStopwords = array_flip([
    'pengadilan', 'negeri', 'natuna', 'kelas', 'yang', 'dari', 'dengan', 'untuk', 'pada', 'dalam',
    'atau', 'oleh', 'serta', 'para', 'akan', 'agar', 'atas', 'bagi', 'adalah', 'ini', 'itu', 'dan',
    'menjadi', 'tentang', 'lewat', 'kepada', 'tahun', 'resmi', 'bahas', 'jalani', 'terkini',
]);
$relatedKeywords = static function (string $title) use ($relatedStopwords): array {
    $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($title), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return array_flip(array_filter($words, static fn(string $word): bool => mb_strlen($word) >= 4 && !isset($relatedStopwords[$word])));
};
$currentKeywords = $relatedKeywords((string) $item->title);
// Kata dibobot menurut kelangkaannya di kanal ini. "Kegiatan" muncul di belasan judul
// dan tidak membuktikan apa-apa; "Posbakum" atau "Pancasila" muncul di segelintir judul
// dan hampir selalu menandai rangkaian peristiwa yang sama. Tanpa bobot ini satu kata
// umum yang kebetulan sama sudah cukup membuat dua berita asing diklaim berkaitan.
$documentFrequency = [];
foreach ($relatedPool as $candidate) {
    foreach ($relatedKeywords((string) $candidate->title) as $word => $ignored) {
        $documentFrequency[$word] = ($documentFrequency[$word] ?? 0) + 1;
    }
}
$relatedScored = [];
foreach ($relatedPool as $index => $candidate) {
    $sharedWords = array_intersect_key($currentKeywords, $relatedKeywords((string) $candidate->title));
    if ($sharedWords === []) {
        continue;
    }
    $weight = 0.0;
    foreach ($sharedWords as $word => $ignored) {
        $weight += 1 / max(1, $documentFrequency[$word] ?? 1);
    }
    // Kaitan diakui bila ada satu kata yang benar-benar khas, atau dua kata yang sama.
    if ($weight >= 0.5 || count($sharedWords) >= 2) {
        $relatedScored[] = ['score' => $weight, 'order' => $index, 'item' => $candidate];
    }
}
usort($relatedScored, static fn(array $a, array $b): int => [$b['score'], $a['order']] <=> [$a['score'], $b['order']]);
// Satu judul tidak bisa jujur menggambarkan daftar campuran, jadi daftarnya dibuat
// seragam: hanya kaitan yang lolos ambang di atas yang boleh berdiri di bawah judul
// "Berita terkait" - satu kartu pun tidak apa-apa. Bila tidak ada satu pun, skornya
// diabaikan sepenuhnya dan yang ditawarkan murni tetangga kronologis, dengan judul
// yang mengatakan persis itu.
$related = array_map(static fn(array $row): object => $row['item'], array_slice($relatedScored, 0, 3));
$hasGenuineRelation = $related !== [];
// Bila tidak ada kaitan sungguhan, seluruh daftarnya diisi tetangga kronologis - bukan
// dicampur, supaya judulnya tetap benar. Kolam sudah terurut terbaru-dulu, jadi posisi
// artikel ini di dalamnya ditemukan lewat tanggal efektifnya, lalu diambil bergantian
// satu yang lebih baru dan satu yang lebih lama - melebar sampai kuotanya penuh.
if (!$hasGenuineRelation) {
    $effectiveDate = static function (object $row) use ($db): string {
        return !empty($row->publish_up) && $row->publish_up !== $db->getNullDate() && $row->publish_up > '2000-01-02 00:00:00'
            ? (string) $row->publish_up
            : (string) $row->created;
    };
    $currentDate = (string) $publishedRaw;
    $position = 0;
    foreach ($relatedPool as $index => $candidate) {
        if ($effectiveDate($candidate) > $currentDate) {
            $position = $index + 1;
        }
    }
    $taken = array_flip(array_map(static fn(object $row): int => (int) $row->id, $related));
    for ($step = 1; $step <= count($relatedPool) && count($related) < 3; $step++) {
        foreach ([$position - $step, $position + $step - 1] as $neighbour) {
            if (!isset($relatedPool[$neighbour]) || count($related) >= 3) {
                continue;
            }
            $candidate = $relatedPool[$neighbour];
            if (isset($taken[(int) $candidate->id])) {
                continue;
            }
            $taken[(int) $candidate->id] = true;
            $related[] = $candidate;
        }
    }
}
$currentUrl = Uri::getInstance()->toString(['scheme', 'host', 'port', 'path', 'query']);
// Pembaca yang datang dari halaman 5 daftar berita harus kembali ke halaman 5.
// Rujukan divalidasi ke host sendiri dan ke jalur kanal ini sebelum dipakai.
$listingReturnUrl = Route::_($basePath);
$referrer = (string) $app->getInput()->server->getString('HTTP_REFERER', '');
if ($referrer !== '') {
    $referrerUri = new Uri($referrer);
    $sameHost = $referrerUri->getHost() === Uri::getInstance()->getHost();
    $listingPath = rtrim(parse_url(Route::_($basePath, false), PHP_URL_PATH) ?: $basePath, '/');
    $referrerPath = rtrim($referrerUri->getPath(), '/');
    $start = (int) $referrerUri->getVar('start', 0);
    if ($sameHost && $start > 0 && ($referrerPath === $listingPath || str_ends_with($referrerPath, $listingPath))) {
        $listingReturnUrl = Route::_($basePath . '?start=' . $start);
    }
}
?>
<article class="editorial-article editorial-article--<?php echo $channel; ?><?php echo $hasRail ? ' editorial-article--railed' : ''; ?>" itemscope itemtype="https://schema.org/<?php echo $schemaType; ?>">    
  <meta itemprop="inLanguage" content="<?php echo $this->escape($item->language === '*' ? $app->get('language') : $item->language); ?>">
  <header class="editorial-article__header">
    <a class="editorial-article__back" href="<?php echo $this->escape($listingReturnUrl); ?>">← Daftar <?php echo $channel === 'news' ? 'berita' : 'pengumuman'; ?></a>
    <p class="editorial-article__masthead"><img src="/images/brand/logo-pn-natuna.webp" alt="" width="31" height="40" loading="eager" decoding="async"><span>Pengadilan Negeri Natuna Kelas II</span></p>
    <?php
    // Judul dibungkus serakah supaya tiap baris mengisi kolom - lihat catatan pada
    // `.editorial-article__title` di template.css. Konsekuensinya baris terakhir bisa
    // tersisa satu kata sangat pendek: "Kelas II" dan "Mahkamah Agung RI" menyisakan
    // "II" dan "RI" sendirian selebar 39px dan 56px. Kata sependek itu dilekatkan pada
    // kata sebelumnya dengan spasi tanpa-putus. Ambangnya tiga huruf: "Riau" dan "Laut"
    // sepanjang 104px masih kata utuh yang terbaca, sedangkan menggabungkan setiap kata
    // empat huruf akan ikut menyeret "2024" dan "2026" - 48 dari 84 judul - dan justru
    // menurunkan pengisian kolom.
    $headline = trim((string) $item->title);
    $headlineWords = preg_split('/\s+/u', $headline) ?: [];
    if (count($headlineWords) > 2 && mb_strlen(preg_replace('/[^\p{L}\p{N}]/u', '', (string) end($headlineWords))) <= 3) {
        $lastWord = array_pop($headlineWords);
        $headline = implode(' ', $headlineWords) . "\u{00a0}" . $lastWord;
    }
    ?>
    <?php if ($params->get('show_title', 1)) : ?><h1 class="editorial-article__title" itemprop="headline"><?php echo $this->escape($headline); ?></h1><?php endif; ?>
    <div class="editorial-article__meta">
      <time itemprop="datePublished" datetime="<?php echo $published->format(DATE_ATOM); ?>">Terbit <?php echo $publishedLabel; ?></time>
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
      <img src="<?php echo $this->escape($imageUrl); ?>"<?php echo $heroSrcset ? ' srcset="' . $this->escape($heroSrcset) . '" sizes="' . $this->escape($photoSizes) . '"' : ''; ?> alt="<?php echo $this->escape($imageAlt); ?>" width="1200" height="800" fetchpriority="high" itemprop="contentUrl">
      <figcaption><?php echo $this->escape($heroCaption); ?></figcaption>
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
    <?php // Rel dan badan berbagi satu pembungkus supaya sticky-nya berhenti di ujung
          // badan artikel. Tanpa pembungkus, Chrome mengurung sticky ke seluruh grid
          // artikel, sehingga rel ikut melayang di atas "Berita terkait" dan kaki artikel. ?>
    <div class="editorial-article__reading">
    <?php if ($hasRail) : ?>
      <aside class="editorial-article__rail" aria-label="Navigasi isi artikel">
        <details class="editorial-article__toc" open>
          <summary>Isi artikel<span><?php echo count($tableOfContents); ?> bagian</span></summary>
          <ol>
            <?php foreach ($tableOfContents as $section) : ?>
              <li><a href="#<?php echo $this->escape($section['anchor']); ?>"><?php echo $this->escape($section['label']); ?></a></li>
            <?php endforeach; ?>
          </ol>
        </details>
      </aside>
    <?php endif; ?>
    <div class="editorial-article__body" itemprop="articleBody"><?php echo $articleBody; ?></div>
    </div>
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
    <?php // Kop berlambang di atas artikel sudah menyebut lembaganya dan baris meta sudah
          // menyebut tanggal terbitnya; mengulangnya di sini hanya menambah panjang ekor.
          // Yang tersisa cuma fakta yang belum pernah dikatakan: tanggal pembaruan. ?>
    <?php if ($isMateriallyModified) : ?>
      <div class="editorial-article__publication"><span>Diperbarui <?php echo $modifiedLabel; ?></span></div>
    <?php endif; ?>
    <div class="editorial-article__share" data-editorial-share data-title="<?php echo $this->escape($item->title); ?>" data-url="<?php echo $this->escape($currentUrl); ?>">
      <span>Bagikan</span>
      <button type="button" data-share-native>Bagikan artikel</button>
      <a href="https://wa.me/?text=<?php echo rawurlencode($item->title . ' ' . $currentUrl); ?>" target="_blank" rel="noopener noreferrer">Kirim lewat WhatsApp</a>
      <button type="button" data-share-copy>Salin tautan</button>
      <span class="editorial-article__share-status" data-share-status aria-live="polite"></span>
    </div>
  </footer>

  <?php if ($related) : ?>
  <section class="editorial-article__related" aria-labelledby="related-heading"><h2 id="related-heading"><?php echo $hasGenuineRelation ? ($channel === 'news' ? 'Berita terkait' : 'Pengumuman terkait') : ($channel === 'news' ? 'Berita di sekitar tanggal ini' : 'Pengumuman di sekitar tanggal ini'); ?></h2><div class="editorial-article__related-grid">
    <?php foreach ($related as $relatedItem) : $relatedImageUrl = $articleImage((string) $relatedItem->images, (string) $relatedItem->introtext, (string) $relatedItem->fulltext); $relatedDateRaw = !empty($relatedItem->publish_up) && $relatedItem->publish_up > '2000-01-02 00:00:00' ? $relatedItem->publish_up : $relatedItem->created; $relatedDate = Factory::getDate($relatedDateRaw); ?>
      <a class="editorial-article__related-card" href="<?php echo Route::_(RouteHelper::getArticleRoute($relatedItem->id . ':' . $relatedItem->alias, $relatedItem->catid, $relatedItem->language)); ?>">
        <?php if ($relatedImageUrl) : $relatedSrcset = $photoSrcset($relatedImageUrl); ?><img src="<?php echo $this->escape($relatedImageUrl); ?>"<?php echo $relatedSrcset ? ' srcset="' . $this->escape($relatedSrcset) . '" sizes="(max-width: 760px) 128px, 320px"' : ''; ?> alt="" width="480" height="270" loading="lazy" decoding="async"><?php elseif ($channel === 'announcement') : ?><img src="/images/brand/pengumuman-resmi-pn-natuna.webp" alt="" width="480" height="270" loading="lazy" decoding="async"><?php else : ?><span class="editorial-article__related-fallback" aria-hidden="true">PN</span><?php endif; ?>
        <time datetime="<?php echo $relatedDate->format(DATE_ATOM); ?>"><?php echo $formatIdDate($relatedDate); ?></time><strong><span><?php echo $this->escape($relatedItem->title); ?></span></strong>
      </a>
    <?php endforeach; ?>
  </div></section>
  <?php endif; ?>
  <a class="editorial-article__return" href="<?php echo $this->escape($listingReturnUrl); ?>">Kembali ke <?php echo $channel === 'news' ? 'berita' : 'pengumuman'; ?></a>
</article>
