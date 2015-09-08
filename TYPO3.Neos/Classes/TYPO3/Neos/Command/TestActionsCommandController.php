<?php
namespace TYPO3\Neos\Command;

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
use TYPO3\Flow\Cli\ConsoleOutput;
use TYPO3\Neos\Service\NodeOperations;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\TYPO3CR\Utility;

/**
 * Temporary command controller to test actionsOnNodeCreation. Should be removed as soon as the functionality and the
 * wizard UI is finished.
 *
 * @Flow\Scope("singleton")
 */
class TestActionsCommandController extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var NodeOperations
	 */
	protected $nodeOperations;

	/**
	 *
	 */
	public function createCarouselCommand() {
		$context = $this->createContext('live');
		$rootNode = $context->getRootNode();
		$parentNode = $rootNode->getNode('/sites/neosdemotypo3org/features/custom-elements/main');
		$this->nodeOperations->create($parentNode, ['nodeType' => 'TYPO3.NeosDemoTypo3Org:Carousel'], 'into', ['text' => 'Text set via data array.']);
	}

	/**
	 * Creates a content context for given workspace and language identifiers
	 *
	 * @param string $workspaceName
	 * @param array $languageIdentifiers
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected function createContext($workspaceName, array $languageIdentifiers = NULL) {
		$contextProperties = array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => TRUE,
			'inaccessibleContentShown' => TRUE
		);
		if ($languageIdentifiers !== NULL) {
			$contextProperties = array_merge($contextProperties, array(
				'dimensions' => array('language' => $languageIdentifiers)
			));
		}
		return $this->contextFactory->create($contextProperties);
	}

}