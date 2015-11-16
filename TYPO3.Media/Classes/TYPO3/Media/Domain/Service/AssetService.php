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
     * @param boolean $async Return asynchronous image URI in case the requested image does not exist already
     * @param ActionRequest $request Request argument must be provided for asynchronous thumbnails
     * @return array with keys "width", "height" and "src"
     * @throws AssetServiceException
     */
    public function getThumbnailUriAndSizeForAsset(AssetInterface $asset, ThumbnailConfiguration $configuration, $async = false, ActionRequest $request = null)
    {
        if ($asset instanceof ImageInterface) {
            $thumbnailImage = $this->thumbnailService->getThumbnail($asset, $configuration, $async);
            if ($async === true && $thumbnailImage->getResource() === null) {
                if ($request === null) {
                    throw new AssetServiceException('Request argument must be provided for async thumbnails.', 1447660835);
                }
                $this->uriBuilder->setRequest($request->getMainRequest());
                $uri = $this->uriBuilder
                    ->reset()
                    ->setCreateAbsoluteUri(true)
                    ->uriFor('thumbnail', array('thumbnail' => $thumbnailImage), 'Thumbnail', 'TYPO3.Media');
            } else {
                $uri = $this->resourceManager->getPublicPersistentResourceUri($thumbnailImage->getResource());
            }
            $thumbnailData = array(
                'width' => $thumbnailImage->getWidth(),
                'height' => $thumbnailImage->getHeight(),
                'src' => $uri
            );
        } else {
            $thumbnailData = $this->getAssetThumbnailImage($asset, $configuration->getWidth() ?: $configuration->getMaximumWidth(), $configuration->getHeight() ?: $configuration->getMaximumHeight());
        }

        return $thumbnailData;
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
