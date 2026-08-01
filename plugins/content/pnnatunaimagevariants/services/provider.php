<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.pnnatunaimagevariants
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\Content\Pnnatunaimagevariants\Extension\PnnatunaImageVariants;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(PnnatunaImageVariants::class, function (Container $container) {
                $plugin = new PnnatunaImageVariants(
                    (array) PluginHelper::getPlugin('content', 'pnnatunaimagevariants')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            })
        );
    }
};
