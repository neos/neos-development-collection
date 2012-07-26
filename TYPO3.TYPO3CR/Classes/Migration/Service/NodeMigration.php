<?php
namespace TYPO3\TYPO3CR\Migration\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3CR".                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\TYPO3CR\Migration\Domain\Model\MigrationStatus as MigrationStatus;

/**
 * Service that runs over all nodes and applies migrations to them as given by configuration.
 */
class NodeMigration {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Migration\Service\NodeFilter
	 */
	protected $nodeFilterService;

	/**
	 * @FLOW3\Inject
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
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Package\PackageManager
	 */
	protected $packageManager;

	/**
	 * @var string
	 */
	protected $workspaceName;

	/**
	 * Constructor.
	 *
	 * @param string $workspaceName
	 * @param array $configuration
	 * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException
	 */
	public function __construct($workspaceName, array $configuration) {
		$this->workspaceName = $workspaceName;
		$this->configuration = $configuration;
	}

	/**
	 * Do up migration.
	 *
	 * @return void
	 */
	public function migrateUp() {
		$rootNode = $this->nodeRepository->findOneByPath('/', $this->getWorkspace());
		$this->walkNodes($rootNode, MigrationStatus::DIRECTION_UP);
	}

	/**
	 * Do down migration.
	 *
	 * @return void
	 */
	public function migrateDown() {
		$rootNode = $this->nodeRepository->findOneByPath('/', $this->getWorkspace());
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

	/**
	 * Returns the current workspace.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace The workspace
	 * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException
	 */
	public function getWorkspace() {
		if ($this->workspace === NULL) {
			$this->workspace = $this->workspaceRepository->findOneByName($this->workspaceName);
			if (!$this->workspace) {
				throw new \TYPO3\TYPO3CR\Migration\Exception\MigrationException('The workspace you want to migrate does not exist.', 1343302352);
			}
		}
		return $this->workspace;
	}
}
?>