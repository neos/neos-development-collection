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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyRelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionWriteQueries;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
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
        $affectedRelationAnchorPoints = [];
        // first step: remove hierarchy relations
        foreach ($event->affectedCoveredDimensionSpacePoints as $dimensionSpacePoint) {
            // TODO: find parent node anchor for $event->nodeAggregateId in CS + Dim $dimensionSpacePoint
            $parentHierarchyRelationRecord = HierarchyRelationRecord::fromDatabaseRow(TODO);

            $this->removeEdgeFromHyperrelationRecursively($parentHierarchyRelationRecord);
        }

        // second step: remove orphaned nodes

        // remove reference relation on orphans first
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

    private function removeEdgeFromHyperrelationRecursively(HierarchyRelationRecord $parentHierarchyRelationRecord) {
        foreach ($parentHierarchyRelationRecord->childNodeAnchors as $childNodeAnchor) {

            $childRecord = // TODO: LOAD THE RECORD
            $this->removeEdgeFromHyperrelationRecursively($childRecord);
        }
        $parentHierarchyRelationRecord->removeFromDatabase($this->getDatabaseConnection());
    }


    abstract protected function getReadQueries(): ProjectionReadQueries;
    abstract protected function getWriteQueries(): ProjectionWriteQueries;

    abstract protected function getDatabaseConnection(): Connection;
    abstract protected function getTableNames(): ContentGraphTableNames;
}
