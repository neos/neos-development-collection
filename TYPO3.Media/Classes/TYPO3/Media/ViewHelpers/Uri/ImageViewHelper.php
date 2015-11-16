<?php
namespace TYPO3\Media\ViewHelpers\Uri;

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
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\ThumbnailConfiguration;

/**
 * Renders the src path of a thumbnail image of a given TYPO3.Media asset instance
 *
 * = Examples =
 *
 * <code title="Rendering an image path as-is">
 * {typo3.media:uri.image(image: imageObject)}
 * </code>
 * <output>
 * (depending on the image)
 * _Resources/Persistent/b29[...]95d.jpeg
 * </output>
 *
 *
 * <code title="Rendering an image path with scaling at a given width only">
 * {typo3.media:uri.image(image: assetObject, maximumWidth: 80)}
 * </code>
 * <output>
 * (depending on the image; has scaled keeping the aspect ratio)
 * _Resources/Persistent/b29[...]95d.jpeg
 * </output>
 *
 * @see \TYPO3\Media\ViewHelpers\ImageViewHelper
 */
class ImageViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @var \TYPO3\Flow\Resource\ResourceManager
     * @Flow\Inject
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var \TYPO3\Media\Domain\Service\ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @Flow\Inject
     * @var \TYPO3\Media\Domain\Service\AssetService
     */
    protected $assetService;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        // @deprecated since 2.0 use the "image" argument instead
        $this->registerArgument('asset', 'TYPO3\Media\Domain\Model\AssetInterface', 'The image to be rendered - DEPRECATED, use "image" argument instead', false);
    }

    /**
     * Renders the path to a thumbnail image, created from a given asset.
     *
     * @param ImageInterface $image
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
        if ($image === null && $this->hasArgument('asset')) {
            $image = $this->arguments['asset'];
        }
        if ($preset) {
            $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset($preset);
        } else {
            $thumbnailConfiguration = new ThumbnailConfiguration($width, $maximumWidth, $height, $maximumHeight, $allowCropping, $allowUpScaling);
        }
        return $this->assetService->getThumbnailUriAndSizeForAsset($image, $thumbnailConfiguration, $async, $this->controllerContext->getRequest())['src'];
    }
}
