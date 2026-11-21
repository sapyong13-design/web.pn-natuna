<?php

defined('_JEXEC') or die;

$iconBase = \Joomla\CMS\Uri\Uri::root(true) . '/images/brand/';
foreach ([['favicon-32.png', 'icon', '32x32'], ['favicon-512.png', 'icon', '512x512'], ['apple-touch-icon.png', 'apple-touch-icon', '180x180']] as [$file, $rel, $size]) :
    $version = filemtime(JPATH_ROOT . '/images/brand/' . $file);
?>
<link rel="<?php echo $rel; ?>" type="image/png" sizes="<?php echo $size; ?>" href="<?php echo $iconBase . $file; ?>?v=<?php echo $version; ?>">
<?php endforeach; ?>
