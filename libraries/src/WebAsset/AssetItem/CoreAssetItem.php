<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebAsset\AssetItem;

use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetAttachBehaviorInterface;
use Joomla\CMS\WebAsset\WebAssetItem;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Web Asset Item class for Core asset
 *
 * @since  4.0.0
 */
class CoreAssetItem extends WebAssetItem implements WebAssetAttachBehaviorInterface
{
    /**
     * Method called when asset attached to the Document.
     * Useful for Asset to add a Script options.
     *
     * @param   Document  $doc  Active document
     *
     * @return void
     *
     * @since   4.0.0
     */
    public function onAttachCallback(Document $doc)
    {
        // Add core and base uri paths so javascript scripts can use them.
        $doc->addScriptOptions(
            'system.paths',
            [
                'root'     => Uri::root(true),
                'rootFull' => Uri::root(),
                'base'     => Uri::base(true),
                'baseFull' => Uri::base(),
            ]
        );

        // Only audited GET pages without session forms may omit this JavaScript token.
        // Form tokens in rendered HTML still make LSCache reject the response.
        $app = Factory::getApplication();
        $path = Uri::getInstance()->getPath();
        $publicPages = ['/', '/profil-pengadilan', '/transparansi', '/berita', '/pengumuman', '/berita-dan-pengumuman', '/kontak'];

        if ($app->isClient('site') && $app->getInput()->getMethod() === 'GET' && $app->getIdentity()->guest && Uri::getInstance()->getQuery() === '' && in_array($path, $publicPages, true)) {
            return;
        }

        HTMLHelper::_('form.csrf');
    }
}
