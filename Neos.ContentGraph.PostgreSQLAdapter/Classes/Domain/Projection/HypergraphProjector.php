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
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWithNodeWasCreated;
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
final class HypergraphProjector extends AbstractProcessedEventsAwareProjector
{
    private DbalClient $databaseClient;

    private ProjectionHypergraph $projectionHypergraph;

    public function __construct(
        DbalClient $databaseClient,
        EventStorageDbalClient $eventStorageDatabaseClient,
        VariableFrontend $processedEventsCache
    ) {
        $this->databaseClient = $databaseClient;
        $this->projectionHypergraph = new ProjectionHypergraph($databaseClient);
        parent::__construct($eventStorageDatabaseClient, $processedEventsCache);
    }

    /**
     * @throws \Throwable
     */
    public function reset(): void
    {
        parent::reset();
        $this->transactional(function () {
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table ' . NodeRecord::TABLE_NAME);
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table ' . HierarchyHyperrelationRecord::TABLE_NAME);
        });
    }

    /**
     * @throws \Throwable
     */
    public function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event): void
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
            $event->getNodeAggregateClassification(),
            null
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
     * @param NodeAggregateWithNodeWasCreated $event
     * @throws \Throwable
     */
    public function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event): void
    {
        $nodeRelationAnchorPoint = NodeRelationAnchorPoint::create();
        $node = new NodeRecord(
            $nodeRelationAnchorPoint,
            $event->getNodeAggregateIdentifier(),
            $event->getOriginDimensionSpacePoint(),
            $event->getOriginDimensionSpacePoint()->getHash(),
            $event->getInitialPropertyValues(),
            $event->getNodeTypeName(),
            $event->getNodeAggregateClassification(),
            $event->getNodeName()
        );

        $this->transactional(function () use ($node, $event) {
            $node->addToDatabase($this->getDatabaseConnection());
            foreach ($event->getCoveredDimensionSpacePoints() as $dimensionSpacePoint) {
                $parentNodeAddress = new NodeAddress(
                    $event->getContentStreamIdentifier(),
                    $dimensionSpacePoint,
                    $event->getParentNodeAggregateIdentifier(),
                    null
                );
                $hierarchyRelation = $this->projectionHypergraph->findChildHierarchyHyperrelationRecordByAddress($parentNodeAddress);
                if ($hierarchyRelation) {
                    $succeedingSiblingNodeAnchor = null;
                    if ($event->getSucceedingNodeAggregateIdentifier()) {
                        $succeedingSiblingNodeAddress = $parentNodeAddress->withNodeAggregateIdentifier($event->getSucceedingNodeAggregateIdentifier());
                        $succeedingSiblingNode = $this->projectionHypergraph->findNodeRecordByAddress($succeedingSiblingNodeAddress);
                        if ($succeedingSiblingNode) {
                            $succeedingSiblingNodeAnchor = $succeedingSiblingNode->relationAnchorPoint;
                        }
                    }
                    $hierarchyRelation->addChildNodeAnchor($node->relationAnchorPoint, $succeedingSiblingNodeAnchor, $this->getDatabaseConnection());
                } else {
                    $parentNode = $this->projectionHypergraph->findNodeRecordByAddress($parentNodeAddress);
                    $hierarchyRelation = new HierarchyHyperrelationRecord(
                        $event->getContentStreamIdentifier(),
                        $parentNode->relationAnchorPoint,
                        $dimensionSpacePoint,
                        [$node->relationAnchorPoint]
                    );
                    $hierarchyRelation->addToDatabase($this->getDatabaseConnection());
                }
            }
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
        ?NodeRelationAnchorPoint $succeedingSiblingNodeAnchorPoint
    ): void {
        foreach ($dimensionSpacePointSet as $dimensionSpacePoint) {
            $hierarchyHyperrelationRecord = new HierarchyHyperrelationRecord(
                $contentStreamIdentifier,
                $parentNodeAnchorPoint,
                $dimensionSpacePoint,
                [$childNodeAnchorPoint]
            );
            $hierarchyHyperrelationRecord->addToDatabase($this->getDatabaseConnection());
        }
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
