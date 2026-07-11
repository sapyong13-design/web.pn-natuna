<?php

defined('_JEXEC') or die;

$module = $displayData['module'];
$params = $displayData['params'];
?>
<table class="moduletable <?php echo htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_COMPAT, 'UTF-8'); ?>">
    <?php if ((bool) $module->showtitle) : ?>
        <tr><th><?php echo htmlspecialchars($module->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></th></tr>
    <?php endif; ?>
    <tr><td><?php echo $module->content; ?></td></tr>
</table>
