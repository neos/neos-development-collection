<?php
namespace TYPO3\Media\Controller;


/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Controller for asset handling
 *
 * @Flow\Scope("singleton")
 */
class AssetController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\AssetRepository
	 */
	protected $assetRepository;

	/**
	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * List existing assets
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('assets', $this->assetRepository->findAll());
	}

	/**
	 * New asset form
	 *
	 * @return void
	 */
	public function newAction() {
	}

	/**
	 * Edit an asset
	 *
	 * @param \TYPO3\Media\Domain\Model\Asset $asset
	 * @return void
	 */
	public function editAction(\TYPO3\Media\Domain\Model\Asset $asset) {
		$this->view->assign('asset', $asset);
	}

	/**
	 * Update an asset
	 *
	 * @param \TYPO3\Media\Domain\Model\Asset $asset
	 * @return void
	 */
	public function updateAction(\TYPO3\Media\Domain\Model\Asset $asset) {
		$this->assetRepository->update($asset);
		$this->addFlashMessage('Asset has been updated.');
		$this->redirect('index');
	}

	/**
	 * Create a new asset
	 *
	 * @param \TYPO3\Media\Domain\Model\Asset $asset
	 * @return void
	 */
	public function createAction(\TYPO3\Media\Domain\Model\Asset $asset) {
		$title = $asset->getTitle();
		if ($title === '') {
			$title = $asset->getResource()->getFilename();
		}

		list($contentType, $subType) = explode('/', $asset->getResource()->getMediaType());
		switch ($contentType) {
			case 'image':
				$asset = new \TYPO3\Media\Domain\Model\Image($asset->getResource());
			break;
			case 'audio':
				$asset = new \TYPO3\Media\Domain\Model\Audio($asset->getResource());
			break;
			case 'video':
				$asset = new \TYPO3\Media\Domain\Model\Video($asset->getResource());
			break;
			case 'text':
				$asset = new \TYPO3\Media\Domain\Model\Document($asset->getResource());
			break;
			case 'application':
				if ($subType === 'pdf') {
					$asset = new \TYPO3\Media\Domain\Model\Document($asset->getResource());
				}
			break;
		}
		$asset->setTitle($title);
		$this->assetRepository->add($asset);
		$this->addFlashMessage('Asset has been added.');
		$this->redirect('index', NULL, NULL, array(), 0, 201);
	}

	/**
	 * Delete an asset
	 *
	 * @param \TYPO3\Media\Domain\Model\Asset $asset
	 * @return void
	 */
	public function deleteAction(\TYPO3\Media\Domain\Model\Asset $asset) {
		$this->resourceManager->deleteResource($asset->getResource());
		$this->assetRepository->remove($asset);
		$this->addFlashMessage('Asset has been deleted.');
		$this->redirect('index');
	}

}

?>