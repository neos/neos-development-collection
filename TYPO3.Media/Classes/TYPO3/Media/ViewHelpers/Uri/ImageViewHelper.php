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
use TYPO3\Flow\Configuration\Exception\InvalidConfigurationException;
use TYPO3\Fluid\Core\ViewHelper\Exception;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Fluid\Core\ViewHelper\Exception as ViewHelperException;
use TYPO3\Media\Exception as MediaException;

/**
 * Renders the src path of a thumbnail image of a given TYPO3.Media asset instance
 *
 * = Examples =
 *
 * <code title="Rendering an asset path as-is">
 * {m:uri.image(asset: assetObject)}
 * </code>
 * <output>
 * (depending on the asset)
 * _Resources/Persistent/b29[...]95d.jpeg
 * </output>
 *
 *
 * <code title="Rendering an asset path with scaling at a given width only">
 * {m:uri.image(asset: assetObject, maximumWidth: 80)}
 * </code>
 * <output>
 * (depending on the asset; has scaled keeping the aspect ratio)
 * _Resources/Persistent/b29[...]95d.jpeg
 * </output>
 *
 * @see \TYPO3\Media\ViewHelpers\ImageViewHelper
 */
class ImageViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
     * @Flow\Inject
     */
    protected $resourcePublisher;

    /**
     * @var \TYPO3\Media\Service\ImageService
     * @Flow\Inject
     */
    protected $imageService;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        // @deprecated since 1.1.0 image argument replaced with asset argument
        $this->registerArgument('image', 'ImageInterface', 'The image to be rendered', false);
    }

    /**
     * Renders the path to a thumbnail image, created from a given asset.
     *
     * @param AssetInterface $image The asset to be rendered as an image
     * @param integer $maximumWidth Desired maximum height of the image
     * @param integer $maximumHeight Desired maximum width of the image
     * @param boolean $allowCropping Whether the image should be cropped if the given sizes would hurt the aspect ratio
     * @param boolean $allowUpScaling Whether the resulting image size might exceed the size of the original image
     * @return string the relative image path, to be used as src attribute for <img /> tags
     * @throws Exception
     */
    public function render(AssetInterface $asset = null, $maximumWidth = null, $maximumHeight = null, $allowCropping = false, $allowUpScaling = false)
    {
        // Fallback for deprecated image argument
        $asset = $asset === null && $this->hasArgument('image') ? $this->arguments['image'] : $asset;
        if (!$asset instanceof AssetInterface) {
            throw new ViewHelperException('No asset given for rendering.', 1415797902);
        }

        try {
            if ($asset instanceof ImageInterface) {
                $thumbnailImage = $this->imageService->getImageThumbnailImage($asset, $maximumWidth, $maximumHeight, $allowCropping, $allowUpScaling);
                return $this->resourcePublisher->getPersistentResourceWebUri($thumbnailImage->getResource());
            } else {
                $thumbnailImage = $this->imageService->getAssetThumbnailImage($asset, $maximumWidth, $maximumHeight);
                return $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $thumbnailImage['src'];
            }
        } catch (MediaException $exception) {
            $this->systemLogger->logException($exception);
            return null;
        } catch (InvalidConfigurationException $exception) {
            $this->systemLogger->logException($exception);
            return null;
        }
    }
}
