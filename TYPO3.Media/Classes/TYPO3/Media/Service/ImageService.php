<?php
namespace TYPO3\Media\Service;

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
use TYPO3\Fluid\Core\ViewHelper\Exception;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ImageInterface;

/**
 * An image view service that generates thumbnails for TypoScript image object and an ImageViewHelper
 *
 * @Flow\Scope("singleton")
 */
class ImageService
{
    /**
     * Calculates the dimensions of the thumbnail to be generated and returns the thumbnail image if the new dimensions
     * differ from the specified image dimensions, otherwise the original image is returned.
     *
     * @param \TYPO3\Media\Domain\Model\ImageInterface $image
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @param boolean $allowCropping
     * @param boolean $allowUpScaling
     * @return \TYPO3\Media\Domain\Model\ImageInterface
     */
    public function getImageThumbnailImage(ImageInterface $image, $maximumWidth = null, $maximumHeight = null, $allowCropping = null, $allowUpScaling = null)
    {
        $ratioMode = ($allowCropping ? ImageInterface::RATIOMODE_OUTBOUND : ImageInterface::RATIOMODE_INSET);
        if ($maximumWidth === null || ($allowUpScaling !== true && $maximumWidth > $image->getWidth())) {
            $maximumWidth = $image->getWidth();
        }
        if ($maximumHeight === null || ($allowUpScaling !== true && $maximumHeight > $image->getHeight())) {
            $maximumHeight = $image->getHeight();
        }
        if ($maximumWidth === $image->getWidth() && $maximumHeight === $image->getHeight()) {
            return $image;
        }

        return $image->getThumbnail($maximumWidth, $maximumHeight, $ratioMode);
    }

    /**
     * @param AssetInterface $asset
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @return array
     */
    public function getAssetThumbnailImage(AssetInterface $asset, $maximumWidth, $maximumHeight)
    {
        $iconSize = $this->getDocumentIconSize($maximumWidth, $maximumHeight);

        if (is_file('resource://TYPO3.Media/Public/Icons/16px/' . $asset->getResource()->getFileExtension() . '.png')) {
            $icon = sprintf('TYPO3.Media/Icons/%spx/' . $asset->getResource()->getFileExtension() . '.png', $iconSize);
        } else {
            $icon = sprintf('TYPO3.Media/Icons/%spx/_blank.png', $iconSize);
        }

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
    public function getDocumentIconSize($maximumWidth, $maximumHeight)
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
