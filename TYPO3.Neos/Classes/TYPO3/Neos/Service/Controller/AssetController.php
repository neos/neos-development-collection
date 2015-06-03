<?php
namespace TYPO3\Neos\Service\Controller;

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

/**
 * Service Controller for managing assets
 */
class AssetController extends AbstractServiceController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\AssetRepository
	 */
	protected $assetRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\TagRepository
	 */
	protected $tagRepository;

	/**
	 * Shows a list of assets
	 *
	 * @param string $searchTerm
	 * @return void
	 */
	public function indexAction($searchTerm = NULL) {
		$assets = $this->assetRepository->findBySearchTermOrTags(
			$searchTerm,
			$this->tagRepository->findBySearchTerm($searchTerm)->toArray()
		);

		$this->view->assign('assets', $assets);
	}

	/**
	 * Shows a specific asset
	 *
	 * @param string $identifier
	 * @return string
	 */
	public function showAction($identifier) {
		$asset = $this->assetRepository->findByIdentifier($identifier);

		$this->view->assign('asset', $asset);
	}

}
