<?php
namespace TYPO3\TYPO3CR\Migration\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Migration\Domain\Model\MigrationStatus as MigrationStatus;

/**
 * Service that runs over all nodes and applies migrations to them as given by configuration.
 */
class NodeMigration {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Migration\Service\NodeFilter
	 */
	protected $nodeFilterService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Migration\Service\NodeTransformation
	 */
	protected $nodeTransformationService;

	/**
	 * Migration configuration
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Model\Workspace
	 */
	protected $workspace;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManager
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected $context;

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Service\Context $context
	 * @param array $configuration
	 * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException
	 */
	public function __construct(\TYPO3\TYPO3CR\Domain\Service\Context $context, array $configuration) {
		$this->context = $context;
		$this->configuration = $configuration;
	}

	/**
	 * Do up migration.
	 *
	 * @return void
	 */
	public function migrateUp() {
		$rootNode = $this->context->getNode('/');
		$this->walkNodes($rootNode, MigrationStatus::DIRECTION_UP);
	}

	/**
	 * Do down migration.
	 *
	 * @return void
	 */
	public function migrateDown() {
		$rootNode = $this->context->getNode('/');
		$this->walkNodes($rootNode, MigrationStatus::DIRECTION_DOWN);
	}

	/**
	 * Walks over the nodes starting at the given node and executes the configured
	 * transformations on nodes matching the defined filters.
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $migrationType One of the MIGRATION_TYPE_* constants.
	 * @return void
	 */
	protected function walkNodes(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $migrationType) {
		foreach ($this->configuration as $migrationDescription) {
			if ($this->nodeFilterService->matchFilters($node, $migrationDescription['filters'])) {
				$this->nodeTransformationService->execute($node, $migrationDescription['transformations']);
			}
		}

		foreach ($node->getChildNodes() as $childNodes) {
			$this->walkNodes($childNodes, $migrationType);
		}
	}

}
?>