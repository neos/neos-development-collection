<?php
declare(strict_types=1);

namespace Neos\Media\Fusion;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Exception;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\DataStructureImplementation;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Domain\Service\ThumbnailService;

/**
 * Render an AssetInterface: object. Accepts the same parameters as the uri.image ViewHelper of the Neos.Media package:
 * asset, width, maximumWidth, height, maximumHeight, allowCropping, allowUpScaling.
 *
 */
class ImageImplementation extends DataStructureImplementation
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
     * @return AssetInterface|null
     */
    public function getAsset(): ?AssetInterface
    {
        return $this->fusionValue('asset');
    }

    /**
     * Width
     *
     * @return integer
     */
    public function getWidth(): int
    {
        return $this->fusionValue('width');
    }

    /**
     * MaximumWidth
     *
     * @return integer
     */
    public function getMaximumWidth(): int
    {
        return $this->fusionValue('maximumWidth');
    }

    /**
     * Height
     *
     * @return integer
     */
    public function getHeight(): int
    {
        return $this->fusionValue('height');
    }

    /**
     * MaximumHeight
     *
     * @return integer
     */
    public function getMaximumHeight(): int
    {
        return $this->fusionValue('maximumHeight');
    }

    /**
     * AllowCropping
     *
     * @return boolean
     */
    public function getAllowCropping(): bool
    {
        return $this->fusionValue('allowCropping');
    }

    /**
     * AllowUpScaling
     *
     * @return boolean
     */
    public function getAllowUpScaling(): bool
    {
        return $this->fusionValue('allowUpScaling');
    }

    /**
     * Quality
     *
     * @return integer
     */
    public function getQuality(): int
    {
        return $this->fusionValue('quality');
    }

    /**
     * Async
     *
     * @return boolean
     */
    public function getAsync(): bool
    {
        return $this->fusionValue('async');
    }

    /**
     * Async
     *
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->fusionValue('format');
    }

    /**
     * Preset
     *
     * @return string
     */
    public function getPreset(): string
    {
        return $this->fusionValue('preset');
    }

    /**
     * Returns a processed image data array
     *
     * @return array
     * @throws Exception
     */
    public function evaluate()
    {
        $asset = $this->getAsset();
        $preset = $this->getPreset();

        if (!$asset instanceof AssetInterface) {
            throw new Exception('No asset given for rendering.', 1415184217);
        }
        if (!empty($preset)) {
            $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset($preset);
        } else {
            $thumbnailConfiguration = new ThumbnailConfiguration($this->getWidth(), $this->getMaximumWidth(), $this->getHeight(), $this->getMaximumHeight(), $this->getAllowCropping(), $this->getAllowUpScaling(), $this->getAsync(), $this->getQuality(), $this->getFormat());
        }
        $request = $this->getRuntime()->getControllerContext()->getRequest();
        $thumbnailData = $this->assetService->getThumbnailUriAndSizeForAsset($asset, $thumbnailConfiguration, $request);
        if ($thumbnailData === null) {
            return [];
        }
        return $thumbnailData;
    }
}
