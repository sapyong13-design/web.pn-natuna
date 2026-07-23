<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_custom
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

$modId = 'mod-custom' . $module->id;

if ($params->get('backgroundimage')) {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->addInlineStyle(
        '#' . $modId . '{background-image: url("' . Uri::root(true) . '/' . HTMLHelper::_('cleanImageURL', $params->get('backgroundimage'))->url . '");}',
        ['name' => $modId]
    );
}

$content = (string) $module->content;
if ((int) $module->id === 110) {
    $active = Factory::getApplication()->getMenu()->getActive();
    $tag = $active && $active->home ? 'h1' : 'p';
    $content = preg_replace(
        '#<h1>\s*(Pengadilan Negeri Natuna Kelas II)\s*</h1>#',
        '<' . $tag . ' class="brand-title">$1</' . $tag . '>',
        $content,
        1
    );
}
?>
<div id="<?php echo $modId; ?>" class="mod-custom custom">
    <?php echo $content; ?>
</div>
