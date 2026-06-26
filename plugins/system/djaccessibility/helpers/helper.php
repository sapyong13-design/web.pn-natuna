<?php
/**
 * @package DJ-Accessibility
 * @copyright Copyright (C) DJ-Extensions.com, All rights reserved.
 * @license http://www.gnu.org/licenses GNU/GPL
 * @author url: http://dj-extensions.com
 * @author email faktycznie@gmail.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use YOOtheme\Application;
use function YOOtheme\app;

class DJAcc {
	public static function getFile( $file, &$params = null ) {
		// Start capturing output into a buffer
		ob_start();

		// Include the requested template filename in the local scope
		// (this will execute the view logic).
		include $file;

		// Done with the requested template; get the buffer and
		// clear it.
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	public static function getParam( $param, $default = null ) {

		//remove prefix from parameter
		$param = str_replace('djacc_', '', $param);

		$plugin = PluginHelper::getPlugin('system','djaccessibility');
		$params = new Registry($plugin->params);

		$field_xml = $params->get('djacc_' . $param, $default);

		//fields priority: Yootheme > XML > default

		if( DJACC_YOOTHEME ) {
			return app()->config->get('~theme.djacc_' . $param, $field_xml);
		} else {
			return $field_xml;
		}
	}

	public static function getLayout( $layout ) {
		$file = $layout . '.php';
		$plugin_path = DJACC_PATH . '/tmpl/' . $file;
		$app = Factory::getApplication();
		$override_path = JPATH_ROOT.'/templates/' . $app->getTemplate() . '/' . 'html/djacc/' . $file;

		if( file_exists($override_path) ) {
			return self::getFile($override_path);
		} else {
			return self::getFile($plugin_path);
		}
	}

	public static function pluginType() {
		if( class_exists('DJAccLight') ) {
			return DJAccLight::getVersion();
		} elseif( class_exists('DJAccPro') ) {
			return DJAccPro::getVersion();
		} else return false;
	}
}
?>