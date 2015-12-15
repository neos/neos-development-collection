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
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Media\Domain\Model\AssetInterface;
use \TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Model\ThumbnailConfiguration;
use TYPO3\Media\Exception\AssetServiceException;

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
     * @Flow\Inject
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * Calculates the dimensions of the thumbnail to be generated and returns the thumbnail URI.
     * In case of Images this is a thumbnail of the image, in case of other assets an icon representation.
     *
     * @param AssetInterface $asset
     * @param ThumbnailConfiguration $configuration
     * @param ActionRequest $request Request argument must be provided for asynchronous thumbnails
     * @return array|null Array with keys "width", "height" and "src" if the thumbnail generation work or null
     * @throws AssetServiceException
     */
    public function getThumbnailUriAndSizeForAsset(AssetInterface $asset, ThumbnailConfiguration $configuration, ActionRequest $request = null)
    {
        $thumbnailImage = $this->getImageThumbnail($asset, $configuration);
        if (!$thumbnailImage instanceof ImageInterface) {
            return null;
        }
        if ($configuration->isAsync() === true && $thumbnailImage->getResource() === null && !$thumbnailImage->isTransient()) {
            if ($request === null) {
                throw new AssetServiceException('Request argument must be provided for async thumbnails.', 1447660835);
            }
            $this->uriBuilder->setRequest($request->getMainRequest());
            $uri = $this->uriBuilder
                ->reset()
                ->setCreateAbsoluteUri(true)
                ->uriFor('thumbnail', array('thumbnail' => $thumbnailImage), 'Thumbnail', 'TYPO3.Media');
        } else {
            if ($thumbnailImage instanceof Thumbnail && $thumbnailImage->isTransient()) {
                $uri = $thumbnailImage->getStaticResource();
            } else {
                $uri = $this->resourceManager->getPublicPersistentResourceUri($thumbnailImage->getResource());
            }
        }
        return array(
            'width' => $thumbnailImage->getWidth(),
            'height' => $thumbnailImage->getHeight(),
            'src' => $uri
        );
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
