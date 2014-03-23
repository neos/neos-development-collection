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
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ImageInterface;

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
	 * @return void
	 */
	public function initializeArguments() {
		parent::initializeArguments();
	}

	/**
	 * Renders the path to a thumbnail image, created from a given asset.
	 *
	 * @param AssetInterface $asset
	 * @param integer $maximumWidth Desired maximum height of the image
	 * @param integer $maximumHeight Desired maximum width of the image
	 * @param boolean $allowCropping Whether the image should be cropped if the given sizes would hurt the aspect ratio
	 * @param boolean $allowUpScaling Whether the resulting image size might exceed the size of the original image
	 * @return string the relative image path, to be used as src attribute for <img /> tags
	 */
	public function render(AssetInterface $asset = NULL, $maximumWidth = NULL, $maximumHeight = NULL, $allowCropping = FALSE, $allowUpScaling = FALSE) {
		return $this->assetService->getThumbnailUriForAsset($asset, $maximumWidth, $maximumHeight, $allowCropping, $allowUpScaling)['src'];
	}
}
