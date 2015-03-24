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
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Media\Domain\Model\AssetInterface;
use \TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Model\ThumbnailConfiguration;

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
     * @param ThumbnailConfiguration $configuration
     * @return array|null Array with keys "width", "height" and "src" if the thumbnail generation work or null
     */
    public function getThumbnailUriAndSizeForAsset(AssetInterface $asset, ThumbnailConfiguration $configuration)
    {
        $thumbnailImage = $this->getImageThumbnail($asset, $configuration);
        if ($thumbnailImage instanceof ImageInterface) {
            if ($thumbnailImage instanceof Thumbnail && $thumbnailImage->isTransient()) {
                $src = $thumbnailImage->getStaticResource();
            } else {
                $src = $this->resourceManager->getPublicPersistentResourceUri($thumbnailImage->getResource());
            }
            $thumbnailData = array(
                'width' => $thumbnailImage->getWidth(),
                'height' => $thumbnailImage->getHeight(),
                'src' => $src
            );
        } else {
            return null;
        }

        return $thumbnailData;
    }

    /**
     * Calculates the dimensions of the thumbnail to be generated and returns the thumbnail image if the new dimensions
     * differ from the specified image dimensions, otherwise the original image is returned.
     *
     * @param AssetInterface $asset
     * @param ThumbnailConfiguration $configuration
     * @return ImageInterface
     */
    protected function getImageThumbnail(AssetInterface $asset, ThumbnailConfiguration $configuration)
    {
        if ($configuration->isUpScalingAllowed() === false && $asset instanceof ImageInterface) {
            $maximumWidth = ($configuration->getMaximumWidth() > $asset->getWidth()) ? $asset->getWidth() : $configuration->getMaximumWidth();
            $maximumHeight = ($configuration->getMaximumHeight() > $asset->getHeight()) ? $asset->getHeight() : $configuration->getMaximumHeight();
            if ($maximumWidth === $asset->getWidth() && $maximumHeight === $asset->getHeight()) {
                return $asset;
            }
        }

        return $this->thumbnailService->getThumbnail($asset, $configuration);
    }
}
