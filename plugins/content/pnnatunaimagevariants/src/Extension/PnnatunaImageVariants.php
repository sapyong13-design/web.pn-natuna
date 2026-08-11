<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.pnnatunaimagevariants
 */

namespace Joomla\Plugin\Content\Pnnatunaimagevariants\Extension;

use Joomla\CMS\Event\Model;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Content\Pnnatunaimagevariants\Helper\VariantMaker;
\defined('_JEXEC') or die;

/**
 * Memberi nama WebP kanonis dan membuat varian responsif saat artikel disimpan.
 *
 * Joomla tidak pernah memperkecil atau mengganti nama berkas saat diunggah. Untuk
 * Berita/Pengumuman, hook before-save membuat `/images/berita/YYYY/slug-N.webp`
 * lalu mengganti JSON `images` dan seluruh `src` badan sebelum data ditulis. Hook
 * after-save membuat `-400/-800/-1200.webp`. Sumber lama dipertahankan agar URL
 * yang pernah dibagikan tetap hidup.
 *
 * Kegagalan apa pun menjadi peringatan: menyimpan artikel tidak boleh gagal hanya
 * karena GD, permission folder, atau foto kamera yang melampaui batas memori.
 */
final class PnnatunaImageVariants extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /** @var array<string,string> */
    private array $canonicalized = [];

    private int $canonicalFailed = 0;

    private int $canonicalTooBig = 0;

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentBeforeSave' => 'onContentBeforeSave',
            'onContentAfterSave' => 'onContentAfterSave',
        ];
    }

    public function onContentBeforeSave(Model\BeforeSaveEvent $event): void
    {
        $context = $event->getContext();
        $item = $event->getItem();
        $this->canonicalized = [];
        $this->canonicalFailed = 0;
        $this->canonicalTooBig = 0;

        if (!$this->supports($context, $item)) {
            return;
        }

        try {
            if (!$this->isNewsArticle($item)) {
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

            $slug = VariantMaker::conciseSlug(
                isset($item->alias) ? (string) $item->alias : '',
                isset($item->title) ? (string) $item->title : ''
            );
            $year = VariantMaker::articleYear(
                isset($item->publish_up) ? (string) $item->publish_up : null,
                isset($item->created) ? (string) $item->created : null
            );
            [$originalMemoryLimit, $raisedMemoryLimit] = $this->raiseMemoryLimit();
            try {
                $canonical = VariantMaker::canonicalizeArticlePaths(JPATH_ROOT, $paths, $slug, $year);
            } finally {
                $this->restoreMemoryLimit($originalMemoryLimit, $raisedMemoryLimit);
            }

            $this->canonicalized = $canonical['replacements'];
            $this->canonicalFailed = $canonical['failed'];
            $this->canonicalTooBig = $canonical['tooBig'];
            if (!$this->canonicalized) {
                return;
            }
            foreach (['images', 'introtext', 'fulltext'] as $field) {
                if (isset($item->$field)) {
                    $item->$field = VariantMaker::replacePaths((string) $item->$field, $this->canonicalized);
                }
            }
        } catch (\Throwable $error) {
            $this->canonicalFailed++;
        }
    }

    public function onContentAfterSave(Model\AfterSaveEvent $event): void
    {
        $context = $event->getContext();
        $item = $event->getItem();

        if (!$this->supports($context, $item)) {
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

        if ($this->canonicalized) {
            $this->getApplication()->enqueueMessage(
                sprintf(
                    '%d nama foto otomatis diubah mengikuti slug artikel; berkas lama dipertahankan agar URL terdahulu tidak rusak.',
                    \count($this->canonicalized)
                ),
                'notice'
            );
        }
        $uncanonical = array_values(array_filter(
            $paths,
            static fn(string $path): bool => !VariantMaker::hasCanonicalArticleName($path)
        ));
        if ($uncanonical) {
            $examples = implode(', ', array_map(
                static fn(string $path): string => basename(rawurldecode((string) parse_url($path, PHP_URL_PATH))),
                array_slice($uncanonical, 0, 3)
            ));
            $this->getApplication()->enqueueMessage(
                sprintf(
                    'Nama foto belum dapat diubah otomatis: %s. Pastikan berkas berada di folder images dan dapat ditulis server.',
                    $examples
                ),
                'warning'
            );
        }
        if ($this->canonicalTooBig > 0) {
            $this->getApplication()->enqueueMessage(
                sprintf(
                    '%d foto terlalu besar untuk diberi nama kanonis secara otomatis. Perkecil dimensinya lalu simpan kembali.',
                    $this->canonicalTooBig
                ),
                'warning'
            );
        }
        if ($this->canonicalFailed > 0) {
            $this->getApplication()->enqueueMessage(
                sprintf('%d foto gagal diberi nama kanonis secara otomatis; periksa keberadaan dan permission berkas.', $this->canonicalFailed),
                'warning'
            );
        }

        $made = 0;
        $failed = 0;
        $tooBig = 0;
        [$originalMemoryLimit, $raisedMemoryLimit] = $this->raiseMemoryLimit();
        try {
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
        } finally {
            $this->restoreMemoryLimit($originalMemoryLimit, $raisedMemoryLimit);
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
                    '%d foto melampaui kapasitas pemrosesan gambar otomatis. Artikel tetap tersimpan; perkecil dimensi foto lalu simpan kembali.',
                    $tooBig
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

    private function supports(string $context, mixed $item): bool
    {
        return \in_array($context, ['com_content.article', 'com_content.form'], true) && \is_object($item);
    }

    private function isNewsArticle(object $item): bool
    {
        $categoryId = isset($item->catid) ? (int) $item->catid : 0;
        if ($categoryId < 1) {
            return false;
        }
        $db = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName('path'))
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $categoryId, \Joomla\Database\ParameterType::INTEGER);
        $path = (string) $db->setQuery($query)->loadResult();

        return \in_array($path, ['berita', 'pengumuman'], true)
            || str_starts_with($path, 'berita/')
            || str_starts_with($path, 'pengumuman/');
    }

    /** @return array{string,bool} */
    private function raiseMemoryLimit(): array
    {
        $original = (string) ini_get('memory_limit');
        $raised = false;
        if (VariantMaker::memoryLimitBytes($original) < 536870912) {
            $raised = ini_set('memory_limit', '512M') !== false;
        }

        return [$original, $raised];
    }

    private function restoreMemoryLimit(string $original, bool $raised): void
    {
        if ($raised) {
            ini_set('memory_limit', $original);
        }
    }
}
