<?php
namespace TYPO3\Media;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Package\Package as BasePackage;

/**
 * The Media Package
 */
class Package extends BasePackage
{
    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect('TYPO3\Media\Domain\Model\Image', 'assetCreated', 'TYPO3\Media\Domain\Service\ThumbnailGenerator', 'generateThumbnails');
        $dispatcher->connect('TYPO3\Media\Domain\Model\ImageVariant', 'assetCreated', 'TYPO3\Media\Domain\Service\ThumbnailGenerator', 'generateThumbnails');
    }
}
