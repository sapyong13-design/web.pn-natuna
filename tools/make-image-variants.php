<?php
/**
 * Membuat varian responsif -400/-800/-1200.webp untuk foto yang dirujuk artikel terbit.
 *
 * Joomla tidak pernah memperkecil berkas saat diunggah: plugin `media-action/resize`
 * hanya tombol manual di Media Manager. Templat artikel pun hanya memasang `srcset`
 * bila varian itu benar-benar ada di sebelah berkas aslinya. Tanpa langkah ini, foto
 * 4032x3024 seberat 1,5 MB dikirim utuh ke ponsel di koneksi kepulauan.
 *
 * Aman diulang: varian yang sudah ada dan lebih baru daripada sumbernya dilewati,
 * dan gambar tidak pernah diperbesar - lebar target yang melampaui sumber diabaikan.
 *
 * Pemakaian:
 *   php tools/make-image-variants.php --dry-run
 *   php tools/make-image-variants.php
 *   php tools/make-image-variants.php --quality 82
 */
require_once __DIR__ . '/../configuration.php';

// Logika pembuatan varian tinggal di dalam plugin supaya ikut terkirim saat deploy;
// tool CLI ini hanya menambahkan pemindaian basis data dan laporan.
define('PN_NATUNA_VARIANT_CLI', 1);
require_once __DIR__ . '/../plugins/content/pnnatunaimagevariants/src/Helper/VariantMaker.php';

use Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker;

$quality = VariantMaker::QUALITY;

$root = dirname(__DIR__);
$options = getopt('', ['dry-run', 'quality::', 'help']);
if (isset($options['help'])) {
    fwrite(STDOUT, "php tools/make-image-variants.php [--dry-run] [--quality=82]\n");
    exit(0);
}
$dryRun = isset($options['dry-run']);
$quality = max(60, min(95, (int) ($options['quality'] ?? 82)));

if (!function_exists('imagewebp')) {
    fwrite(STDERR, "GD tanpa dukungan WebP; tidak bisa membuat varian.\n");
    exit(1);
}

$config = new JConfig();
$db = new mysqli($config->host, $config->user, $config->password, $config->db, (int) ($config->dbport ?? 3306));
if ($db->connect_errno) {
    fwrite(STDERR, "Database connection failed: {$db->connect_error}\n");
    exit(1);
}
$db->set_charset('utf8mb4');

/**
 * Setiap foto yang benar-benar dirujuk artikel Berita/Pengumuman, hero maupun di dalam
 * badan. Kategori lain sengaja dilewati: halaman Profil, Layanan, dan Transparansi
 * dirender lewat layout inti Joomla yang tidak pernah memasang `srcset`, jadi varian
 * untuk foto-fotonya hanya akan menjadi berkas mati di repositori.
 */
function referenced_images(mysqli $db, string $prefix): array
{
    $paths = [];
    $result = $db->query(
        "SELECT a.images, a.introtext, a.`fulltext` FROM {$prefix}content a"
        . " INNER JOIN {$prefix}categories c ON c.id = a.catid"
        . " WHERE a.state = 1 AND c.path IN ('berita', 'pengumuman')"
    );
    if (!$result) {
        fwrite(STDERR, "Content query failed: {$db->error}\n");
        exit(1);
    }
    while ($row = $result->fetch_assoc()) {
        $images = json_decode((string) $row['images'], true) ?: [];
        $candidates = [];
        foreach (['image_intro', 'image_fulltext'] as $key) {
            $candidates[] = (string) ($images[$key] ?? '');
        }
        if (preg_match_all('#<img[^>]+src="([^"]+)"#i', (string) $row['introtext'] . (string) $row['fulltext'], $found)) {
            $candidates = array_merge($candidates, $found[1]);
        }
        foreach ($candidates as $candidate) {
            $candidate = trim(strtok($candidate, '#'));
            if ($candidate === '') {
                continue;
            }
            $path = parse_url($candidate, PHP_URL_PATH);
            if (!is_string($path) || !str_starts_with($path = '/' . ltrim($path, '/'), '/images/')) {
                continue;
            }
            $paths[$path] = true;
        }
    }
    return array_keys($paths);
}

$made = $skipped = $failed = 0;
$sourceBytes = $variantBytes = 0;
$plan = [];

foreach (referenced_images($db, $config->dbprefix) as $path) {
    $file = $root . $path;
    if (!is_file($file)) {
        continue;
    }
    $size = @getimagesize($file);
    if (!$size || !in_array($size['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
        continue;
    }
    if ($dryRun) {
        $base = preg_replace('/\.[a-z0-9]+$/i', '', $file);
        $sourceMtime = filemtime($file);
        foreach (VariantMaker::WIDTHS as $target) {
            if ((int) $size[0] < $target) {
                continue;
            }
            $variant = $base . '-' . $target . '.webp';
            if (is_file($variant) && filemtime($variant) >= $sourceMtime) {
                $skipped++;
                continue;
            }
            $plan[] = substr($path, 8) . ' -> ' . $target . 'w';
            $made++;
        }
        continue;
    }
    $tally = VariantMaker::build($root, $path, $quality);
    $made += $tally['made'];
    $skipped += $tally['skipped'];
    $failed += $tally['failed'];
    $variantBytes += $tally['bytes'];
    if ($tally['made'] > 0) {
        $sourceBytes += (int) filesize($file);
    }
}

if ($dryRun) {
    foreach (array_slice($plan, 0, 12) as $line) {
        echo '  ', $line, "\n";
    }
    if (count($plan) > 12) {
        printf("  ... dan %d varian lain\n", count($plan) - 12);
    }
    printf("dry run: %d varian akan dibuat, %d sudah ada\n", $made, $skipped);
    exit(0);
}

printf(
    "varian dibuat: %d (%.1f MB dari %.1f MB sumber), dilewati: %d, gagal: %d\n",
    $made,
    $variantBytes / 1048576,
    $sourceBytes / 1048576,
    $skipped,
    $failed
);
exit($failed > 0 ? 1 : 0);
