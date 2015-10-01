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

/**
 * Service that runs over all nodes and applies migrations to them as given by configuration.
 */
class NodeMigration
{
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
     * @param array $configuration
     * @throws \TYPO3\TYPO3CR\Migration\Exception\MigrationException
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Execute the migration
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->nodeDataRepository->findAll() as $node) {
            foreach ($this->configuration as $migrationDescription) {
                if ($this->nodeFilterService->matchFilters($node, $migrationDescription['filters'])) {
                    $this->nodeTransformationService->execute($node, $migrationDescription['transformations']);
                    if (!$this->nodeDataRepository->isInRemovedNodes($node)) {
                        $this->nodeDataRepository->update($node);
                    }
                }
            }
        }
    }
}
