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
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Domain\Service\ThumbnailService;

/**
 * Renders an <img> HTML tag from a given Neos.Media's asset instance
 *
 * = Examples =
 *
 * <code title="Rendering an asset thumbnail">
 * <neos.media:thumbnail asset="{assetObject}" alt="a sample asset without scaling" />
 * </code>
 * <output>
 * (depending on the asset, no scaling applied)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="120" height="180" alt="a sample asset without scaling" />
 * </output>
 *
 *
 * <code title="Rendering an asset thumbnail with scaling at a given width only">
 * <neos.media:thumbnail asset="{assetObject}" maximumWidth="80" alt="sample" />
 * </code>
 * <output>
 * (depending on the asset; scaled down to a maximum width of 80 pixels, keeping the aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="120" alt="sample" />
 * </output>
 *
 *
 * <code title="Rendering an asset thumbnail with scaling at given width and height, keeping aspect ratio">
 * <neos.media:thumbnail asset="{assetObject}" maximumWidth="80" maximumHeight="80" alt="sample" />
 * </code>
 * <output>
 * (depending on the asset; scaled down to a maximum width and height of 80 pixels, keeping the aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="53" height="80" alt="sample" />
 * </output>
 *
 *
 * <code title="Rendering an asset thumbnail with crop-scaling at given width and height">
 * <neos.media:thumbnail asset="{assetObject}" maximumWidth="80" maximumHeight="80" allowCropping="true" alt="sample" />
 * </code>
 * <output>
 * (depending on the asset; scaled down to a width and height of 80 pixels, possibly changing aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="80" alt="sample" />
 * </output>
 *
 * <code title="Rendering an asset thumbnail with allowed up-scaling at given width and height">
 * <neos.media:thumbnail asset="{assetObject}" maximumWidth="5000" allowUpScaling="true" alt="sample" />
 * </code>
 * <output>
 * (depending on the asset; scaled up or down to a width 5000 pixels, keeping aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="80" alt="sample" />
 * </output>
 *
 */
class ThumbnailViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var ResourceManager
     * @Flow\Inject
     */
    protected $resourceManager;

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
        $this->registerTagAttribute('alt', 'string', 'Specifies an alternate text for an asset', true);
    }

    /**
     * Renders an HTML img tag with a thumbnail image, created from a given asset.
     *
     * @param AssetInterface $asset The asset to be rendered as a thumbnail
     * @param integer $width Desired width of the thumbnail
     * @param integer $maximumWidth Desired maximum width of the thumbnail
     * @param integer $height Desired height of the thumbnail
     * @param integer $maximumHeight Desired maximum height of the thumbnail
     * @param boolean $allowCropping Whether the thumbnail should be cropped if the given sizes would hurt the aspect ratio
     * @param boolean $allowUpScaling Whether the resulting thumbnail size might exceed the size of the original asset
     * @param boolean $async Return asynchronous image URI in case the requested image does not exist already
     * @param string $preset Preset used to determine image configuration
     * @return string an <img...> html tag
     */
    public function render(AssetInterface $asset = null, $width = null, $maximumWidth = null, $height = null, $maximumHeight = null, $allowCropping = false, $allowUpScaling = false, $async = false, $preset = null)
    {
        if ($preset) {
            $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset($preset, $async);
        } else {
            $thumbnailConfiguration = new ThumbnailConfiguration($width, $maximumWidth, $height, $maximumHeight, $allowCropping, $allowUpScaling, $async);
        }
        $thumbnailData = $this->assetService->getThumbnailUriAndSizeForAsset($asset, $thumbnailConfiguration, $this->controllerContext->getRequest());

        if ($thumbnailData === null) {
            return '';
        }

        $this->tag->addAttributes(array(
            'width' => $thumbnailData['width'],
            'height' => $thumbnailData['height'],
            'src' => $thumbnailData['src']
        ));

        return $this->tag->render();
    }
}
