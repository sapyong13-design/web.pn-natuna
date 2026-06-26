<?php
/**
 * @package DJ-Accessibility
 * @copyright Copyright (C) DJ-Extensions.com, All rights reserved.
 * @license http://www.gnu.org/licenses GNU/GPL
 * @author url: http://dj-extensions.com
 * @author email faktycznie@gmail.com
 */

defined('_JEXEC') or die;

define ('DJACC_DEBUG', false);
define ('DJACC_VERSION', '1.15');
define ('DJACC_PATH', __DIR__);

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use YOOtheme\Application;
use function YOOtheme\app;

//helpers
require __DIR__ . '/helpers/helper.php';
if( file_exists(__DIR__ . '/helpers/version.php') ) require __DIR__ . '/helpers/version.php';

class plgSystemDjaccessibility extends CMSPlugin {
	public function onAfterInitialise() {

		// check if YOOtheme Pro is loaded
		define('DJACC_YOOTHEME', class_exists(Application::class, false));

		JLoader::registerNamespace('DJAccessibility', JPATH_ROOT . "/plugins/system/djaccessibility");

		$this->loadLanguage();

		if( DJACC_YOOTHEME ) {
			/* Load Needed Classes */
			require __DIR__ . '/module/src/ConfigListener.php';

			// bootstrap modules
			$app = Application::getInstance();
			$app->load(__DIR__ . '/module/bootstrap.php');
		}
	}

	function onBeforeCompileHead() {
        $app = Factory::getApplication();
        $input = Factory::getApplication()->input;
        if($input->getCmd("option") == "com_sppagebuilder" && $input->getCmd("view") != "page"){
            return;
        }
		$app = Factory::getApplication();

		if ( $app->isClient('administrator') ) {
			return;
		}

		$doc = Factory::getDocument();
        if ($doc->getType() === 'error') {
            return 0;
        }
		$min = ( DJACC_DEBUG ) ? '' : '.min';
		$ver = ( DJACC_DEBUG ) ? DJACC_VERSION . '-' . time() : DJACC_VERSION;

		$doc->addStyleSheet(Uri::root(true).'/plugins/system/djaccessibility/module/assets/css/accessibility.css', array('version' => $ver));
		$doc->addScript(Uri::root(true).'/plugins/system/djaccessibility/module/assets/js/accessibility' . $min . '.js', array('version' => $ver));
		
		$load_font = DJAcc::getParam('djacc_load_font', 1);
		$font_url = DJAcc::getParam('font_url', 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
		$font_family = DJAcc::getParam('font_family', 'Roboto, sans-serif');

		if( !empty($load_font) ) {
			if( !empty($font_url) ) {
				$font_url = filter_var($font_url, FILTER_SANITIZE_URL);
				$doc->addStyleSheet($font_url);
			}
			if( !empty($font_family) ) {
				$font_family = htmlspecialchars(trim($font_family, ';'));
				$doc->addStyleDeclaration('.djacc { font-family: '.$font_family.'; }');
			}
		}

		//inline css styles
		$position = DJAcc::getParam('position', 'sticky');
		$mobile_position = DJAcc::getParam('mobile_position', 'sticky');
		$layout = DJAcc::getParam('layout', 'popup');
		$mobile_layout = DJAcc::getParam('mobile_layout', 'popup');

		$popup_voff = DJAcc::getParam('voff_popup', 20);
		$popup_hoff = DJAcc::getParam('hoff_popup', 20);
		if( $popup_voff > 0 || $popup_hoff > 0 ) $doc->addStyleDeclaration('.djacc--sticky.djacc-popup { margin: '.$popup_voff.'px '.$popup_hoff.'px; }');

		$toolbar_voff = DJAcc::getParam('voff_toolbar', 0);
		$toolbar_hoff = DJAcc::getParam('hoff_toolbar', 0);
		if( $toolbar_voff > 0 || $toolbar_hoff > 0 ) $doc->addStyleDeclaration('.djacc--sticky.djacc-toolbar { margin: '.$toolbar_voff.'px '.$toolbar_hoff.'px; }');

		$align_popup = DJAcc::getParam('align_popup', 'top right');
		$btn = DJAcc::getParam('image', false);
		$width = DJAcc::getParam('width', 48);
		$height = DJAcc::getParam('height', 48);
		if( $btn ) $doc->addStyleDeclaration('.djacc-popup .djacc__openbtn { width: '.$width.'px; height: '.$height.'px; }');

		$align_toolbar = DJAcc::getParam('align_toolbar', 'top center');

		$align_mobile_position  = DJAcc::getParam('align_mobile', 'bottom right');
		$direction = DJAcc::getParam('direction', 'top left');
		$space = DJAcc::getParam('space', true);

		$speech_pitch = DJAcc::getParam('speech_pitch', '1');
		$speech_rate = DJAcc::getParam('speech_rate', '1');
		$speech_volume = DJAcc::getParam('speech_volume', '1');

		$plugin_type = DJAcc::pluginType();

		$mobile = DJAcc::getParam('mobile', true);
		$breakpoint = DJAcc::getParam('breakpoint', '767') ?: 767;

		if(!$mobile) {
			$doc->addStyleDeclaration('@media(max-width: ' . $breakpoint - 1 . 'px) { .djacc { display: none !important; }');
		}

		$options = json_encode(array(
			'cms'                    => 'joomla',
			'yootheme'               => DJACC_YOOTHEME,
			'position'               => $position,
			'mobile_position'        => $mobile_position,
			'layout'                 => $layout,
			'mobile_layout'          => $mobile_layout,
			'align_position_popup'   => $align_popup,
			'align_position_toolbar' => $align_toolbar,
			'align_mobile_position'  => $align_mobile_position,
			'breakpoint'             => $breakpoint,
			'direction'              => $direction,
			'space'                  => $space,
			'version'                => $plugin_type,
			'speech_pitch'           => $speech_pitch,
			'speech_rate'            => $speech_rate,
			'speech_volume'          => $speech_volume,
			'ajax_url'               => '?option=com_ajax&plugin=Djaccessibility&format=raw',
		));

		// init script
		$js = 'new DJAccessibility( ' . $options . ' )';
		$doc->addScriptDeclaration($js);
	}

	function onAfterRender() {

		$app = Factory::getApplication();
        $input = Factory::getApplication()->input;
        if($input->getCmd("option") == "com_sppagebuilder" && $input->getCmd("view") != "page"){
            return;
        }
		if ( $app->isClient('administrator') ) {
			return;
		}

		$layout = DJAcc::getParam('layout', 'popup');
		$documentFormat = $app->input->getCmd('format', 'html');
	
		if ( ($documentFormat == 'html' || is_null($documentFormat)) ) {
			$html = $app->getBody();
			if( 'toolbar' == $layout ) {
				$accTemplate = DJAcc::getLayout( 'toolbar' );
			} else {
				$accTemplate = DJAcc::getLayout( 'default' );
			}

			if(preg_match("/<body[^>]*>(.*?)<\/body>/is", $html, $matches)) {
				$body = $accTemplate .  $matches[1];
				$html = str_replace($matches[1], $body, $html);
			}
			$app->setBody($html);
		}
	}

	function onAjaxDjaccessibility() {
		$layout = (isset($_REQUEST['djacc_template'])) ? $_REQUEST['djacc_template'] : false;
		if( 'toolbar' == $layout ) {
			$accTemplate = DJAcc::getLayout( 'toolbar' );
		} else {
			$accTemplate = DJAcc::getLayout( 'default' );
		}
		echo $accTemplate;
	}
}