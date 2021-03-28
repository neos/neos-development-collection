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
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasDisabled;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasRemoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeReferencesWereSet;
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
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table ' . RestrictionHyperrelationRecord::TABLE_NAME);
            $this->getDatabaseConnection()->executeQuery('TRUNCATE table ' . ReferenceHyperrelationRecord::TABLE_NAME);
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
            $this->connectToHierarchy(
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
                        NodeRelationAnchorPoints::fromArray([$node->relationAnchorPoint])
                    );
                    $hierarchyRelation->addToDatabase($this->getDatabaseConnection());
                }
                $this->connectToRestrictionRelations($parentNodeAddress, $event->getNodeAggregateIdentifier());
            }
        });
    }

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function connectToHierarchy(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeRelationAnchorPoint $parentNodeAnchor,
        NodeRelationAnchorPoint $childNodeAnchor,
        DimensionSpacePointSet $dimensionSpacePointSet,
        ?NodeRelationAnchorPoint $succeedingSiblingNodeAnchor
    ): void {
        foreach ($dimensionSpacePointSet as $dimensionSpacePoint) {
            $hierarchyRelation = $this->projectionHypergraph->findHierarchyHyperrelationRecordByParentNodeAnchor(
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $parentNodeAnchor
            );
            if ($hierarchyRelation) {
                $hierarchyRelation->addChildNodeAnchor($childNodeAnchor, $succeedingSiblingNodeAnchor, $this->getDatabaseConnection());
            } else {
                $hierarchyRelation = new HierarchyHyperrelationRecord(
                    $contentStreamIdentifier,
                    $parentNodeAnchor,
                    $dimensionSpacePoint,
                    NodeRelationAnchorPoints::fromArray([$childNodeAnchor])
                );
                $hierarchyRelation->addToDatabase($this->getDatabaseConnection());
            }
        }
    }

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function connectToRestrictionRelations(
        NodeAddress $parentNodeAddress,
        NodeAggregateIdentifier $affectedNodeAggregateIdentifier
    ): void {
        foreach ($this->projectionHypergraph->findIngoingRestrictionRelations($parentNodeAddress) as $ingoingRestrictionRelation) {
            $ingoingRestrictionRelation->addAffectedNodeAggregateIdentifier($affectedNodeAggregateIdentifier, $this->getDatabaseConnection());
        }
    }

    /**
     * @param NodeAggregateWasDisabled $event
     * @throws \Throwable
     */
    public function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        $this->transactional(function() use($event) {
            $descendantNodeAggregateIdentifiersByAffectedDimensionSpacePoint = $this->projectionHypergraph->findDescendantNodeAggregateIdentifiers(
                $event->getContentStreamIdentifier(),
                $event->getAffectedDimensionSpacePoints(),
                $event->getNodeAggregateIdentifier()
            );

            foreach ($descendantNodeAggregateIdentifiersByAffectedDimensionSpacePoint as $dimensionSpacePointHash => $descendantNodeAggregateIdentifiers) {
                $restrictionRelation = new RestrictionHyperrelationRecord(
                    $event->getContentStreamIdentifier(),
                    $dimensionSpacePointHash,
                    $event->getNodeAggregateIdentifier(),
                    $descendantNodeAggregateIdentifiers
                );

                $restrictionRelation->addToDatabase($this->getDatabaseConnection());
            }
        });
    }

    public function whenNodeReferencesWereSet(NodeReferencesWereSet $event): void
    {
        $this->transactional(function() use($event) {
            $nodeRecord = $this->projectionHypergraph->findNodeRecordByOrigin(
                $event->getContentStreamIdentifier(),
                $event->getSourceOriginDimensionSpacePoint(),
                $event->getSourceNodeAggregateIdentifier()
            );

            if ($nodeRecord) {
                $existingReferenceRelation = $this->projectionHypergraph->findReferenceRelationByOrigin(
                    $nodeRecord->relationAnchorPoint,
                    $event->getReferenceName()
                );
                if ($existingReferenceRelation) {
                    $existingReferenceRelation->setDestinationNodeAggregateIdentifiers(
                        NodeAggregateIdentifiers::fromArray($event->getDestinationNodeAggregateIdentifiers()->getIterator()->getArrayCopy()),
                        $this->getDatabaseConnection()
                    );
                } else {
                    $referenceRelation = new ReferenceHyperrelationRecord(
                        $nodeRecord->relationAnchorPoint,
                        $event->getReferenceName(),
                        NodeAggregateIdentifiers::fromArray($event->getDestinationNodeAggregateIdentifiers()->getIterator()->getArrayCopy())
                    );
                    $referenceRelation->addToDatabase($this->getDatabaseConnection());
                }
            } else {
                // @todo log
            }
        });
    }

    /**
     * @throws \Throwable
     */
    public function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->transactional(function() use($event) {

        });
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
