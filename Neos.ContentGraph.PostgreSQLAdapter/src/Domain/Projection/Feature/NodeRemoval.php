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
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyRelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionWriteQueries;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The node removal feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeRemoval
{
    /**
     * @throws \Throwable
     */
    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        // first step: find ingoing hierarchy relations for the node aggregate and remove the node from them,
        // then recursively remove all descendant hierarchy relations
        $ingoingRelations = $this->getReadQueries()->findIngoingHierarchyHyperrelationRecordsForNodeAggregate(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->affectedCoveredDimensionSpacePoints
        );

        foreach ($ingoingRelations as $ingoingRelationData) {
            /** @var HierarchyRelationRecord $parentRelation */
            $parentRelation = $ingoingRelationData['relation'];
            /** @var NodeRelationAnchorPoint $childNodeAnchor */
            $childNodeAnchor = $ingoingRelationData['childNodeAnchor'];

            // recursively remove all outgoing (descendant) hierarchy relations
            $this->removeDescendantHierarchyRelationsRecursively(
                $event->contentStreamId,
                $childNodeAnchor,
                $parentRelation->dimensionSpacePoint
            );

            // remove the node from its parent's child list (or delete the relation if it becomes empty)
            $parentRelation->removeChildNodeAnchor(
                $childNodeAnchor,
                $this->getDatabaseConnection(),
                $this->getTableNames()
            );
        }

        // second step: remove orphaned nodes

        // remove reference relations on orphans first
        $this->getDatabaseConnection()->executeStatement(
            "
                DELETE FROM {$this->tableNames->referenceRelation()} r
                WHERE sourcenodeanchor IN (
                SELECT relationanchorpoint FROM {$this->tableNames->node()} n WHERE
                    NOT EXISTS (
                        SELECT 1
                        FROM {$this->tableNames->hierarchyRelation()} h
                        WHERE n.relationanchorpoint = ANY(h.childnodeanchors)
                    ))"
        );

        $this->getDatabaseConnection()->executeStatement(
            /** @lang PostgreSQL */
            "
                DELETE FROM {$this->tableNames->node()} n
                WHERE
                    NOT EXISTS (
                        SELECT 1
                        FROM {$this->tableNames->hierarchyRelation()} h
                        WHERE n.relationanchorpoint = ANY(h.childnodeanchors)
                    )
            ",
        );
    }

    private function removeDescendantHierarchyRelationsRecursively(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $nodeAnchor,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        $outgoingRelation = $this->getReadQueries()->findHierarchyHyperrelationRecordByParentNodeAnchor(
            $contentStreamId,
            $dimensionSpacePoint,
            $nodeAnchor
        );

        if ($outgoingRelation) {
            foreach ($outgoingRelation->childNodeAnchors as $childNodeAnchor) {
                $this->removeDescendantHierarchyRelationsRecursively(
                    $contentStreamId,
                    $childNodeAnchor,
                    $dimensionSpacePoint
                );
            }
            $outgoingRelation->removeFromDatabase($this->getDatabaseConnection(), $this->getTableNames());
        }
    }


    abstract protected function getReadQueries(): ProjectionReadQueries;
    abstract protected function getWriteQueries(): ProjectionWriteQueries;

    abstract protected function getDatabaseConnection(): Connection;
    abstract protected function getTableNames(): ContentGraphTableNames;
}
