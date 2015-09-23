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
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Media\Domain\Model\AssetInterface;
use \TYPO3\Media\Domain\Model\ImageInterface;

class AssetService
{
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * Calculates the dimensions of the thumbnail to be generated and returns the thumbnail URI.
     * In case of Images this is a thumbnail of the image, in case of other assets an icon representation.
     *
     * @param AssetInterface $asset
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @param boolean $allowCropping
     * @param boolean $allowUpScaling
     * @return array with keys "width", "height" and "src"
     */
    public function getThumbnailUriAndSizeForAsset(AssetInterface $asset, $maximumWidth, $maximumHeight, $allowCropping = false, $allowUpScaling = null)
    {
        if ($asset instanceof ImageInterface) {
            $thumbnailImage = $this->getImageThumbnailImage($asset, $maximumWidth, $maximumHeight, $allowCropping, $allowUpScaling);
            $thumbnailData = array(
                'width' => $thumbnailImage->getWidth(),
                'height' => $thumbnailImage->getHeight(),
                'src' => $this->resourceManager->getPublicPersistentResourceUri($thumbnailImage->getResource())
            );
        } else {
            $thumbnailData = $this->getAssetThumbnailImage($asset, $maximumWidth, $maximumHeight);
        }

        return $thumbnailData;
    }

    /**
     * Calculates the dimensions of the thumbnail to be generated and returns the thumbnail image if the new dimensions
     * differ from the specified image dimensions, otherwise the original image is returned.
     *
     * @param ImageInterface $image
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @param boolean $allowCropping
     * @param boolean $allowUpScaling
     * @return ImageInterface
     */
    protected function getImageThumbnailImage(ImageInterface $image, $maximumWidth = null, $maximumHeight = null, $allowCropping = null, $allowUpScaling = null)
    {
        $ratioMode = ($allowCropping ? ImageInterface::RATIOMODE_OUTBOUND : ImageInterface::RATIOMODE_INSET);
        if ($allowUpScaling === false) {
            $maximumWidth = ($maximumWidth > $image->getWidth()) ? $image->getWidth() : $maximumWidth;
            $maximumHeight = ($maximumHeight > $image->getHeight()) ? $image->getHeight() : $maximumHeight;
        }
        if ($maximumWidth === $image->getWidth() && $maximumHeight === $image->getHeight()) {
            return $image;
        }

        return $this->thumbnailService->getThumbnail($image, $maximumWidth, $maximumHeight, $ratioMode, $allowUpScaling);
    }

    /**
     * @param AssetInterface $asset
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @return array
     */
    protected function getAssetThumbnailImage(AssetInterface $asset, $maximumWidth, $maximumHeight)
    {
        // TODO: Could be configurable at some point
        $iconPackage = 'TYPO3.Media';

        $iconSize = $this->getDocumentIconSize($maximumWidth, $maximumHeight);

        if (is_file('resource://' . $iconPackage . '/Public/Icons/16px/' . $asset->getResource()->getFileExtension() . '.png')) {
            $icon = sprintf('Icons/%spx/' . $asset->getResource()->getFileExtension() . '.png', $iconSize);
        } else {
            $icon = sprintf('Icons/%spx/_blank.png', $iconSize);
        }

        return array(
            'width' => $iconSize,
            'height' => $iconSize,
            'src' => $this->resourceManager->getPublicPackageResourceUri($iconPackage, $icon)
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
