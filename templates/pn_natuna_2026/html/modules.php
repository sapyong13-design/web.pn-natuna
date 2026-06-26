<?php
defined('_JEXEC') or die;

function modChrome_card($module, &$params, &$attribs)
{
    if ($module->content === '') {
        return;
    }
    ?>
    <section class="module-card <?php echo htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($module->showtitle) : ?>
            <h2><?php echo $module->title; ?></h2>
        <?php endif; ?>
        <?php echo $module->content; ?>
    </section>
    <?php
}
