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
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Domain\Service\ThumbnailService;

/**
 * Renders the src path of a thumbnail image of a given Neos.Media asset instance
 *
 * = Examples =
 *
 * <code title="Rendering an asset thumbnail path as-is">
 * {neos.media:uri.thumbnail(asset: assetObject)}
 * </code>
 * <output>
 * (depending on the asset)
 * _Resources/Persistent/b29[...]95d.jpeg
 * </output>
 *
 *
 * <code title="Rendering an asset thumbnail path with scaling at a given width only">
 * {neos.media:uri.thumbnail(asset: assetObject, maximumWidth: 80)}
 * </code>
 * <output>
 * (depending on the asset; has scaled keeping the aspect ratio)
 * _Resources/Persistent/b29[...]95d.jpeg
 * </output>
 *
 * @see \Neos\Media\ViewHelpers\ThumbnailViewHelper
 */
class ThumbnailViewHelper extends AbstractViewHelper
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
     * Renders the path to a thumbnail image, created from a given asset.
     *
     * @param AssetInterface $asset
     * @param integer $width Desired width of the thumbnail
     * @param integer $maximumWidth Desired maximum width of the thumbnail
     * @param integer $height Desired height of the thumbnail
     * @param integer $maximumHeight Desired maximum height of the thumbnail
     * @param boolean $allowCropping Whether the thumbnail should be cropped if the given sizes would hurt the aspect ratio
     * @param boolean $allowUpScaling Whether the resulting thumbnail size might exceed the size of the original asset
     * @param boolean $async Return asynchronous image URI in case the requested image does not exist already
     * @param string $preset Preset used to determine image configuration
     * @return string the relative thumbnail path, to be used as src attribute for <img /> tags
     */
    public function render(AssetInterface $asset = null, $width = null, $maximumWidth = null, $height = null, $maximumHeight = null, $allowCropping = false, $allowUpScaling = false, $async = false, $preset = null)
    {
        if ($preset) {
            $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset($preset, $async);
        } else {
            $thumbnailConfiguration = new ThumbnailConfiguration($width, $maximumWidth, $height, $maximumHeight, $allowCropping, $allowUpScaling, $async);
        }
        return $this->assetService->getThumbnailUriAndSizeForAsset($asset, $thumbnailConfiguration, $this->controllerContext->getRequest())['src'];
    }
}
