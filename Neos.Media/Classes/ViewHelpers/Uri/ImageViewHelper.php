<?php
namespace Neos\Media\ViewHelpers\Uri;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;

/**
 * Renders the src path of a thumbnail image of a given Neos.Media image instance
 *
 * = Examples =
 *
 * <code title="Rendering an image path as-is">
 * {neos.media:uri.image(image: imageObject)}
 * </code>
 * <output>
 * (depending on the image)
 * _Resources/Persistent/b29[...]95d.jpeg
 * </output>
 *
 *
 * <code title="Rendering an image path with scaling at a given width only">
 * {neos.media:uri.image(image: imageObject, maximumWidth: 80)}
 * </code>
 * <output>
 * (depending on the image; has scaled keeping the aspect ratio)
 * _Resources/Persistent/b29[...]95d.jpeg
 * </output>
 *
 * @see \Neos\Media\ViewHelpers\ImageViewHelper
 */
class ImageViewHelper extends AbstractViewHelper
{
    /**
     * @Flow\Inject
     * @var \Neos\Media\Domain\Service\ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @Flow\Inject
     * @var \Neos\Media\Domain\Service\AssetService
     */
    protected $assetService;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
    }

    /**
     * Renders the path to a thumbnail image, created from a given image.
     *
     * @param ImageInterface $image The image to retrieve the path from
     * @param integer $width Desired width of the image
     * @param integer $maximumWidth Desired maximum width of the image
     * @param integer $height Desired height of the image
     * @param integer $maximumHeight Desired maximum height of the image
     * @param boolean $allowCropping Whether the image should be cropped if the given sizes would hurt the aspect ratio
     * @param boolean $allowUpScaling Whether the resulting image size might exceed the size of the original image
     * @param boolean $async Return asynchronous image URI in case the requested image does not exist already
     * @param string $preset Preset used to determine image configuration
     * @return string the relative image path, to be used as src attribute for <img /> tags
     */
    public function render(ImageInterface $image = null, $width = null, $maximumWidth = null, $height = null, $maximumHeight = null, $allowCropping = false, $allowUpScaling = false, $async = false, $preset = null)
    {
        if ($image === null) {
            return '';
        }

        if ($preset) {
            $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset($preset, $async);
        } else {
            $thumbnailConfiguration = new ThumbnailConfiguration($width, $maximumWidth, $height, $maximumHeight, $allowCropping, $allowUpScaling, $async);
        }
        return $this->assetService->getThumbnailUriAndSizeForAsset($image, $thumbnailConfiguration, $this->controllerContext->getRequest())['src'];
    }
}
