<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.pnnatunaimagevariants
 */

namespace Joomla\Plugin\Content\Pnnatunaimagevariants\Extension;

use Joomla\CMS\Event\Model;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker;
\defined('_JEXEC') or die;

/**
 * Membuat varian foto responsif begitu artikel disimpan.
 *
 * Joomla tidak pernah memperkecil berkas saat diunggah - plugin `media-action/resize`
 * hanya tombol manual di Media Manager - sementara templat artikel hanya memasang
 * `srcset` bila berkas `-400/-800/-1200.webp` sudah ada di sebelah aslinya. Tanpa
 * plugin ini, setiap berita baru mengirim foto ukuran penuh ke ponsel sampai ada
 * yang ingat menjalankan `php tools/make-image-variants.php`.
 *
 * Hanya artikel yang dijalankan, hanya foto di dalam `/images/`, dan kegagalan apa
 * pun ditelan menjadi peringatan: menyimpan artikel tidak boleh gagal gara-gara GD.
 */
final class PnnatunaImageVariants extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onContentAfterSave' => 'onContentAfterSave'];
    }

    public function onContentAfterSave(Model\AfterSaveEvent $event): void
    {
        $context = $event->getContext();
        $item = $event->getItem();

        if (!\in_array($context, ['com_content.article', 'com_content.form'], true) || !\is_object($item)) {
            return;
        }

        $paths = VariantMaker::collectPaths(
            isset($item->images) ? (string) $item->images : null,
            isset($item->introtext) ? (string) $item->introtext : '',
            isset($item->fulltext) ? (string) $item->fulltext : ''
        );
        if (!$paths) {
            return;
        }

        $made = 0;
        $failed = 0;
        $tooBig = 0;
        foreach ($paths as $path) {
            try {
                $tally = VariantMaker::build(JPATH_ROOT, $path);
            } catch (\Throwable $error) {
                $failed++;
                continue;
            }
            $made += $tally['made'];
            $failed += $tally['failed'];
            $tooBig += $tally['tooBig'];
        }

        if ($made > 0) {
            $this->getApplication()->enqueueMessage(
                sprintf('%d varian foto responsif dibuat untuk artikel ini.', $made),
                'notice'
            );
        }
        if ($tooBig > 0) {
            $this->getApplication()->enqueueMessage(
                sprintf(
                    '%d foto terlalu besar untuk diproses dengan batas memori server (%s). Artikel tetap tersimpan; jalankan php -d memory_limit=1024M tools/make-image-variants.php untuk menyelesaikannya.',
                    $tooBig,
                    (string) ini_get('memory_limit')
                ),
                'warning'
            );
        }
        if ($failed > 0) {
            $this->getApplication()->enqueueMessage(
                sprintf('%d foto gagal dibuatkan varian; jalankan php tools/make-image-variants.php.', $failed),
                'warning'
            );
        }
    }
}
