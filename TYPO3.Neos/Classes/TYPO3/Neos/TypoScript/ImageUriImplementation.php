<?php
namespace TYPO3\Neos\TypoScript;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Service\AssetService;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * Render an AssetInterface: object. Accepts the same parameters as the uri.image ViewHelper of the TYPO3.Media package:
 * asset, maximumWidth, maximumHeight, allowCropping, allowUpScaling.
 *
 */
class ImageUriImplementation extends AbstractTypoScriptObject {

	/**
	 * Resource publisher
	 *
	 * @Flow\Inject
	 * @var AssetService
	 */
	protected $assetService;

	/**
	 * Asset
	 *
	 * @return AssetInterface
	 */
	public function getAsset() {
		return $this->tsValue('asset');
	}

	/**
	 * MaximumWidth
	 *
	 * @return integer
	 */
	public function getMaximumWidth() {
		return $this->tsValue('maximumWidth');
	}

	/**
	 * MaximumHeight
	 *
	 * @return integer
	 */
	public function getMaximumHeight() {
		return $this->tsValue('maximumHeight');
	}

	/**
	 * AllowCropping
	 *
	 * @return boolean
	 */
	public function getAllowCropping() {
		return $this->tsValue('allowCropping');
	}

	/**
	 * AllowUpScaling
	 *
	 * @return boolean
	 */
	public function getAllowUpScaling() {
		return $this->tsValue('allowUpScaling');
	}

	/**
	 * Returns a processed image path
	 *
	 * @return string
	 */
	public function evaluate() {
		$asset = $this->getAsset();
		$maximumWidth = $this->getMaximumWidth();
		$maximumHeight = $this->getMaximumHeight();
		$allowCropping = $this->getAllowCropping();
		$allowUpScaling = $this->getAllowUpScaling();

		if (!$asset instanceof AssetInterface) {
			throw new \Exception('No asset given for rendering.', 1415184217);
		}
		return $this->assetService->getThumbnailUriAndSizeForAsset($asset, $maximumWidth, $maximumHeight, $allowCropping, $allowUpScaling)['src'];
	}
}
