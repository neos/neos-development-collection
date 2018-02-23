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
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;

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
     * @var NodeFilter
     */
    protected $nodeFilterService;

    /**
     * @Flow\Inject
     * @var NodeTransformation
     */
    protected $nodeTransformationService;

    /**
     * @Flow\Inject
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * Migration configuration
     * @var array
     */
    protected $configuration = [];

    /**
     * @param array $configuration
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
        $iterator = $this->nodeDataRepository->findAllIterator();
        $processed = 0;

        foreach ($this->nodeDataRepository->iterate($iterator) as $node) {
            foreach ($this->configuration as $migrationDescription) {
                if ($this->nodeFilterService->matchFilters($node, $migrationDescription['filters'])) {
                    $this->nodeTransformationService->execute($node, $migrationDescription['transformations']);

                    if (!$this->nodeDataRepository->isInRemovedNodes($node)) {
                        $this->nodeDataRepository->update($node);
                    }
                }
            }

            if ($processed % 1000 === 0) {
                $this->persistenceManager->persistAll();
                $this->persistenceManager->clearState();
            }

            $processed++;
        }
    }
}
