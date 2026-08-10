<?php

/**
 * Pembuat varian foto responsif, dipakai bersama oleh plugin konten dan
 * `tools/make-image-variants.php`. Kelasnya sengaja polos tanpa dependensi Joomla
 * supaya berkas ini bisa di-`require` langsung dari CLI dev yang tidak memuat CMS.
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.pnnatunaimagevariants
 */

namespace Joomla\Plugin\Content\Pnnatunaimagevariants\Helper;

\defined('_JEXEC') or \defined('PN_NATUNA_VARIANT_CLI') or die;

final class VariantMaker
{
    /** Lebar yang dicari `$photoSrcset` di templat artikel. */
    public const WIDTHS = [400, 800, 1200];

    public const QUALITY = 82;

    /**
     * Merangkai `srcset` dari varian yang benar-benar ada di cakram. Dipakai templat
     * artikel maupun kartu daftar supaya keduanya menawarkan berkas yang sama.
     * Berkas asli hanya ditawarkan bila lebih besar daripada varian terbesar, supaya
     * layar DPR 2 tetap terlayani tanpa memaksa DPR 1 mengunduhnya.
     */
    public static function srcset(string $root, string $src): string
    {
        $path = parse_url($src, PHP_URL_PATH);
        if (!\is_string($path) || $path === '') {
            return '';
        }
        $path = '/' . ltrim($path, '/');
        if (!str_starts_with($path, '/images/')) {
            return '';
        }
        $root = rtrim($root, '/\\');
        $base = preg_replace('/\.[a-z0-9]+$/i', '', $path);
        $candidates = [];
        foreach (self::WIDTHS as $width) {
            if (is_file($root . $base . '-' . $width . '.webp')) {
                $candidates[] = $base . '-' . $width . '.webp ' . $width . 'w';
            }
        }
        if (!$candidates) {
            return '';
        }
        $size = @getimagesize($root . $path);
        if ($size && (int) $size[0] > max(self::WIDTHS)) {
            $candidates[] = $path . ' ' . (int) $size[0] . 'w';
        }

        return implode(', ', $candidates);
    }

    /**
     * Nama publik foto berita harus stabil, mudah dibaca, dan tidak membocorkan nama
     * kamera/WhatsApp. Urutan angka membedakan beberapa foto dari momen yang sama.
     */
    public static function hasCanonicalArticleName(string $src): bool
    {
        $path = parse_url($src, PHP_URL_PATH);
        if (!\is_string($path) || $path === '') {
            return false;
        }

        $path = rawurldecode('/' . ltrim($path, '/'));

        return preg_match(
            '#^/images/berita/[0-9]{4}/[a-z0-9]+(?:-[a-z0-9]+)+-[0-9]+\.(?:jpe?g|png|webp)$#',
            $path
        ) === 1;
    }

    /**
     * Mengumpulkan jalur foto lokal dari satu artikel: hero pada JSON images dan
     * setiap `<img>` di badan. Jalur relatif warisan (`images/...`) diseragamkan
     * karena templat mencarinya sebagai `/images/...`.
     *
     * @return string[]
     */
    public static function collectPaths(?string $imagesJson, string ...$html): array
    {
        $candidates = [];
        $images = json_decode((string) $imagesJson, true);
        if (\is_array($images)) {
            foreach (['image_intro', 'image_fulltext'] as $key) {
                $candidates[] = (string) ($images[$key] ?? '');
            }
        }
        if (preg_match_all('#<img[^>]+src="([^"]+)"#i', implode('', $html), $found)) {
            $candidates = array_merge($candidates, $found[1]);
        }

        $paths = [];
        foreach ($candidates as $candidate) {
            $candidate = trim(strtok($candidate, '#'));
            if ($candidate === '') {
                continue;
            }
            $path = parse_url($candidate, PHP_URL_PATH);
            if (!\is_string($path) || $path === '') {
                continue;
            }
            $path = '/' . ltrim($path, '/');
            if (str_starts_with($path, '/images/')) {
                $paths[$path] = true;
            }
        }

        return array_keys($paths);
    }

    /** Mengubah nilai memory_limit PHP menjadi byte untuk pemeriksaan kapasitas GD. */
    public static function memoryLimitBytes(string $limit): int
    {
        $limit = trim($limit);
        if ($limit === '' || $limit === '-1') {
            return PHP_INT_MAX;
        }
        $bytes = (int) $limit;
        $unit = strtolower(substr($limit, -1));

        return $bytes * match ($unit) {
            'g' => 1073741824,
            'm' => 1048576,
            'k' => 1024,
            default => 1,
        };
    }

    /** Mengambil foto lokal pertama dari badan artikel sebagai fallback gambar utama. */
    public static function firstImage(string ...$html): string
    {
        if (!preg_match('#<img[^>]+src=["\']([^"\']+)["\']#i', implode('', $html), $found)) {
            return '';
        }
        $candidate = trim(strtok((string) $found[1], '#'));
        $path = parse_url($candidate, PHP_URL_PATH);
        if (!\is_string($path) || $path === '') {
            return '';
        }
        $path = '/' . ltrim($path, '/');

        return str_starts_with($path, '/images/') ? $path : '';
    }

    /**
     * Memastikan bitmap sumber muat di sisa memori proses. GD memegang foto sebagai
     * truecolor 4 byte per piksel: kamera 24MP butuh 92 MB sebelum kanvas resample
     * dihitung. Kehabisan memori adalah fatal error yang tidak bisa ditangkap
     * `try/catch`, jadi penyimpanan artikel akan mati dengan 500 - lebih baik foto itu
     * dilewati dengan peringatan dan diselesaikan lewat CLI yang batasnya bisa dinaikkan.
     */
    public static function fits(int $width, int $height): bool
    {
        $bytes = self::memoryLimitBytes((string) ini_get('memory_limit'));
        if ($bytes === PHP_INT_MAX) {
            return true;
        }
        // Decoder JPEG/PNG, bitmap sumber, rotasi EXIF, dan kanvas resample dapat
        // hidup bersamaan. Faktor konservatif mencegah fatal OOM yang tak tertangkap.
        $needed = (int) ($width * $height * 4 * 2.5);

        return $needed < ($bytes - memory_get_usage(true));
    }

    /**
     * Membuat varian yang belum ada untuk satu foto. Tidak pernah memperbesar, dan
     * melewati varian yang sudah lebih baru daripada sumbernya, jadi aman diulang.
     *
     * @return array{made:int,skipped:int,failed:int,tooBig:int,bytes:int}
     */
    public static function build(string $root, string $path, int $quality = self::QUALITY): array
    {
        $tally = ['made' => 0, 'skipped' => 0, 'failed' => 0, 'tooBig' => 0, 'bytes' => 0];
        $file = rtrim($root, '/\\') . $path;
        if (!is_file($file) || !\function_exists('imagewebp')) {
            return $tally;
        }
        $size = @getimagesize($file);
        if (!$size || !\in_array($size['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return $tally;
        }
        if (!self::fits((int) $size[0], (int) $size[1])) {
            $tally['tooBig']++;

            return $tally;
        }

        $base = preg_replace('/\.[a-z0-9]+$/i', '', $file);
        $sourceMtime = filemtime($file);
        $image = null;
        $width = (int) $size[0];
        $height = (int) $size[1];

        foreach (self::WIDTHS as $target) {
            if ((int) $size[0] < $target) {
                continue;
            }
            $variant = $base . '-' . $target . '.webp';
            if (is_file($variant) && filemtime($variant) >= $sourceMtime) {
                $tally['skipped']++;
                continue;
            }
            if ($image === null) {
                $image = self::loadUpright($file, (string) $size['mime']);
                if (!$image) {
                    $tally['failed']++;
                    break;
                }
                imagepalettetotruecolor($image);
                $width = imagesx($image);
                $height = imagesy($image);
            }
            $canvas = imagecreatetruecolor($target, (int) round($height * ($target / $width)));
            imagecopyresampled($canvas, $image, 0, 0, 0, 0, $target, imagesy($canvas), $width, $height);
            $ok = imagewebp($canvas, $variant, $quality);
            imagedestroy($canvas);
            if ($ok) {
                $tally['made']++;
                $tally['bytes'] += (int) filesize($variant);
            } else {
                $tally['failed']++;
            }
        }

        if ($image !== null) {
            imagedestroy($image);
        }

        return $tally;
    }

    /**
     * GD mengabaikan EXIF. Tanpa koreksi ini, foto ponsel yang tegak lewat metadata
     * akan berdiri terbalik di variannya sementara aslinya tampak benar.
     */
    private static function loadUpright(string $file, string $mime)
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file),
            'image/png' => @imagecreatefrompng($file),
            'image/webp' => @imagecreatefromwebp($file),
            default => false,
        };
        if (!$image || $mime !== 'image/jpeg' || !\function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($file);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($angle === 0) {
            return $image;
        }
        $rotated = @imagerotate($image, $angle, 0);
        if (!$rotated) {
            return $image;
        }
        imagedestroy($image);

        return $rotated;
    }
}
