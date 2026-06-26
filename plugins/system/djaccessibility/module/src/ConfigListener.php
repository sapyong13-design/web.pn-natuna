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
use YOOtheme\Metadata;
use YOOtheme\Path;
use YOOtheme\Url;
use function YOOtheme\app;

class ConfigListener
{
	public static function initCustomizer(Config $config, Metadata $metadata)
	{
		$conf_file = Path::get('../assets/json/config.json');
		$fconf_file = Path::get('../assets/json/fconfig.json');

		$plugin_type = \DJAcc::pluginType();

		if( file_exists($conf_file) && $plugin_type) {
			$config->addFile('customizer', $conf_file);
		} elseif( file_exists($fconf_file) ) {
			$config->addFile('customizer', $fconf_file);
		}
	}
}
