<?php
namespace TYPO3\Neos\Controller\Module\Management;

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
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Error\Message;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

/**
 * Controller for asset handling
 *
 * @Flow\Scope("singleton")
 */
class AssetController extends \TYPO3\Media\Controller\AssetController {

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 *
	 */
	public function initializeObject() {
		$this->settings = $this->configurationManager->getConfiguration('Settings', 'TYPO3.Media');
	}

	/**
	 * Delete an asset
	 *
	 * @param \TYPO3\Media\Domain\Model\Asset $asset
	 * @return void
	 */
	public function deleteAction(\TYPO3\Media\Domain\Model\Asset $asset) {
		$identifier = $this->persistenceManager->getIdentifierByObject($asset);
		$relatedNodes = $this->nodeDataRepository->findByRelationWithGivenPersistenceIdentifierAndObjectTypeMap($identifier, array(
			'TYPO3\Media\Domain\Model\Asset' => '',
			'TYPO3\Media\Domain\Model\ImageVariant' => 'originalImage'
		));
		if (count($relatedNodes) > 0) {
			$this->addFlashMessage('Asset could not be deleted, because there are still Nodes using it.', '', Message::SEVERITY_WARNING, array(), 1412422767);
			$this->redirect('index');
		}

		// FIXME: Resources are not deleted, because we cannot be sure that the resource isn't used anywhere else.
		$this->assetRepository->remove($asset);
		$this->addFlashMessage('Asset has been deleted.', NULL, NULL, array(), 1412375050);
		$this->redirect('index');
	}

}
