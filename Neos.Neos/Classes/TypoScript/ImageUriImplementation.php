<?php
namespace Neos\Neos\TypoScript;

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
use Neos\Fusion\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * Render an AssetInterface: object. Accepts the same parameters as the uri.image ViewHelper of the Neos.Media package:
 * asset, width, maximumWidth, height, maximumHeight, allowCropping, allowUpScaling.
 *
 */
class ImageUriImplementation extends AbstractTypoScriptObject
{
    /**
     * Resource publisher
     *
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

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
     * Width
     *
     * @return integer
     */
    public function getWidth()
    {
        return $this->tsValue('width');
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
     * Height
     *
     * @return integer
     */
    public function getHeight()
    {
        return $this->tsValue('height');
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
     * @throws \Exception
     */
    public function evaluate()
    {
        $asset = $this->getAsset();
        if (!$asset instanceof AssetInterface) {
            throw new \Exception('No asset given for rendering.', 1415184217);
        }
        $thumbnailConfiguration = new ThumbnailConfiguration($this->getWidth(), $this->getMaximumWidth(), $this->getHeight(), $this->getMaximumHeight(), $this->getAllowCropping(), $this->getAllowUpScaling());
        $thumbnailData = $this->assetService->getThumbnailUriAndSizeForAsset($asset, $thumbnailConfiguration);
        if ($thumbnailData === null) {
            return '';
        }
        return $thumbnailData['src'];
    }
}
