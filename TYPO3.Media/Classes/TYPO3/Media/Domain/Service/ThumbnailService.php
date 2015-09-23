<?php
namespace TYPO3\Media\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\SignalSlot\Dispatcher;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ImageInterface;
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
     * @param integer $maximumWidth The thumbnail's maximum width in pixels
     * @param integer $maximumHeight The thumbnail's maximum height in pixels
     * @param string $ratioMode Whether the resulting image should be cropped if both edge's sizes are supplied that would hurt the aspect ratio
     * @param boolean $allowUpScaling Whether the resulting image should be upscaled
     * @return Thumbnail
     * @throws \Exception
     */
    public function getThumbnail(AssetInterface $asset, $maximumWidth = null, $maximumHeight = null, $ratioMode = ImageInterface::RATIOMODE_INSET, $allowUpScaling = null)
    {
        $thumbnail = $this->thumbnailRepository->findOneByAssetAndDimensions($asset, $ratioMode, $maximumWidth, $maximumHeight, $allowUpScaling);
        if ($thumbnail === null) {
            if (!$asset instanceof ImageInterface) {
                throw new NoThumbnailAvailableException(sprintf('ThumbnailService could not generate a thumbnail for asset of type "%s" because currently only Image assets are supported.', get_class($asset)), 1381493670);
            }
            $thumbnail = new Thumbnail($asset, $maximumWidth, $maximumHeight, $ratioMode, $allowUpScaling);

            // Allow thumbnails to be persisted even if this is a "safe" HTTP request:
            $this->thumbnailRepository->add($thumbnail);
            $asset->addThumbnail($thumbnail);
            $this->persistenceManager->whiteListObject($thumbnail);
            $this->persistenceManager->whiteListObject($thumbnail->getResource());
        }

        return $thumbnail;
    }

    /**
     * @param AssetInterface $asset
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @return array
     */
    public function getStaticThumbnailForAsset(AssetInterface $asset, $maximumWidth, $maximumHeight)
    {
        $iconSize = $this->getDocumentIconSize($maximumWidth, $maximumHeight);

        if (is_file('resource://TYPO3.Media/Public/Icons/16px/' . $asset->getResource()->getFileExtension() . '.png')) {
            $icon = sprintf('Icons/%spx/' . $asset->getResource()->getFileExtension() . '.png', $iconSize);
        } else {
            $icon = sprintf('Icons/%spx/_blank.png', $iconSize);
        }

        $icon = $this->resourceManager->getPublicPackageResourceUri('TYPO3.Media', $icon);

        return array(
            'width' => $iconSize,
            'height' => $iconSize,
            'src' => $icon
        );
    }

    /**
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @return integer
     */
    protected function getDocumentIconSize($maximumWidth, $maximumHeight)
    {
        $size = max($maximumWidth, $maximumHeight);
        if ($size <= 16) {
            return 16;
        } elseif ($size <= 32) {
            return 32;
        } elseif ($size <= 48) {
            return 48;
        } else {
            return 512;
        }
    }
}
