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
        $this->registerArgument('image', ImageInterface::class, 'The image to be rendered as an image');
        $this->registerArgument('width', 'integer', 'Desired width of the image');
        $this->registerArgument('maximumWidth', 'integer', 'Desired maximum width of the image');
        $this->registerArgument('height', 'integer', 'Desired height of the image');
        $this->registerArgument('maximumHeight', 'integer', 'Desired maximum height of the image');
        $this->registerArgument('allowCropping', 'boolean', 'Whether the image should be cropped if the given sizes would hurt the aspect ratio', false, false);
        $this->registerArgument('allowUpScaling', 'boolean', 'Whether the resulting image size might exceed the size of the original asset', false, false);
        $this->registerArgument('async', 'boolean', 'Return asynchronous image URI in case the requested image does not exist already', false, false);
        $this->registerArgument('preset', 'string', 'Preset used to determine image configuration');
        $this->registerArgument('quality', 'integer', 'Quality of the image, from 0 to 100');
        $this->registerArgument('format', 'string', 'Format for the image, jpg, jpeg, gif, png, wbmp, xbm, webp and bmp are supported');
    }

    /**
     * Renders the path to a thumbnail image, created from a given image.
     *
     * @return string the relative image path, to be used as src attribute for <img /> tags
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Media\Exception\AssetServiceException
     * @throws \Neos\Media\Exception\ThumbnailServiceException
     */
    public function render(): string
    {
        if ($this->arguments['image'] === null) {
            return '';
        }

        if ($this->arguments['preset'] !== null) {
            $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset($this->arguments['preset'], $this->arguments['async']);
        } else {
            $thumbnailConfiguration = new ThumbnailConfiguration($this->arguments['width'], $this->arguments['maximumWidth'], $this->arguments['height'], $this->arguments['maximumHeight'], $this->arguments['allowCropping'], $this->arguments['allowUpScaling'], $this->arguments['async'], $this->arguments['quality'], $this->arguments['format']);
        }
        $thumbnailData = $this->assetService->getThumbnailUriAndSizeForAsset($this->arguments['image'], $thumbnailConfiguration, $this->controllerContext->getRequest());

        if ($thumbnailData === null) {
            return '';
        }

        return $thumbnailData['src'];
    }
}
