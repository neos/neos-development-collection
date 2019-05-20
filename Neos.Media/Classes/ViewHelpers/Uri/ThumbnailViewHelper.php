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
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('asset', AssetInterface::class, 'The asset to be rendered as a thumbnail', true);
        $this->registerArgument('width', 'integer', 'Desired width of the thumbnail');
        $this->registerArgument('maximumWidth', 'integer', 'Desired maximum width of the thumbnail');
        $this->registerArgument('height', 'integer', 'Desired height of the thumbnail');
        $this->registerArgument('maximumHeight', 'integer', 'Desired maximum height of the thumbnail');
        $this->registerArgument('allowCropping', 'boolean', 'Whether the thumbnail should be cropped if the given sizes would hurt the aspect ratio', false, false);
        $this->registerArgument('allowUpScaling', 'boolean', 'Whether the resulting thumbnail size might exceed the size of the original asset', false, false);
        $this->registerArgument('async', 'boolean', 'Return asynchronous image URI in case the requested image does not exist already', false, false);
        $this->registerArgument('preset', 'string', 'Preset used to determine image configuration');
        $this->registerArgument('quality', 'integer', 'Quality of the image');
    }

    /**
     * Renders the path to a thumbnail image, created from a given asset.
     *
     * @return string the relative thumbnail path, to be used as src attribute for <img /> tags
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Media\Exception\AssetServiceException
     * @throws \Neos\Media\Exception\ThumbnailServiceException
     */
    public function render(): string
    {
        if ($this->arguments['preset'] !== null) {
            $thumbnailConfiguration = $this->thumbnailService->getThumbnailConfigurationForPreset($this->arguments['preset'], $this->arguments['async']);
        } else {
            $thumbnailConfiguration = new ThumbnailConfiguration($this->arguments['width'], $this->arguments['maximumWidth'], $this->arguments['height'], $this->arguments['maximumHeight'], $this->arguments['allowCropping'], $this->arguments['allowUpScaling'], $this->arguments['async'], $this->arguments['quality']);
        }
        $thumbnailData = $this->assetService->getThumbnailUriAndSizeForAsset($this->arguments['asset'], $thumbnailConfiguration, $this->controllerContext->getRequest());

        if ($thumbnailData === null) {
            return '';
        }

        return $thumbnailData['src'];
    }
}
