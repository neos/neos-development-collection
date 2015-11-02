<?php
namespace TYPO3\Media\ViewHelpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3\Media\Domain\Model\AssetInterface;

/**
 * Renders an <img> HTML tag from a given TYPO3.Media's asset instance
 *
 * = Examples =
 *
 * <code title="Rendering an image as-is">
 * <typo3.media:image image="{imageObject}" alt="a sample image without scaling" />
 * </code>
 * <output>
 * (depending on the image, no scaling applied)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="120" height="180" alt="a sample image without scaling" />
 * </output>
 *
 *
 * <code title="Rendering an image with scaling at a given width only">
 * <typo3.media:image image="{imageObject}" maximumWidth="80" alt="sample" />
 * </code>
 * <output>
 * (depending on the image; scaled down to a maximum width of 80 pixels, keeping the aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="120" alt="sample" />
 * </output>
 *
 *
 * <code title="Rendering an image with scaling at given width and height, keeping aspect ratio">
 * <typo3.media:image image="{imageObject}" maximumWidth="80" maximumHeight="80" alt="sample" />
 * </code>
 * <output>
 * (depending on the image; scaled down to a maximum width and height of 80 pixels, keeping the aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="53" height="80" alt="sample" />
 * </output>
 *
 *
 * <code title="Rendering an image with crop-scaling at given width and height">
 * <typo3.media:image image="{imageObject}" maximumWidth="80" maximumHeight="80" allowCropping="true" alt="sample" />
 * </code>
 * <output>
 * (depending on the image; scaled down to a width and height of 80 pixels, possibly changing aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="80" alt="sample" />
 * </output>
 *
 * <code title="Rendering an image with allowed up-scaling at given width and height">
 * <typo3.media:image image="{imageObject}" maximumWidth="5000" allowUpScaling="true" alt="sample" />
 * </code>
 * <output>
 * (depending on the image; scaled up or down to a width 5000 pixels, keeping aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="80" alt="sample" />
 * </output>
 *
 */
class ThumbnailViewHelper extends AbstractTagBasedViewHelper
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
    }

    /**
     * Renders an HTML img tag with a thumbnail image, created from a given asset.
     *
     * @param AssetInterface $asset The asset to be rendered as an thumbnail
     * @param integer $maximumWidth Desired maximum height of the image
     * @param integer $maximumHeight Desired maximum width of the image
     * @param boolean $allowCropping Whether the image should be cropped if the given sizes would hurt the aspect ratio
     * @param boolean $allowUpScaling Whether the resulting image size might exceed the size of the original image
     *
     * @return string an <img...> html tag
     */
    public function render(AssetInterface $asset = null, $maximumWidth = null, $maximumHeight = null, $allowCropping = false, $allowUpScaling = false)
    {
        $thumbnailData = $this->assetService->getThumbnailUriAndSizeForAsset($asset, $maximumWidth, $maximumHeight, $allowCropping, $allowUpScaling);

        $this->tag->addAttributes(array(
            'width' => $thumbnailData['width'],
            'height' => $thumbnailData['height'],
            'src' => $thumbnailData['src']
        ));

        return $this->tag->render();
    }
}
