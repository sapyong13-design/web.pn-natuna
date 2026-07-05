<?php

/**
 * Module chrome "card" (Joomla 4/5 layout).
 * Membungkus semua module style="card" dengan wrapper .module-card
 * supaya seluruh card homepage konsisten (radius/border/shadow sama).
 */

defined('_JEXEC') or die;

$module  = $displayData['module'];
$params  = $displayData['params'];

if (trim((string) $module->content) === '') {
    return;
}
?>
<section class="module-card <?php echo htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($module->showtitle) : ?>
        <h2><?php echo $module->title; ?></h2>
    <?php endif; ?>
    <?php echo $module->content; ?>
</section>
