<?php
/**
 * @package DJ-Extensions-Example-module
 * @copyright Copyright (C) DJ-Extensions.com, All rights reserved.
 * @license http://www.gnu.org/licenses GNU/GPL
 * @author url: http://dj-extensions.com
 * @author email faktycznie@gmail.com
 */

namespace DJExtensions\YOOessentials\YOOtheme;

defined('_JEXEC') or die;

use YOOtheme\Config;
use YOOtheme\Path;

return [

	'events' => [

		'customizer.init' => [
			ConfigListener::class => ['initCustomizer', -10],
		],

	],
	'extend' => [
		Config::class => function (Config $config){

			$conf_file = Path::get('../module/assets/json/config.json');
			$fconf_file = Path::get('../module/assets/json/fconfig.json');

			$plugin_type = \DJAcc::pluginType();

			if( file_exists($conf_file) && $plugin_type ) {
				$config->addFile('accessibility', $conf_file);
			} elseif( file_exists($fconf_file) ) {
				$config->addFile('accessibility', $fconf_file);
			}
		},
	],
];
