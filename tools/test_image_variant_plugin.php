<?php
/**
 * Kontrak plugin varian foto: menyimpan artikel benar-benar membuat varian responsif.
 *
 * Bukan sekadar memeriksa berkas plugin ada - kontrak ini menyalakan Joomla, memuat
 * grup plugin `content`, lalu melepas event `onContentAfterSave` yang sesungguhnya
 * dengan artikel tiruan. Yang diuji: pendaftaran di `#__extensions`, `services/provider.php`,
 * autoload namespace, kabel `SubscriberInterface`, dan pembuatan berkasnya.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const _JEXEC = 1;
$root = dirname(__DIR__);
define('JPATH_BASE', $root);
require_once $root . '/includes/defines.php';
require_once $root . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Event\Model\AfterSaveEvent;
use Joomla\CMS\Event\Model\BeforeSaveEvent;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
    ->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(\Joomla\Console\Application::class);
Factory::$application = $app;

// Peta PSR-4 ekstensi di-cache per aplikasi (`JPATH_CACHE/autoload_psr4.php`) dan
// `cache:clean` tidak menghapusnya. Setelah plugin didaftarkan lewat migrasi, peta
// lama masih menyembunyikan namespace-nya sampai berkas itu dibangun ulang - persis
// yang dilakukan situs pada permintaan pertama ketika berkasnya hilang.
require_once JPATH_LIBRARIES . '/namespacemap.php';
foreach ([JPATH_SITE . '/cache/autoload_psr4.php', JPATH_ADMINISTRATOR . '/cache/autoload_psr4.php'] as $mapFile) {
    @unlink($mapFile);
}
$namespaceMap = new JNamespacePsr4Map();
$namespaceMap->create();
$namespaceMap->load();

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$pluginDir = $root . '/plugins/content/pnnatunaimagevariants';
$expect(is_file($pluginDir . '/pnnatunaimagevariants.xml'), 'Plugin manifest is missing.');
$expect(is_file($pluginDir . '/services/provider.php'), 'Plugin service provider is missing.');
$expect(is_file($pluginDir . '/src/Extension/PnnatunaImageVariants.php'), 'Plugin extension class is missing.');
$expect(is_file($pluginDir . '/src/Helper/VariantMaker.php'), 'Shared variant maker is missing.');
$expect(PluginHelper::isEnabled('content', 'pnnatunaimagevariants'), 'Plugin must be registered and enabled; run python tools/apply-db-migrations.py.');
PluginHelper::importPlugin('content', 'pnnatunaimagevariants');
$expect(
    \Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::firstImage('<p>Awal</p><img src="images/berita/foto-utama.jpg#joomlaImage://local-images/foto-utama.jpg" alt="">') === '/images/berita/foto-utama.jpg',
    'First body image must become the automatic primary-image fallback.'
);
$expect(
    \Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker::firstImage('<img src="https://example.com/external.jpg" alt="">') === '',
    'External body images must not become local primary-image fallbacks.'
);

// Foto uji ditaruh di dalam /images/ karena hanya jalur itu yang boleh diubah.
$fixtureDir = $root . '/images/_variant-selftest';
$fixture = $fixtureDir . '/selftest-photo.jpg';
$canonicalDir = $root . '/images/berita/2026';
$canonical = $canonicalDir . '/selftest-varian-foto-1.webp';
@mkdir($fixtureDir, 0755, true);
@mkdir($canonicalDir, 0755, true);
$canvas = imagecreatetruecolor(1500, 1000);
imagefilledrectangle($canvas, 0, 0, 1499, 999, imagecolorallocate($canvas, 143, 31, 11));
imagejpeg($canvas, $fixture, 90);
imagedestroy($canvas);
foreach ([$canonical, ...array_map(
    static fn(int $width): string => $canonicalDir . '/selftest-varian-foto-1-' . $width . '.webp',
    [400, 800, 1200]
)] as $generated) {
    @unlink($generated);
}

// Properti lengkap supaya plugin inti lain (Finder) tidak mengeluh atas artikel tiruan.
$item = (object) [
    'id' => 0,
    'title' => 'Selftest varian foto',
    'alias' => 'selftest-varian-foto',
    'catid' => 12,
    'state' => 0,
    'access' => 1,
    'language' => '*',
    'images' => json_encode(['image_intro' => 'images/_variant-selftest/selftest-photo.jpg', 'image_fulltext' => '']),
    'introtext' => '<p><img src="images/_variant-selftest/selftest-photo.jpg" alt=""></p>',
    'fulltext' => '',
    'created' => '2026-08-10 00:00:00',
    'publish_up' => '2026-08-10 00:00:00',
];

// Before-save mengubah nama fisik dan semua referensi pada objek yang akan disimpan.
$app->getDispatcher()->dispatch('onContentBeforeSave', new BeforeSaveEvent('onContentBeforeSave', [
    'context' => 'com_content.article',
    'subject' => $item,
    'isNew' => true,
    'data' => [],
]));
$expectedPath = 'images/berita/2026/selftest-varian-foto-1.webp';
$rewrittenImages = json_decode((string) $item->images, true);
$expect(($rewrittenImages['image_intro'] ?? '') === $expectedPath, 'Before-save must rewrite Joomla images JSON to the canonical article slug.');
$expect(str_contains((string) $item->introtext, $expectedPath), 'Before-save must rewrite body image references to the canonical article slug.');
$expect(is_file($canonical), 'Before-save must create the canonical WebP source.');
$expect(is_file($fixture), 'The temporary upload must remain available until the article save succeeds.');

$app->getDispatcher()->dispatch('onContentAfterSave', new AfterSaveEvent('onContentAfterSave', [
    'context' => 'com_content.article',
    'subject' => $item,
    'isNew' => true,
    'data' => [],
]));
$expect(!is_file($fixture), 'A successful new-article save must remove its unreferenced temporary upload.');

$made = [];
foreach ([400, 800, 1200] as $width) {
    $variant = $canonicalDir . '/selftest-varian-foto-1-' . $width . '.webp';
    if (is_file($variant)) {
        $size = @getimagesize($variant);
        $made[$width] = $size ? (int) $size[0] : 0;
    }
}
$expect(count($made) === 3, 'Saving an article must create the 400/800/1200 variants; got ' . implode(',', array_keys($made)) . '.');
foreach ($made as $width => $actual) {
    $expect($actual === $width, "Variant {$width}w rendered at {$actual}px.");
}

// Sumber 1500px tidak boleh melahirkan varian yang lebih lebar daripada dirinya.
$expect(!is_file($canonicalDir . '/selftest-varian-foto-1-1600.webp'), 'Variant maker must never upscale.');

// Aman diulang: pemanggilan kedua tidak menulis ulang berkas yang sudah ada.
$before = array_map('filemtime', glob($canonicalDir . '/selftest-varian-foto-1-*.webp') ?: []);
sleep(1);
$app->getDispatcher()->dispatch('onContentAfterSave', new AfterSaveEvent('onContentAfterSave', [
    'context' => 'com_content.article',
    'subject' => $item,
    'isNew' => false,
    'data' => [],
]));
$after = array_map('filemtime', glob($canonicalDir . '/selftest-varian-foto-1-*.webp') ?: []);
$expect($before === $after, 'Second save must skip variants that are already newer than their source.');

// Artikel yang sudah pernah tersimpan dapat memiliki URL publik lama: sumbernya dipertahankan.
$existingItem = clone $item;
$existingItem->images = json_encode(['image_intro' => 'images/_variant-selftest/selftest-photo.jpg', 'image_fulltext' => '']);
$existingItem->introtext = '<p><img src="images/_variant-selftest/selftest-photo.jpg" alt=""></p>';
$canvas = imagecreatetruecolor(1500, 1000);
imagefilledrectangle($canvas, 0, 0, 1499, 999, imagecolorallocate($canvas, 143, 31, 11));
imagejpeg($canvas, $fixture, 90);
imagedestroy($canvas);
$app->getDispatcher()->dispatch('onContentBeforeSave', new BeforeSaveEvent('onContentBeforeSave', [
    'context' => 'com_content.article',
    'subject' => $existingItem,
    'isNew' => false,
    'data' => [],
]));
$app->getDispatcher()->dispatch('onContentAfterSave', new AfterSaveEvent('onContentAfterSave', [
    'context' => 'com_content.article',
    'subject' => $existingItem,
    'isNew' => false,
    'data' => [],
]));
$expect(is_file($fixture), 'An existing article save must retain its legacy source for published URL compatibility.');

array_map('unlink', glob($fixtureDir . '/*') ?: []);
array_map('unlink', glob($canonicalDir . '/selftest-varian-foto-*.webp') ?: []);
@rmdir($fixtureDir);

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "image variant plugin contract: ok (new upload cleanup, existing-source retention, responsive variants)\n";
