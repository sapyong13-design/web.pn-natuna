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

    /**
     * Membuat varian yang belum ada untuk satu foto. Tidak pernah memperbesar, dan
     * melewati varian yang sudah lebih baru daripada sumbernya, jadi aman diulang.
     *
     * @return array{made:int,skipped:int,failed:int,bytes:int}
     */
    public static function build(string $root, string $path, int $quality = self::QUALITY): array
    {
        $tally = ['made' => 0, 'skipped' => 0, 'failed' => 0, 'bytes' => 0];
        $file = rtrim($root, '/\\') . $path;
        if (!is_file($file) || !\function_exists('imagewebp')) {
            return $tally;
        }
        $size = @getimagesize($file);
        if (!$size || !\in_array($size['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
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
