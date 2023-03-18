<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoints;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;

/**
 * The node creation feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeCreation
{
    /**
     * @throws \Throwable
     */
    private function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event): void
    {
        $nodeRelationAnchorPoint = NodeRelationAnchorPoint::create();
        $originDimensionSpacePoint = OriginDimensionSpacePoint::fromArray([]);

        $node = new NodeRecord(
            $nodeRelationAnchorPoint,
            $event->nodeAggregateId,
            $originDimensionSpacePoint,
            $originDimensionSpacePoint->hash,
            SerializedPropertyValues::fromArray([]),
            $event->nodeTypeName,
            $event->nodeAggregateClassification,
            null
        );

        $this->transactional(function () use ($node, $event) {
            $node->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
            $this->connectToHierarchy(
                $event->contentStreamId,
                NodeRelationAnchorPoint::forRootHierarchyRelation(),
                $node->relationAnchorPoint,
                $event->coveredDimensionSpacePoints,
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
            $event->nodeAggregateId,
            $event->originDimensionSpacePoint,
            $event->originDimensionSpacePoint->hash,
            $event->initialPropertyValues,
            $event->nodeTypeName,
            $event->nodeAggregateClassification,
            $event->nodeName
        );

        $this->transactional(function () use ($node, $event) {
            $node->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
            foreach ($event->coveredDimensionSpacePoints as $dimensionSpacePoint) {
                $hierarchyRelation = $this->getProjectionHypergraph()->findChildHierarchyHyperrelationRecord(
                    $event->contentStreamId,
                    $dimensionSpacePoint,
                    $event->parentNodeAggregateId
                );
                if ($hierarchyRelation) {
                    $succeedingSiblingNodeAnchor = null;
                    if ($event->succeedingNodeAggregateId) {
                        $succeedingSiblingNode = $this->getProjectionHypergraph()->findNodeRecordByCoverage(
                            $event->contentStreamId,
                            $dimensionSpacePoint,
                            $event->succeedingNodeAggregateId
                        );
                        if ($succeedingSiblingNode) {
                            $succeedingSiblingNodeAnchor = $succeedingSiblingNode->relationAnchorPoint;
                        }
                    }
                    $hierarchyRelation->addChildNodeAnchor(
                        $node->relationAnchorPoint,
                        $succeedingSiblingNodeAnchor,
                        $this->getDatabaseConnection(),
                        $this->tableNamePrefix
                    );
                } else {
                    $parentNode = $this->getProjectionHypergraph()->findNodeRecordByCoverage(
                        $event->contentStreamId,
                        $dimensionSpacePoint,
                        $event->parentNodeAggregateId
                    );
                    if (is_null($parentNode)) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                            get_class($event)
                        );
                    }
                    $hierarchyRelation = new HierarchyHyperrelationRecord(
                        $event->contentStreamId,
                        $parentNode->relationAnchorPoint,
                        $dimensionSpacePoint,
                        NodeRelationAnchorPoints::fromArray([$node->relationAnchorPoint])
                    );
                    $hierarchyRelation->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
                }
                $this->connectToRestrictionRelations(
                    $event->contentStreamId,
                    $dimensionSpacePoint,
                    $event->parentNodeAggregateId,
                    $event->nodeAggregateId
                );
            }
        });
    }

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function connectToHierarchy(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $parentNodeAnchor,
        NodeRelationAnchorPoint $childNodeAnchor,
        DimensionSpacePointSet $dimensionSpacePointSet,
        ?NodeRelationAnchorPoint $succeedingSiblingNodeAnchor
    ): void {
        foreach ($dimensionSpacePointSet as $dimensionSpacePoint) {
            $hierarchyRelation = $this->getProjectionHypergraph()->findHierarchyHyperrelationRecordByParentNodeAnchor(
                $contentStreamId,
                $dimensionSpacePoint,
                $parentNodeAnchor
            );
            if ($hierarchyRelation) {
                $hierarchyRelation->addChildNodeAnchor(
                    $childNodeAnchor,
                    $succeedingSiblingNodeAnchor,
                    $this->getDatabaseConnection(),
                    $this->tableNamePrefix
                );
            } else {
                $hierarchyRelation = new HierarchyHyperrelationRecord(
                    $contentStreamId,
                    $parentNodeAnchor,
                    $dimensionSpacePoint,
                    NodeRelationAnchorPoints::fromArray([$childNodeAnchor])
                );
                $hierarchyRelation->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
            }
        }
    }

    /**
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function connectToRestrictionRelations(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $parentNodeAggregateId,
        NodeAggregateId $affectedNodeAggregateId
    ): void {
        foreach (
            $this->getProjectionHypergraph()->findIngoingRestrictionRelations(
                $contentStreamId,
                $dimensionSpacePoint,
                $parentNodeAggregateId
            ) as $ingoingRestrictionRelation
        ) {
            $ingoingRestrictionRelation->addAffectedNodeAggregateId(
                $affectedNodeAggregateId,
                $this->getDatabaseConnection(),
                $this->tableNamePrefix
            );
        }
    }

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(\Closure $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
