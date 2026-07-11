<?php

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

$module  = $displayData['module'];
$params  = $displayData['params'];
$attribs = $displayData['attribs'];

if ((string) $module->content === '') {
    return;
}

$moduleTag              = htmlspecialchars($params->get('module_tag', 'div'), ENT_QUOTES, 'UTF-8');
$moduleAttribs          = [];
$moduleAttribs['class'] = 'moduletable ' . htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8');
$bootstrapSize          = (int) $params->get('bootstrap_size', 0);
$moduleAttribs['class'] .= $bootstrapSize !== 0 ? ' col-md-' . $bootstrapSize : '';
$headerTag              = htmlspecialchars($params->get('header_tag', 'h3'), ENT_QUOTES, 'UTF-8');
$headerClass            = htmlspecialchars($params->get('header_class', ''), ENT_QUOTES, 'UTF-8');
$headerAttribs          = [];

if ($headerClass !== '') {
    $headerAttribs['class'] = $headerClass;
}
if (!empty($attribs['class'])) {
    $moduleAttribs['class'] .= ' ' . htmlspecialchars($attribs['class'], ENT_QUOTES, 'UTF-8');
}
if ($moduleTag !== 'div') {
    if ($module->showtitle) {
        $moduleAttribs['aria-labelledby'] = 'mod-' . $module->id;
        $headerAttribs['id'] = 'mod-' . $module->id;
    } else {
        $moduleAttribs['aria-label'] = htmlspecialchars($module->title, ENT_QUOTES, 'UTF-8');
    }
}

$header = '<' . $headerTag . ' ' . ArrayHelper::toString($headerAttribs) . '>'
    . htmlspecialchars($module->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
    . '</' . $headerTag . '>';
?>
<<?php echo $moduleTag; ?> <?php echo ArrayHelper::toString($moduleAttribs); ?>>
    <?php if ((bool) $module->showtitle) : ?><?php echo $header; ?><?php endif; ?>
    <?php echo $module->content; ?>
</<?php echo $moduleTag; ?>>
