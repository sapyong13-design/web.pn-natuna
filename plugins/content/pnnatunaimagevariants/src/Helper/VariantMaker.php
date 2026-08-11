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

    /** Sumber kanonis cukup 1600px; kamera asli tidak perlu dikirim ke pengunjung. */
    public const SOURCE_MAX_WIDTH = 1600;

    /** Menjaga nama akhir tetap ringkas setelah nomor urut dan ekstensi ditambahkan. */
    public const SLUG_MAX_LENGTH = 56;

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

    /** Membentuk bagian nama berkas dari alias artikel tanpa memotong kata. */
    public static function conciseSlug(string $alias, string $title = ''): string
    {
        $value = trim($alias) !== '' ? $alias : $title;
        $value = rawurldecode(trim($value));
        if (\function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (\is_string($transliterated) && $transliterated !== '') {
                $value = $transliterated;
            }
        }
        $value = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $value));
        $value = trim($value, '-');
        $specific = preg_replace('/^pengadilan-negeri-natuna(?:-kelas-ii)?-/', '', $value);
        if (\is_string($specific) && str_contains($specific, '-')) {
            $value = $specific;
        }

        $kept = [];
        foreach (array_values(array_filter(explode('-', $value))) as $part) {
            $candidate = implode('-', [...$kept, $part]);
            if (strlen($candidate) > self::SLUG_MAX_LENGTH) {
                break;
            }
            $kept[] = $part;
        }
        if (!$kept) {
            $kept = ['foto', 'berita'];
        } elseif (\count($kept) === 1) {
            $kept[] = 'berita';
        }

        return implode('-', $kept);
    }

    /** Memakai tahun publikasi/penulisan yang nyata, bukan tanggal sentinel Joomla. */
    public static function articleYear(?string $publishUp, ?string $created): string
    {
        foreach ([$publishUp, $created] as $date) {
            if (preg_match('/^(20[0-9]{2})-/', (string) $date, $match) && (int) $match[1] > 2000) {
                return $match[1];
            }
        }

        return date('Y');
    }

    /**
     * Membuat sumber WebP kanonis dan peta penggantian URL untuk foto yang namanya
     * masih berasal dari kamera/WhatsApp. Sumber lama sengaja dipertahankan supaya
     * URL yang pernah dibagikan tidak mendadak mati.
     *
     * @param string[] $paths
     *
     * @return array{replacements:array<string,string>,made:int,failed:int,tooBig:int}
     */
    public static function canonicalizeArticlePaths(
        string $root,
        array $paths,
        string $slug,
        string $year,
        int $quality = self::QUALITY
    ): array {
        $result = ['replacements' => [], 'made' => 0, 'failed' => 0, 'tooBig' => 0];
        $root = rtrim($root, '/\\');
        $slug = self::conciseSlug($slug);
        if (!preg_match('/^20[0-9]{2}$/', $year)) {
            $year = date('Y');
        }

        $sequence = 1;
        foreach ($paths as $path) {
            if (self::hasCanonicalArticleName($path)) {
                continue;
            }
            $sourcePath = self::localPath($path);
            if ($sourcePath === null) {
                continue;
            }
            $source = $root . $sourcePath;
            if (!is_file($source)) {
                $result['failed']++;
                continue;
            }

            do {
                $targetPath = "/images/berita/{$year}/{$slug}-{$sequence}.webp";
                $sequence++;
            } while (is_file($root . $targetPath));

            $status = self::writeCanonicalSource($source, $root . $targetPath, $quality);
            if ($status === 'made') {
                $result['replacements'][$path] = $targetPath;
                $result['made']++;
            } elseif ($status === 'tooBig') {
                $result['tooBig']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    /** Mengganti bentuk root-relative, relatif, literal-spasi, dan `%20`. */
    public static function replacePaths(string $content, array $replacements): string
    {
        $forms = [];
        foreach ($replacements as $source => $target) {
            $source = '/' . ltrim((string) $source, '/');
            $target = '/' . ltrim((string) $target, '/');
            foreach (array_unique([$source, rawurldecode($source)]) as $from) {
                $relativeFrom = ltrim($from, '/');
                $relativeTarget = ltrim($target, '/');
                $forms[$from] = $target;
                $forms[$relativeFrom] = $relativeTarget;
                $forms[str_replace('/', '\\/', $from)] = str_replace('/', '\\/', $target);
                $forms[str_replace('/', '\\/', $relativeFrom)] = str_replace('/', '\\/', $relativeTarget);
            }
        }
        uksort($forms, static fn(string $left, string $right): int => strlen($right) <=> strlen($left));

        return str_replace(array_keys($forms), array_values($forms), $content);
    }

    /**
     * Menghapus upload generik beserta varian lamanya setelah artikel baru sukses
     * tersimpan. Pemanggil wajib lebih dulu memastikan jalur itu tidak dipakai konten lain.
     *
     * @return array{removed:int,bytes:int}
     */
    public static function removeSourceFamily(string $root, string $src): array
    {
        $result = ['removed' => 0, 'bytes' => 0];
        $path = self::localPath($src);
        if ($path === null || self::hasCanonicalArticleName($path)) {
            return $result;
        }
        $file = rtrim($root, '/\\') . $path;
        $family = [$file];
        $base = preg_replace('/\.[a-z0-9]+$/i', '', $file);
        foreach (self::WIDTHS as $width) {
            $family[] = $base . '-' . $width . '.webp';
        }
        foreach ($family as $candidate) {
            if (!is_file($candidate)) {
                continue;
            }
            $bytes = (int) filesize($candidate);
            if (@unlink($candidate)) {
                $result['removed']++;
                $result['bytes'] += $bytes;
            }
        }

        return $result;
    }

    private static function localPath(string $src): ?string
    {
        $path = parse_url($src, PHP_URL_PATH);
        if (!\is_string($path) || $path === '') {
            return null;
        }
        $path = rawurldecode('/' . ltrim($path, '/'));
        if (!str_starts_with($path, '/images/') || str_contains($path, "\0")) {
            return null;
        }
        foreach (explode('/', $path) as $part) {
            if ($part === '..' || $part === '.') {
                return null;
            }
        }

        return $path;
    }

    private static function writeCanonicalSource(string $source, string $target, int $quality): string
    {
        if (!\function_exists('imagewebp')) {
            return 'failed';
        }
        $size = @getimagesize($source);
        if (!$size || !\in_array($size['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return 'failed';
        }
        if (!self::fits((int) $size[0], (int) $size[1])) {
            return 'tooBig';
        }
        $image = self::loadUpright($source, (string) $size['mime']);
        if (!$image) {
            return 'failed';
        }
        imagepalettetotruecolor($image);
        $width = imagesx($image);
        $height = imagesy($image);
        $targetWidth = min($width, self::SOURCE_MAX_WIDTH);
        $output = $image;
        if ($targetWidth < $width) {
            $output = imagecreatetruecolor($targetWidth, (int) round($height * ($targetWidth / $width)));
            imagealphablending($output, false);
            imagesavealpha($output, true);
            imagefilledrectangle(
                $output,
                0,
                0,
                $targetWidth,
                imagesy($output),
                imagecolorallocatealpha($output, 0, 0, 0, 127)
            );
            imagecopyresampled(
                $output,
                $image,
                0,
                0,
                0,
                0,
                $targetWidth,
                imagesy($output),
                $width,
                $height
            );
        }

        $directory = dirname($target);
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            if ($output !== $image) {
                imagedestroy($output);
            }
            imagedestroy($image);

            return 'failed';
        }
        $temporary = tempnam($directory, '.pnnatuna-');
        $ok = \is_string($temporary) && imagewebp($output, $temporary, $quality);
        if ($output !== $image) {
            imagedestroy($output);
        }
        imagedestroy($image);
        if (!$ok || !\is_string($temporary) || filesize($temporary) === 0) {
            if (\is_string($temporary)) {
                @unlink($temporary);
            }

            return 'failed';
        }
        @chmod($temporary, 0644);
        if (!@rename($temporary, $target)) {
            @unlink($temporary);

            return 'failed';
        }

        return 'made';
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
