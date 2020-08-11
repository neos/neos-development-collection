<?php
namespace Neos\Media\Browser\ViewHelpers;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Media\Domain\Model\AssetSource\AssetProxy\AssetProxyInterface;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Renders an <img> HTML tag from a given Asset Proxy instance
 *
 * This view helper is INTERNAL for now, and only used in the Media Browser.
 * The functionality of this view helper might become part of the Neos.Media
 * thumbnail view helper if there is a demand for it.
 *
 * = Examples =
 *
 * <code title="Rendering an asset proxy thumbnail">
 * <mediaBrowser:thumbnail assetProxy="{assetProxyObject}" alt="a sample asset" />
 * </code>
 * <output>
 * <img src="https://my-asset-management.com/thumbnails/espresso.jpg" width="120" height="180" alt="a sample asset without scaling" />
 * </output>
 */
class ThumbnailViewHelper extends AbstractTagBasedViewHelper
{
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
        $this->registerTagAttribute('alt', 'string', 'Specifies an alternate text for an asset', true);
    }

    /**
     * Renders an HTML img tag with a thumbnail or preview image, created from a given asset proxy.
     *
     * @param AssetProxyInterface $assetProxy The asset to be rendered as a thumbnail
     * @param integer $width Desired width of the thumbnail
     * @param integer $height Desired height of the thumbnail
     * @return string an <img...> html tag
     */
    public function render(AssetProxyInterface $assetProxy = null, $width = null, $height = null)
    {
        if ($width === null || $height === null) {
            $width = 250;
            $height = 250;
        }

        if ($width <= 250 && $height <= 250) {
            $thumbnailUri = $assetProxy->getThumbnailUri();
        } else {
            $thumbnailUri = $assetProxy->getPreviewUri();
        }

        $this->tag->addAttributes([
            'width' => $width,
            'height' => $height,
            'src' => $thumbnailUri
        ]);

        return $this->tag->render();
    }
}
