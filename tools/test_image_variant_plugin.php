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

// Foto uji ditaruh di dalam /images/ karena hanya jalur itu yang dilayani templat.
$fixtureDir = $root . '/images/_variant-selftest';
$fixture = $fixtureDir . '/selftest-photo.jpg';
@mkdir($fixtureDir, 0755, true);
$canvas = imagecreatetruecolor(1500, 1000);
imagefilledrectangle($canvas, 0, 0, 1499, 999, imagecolorallocate($canvas, 143, 31, 11));
imagejpeg($canvas, $fixture, 90);
imagedestroy($canvas);
foreach ([400, 800, 1200] as $width) {
    @unlink($fixtureDir . '/selftest-photo-' . $width . '.webp');
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
];

// Hanya plugin ini yang dimuat: indexer Finder tidak perlu ikut memproses artikel tiruan.
PluginHelper::importPlugin('content', 'pnnatunaimagevariants');
$app->getDispatcher()->dispatch('onContentAfterSave', new AfterSaveEvent('onContentAfterSave', [
    'context' => 'com_content.article',
    'subject' => $item,
    'isNew' => true,
    'data' => [],
]));

$made = [];
foreach ([400, 800, 1200] as $width) {
    $variant = $fixtureDir . '/selftest-photo-' . $width . '.webp';
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
$expect(!is_file($fixtureDir . '/selftest-photo-1600.webp'), 'Variant maker must never upscale.');

// Aman diulang: pemanggilan kedua tidak menulis ulang berkas yang sudah ada.
$before = array_map('filemtime', glob($fixtureDir . '/selftest-photo-*.webp') ?: []);
sleep(1);
$app->getDispatcher()->dispatch('onContentAfterSave', new AfterSaveEvent('onContentAfterSave', [
    'context' => 'com_content.article',
    'subject' => $item,
    'isNew' => false,
    'data' => [],
]));
$after = array_map('filemtime', glob($fixtureDir . '/selftest-photo-*.webp') ?: []);
$expect($before === $after, 'Second save must skip variants that are already newer than their source.');

array_map('unlink', glob($fixtureDir . '/*') ?: []);
@rmdir($fixtureDir);

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "image variant plugin contract: ok (3 variants from one simulated save)\n";
