<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\DbalClient;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\AbstractProcessedEventsAwareProjector;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient as EventStorageDbalClient;
use Neos\Flow\Annotations as Flow;

/**
 * The alternate reality-aware hypergraph projector for the PostgreSQL backend via Doctrine DBAL
 *
 * @Flow\Scope("singleton")
 */
class HypergraphProjector extends AbstractProcessedEventsAwareProjector
{
    private DbalClient $databaseClient;

    public function __construct(
        DbalClient $databaseClient,
        EventStorageDbalClient $eventStorageDatabaseClient,
        VariableFrontend $processedEventsCache
    ) {
        $this->databaseClient = $databaseClient;
        parent::__construct($eventStorageDatabaseClient, $processedEventsCache);
    }

    /**
     * @throws \Throwable
     */
    public function whenRootNodeAggregateWasCreated(RootNodeAggregateWithNodeWasCreated $event): void
    {
        $nodeRelationAnchorPoint = NodeRelationAnchorPoint::create();
        $originDimensionSpacePoint = new OriginDimensionSpacePoint([]);

        $node = new NodeRecord(
            $nodeRelationAnchorPoint,
            $event->getNodeAggregateIdentifier(),
            $originDimensionSpacePoint,
            $originDimensionSpacePoint->getHash(),
            SerializedPropertyValues::fromArray([]),
            $event->getNodeTypeName(),
            $event->getNodeAggregateClassification()
        );

        $this->transactional(function () use ($node, $event) {
            $node->addToDatabase($this->getDatabaseConnection());
            $this->connectHierarchy(
                $event->getContentStreamIdentifier(),
                NodeRelationAnchorPoint::forRootHierarchyRelation(),
                $node->relationAnchorPoint,
                $event->getCoveredDimensionSpacePoints(),
                null
            );
        });
    }

    /**
     * @throws DBALException
     */
    private function connectHierarchy(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeRelationAnchorPoint $parentNodeAnchorPoint,
        NodeRelationAnchorPoint $childNodeAnchorPoint,
        DimensionSpacePointSet $dimensionSpacePointSet,
        ?NodeRelationAnchorPoint $succeedingSiblingNodeAnchorPoint,
        ?NodeName $relationName = null
    ): void {
        $childNodeAnchorPoints = [];
        foreach ($dimensionSpacePointSet->getPoints() as $dimensionSpacePoint) {
            $childNodeAnchorPoints[$dimensionSpacePoint->getHash()][$relationName ? (string)$relationName : $childNodeAnchorPoint] = $childNodeAnchorPoint;
        }
        $hierarchyRelationSet = new HierarchyRelationSetRecord(
            $contentStreamIdentifier,
            $parentNodeAnchorPoint,
            $childNodeAnchorPoints
        );

        $hierarchyRelationSet->addToDatabase($this->getDatabaseConnection());
    }

    /**
     * @throws \Throwable
     */
    protected function transactional(callable $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->databaseClient->getConnection();
    }
}
