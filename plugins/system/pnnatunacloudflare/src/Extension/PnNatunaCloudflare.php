<?php

namespace Joomla\Plugin\System\Pnnatunacloudflare\Extension;

use Joomla\CMS\Event\Model;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\System\Pnnatunacloudflare\Helper\CloudflarePurgeQueue;

\defined('_JEXEC') or die;

final class PnNatunaCloudflare extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onContentAfterSave' => 'onContentAfterSave'];
    }

    public function onContentAfterSave(Model\AfterSaveEvent $event): void
    {
        if (!\in_array($event->getContext(), ['com_content.article', 'com_content.form'], true)) {
            return;
        }
        $item = $event->getItem();
        if (!\is_object($item)) {
            return;
        }
        CloudflarePurgeQueue::enqueue(CloudflarePurgeQueue::articlePaths($item));
    }
}
