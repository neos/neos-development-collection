<?php
namespace TYPO3\Neos\Controller\Backend;

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
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\ImageVariant;
use TYPO3\Media\Domain\Repository\ImageRepository;

/**
 * Controller for browsing images in the ImageEditor
 */
class ImageBrowserController extends MediaBrowserController {

	/**
	 * @Flow\Inject
	 * @var ImageRepository
	 */
	protected $assetRepository;

	/**
	 * @param Asset $asset
	 * @return void
	 */
	public function editAction(Asset $asset) {
		if ($asset instanceof ImageVariant) {
			$asset = $asset->getOriginalAsset();
		}
		parent::editAction($asset);
	}


}