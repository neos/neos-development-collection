<?php
namespace Neos\ContentRepository\Migration\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Migration\Exception\MigrationException;
use Neos\ContentRepository\Migration\Service\NodeFilter;
use Neos\ContentRepository\Migration\Service\NodeTransformation;

/**
 * Service that runs over all nodes and applies migrations to them as given by configuration.
 */
class NodeMigration
{
    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var NodeFilter
     */
    protected $nodeFilterService;

    /**
     * @Flow\Inject
     * @var NodeTransformation
     */
    protected $nodeTransformationService;

    /**
     * Migration configuration
     * @var array
     */
    protected $configuration = array();

    /**
     * @var Workspace
     */
    protected $workspace;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;


    /**
     * @param array $configuration
     * @throws MigrationException
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
