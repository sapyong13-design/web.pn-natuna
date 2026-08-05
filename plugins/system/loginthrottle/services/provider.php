<?php
declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use PNNatuna\Plugin\System\LoginThrottle\Extension\LoginThrottle;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, $container->lazy(LoginThrottle::class, static function (): LoginThrottle {
            $plugin = new LoginThrottle((array) PluginHelper::getPlugin('system', 'loginthrottle'));
            $plugin->setApplication(Factory::getApplication());
            return $plugin;
        }));
    }
};
