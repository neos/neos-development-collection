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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;

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
        $originDimensionSpacePoint = OriginDimensionSpacePoint::fromArray([]);

        $node = new NodeRecord(
            $nodeRelationAnchorPoint,
            $event->nodeAggregateIdentifier,
            $originDimensionSpacePoint,
            $originDimensionSpacePoint->hash,
            SerializedPropertyValues::fromArray([]),
            $event->nodeTypeName,
            $event->nodeAggregateClassification,
            null
        );

        $this->transactional(function () use ($node, $event) {
            $node->addToDatabase($this->getDatabaseConnection());
            $this->connectToHierarchy(
                $event->contentStreamIdentifier,
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
            $event->nodeAggregateIdentifier,
            $event->originDimensionSpacePoint,
            $event->originDimensionSpacePoint->hash,
            $event->initialPropertyValues,
            $event->nodeTypeName,
            $event->nodeAggregateClassification,
            $event->nodeName
        );

        $this->transactional(function () use ($node, $event) {
            $node->addToDatabase($this->getDatabaseConnection());
            foreach ($event->coveredDimensionSpacePoints as $dimensionSpacePoint) {
                $hierarchyRelation = $this->getProjectionHypergraph()->findChildHierarchyHyperrelationRecord(
                    $event->contentStreamIdentifier,
                    $dimensionSpacePoint,
                    $event->parentNodeAggregateIdentifier
                );
                if ($hierarchyRelation) {
                    $succeedingSiblingNodeAnchor = null;
                    if ($event->succeedingNodeAggregateIdentifier) {
                        $succeedingSiblingNode = $this->getProjectionHypergraph()->findNodeRecordByCoverage(
                            $event->contentStreamIdentifier,
                            $dimensionSpacePoint,
                            $event->succeedingNodeAggregateIdentifier
                        );
                        if ($succeedingSiblingNode) {
                            $succeedingSiblingNodeAnchor = $succeedingSiblingNode->relationAnchorPoint;
                        }
                    }
                    $hierarchyRelation->addChildNodeAnchor(
                        $node->relationAnchorPoint,
                        $succeedingSiblingNodeAnchor,
                        $this->getDatabaseConnection()
                    );
                } else {
                    $parentNode = $this->getProjectionHypergraph()->findNodeRecordByCoverage(
                        $event->contentStreamIdentifier,
                        $dimensionSpacePoint,
                        $event->parentNodeAggregateIdentifier
                    );
                    if (is_null($parentNode)) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                            get_class($event)
                        );
                    }
                    $hierarchyRelation = new HierarchyHyperrelationRecord(
                        $event->contentStreamIdentifier,
                        $parentNode->relationAnchorPoint,
                        $dimensionSpacePoint,
                        NodeRelationAnchorPoints::fromArray([$node->relationAnchorPoint])
                    );
                    $hierarchyRelation->addToDatabase($this->getDatabaseConnection());
                }
                $this->connectToRestrictionRelations(
                    $event->contentStreamIdentifier,
                    $dimensionSpacePoint,
                    $event->parentNodeAggregateIdentifier,
                    $event->nodeAggregateIdentifier
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
                $hierarchyRelation->addChildNodeAnchor(
                    $childNodeAnchor,
                    $succeedingSiblingNodeAnchor,
                    $this->getDatabaseConnection()
                );
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
        foreach (
            $this->getProjectionHypergraph()->findIngoingRestrictionRelations(
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $parentNodeAggregateIdentifier
            ) as $ingoingRestrictionRelation
        ) {
            $ingoingRestrictionRelation->addAffectedNodeAggregateIdentifier(
                $affectedNodeAggregateIdentifier,
                $this->getDatabaseConnection()
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
