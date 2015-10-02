<?php
namespace TYPO3\Neos\TypoScript;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * Render an AssetInterface: object. Accepts the same parameters as the uri.image ViewHelper of the TYPO3.Media package:
 * asset, maximumWidth, maximumHeight, allowCropping, allowUpScaling.
 *
 */
class ImageUriImplementation extends AbstractTypoScriptObject
{
    /**
     * Image service
     *
     * @var \TYPO3\Media\Service\ImageService
     * @Flow\Inject
     */
    protected $imageService;

    /**
     * Resource publisher
     *
     * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
     * @Flow\Inject
     */
    protected $resourcePublisher;

    /**
     * Asset
     *
     * @return AssetInterface
     */
    public function getAsset()
    {
        return $this->tsValue('asset');
    }

    /**
     * MaximumWidth
     *
     * @return integer
     */
    public function getMaximumWidth()
    {
        return $this->tsValue('maximumWidth');
    }

    /**
     * MaximumHeight
     *
     * @return integer
     */
    public function getMaximumHeight()
    {
        return $this->tsValue('maximumHeight');
    }

    /**
     * AllowCropping
     *
     * @return boolean
     */
    public function getAllowCropping()
    {
        return $this->tsValue('allowCropping');
    }

    /**
     * AllowUpScaling
     *
     * @return boolean
     */
    public function getAllowUpScaling()
    {
        return $this->tsValue('allowUpScaling');
    }

    /**
     * Returns a processed image path
     *
     * @return string
     */
    public function evaluate()
    {
        $asset = $this->getAsset();
        $maximumWidth = $this->getMaximumWidth();
        $maximumHeight = $this->getMaximumHeight();
        $allowCropping = $this->getAllowCropping();
        $allowUpScaling = $this->getAllowUpScaling();

        if (!$asset instanceof AssetInterface) {
            throw new \Exception('No asset given for rendering.', 1415184217);
        }
        if ($asset instanceof ImageInterface) {
            $thumbnailImage = $this->imageService->getImageThumbnailImage($asset, $maximumWidth, $maximumHeight, $allowCropping, $allowUpScaling);

            return $this->resourcePublisher->getPersistentResourceWebUri($thumbnailImage->getResource());
        } else {
            $thumbnailImage = $this->imageService->getAssetThumbnailImage($asset, $maximumWidth, $maximumHeight);

            return $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $thumbnailImage['src'];
        }
    }
}
