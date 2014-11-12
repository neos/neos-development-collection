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
class ImageViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	/**
	 * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
	 * @Flow\Inject
	 */
	protected $resourcePublisher;

	/**
	 * name of the tag to be created by this view helper
	 *
	 * @var string
	 */
	protected $tagName = 'img';

	/**
	 * @return void
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('alt', 'string', 'Specifies an alternate text for an image', TRUE);
		$this->registerTagAttribute('ismap', 'string', 'Specifies an image as a server-side image-map. Rarely used. Look at usemap instead', FALSE);
		$this->registerTagAttribute('usemap', 'string', 'Specifies an image as a client-side image-map', FALSE);
		// @deprecated since 1.1.0 image argument replaced with asset argument
		$this->registerArgument('image', 'ImageInterface', 'The image to be rendered', FALSE);
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
	public function render(AssetInterface $asset = NULL, $maximumWidth = NULL, $maximumHeight = NULL, $allowCropping = FALSE, $allowUpScaling = FALSE) {
		// Fallback for deprecated image argument
		$asset = $asset === NULL && $this->hasArgument('image') ? $this->arguments['image'] : $asset;
		if (!$asset instanceof AssetInterface) {
			throw new ViewHelperException('No asset given for rendering.', 1415797903);
		}
		if ($asset instanceof ImageInterface) {
			$thumbnailImage = $this->getImageThumbnailImage($asset, $maximumWidth, $maximumHeight, $allowCropping, $allowUpScaling);
			$this->tag->addAttributes(array(
				'width' => $thumbnailImage->getWidth(),
				'height' => $thumbnailImage->getHeight(),
				'src' => $this->resourcePublisher->getPersistentResourceWebUri($thumbnailImage->getResource()),
			));
		} else {
			$thumbnailImage = $this->getAssetThumbnailImage($asset, $maximumWidth, $maximumHeight);
			$this->tag->addAttributes(array(
				'width' => $thumbnailImage['width'],
				'height' => $thumbnailImage['height'],
				'src' => $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $thumbnailImage['src'],
			));
		}

		return $this->tag->render();
	}

	/**
	 * Calculates the dimensions of the thumbnail to be generated and returns the thumbnail image if the new dimensions
	 * differ from the specified image dimensions, otherwise the original image is returned.
	 *
	 * @param \TYPO3\Media\Domain\Model\ImageInterface $image
	 * @param integer $maximumWidth
	 * @param integer $maximumHeight
	 * @param boolean $allowCropping
	 * @param boolean $allowUpScaling
	 * @return \TYPO3\Media\Domain\Model\ImageInterface
	 *
	 * @TODO move code to trait in order to avoid duplication with uri.image ViewHelper
	 */
	protected function getImageThumbnailImage(ImageInterface $image, $maximumWidth, $maximumHeight, $allowCropping, $allowUpScaling) {
		$ratioMode = ($allowCropping ? ImageInterface::RATIOMODE_OUTBOUND : ImageInterface::RATIOMODE_INSET);
		if ($maximumWidth === NULL || ($allowUpScaling !== TRUE && $maximumWidth > $image->getWidth())) {
			$maximumWidth = $image->getWidth();
		}
		if ($maximumHeight === NULL || ($allowUpScaling !== TRUE && $maximumHeight > $image->getHeight())) {
			$maximumHeight = $image->getHeight();
		}
		if ($maximumWidth === $image->getWidth() && $maximumHeight === $image->getHeight()) {
			return $image;
		}
		return $image->getThumbnail($maximumWidth, $maximumHeight, $ratioMode);
	}

	/**
	 * @param AssetInterface $asset
	 * @param integer $maximumWidth
	 * @param integer $maximumHeight
	 * @return array
	 */
	protected function getAssetThumbnailImage(AssetInterface $asset, $maximumWidth, $maximumHeight) {
		$iconSize = $this->getDocumentIconSize($maximumWidth, $maximumHeight);

		if (is_file('resource://TYPO3.Media/Public/Icons/16px/' . $asset->getResource()->getFileExtension() . '.png')) {
			$icon = sprintf('TYPO3.Media/Icons/%spx/' . $asset->getResource()->getFileExtension() . '.png', $iconSize);
		} else {
			$icon =  sprintf('TYPO3.Media/Icons/%spx/_blank.png', $iconSize);
		}

		return array(
			'width' => $iconSize,
			'height' => $iconSize,
			'src' => $icon
		);
	}

	/**
	 * @param integer $maximumWidth
	 * @param integer $maximumHeight
	 * @return integer
	 */
	protected function getDocumentIconSize($maximumWidth, $maximumHeight) {
		$size = max($maximumWidth, $maximumHeight);
		if ($size <= 16) {
			return 16;
		} elseif ($size <= 32) {
			return 32;
		} elseif ($size <= 48) {
			return 48;
		} else {
			return 512;
		}
	}

}