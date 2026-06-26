<?php 
/**

 * @package DJ-Accessibility
 * @copyright Copyright (C) 2021 DJ-Extensions.com, All rights reserved.
 * @license http://www.gnu.org/licenses GNU/GPL
 * @author url: https://dj-extensions.com
 * @author email contact@dj-extensions.com
 * @developer Artur Kaczmarek
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

$class_sfx = htmlspecialchars((string) $params->get('moduleclass_sfx'));
// only placeholder here, code will be added via plugin js
?>
<div id="djacc"<?php echo !empty($class_sfx) ? ' class="' . $class_sfx . '"' : ''; ?>></div>