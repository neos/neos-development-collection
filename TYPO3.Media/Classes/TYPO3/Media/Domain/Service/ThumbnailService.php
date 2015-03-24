<?php
namespace TYPO3\Media\Domain\Service;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\SignalSlot\Dispatcher;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\ThumbnailConfiguration;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Repository\ThumbnailRepository;
use TYPO3\Media\Exception\NoThumbnailAvailableException;

/**
 * An internal thumbnail service.
 *
 * Note that this repository is not part of the public API. Use the asset's getThumbnail() method instead.
 *
 * @Flow\Scope("singleton")
 */
class ThumbnailService
{
    /**
     * @Flow\Inject
     * @var ImageService
     */
    protected $imageService;

    /**
     * @Flow\Inject
     * @var ThumbnailRepository
     */
    protected $thumbnailRepository;

    /**
     * @Flow\Inject
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Resource\ResourceManager
     */
    protected $resourceManager;

    /**
     * Returns a thumbnail of the given asset
     *
     * If the maximum width / height is not specified or exceeds the original asset's dimensions, the width / height of
     * the original asset is used.
     *
     * @param AssetInterface $asset The asset to render a thumbnail for
     * @param ThumbnailConfiguration $configuration
     * @return Thumbnail
     * @throws \Exception
     */
    public function getThumbnail(AssetInterface $asset, ThumbnailConfiguration $configuration)
    {
        $thumbnail = $this->thumbnailRepository->findOneByAssetAndThumbnailConfiguration($asset, $configuration);
        if ($thumbnail === null) {
            try {
                $thumbnail = new Thumbnail($asset, $configuration);

                if ($thumbnail->isTransient() === false) {
                    // If the thumbnail strategy failed to generate a valid thumbnail
                    if ($thumbnail->getResource() === null) {
                        $this->thumbnailRepository->remove($thumbnail);
                        return null;
                    }

                    $this->thumbnailRepository->add($thumbnail);
                    $asset->addThumbnail($thumbnail);

                    $this->persistenceManager->whiteListObject($thumbnail);
                    $this->persistenceManager->whiteListObject($thumbnail->getResource());
                }
            } catch (NoThumbnailAvailableException $exception) {
                $this->systemLogger->logException($exception);
                return null;
            }
        }

        return $thumbnail;
    }
}
