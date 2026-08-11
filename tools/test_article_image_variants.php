<?php
/** Kontrak varian foto: setiap foto artikel Berita/Pengumuman siap dikirim ke ponsel. */
require_once __DIR__ . '/../configuration.php';
\defined('PN_NATUNA_VARIANT_CLI') or \define('PN_NATUNA_VARIANT_CLI', 1);
require_once __DIR__ . '/../plugins/content/pnnatunaimagevariants/src/Helper/VariantMaker.php';

const VARIANT_WIDTHS = [400, 800, 1200];

$root = dirname(__DIR__);
$config = new JConfig();
$db = new mysqli($config->host, $config->user, $config->password, $config->db, (int) ($config->dbport ?? 3306));
if ($db->connect_errno) {
    fwrite(STDERR, "Database connection failed: {$db->connect_error}\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

// Templat harus benar-benar memakai varian itu, dan hanya boleh ada satu tempat yang
// tahu bagaimana nama varian dibentuk. Tiga regresi yang pernah terjadi: foto badan
// artikel warisan tidak pernah disentuh, jalur relatif `images/...` ditolak diam-diam,
// dan kartu daftar menyajikan berkas asli - thumbnail 126px mengunduh foto 5 MB.
$template = (string) file_get_contents($root . '/templates/pn_natuna_2026/html/com_content/article/default.php');
$card = (string) file_get_contents($root . '/templates/pn_natuna_2026/html/com_content/category/blog_item.php');
$helper = (string) file_get_contents($root . '/plugins/content/pnnatunaimagevariants/src/Helper/VariantMaker.php');
$expect(str_contains($helper, "\$path = '/' . ltrim(\$path, '/');"), 'Srcset builder must normalise relative image paths; legacy content stores src without a leading slash.');
$expect(substr_count($template, 'VariantMaker::srcset(') === 1, 'Article template must route srcset through the shared builder.');
$expect(substr_count($card, 'VariantMaker::srcset(') === 1, 'Listing cards must route srcset through the same shared builder.');
$expect((bool) preg_match('/srcset="[^"]*\$this->escape\(\$cardCandidates\)/', $card) || str_contains($card, '$cardCandidates ?'), 'Listing card <img> must emit the srcset it built.');
// Kartu ponsel kini menumpuk foto selebar kolom (354px di 390), bukan bilah 126px, jadi
// `sizes` wajib mengikuti slot sungguhannya - kalau tidak, peramban memilih varian 126px
// dan fotonya buram di layar retina.
$expect(str_contains($card, "\$cardSizes = '(max-width: 760px) calc(100vw - 36px), 533px';"), 'Listing cards must declare the size they actually render at.');
$expect(substr_count($template, "str_contains(\$img[0], 'srcset=')") >= 2, 'Every body image must pass through the srcset builder, not just those inside an editorial figure.');
$expect(is_file($root . '/tools/make-image-variants.php'), 'The variant generator must ship with the site; Joomla never resizes on upload.');
// Media Manager menyimpan `foo.jpg#joomlaImage://local-images/foo.jpg?width=...`.
// Fragmen itu tidak boleh ikut tercetak ke `src` mana pun.
$expect(substr_count($template, "strtok(") >= 2, 'Article template must strip the Media Manager fragment from hero and body images.');
$expect(str_contains($card, "\$image = strtok(\$image, '#');"), 'Listing cards must strip the Media Manager fragment.');
$expect(
    str_contains($template, "\$relatedImageUrl = \$articleImage((string) (\$relatedItem->images ?? ''), (string) (\$relatedItem->introtext ?? ''), (string) (\$relatedItem->fulltext ?? ''))")
        || str_contains($template, '$relatedImageUrl = $articleImage((string) $relatedItem->images, (string) $relatedItem->introtext, (string) $relatedItem->fulltext)'),
    'Related cards must fall back to the first article-body photo when Joomla images JSON is empty.'
);
// Foto 24MP butuh 92 MB hanya untuk bitmapnya. Kehabisan memori adalah fatal error
// yang tidak bisa ditangkap, jadi penyimpanan artikel akan mati dengan 500.
$expect(str_contains($helper, 'public static function fits('), 'Variant maker must refuse photos that do not fit in the remaining memory.');
$expect(str_contains($helper, "\$tally['tooBig']++"), 'Photos skipped for memory must be reported, not silently dropped.');
$plugin = (string) file_get_contents($root . '/plugins/content/pnnatunaimagevariants/src/Extension/PnnatunaImageVariants.php');
$expect(str_contains($plugin, 'memory_limit'), 'The editor must be told which photos were skipped and how to finish them.');
$expect(\Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::hasCanonicalArticleName(
    '/images/berita/2026/mobile-legends-hut-81-ri-ma-2.webp'
), 'A concise article slug with year and sequence must be accepted as canonical.');
$expect(!\Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::hasCanonicalArticleName(
    '/images/IMG_3701.jpg'
), 'A camera filename must never be accepted as a canonical public article image URL.');
$expect(str_contains($plugin, 'nama foto otomatis diubah mengikuti slug artikel'), 'Saving a news article must report automatic canonical naming, not merely warn the editor.');
// Auto-naming mengambil bagian bermakna dari alias, berhenti pada batas kata, dan
// mengganti bentuk URL-encoded yang lazim ditulis Media Manager.
$autoSlug = \Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::conciseSlug(
    'pengadilan-negeri-natuna-berikan-pembekalan-sistem-peradilan-indonesia-kepada-mahasiswa-hukum-stai-natuna'
);
$expect($autoSlug === 'berikan-pembekalan-sistem-peradilan-indonesia-kepada', 'Automatic image slug must stay concise without cutting a word.');
$expect(strlen($autoSlug) <= \Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::SLUG_MAX_LENGTH, 'Automatic image slug exceeds its public filename budget.');
$rewrittenPath = \Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::replacePaths(
    '<img src="images/WhatsApp%20Image%202026-08-10.jpg">',
    ['/images/WhatsApp%20Image%202026-08-10.jpg' => '/images/berita/2026/berita-sidang-1.webp']
);
$expect(str_contains($rewrittenPath, 'images/berita/2026/berita-sidang-1.webp'), 'Automatic naming must rewrite URL-encoded image references.');
$unsafeRename = \Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::canonicalizeArticlePaths(
    $root,
    ['/images/%2e%2e/configuration.php'],
    'uji-keamanan-path',
    '2026'
);
$expect($unsafeRename['replacements'] === [], 'Automatic naming must reject encoded traversal outside the images directory.');
// Tiga cacat yang ditemukan lewat audit seluruh korpus (1 Agu 2026): foto hero dicetak
// ulang di badan pada 75 dari 81 artikel berhero, 93 dari 93 kapsi berupa cap lembaga
// berulang yang tanggalnya bahkan bertentangan dengan dateline, dan 165 dari 177 foto
// badan tanpa `width`/`height` sehingga teks melompat di koneksi lambat.
$expect(str_contains($template, '$photoKey'), 'Article template must drop a body photo that repeats the hero; 75 of 81 articles ship that duplicate.');
$expect(str_contains($template, '$photoCaption = static function'), 'Figure captions must be derived from the image alt text, not a repeated institutional credit.');
$expect(!str_contains($template, "'Dokumentasi Pengadilan Negeri Natuna · '"), 'The caption must not restate the publish date; it contradicted the article dateline.');
$expect(str_contains($template, '$photoBox'), 'Body images must carry width/height so text does not jump while photos arrive.');

// Tiga artikel terbaru sempat menyimpan nama kamera/WhatsApp. URL gambar publik harus
// memakai slug ringkas yang menjelaskan momen, lengkap dengan seluruh varian responsif.
$renameMigration = (string) file_get_contents($root . '/database/migrations/20260810_normalize_recent_news_image_paths.sql');
$recentCanonicalPhotos = [
    'images/PATCANIA.jpg' => 'images/berita/2026/alih-tugas-cania-kirana-1.webp',
    'images/IMG_3408.jpg' => 'images/berita/2026/alih-tugas-cania-kirana-2.webp',
    'images/WhatsApp Image 2026-07-31 at 08.17.57.jpeg' => 'images/berita/2026/bola-voli-hut-81-ri-ma-1.webp',
    'images/IMG_3150.jpg' => 'images/berita/2026/bola-voli-hut-81-ri-ma-2.webp',
    'images/IMG_3204.jpg' => 'images/berita/2026/bola-voli-hut-81-ri-ma-3.webp',
    'images/IMG_3729 1.jpg' => 'images/berita/2026/mobile-legends-hut-81-ri-ma-1.webp',
    'images/IMG_3701.jpg' => 'images/berita/2026/mobile-legends-hut-81-ri-ma-2.webp',
    'images/IMG_3738 1.jpg' => 'images/berita/2026/mobile-legends-hut-81-ri-ma-3.webp',
];
foreach ($recentCanonicalPhotos as $legacyPath => $canonicalPath) {
    $expect(str_contains($renameMigration, "'{$legacyPath}'")
        && str_contains($renameMigration, "'{$canonicalPath}'"), "Image migration omits {$legacyPath} -> {$canonicalPath}.");
    $canonicalFile = $root . '/' . $canonicalPath;
    $expect(is_file($canonicalFile), "Canonical article photo is missing: {$canonicalPath}");
    $canonicalSize = @getimagesize($canonicalFile);
    $expect($canonicalSize !== false && (int) $canonicalSize[0] >= 1200 && (int) $canonicalSize[0] <= 1600,
        "Canonical article photo {$canonicalPath} must retain useful detail without shipping a camera-size source.");
    $base = preg_replace('/\.[a-z0-9]+$/i', '', $canonicalFile);
    foreach (VARIANT_WIDTHS as $width) {
        $variantFile = "{$base}-{$width}.webp";
        $expect(is_file($variantFile), "Canonical article photo {$canonicalPath} has no {$width}w variant.");
        $variantSize = @getimagesize($variantFile);
        $expect($variantSize !== false && (int) $variantSize[0] === $width,
            "Canonical article photo {$canonicalPath} has an invalid {$width}w variant.");
    }
}

$encodedRepair = (string) file_get_contents($root . '/database/migrations/20260810_repair_encoded_recent_news_image_paths.sql');
$encodedCanonicalPhotos = [
    'images/WhatsApp%20Image%202026-07-31%20at%2008.17.57.jpeg' => 'images/berita/2026/bola-voli-hut-81-ri-ma-1.webp',
    'images/IMG_3729%201.jpg' => 'images/berita/2026/mobile-legends-hut-81-ri-ma-1.webp',
    'images/IMG_3738%201.jpg' => 'images/berita/2026/mobile-legends-hut-81-ri-ma-3.webp',
];
foreach ($encodedCanonicalPhotos as $legacyPath => $canonicalPath) {
    $expect(str_contains($encodedRepair, "'{$legacyPath}'")
        && str_contains($encodedRepair, "'{$canonicalPath}'"), "Encoded image migration omits {$legacyPath} -> {$canonicalPath}.");
}

$canonicalUrlMigration = (string) file_get_contents($root . '/database/migrations/20261008_canonicalize_published_news_aliases.sql');
$expect(
    str_contains($canonicalUrlMigration, "CONCAT('/', c.path, '/', a.alias)")
        && str_contains($canonicalUrlMigration, "CONCAT('/', c.path, '/', SUBSTRING(a.alias, 8))"),
    'Canonical news URL migration must preserve every legacy route with a permanent redirect.'
);
$expect(
    str_contains($canonicalUrlMigration, 'SET a.alias = SUBSTRING(a.alias, 8)'),
    'Published imported news aliases must drop the internal legacy marker.'
);

$legacyAliasResult = $db->query(
    "SELECT COUNT(*) FROM {$config->dbprefix}content a"
    . " INNER JOIN {$config->dbprefix}categories c ON c.id = a.catid"
    . " WHERE a.state = 1 AND c.path IN ('berita', 'pengumuman') AND a.alias LIKE 'legacy-%'"
);
$legacyAliasCount = $legacyAliasResult ? (int) $legacyAliasResult->fetch_row()[0] : -1;
$expect($legacyAliasCount === 0, "Published article URLs still expose {$legacyAliasCount} import-only legacy aliases.");
$redirectResult = $db->query(
    "SELECT COUNT(*) FROM {$config->dbprefix}redirect_links"
    . " WHERE published = 1 AND comment = 'Alias impor berita dibersihkan; URL lama dialihkan ke route kanonis.'"
);
$canonicalRedirectCount = $redirectResult ? (int) $redirectResult->fetch_row()[0] : 0;
$expect($canonicalRedirectCount > 0, 'Canonical news aliases must retain redirects from their former public URLs.');

$result = $db->query(
    "SELECT a.alias, a.images, a.introtext, a.`fulltext` FROM {$config->dbprefix}content a"
    . " INNER JOIN {$config->dbprefix}categories c ON c.id = a.catid"
    . " WHERE a.state = 1 AND c.path IN ('berita', 'pengumuman')"
);
if (!$result) {
    fwrite(STDERR, "Content query failed: {$db->error}\n");
    exit(1);
}

$photos = [];
while ($row = $result->fetch_assoc()) {
    $images = json_decode((string) $row['images'], true) ?: [];
    $candidates = [(string) ($images['image_intro'] ?? ''), (string) ($images['image_fulltext'] ?? '')];
    if (preg_match_all('#<img[^>]+src="([^"]+)"#i', (string) $row['introtext'] . (string) $row['fulltext'], $found)) {
        $candidates = array_merge($candidates, $found[1]);
    }
    foreach ($candidates as $candidate) {
        $candidate = trim(strtok($candidate, '#'));
        if ($candidate === '') {
            continue;
        }
        $path = parse_url($candidate, PHP_URL_PATH);
        if (!is_string($path)) {
            continue;
        }
        $path = '/' . ltrim($path, '/');
        if (str_starts_with($path, '/images/')) {
            $photos[$path] = $photos[$path] ?? $row['alias'];
        }
    }
}

$checked = 0;
$variantCount = 0;
foreach ($photos as $path => $alias) {
    $file = $root . $path;
    if (!is_file($file)) {
        $failures[] = "Article {$alias} references a missing photo: {$path}";
        continue;
    }
    $size = @getimagesize($file);
    if (!$size || !in_array($size['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
        continue;
    }
    $checked++;
    $base = preg_replace('/\.[a-z0-9]+$/i', '', $path);
    foreach (VARIANT_WIDTHS as $width) {
        if ((int) $size[0] < $width) {
            continue;
        }
        $variant = $root . $base . '-' . $width . '.webp';
        if (!is_file($variant)) {
            $failures[] = "Photo {$path} (article {$alias}) has no {$width}w variant; run php tools/make-image-variants.php";
            continue;
        }
        $variantCount++;
        $variantSize = @getimagesize($variant);
        // Varian yang lebih lebar daripada sumbernya adalah pembesaran: berkasnya berat
        // tanpa menambah satu piksel detail pun.
        if ($variantSize && (int) $variantSize[0] > (int) $size[0]) {
            $failures[] = "Variant {$base}-{$width}.webp is upscaled beyond its source.";
        }
    }
}
$expect($checked > 0, 'No article photos were checked; the scan is broken.');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, array_slice($failures, 0, 12)) . PHP_EOL);
    if (count($failures) > 12) {
        fwrite(STDERR, sprintf("... dan %d masalah lain\n", count($failures) - 12));
    }
    exit(1);
}

echo "article image variant contract: ok ({$checked} photos, {$variantCount} variants)\n";
