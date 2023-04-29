<?php

namespace Neos\Media\ViewHelpers;

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
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Domain\Service\ThumbnailService;

/**
 * Renders an <img> HTML tag from a given Neos.Media's image instance
 *
 * = Examples =
 *
 * <code title="Rendering an image as-is">
 * <neos.media:image image="{imageObject}" alt="a sample image without scaling" />
 * </code>
 * <output>
 * (depending on the image, no scaling applied)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="120" height="180" alt="a sample image without scaling" />
 * </output>
 *
 *
 * <code title="Rendering an image with scaling at a given width only">
 * <neos.media:image image="{imageObject}" maximumWidth="80" alt="sample" />
 * </code>
 * <output>
 * (depending on the image; scaled down to a maximum width of 80 pixels, keeping the aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="120" alt="sample" />
 * </output>
 *
 *
 * <code title="Rendering an image with scaling at given width and height, keeping aspect ratio">
 * <neos.media:image image="{imageObject}" maximumWidth="80" maximumHeight="80" alt="sample" />
 * </code>
 * <output>
 * (depending on the image; scaled down to a maximum width and height of 80 pixels, keeping the aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="53" height="80" alt="sample" />
 * </output>
 *
 *
 * <code title="Rendering an image with crop-scaling at given width and height">
 * <neos.media:image image="{imageObject}" maximumWidth="80" maximumHeight="80" allowCropping="true" alt="sample" />
 * </code>
 * <output>
 * (depending on the image; scaled down to a width and height of 80 pixels, possibly changing aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="80" alt="sample" />
 * </output>
 *
 * <code title="Rendering an image with allowed up-scaling at given width and height">
 * <neos.media:image image="{imageObject}" maximumWidth="5000" allowUpScaling="true" alt="sample" />
 * </code>
 * <output>
 * (depending on the image; scaled up or down to a width 5000 pixels, keeping aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="80" alt="sample" />
 * </output>
 *
 */
class ImageViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * name of the tag to be created by this view helper
     *
     * @var string
     */
    protected $tagName = 'img';

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('alt', 'string', 'Specifies an alternate text for an image', true);
        $this->registerTagAttribute('ismap', 'string', 'Specifies an image as a server-side image-map. Rarely used. Look at usemap instead');
        $this->registerTagAttribute('usemap', 'string', 'Specifies an image as a client-side image-map');
        $this->registerTagAttribute('loading', 'string', 'Specifies the loading attribute for an image', false, 'lazy');

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
     * Renders an HTML img tag with a thumbnail image, created from a given image.
     *
     * @return string an <img...> html tag
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

        $this->tag->addAttribute('src', $thumbnailData['src']);

        if ($thumbnailData['width'] > 0 && $thumbnailData['height'] > 0) {
            $this->tag->addAttributes([
                'width' => $thumbnailData['width'],
                'height' => $thumbnailData['height']
            ]);
        }

        // alt argument must be set because it is required (see $this->initializeArguments())
        if ($this->arguments['alt'] === '') {
            // has to be added explicitly because empty strings won't be added as attributes in general (see parent::initialize())
            $this->tag->addAttribute('alt', '');
        }

        return $this->tag->render();
    }
}
