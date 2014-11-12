<?php
namespace TYPO3\Media\ViewHelpers\Uri;

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
class ImageViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
	 * @Flow\Inject
	 */
	protected $resourcePublisher;

	/**
	 * @return void
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		// @deprecated since 1.1.0 image argument replaced with asset argument
		$this->registerArgument('image', 'ImageInterface', 'The image to be rendered', FALSE);
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
	public function render(AssetInterface $asset = NULL, $maximumWidth = NULL, $maximumHeight = NULL, $allowCropping = FALSE, $allowUpScaling = FALSE) {
		// Fallback for deprecated image argument
		$asset = $asset === NULL && $this->hasArgument('image') ? $this->arguments['image'] : $asset;
		if (!$asset instanceof AssetInterface) {
			throw new ViewHelperException('No asset given for rendering.', 1415797902);
		}
		if ($asset instanceof ImageInterface) {
			$thumbnailImage = $this->getImageThumbnailImage($asset, $maximumWidth, $maximumHeight, $allowCropping, $allowUpScaling);
			return $this->resourcePublisher->getPersistentResourceWebUri($thumbnailImage->getResource());
		} else {
			$thumbnailImage = $this->getAssetThumbnailImage($asset, $maximumWidth, $maximumHeight);
			return $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $thumbnailImage['src'];
		}
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
	 * @TODO move code to trait in order to avoid duplication with image ViewHelper
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