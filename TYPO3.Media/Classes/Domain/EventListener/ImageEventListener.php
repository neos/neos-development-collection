<?php
namespace TYPO3\Media\Domain\EventListener;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Event\LifecycleEventArgs;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Resource\Resource as PersistentResource;
use TYPO3\Media\Domain\Model\ImageInterface;

/**
 * Doctrine event listener for getting image size and type if needed
 *
 * @Flow\Scope("singleton")
 */
class ImageEventListener
{
    /**
     * @var CacheManager
     * @Flow\Inject
     */
    protected $cacheManager;

    /**
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function postRemove(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if ($entity instanceof ImageInterface) {
            /** @var PersistentResource $resource */
            $resource = $eventArgs->getEntity()->getResource();
            if ($resource !== null) {
                $this->cacheManager->getCache('TYPO3_Media_ImageSize')->remove($resource->getCacheEntryIdentifier());
            }
        }
    }
}
