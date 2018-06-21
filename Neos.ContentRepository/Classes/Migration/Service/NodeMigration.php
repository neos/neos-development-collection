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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\Flow\Persistence\Doctrine\Query;

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
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

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
     * Execute all migrations
     *
     * @throws \Neos\ContentRepository\Migration\Exception\MigrationException
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function execute()
    {
        foreach ($this->configuration as $migrationDescription) {
            /** array $migrationDescription */
            $this->executeSingle($migrationDescription);
        }
    }

    /**
     * Execute a single migration
     *
     * @param array $migrationDescription
     * @return void
     * @throws \Neos\ContentRepository\Migration\Exception\MigrationException
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    protected function executeSingle(array $migrationDescription)
    {
        $filterExpressions = [];
        $baseQuery = new Query(NodeData::class);
        foreach ($this->nodeFilterService->getFilterExpressions($migrationDescription['filters'], $baseQuery) as $filterExpression) {
            $filterExpressions[] = $filterExpression;
        }

        $query = new Query(NodeData::class);
        if ($filterExpressions !== []) {
            $query->matching(call_user_func_array([new Expr(), 'andX'], $filterExpressions));
        }
        $iterator = $query->getQueryBuilder()->getQuery()->iterate();

        $processed = 0;
        foreach ($this->nodeDataRepository->iterate($iterator) as $node) {
            if ($this->nodeFilterService->matchFilters($node, $migrationDescription['filters'])) {
                $this->nodeTransformationService->execute($node, $migrationDescription['transformations']);

                if (!$this->nodeDataRepository->isInRemovedNodes($node)) {
                    $this->nodeDataRepository->update($node);
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
