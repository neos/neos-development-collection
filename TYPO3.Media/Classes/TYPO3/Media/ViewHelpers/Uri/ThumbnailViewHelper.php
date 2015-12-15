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
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ThumbnailConfiguration;

/**
 * Renders the src path of a thumbnail image of a given TYPO3.Media asset instance
 *
 * = Examples =
 *
 * <code title="Rendering an asset thumbnail path as-is">
 * {typo3.media:uri.thumbnail(asset: assetObject)}
 * </code>
 * <output>
 * (depending on the asset)
 * _Resources/Persistent/b29[...]95d.jpeg
 * </output>
 *
 *
 * <code title="Rendering an asset thumbnail path with scaling at a given width only">
 * {typo3.media:uri.thumbnail(asset: assetObject, maximumWidth: 80)}
 * </code>
 * <output>
 * (depending on the asset; has scaled keeping the aspect ratio)
 * _Resources/Persistent/b29[...]95d.jpeg
 * </output>
 *
 * @see \TYPO3\Media\ViewHelpers\ThumbnailViewHelper
 */
class ThumbnailViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper
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
     * Renders the path to a thumbnail image, created from a given asset.
     *
     * @param AssetInterface $asset
     * @param integer $width Desired width of the thumbnail
     * @param integer $maximumWidth Desired maximum width of the thumbnail
     * @param integer $height Desired height of the thumbnail
     * @param integer $maximumHeight Desired maximum height of the thumbnail
     * @param boolean $allowCropping Whether the thumbnail should be cropped if the given sizes would hurt the aspect ratio
     * @param boolean $allowUpScaling Whether the resulting thumbnail size might exceed the size of the original asset
     * @return string the relative thumbnail path, to be used as src attribute for <img /> tags
     */
    public function render(AssetInterface $asset = null, $width = null, $maximumWidth = null, $height = null, $maximumHeight = null, $allowCropping = false, $allowUpScaling = false)
    {
        $thumbnailConfiguration = new ThumbnailConfiguration($width, $maximumWidth, $height, $maximumHeight, $allowCropping, $allowUpScaling);
        return $this->assetService->getThumbnailUriAndSizeForAsset($asset, $thumbnailConfiguration)['src'];
    }
}
