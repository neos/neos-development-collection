<?php
namespace TYPO3\Media\Domain\EventListener;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Event\LifecycleEventArgs;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Model\ImageInterface;

/**
 * Doctrine event listener for getting image size and type if needed
 *
 * @Flow\Scope("singleton")
 */
class ImageEventListener
{
    /**
     * @var \TYPO3\Flow\Cache\CacheManager
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
            /** @var \TYPO3\Flow\Resource\Resource $resource */
            $resource = $eventArgs->getEntity()->getResource();
            if ($resource !== null) {
                $this->cacheManager->getCache('TYPO3_Media_ImageSize')->remove($resource->getCacheEntryIdentifier());
            }
        }
    }
}
