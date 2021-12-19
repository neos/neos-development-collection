<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoints;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;

/**
 * The node creation feature set for the hypergraph projector
 */
trait NodeCreation
{
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
                $hierarchyRelation = $this->getProjectionHypergraph()->findChildHierarchyHyperrelationRecord(
                    $event->getContentStreamIdentifier(),
                    $dimensionSpacePoint,
                    $event->getParentNodeAggregateIdentifier()
                );
                if ($hierarchyRelation) {
                    $succeedingSiblingNodeAnchor = null;
                    if ($event->getSucceedingNodeAggregateIdentifier()) {
                        $succeedingSiblingNode = $this->getProjectionHypergraph()->findNodeRecordByCoverage(
                            $event->getContentStreamIdentifier(),
                            $dimensionSpacePoint,
                            $event->getSucceedingNodeAggregateIdentifier()
                        );
                        if ($succeedingSiblingNode) {
                            $succeedingSiblingNodeAnchor = $succeedingSiblingNode->relationAnchorPoint;
                        }
                    }
                    $hierarchyRelation->addChildNodeAnchor($node->relationAnchorPoint, $succeedingSiblingNodeAnchor, $this->getDatabaseConnection());
                } else {
                    $parentNode = $this->getProjectionHypergraph()->findNodeRecordByCoverage(
                        $event->getContentStreamIdentifier(),
                        $dimensionSpacePoint,
                        $event->getParentNodeAggregateIdentifier()
                    );
                    $hierarchyRelation = new HierarchyHyperrelationRecord(
                        $event->getContentStreamIdentifier(),
                        $parentNode->relationAnchorPoint,
                        $dimensionSpacePoint,
                        NodeRelationAnchorPoints::fromArray([$node->relationAnchorPoint])
                    );
                    $hierarchyRelation->addToDatabase($this->getDatabaseConnection());
                }
                $this->connectToRestrictionRelations(
                    $event->getContentStreamIdentifier(),
                    $dimensionSpacePoint,
                    $event->getParentNodeAggregateIdentifier(),
                    $event->getNodeAggregateIdentifier()
                );
            }
        });
    }

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function connectToHierarchy(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeRelationAnchorPoint $parentNodeAnchor,
        NodeRelationAnchorPoint $childNodeAnchor,
        DimensionSpacePointSet $dimensionSpacePointSet,
        ?NodeRelationAnchorPoint $succeedingSiblingNodeAnchor
    ): void {
        foreach ($dimensionSpacePointSet as $dimensionSpacePoint) {
            $hierarchyRelation = $this->getProjectionHypergraph()->findHierarchyHyperrelationRecordByParentNodeAnchor(
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
    protected function connectToRestrictionRelations(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeAggregateIdentifier $affectedNodeAggregateIdentifier
    ): void {
        foreach ($this->getProjectionHypergraph()->findIngoingRestrictionRelations(
            $contentStreamIdentifier,
            $dimensionSpacePoint,
            $parentNodeAggregateIdentifier
        ) as $ingoingRestrictionRelation) {
            $ingoingRestrictionRelation->addAffectedNodeAggregateIdentifier($affectedNodeAggregateIdentifier, $this->getDatabaseConnection());
        }
    }

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(callable $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
