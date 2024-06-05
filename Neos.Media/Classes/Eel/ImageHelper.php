<?php

namespace Neos\Media\Eel;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Media\Exception\ThumbnailServiceException;

class ImageHelper implements ProtectedContextAwareInterface
{
    #[Flow\Inject]
    protected ThumbnailService $thumbnailService;

    /**
     * Returns a thumbnail of the given asset, allowing integrators to access the thumbnail size and other metadata.
     *
     * @param string|null $preset Name of the preset that should be used as basis for the configuration
     * @param integer|null $width Desired width of the image
     * @param integer|null $maximumWidth Desired maximum width of the image
     * @param integer|null $height Desired height of the image
     * @param integer|null $maximumHeight Desired maximum height of the image
     * @param boolean $allowCropping Whether the image should be cropped if the given sizes would hurt the aspect ratio
     * @param boolean $allowUpScaling Whether the resulting image size might exceed the size of the original image
     * @param boolean $async Whether the thumbnail can be generated asynchronously
     * @param integer|null $quality Quality of the processed image
     * @param string|null $format Format for the image, only jpg, jpeg, gif, png, wbmp, xbm, webp and bmp are supported.
     * @throws ThumbnailServiceException
     */
    public function createThumbnail(
        AssetInterface $asset,
        string $preset = null,
        int $width = null,
        int $maximumWidth = null,
        int $height = null,
        int $maximumHeight = null,
        bool $allowCropping = false,
        bool $allowUpScaling = false,
        bool $async = false,
        int $quality = null,
        string $format = null
    ): ?ImageInterface {
        if (!empty($preset)) {
            $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset($preset);
        } else {
            $thumbnailConfiguration = new ThumbnailConfiguration(
                $width,
                $maximumWidth,
                $height,
                $maximumHeight,
                $allowCropping,
                $allowUpScaling,
                $async,
                $quality,
                $format
            );
        }
        $thumbnailImage = $this->thumbnailService->getThumbnail($asset, $thumbnailConfiguration);
        if (!$thumbnailImage instanceof ImageInterface) {
            return null;
        }
        return $thumbnailImage;
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
