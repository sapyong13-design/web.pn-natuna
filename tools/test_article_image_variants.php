<?php
/** Kontrak varian foto: setiap foto artikel Berita/Pengumuman siap dikirim ke ponsel. */
require_once __DIR__ . '/../configuration.php';

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

// Templat harus benar-benar memakai varian itu. Dua regresi yang pernah terjadi:
// foto badan artikel warisan tidak pernah disentuh, dan jalur relatif `images/...`
// tanpa garis miring awal ditolak diam-diam sehingga srcset-nya hilang.
$template = (string) file_get_contents($root . '/templates/pn_natuna_2026/html/com_content/article/default.php');
$expect(str_contains($template, "\$path = '/' . ltrim(parse_url(\$src, PHP_URL_PATH) ?: \$src, '/');"), 'Srcset builder must normalise relative image paths; legacy content stores src without a leading slash.');
$expect(substr_count($template, "str_contains(\$img[0], 'srcset=')") >= 2, 'Every body image must pass through the srcset builder, not just those inside an editorial figure.');
$expect(is_file($root . '/tools/make-image-variants.php'), 'The variant generator must ship with the site; Joomla never resizes on upload.');

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
