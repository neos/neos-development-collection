<?php
namespace Neos\Neos\Fusion;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\AssetService;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Media\Domain\Service\ThumbnailService;

/**
 * Render an AssetInterface: object. Accepts the same parameters as the uri.image ViewHelper of the Neos.Media package:
 * asset, width, maximumWidth, height, maximumHeight, allowCropping, allowUpScaling.
 *
 */
class ImageUriImplementation extends AbstractFusionObject
{
    /**
     * Resource publisher
     *
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * Asset
     *
     * @return AssetInterface
     */
    public function getAsset()
    {
        return $this->fusionValue('asset');
    }

    /**
     * Width
     *
     * @return integer
     */
    public function getWidth()
    {
        return $this->fusionValue('width');
    }

    /**
     * MaximumWidth
     *
     * @return integer
     */
    public function getMaximumWidth()
    {
        return $this->fusionValue('maximumWidth');
    }

    /**
     * Height
     *
     * @return integer
     */
    public function getHeight()
    {
        return $this->fusionValue('height');
    }

    /**
     * MaximumHeight
     *
     * @return integer
     */
    public function getMaximumHeight()
    {
        return $this->fusionValue('maximumHeight');
    }

    /**
     * AllowCropping
     *
     * @return boolean
     */
    public function getAllowCropping()
    {
        return $this->fusionValue('allowCropping');
    }

    /**
     * AllowUpScaling
     *
     * @return boolean
     */
    public function getAllowUpScaling()
    {
        return $this->fusionValue('allowUpScaling');
    }

    /**
     * Async
     *
     * @return boolean
     */
    public function getAsync()
    {
        return $this->fusionValue('async');
    }

    /**
     * Preset
     *
     * @return string
     */
    public function getPreset()
    {
        return $this->fusionValue('preset');
    }

    /**
     * Returns a processed image path
     *
     * @return string
     * @throws \Exception
     */
    public function evaluate()
    {
        $asset = $this->getAsset();
        $preset = $this->getPreset();

        if (!$asset instanceof AssetInterface) {
            throw new \Exception('No asset given for rendering.', 1415184217);
        }
        if ($preset !== null) {
            $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset($preset);
        } else {
            $thumbnailConfiguration = new ThumbnailConfiguration($this->getWidth(), $this->getMaximumWidth(), $this->getHeight(), $this->getMaximumHeight(), $this->getAllowCropping(), $this->getAllowUpScaling(), $this->getAsync());
        }
        $request = $this->getRuntime()->getControllerContext()->getRequest();
        $thumbnailData = $this->assetService->getThumbnailUriAndSizeForAsset($asset, $thumbnailConfiguration, $request);
        if ($thumbnailData === null) {
            return '';
        }
        return $thumbnailData['src'];
    }
}
