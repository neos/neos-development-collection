<?php
namespace TYPO3\Media\ViewHelpers;

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
use TYPO3\Fluid\Core\ViewHelper\Exception;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Fluid\Core\ViewHelper\Exception as ViewHelperException;

/**
 * Renders an <img> HTML tag from a given TYPO3.Media's asset instance
 *
 * = Examples =
 *
 * <code title="Rendering an asset as-is">
 * <m:image asset="{assetObject}" alt="a sample image without scaling" />
 * </code>
 * <output>
 * (depending on the asset, no scaling applied)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="120" height="180" alt="a sample image without scaling" />
 * </output>
 *
 *
 * <code title="Rendering an image with scaling at a given width only">
 * <m:image asset="{assetObject}" maximumWidth="80" alt="sample" />
 * </code>
 * <output>
 * (depending on the asset; scaled down to a maximum width of 80 pixels, keeping the aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="120" alt="sample" />
 * </output>
 *
 *
 * <code title="Rendering an image with scaling at given width and height, keeping aspect ratio">
 * <m:image asset="{assetObject}" maximumWidth="80" maximumHeight="80" alt="sample" />
 * </code>
 * <output>
 * (depending on the asset; scaled down to a maximum width and height of 80 pixels, keeping the aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="53" height="80" alt="sample" />
 * </output>
 *
 *
 * <code title="Rendering an image with crop-scaling at given width and height">
 * <m:image asset="{assetObject}" maximumWidth="80" maximumHeight="80" allowCropping="true" alt="sample" />
 * </code>
 * <output>
 * (depending on the asset; scaled down to a width and height of 80 pixels, possibly changing aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="80" alt="sample" />
 * </output>
 *
 * <code title="Rendering an image with allowed up-scaling at given width and height">
 * <m:image asset="{assetObject}" maximumWidth="5000" allowUpScaling="true" alt="sample" />
 * </code>
 * <output>
 * (depending on the asset; scaled up or down to a width 5000 pixels, keeping aspect ratio)
 * <img src="_Resources/Persistent/b29[...]95d.jpeg" width="80" height="80" alt="sample" />
 * </output>
 *
 */
class ImageViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
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
        $this->registerTagAttribute('ismap', 'string', 'Specifies an image as a server-side image-map. Rarely used. Look at usemap instead', false);
        $this->registerTagAttribute('usemap', 'string', 'Specifies an image as a client-side image-map', false);
        // @deprecated since 1.1.0 image argument replaced with asset argument
        $this->registerArgument('image', 'ImageInterface', 'The image to be rendered', false);
    }

    /**
     * Renders an HTML tag from a given asset.
     *
     * @param AssetInterface $asset The asset to be rendered as an image
     * @param integer $maximumWidth Desired maximum height of the image
     * @param integer $maximumHeight Desired maximum width of the image
     * @param boolean $allowCropping Whether the image should be cropped if the given sizes would hurt the aspect ratio
     * @param boolean $allowUpScaling Whether the resulting image size might exceed the size of the original image
     * @return string an <img...> html tag
     * @throws Exception
     */
    public function render(AssetInterface $asset = null, $maximumWidth = null, $maximumHeight = null, $allowCropping = false, $allowUpScaling = false)
    {
        // Fallback for deprecated image argument
        $asset = $asset === null && $this->hasArgument('image') ? $this->arguments['image'] : $asset;
        if (!$asset instanceof AssetInterface) {
            throw new ViewHelperException('No asset given for rendering.', 1415797903);
        }

        try {
            if ($asset instanceof ImageInterface) {
                $thumbnailImage = $this->imageService->getImageThumbnailImage($asset, $maximumWidth, $maximumHeight, $allowCropping, $allowUpScaling);
                $this->tag->addAttributes(array(
                    'width' => $thumbnailImage->getWidth(),
                    'height' => $thumbnailImage->getHeight(),
                    'src' => $this->resourcePublisher->getPersistentResourceWebUri($thumbnailImage->getResource()),
                ));
            } else {
                $thumbnailImage = $this->imageService->getAssetThumbnailImage($asset, $maximumWidth, $maximumHeight);
                $this->tag->addAttributes(array(
                    'width' => $thumbnailImage['width'],
                    'height' => $thumbnailImage['height'],
                    'src' => $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $thumbnailImage['src'],
                ));
            }
        } catch (\Exception $exception) {
            $this->systemLogger->logException($exception);
            return '<!-- Unable to render image, exception code ' . $exception->getCode() . ' -->';
        }

        return $this->tag->render();
    }
}
