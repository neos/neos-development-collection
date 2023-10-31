<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * @internal
 */
trait RestrictionRelations
{
    abstract protected function getProjectionContentGraph(): ProjectionContentGraph;

    abstract protected function getTableNamePrefix(): string;

    /**
     * @param ContentStreamId $contentStreamId
     * @param NodeAggregateId $originNodeAggregateId
     * @param DimensionSpacePointSet $affectedDimensionSpacePoints
     * @throws \Doctrine\DBAL\DBALException
     */
    private function removeOutgoingRestrictionRelationsOfNodeAggregateInDimensionSpacePoints(
        ContentStreamId $contentStreamId,
        NodeAggregateId $originNodeAggregateId,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        $this->getDatabaseConnection()->executeUpdate(
            '
-- GraphProjector::removeOutgoingRestrictionRelationsOfNodeAggregateInDimensionSpacePoints

DELETE r.*
FROM ' . $this->getTableNamePrefix() . '_restrictionrelation r
WHERE r.contentstreamid = :contentStreamId
AND r.originnodeaggregateid = :originNodeAggregateId
AND r.dimensionspacepointhash in (:dimensionSpacePointHashes)',
            [
                'contentStreamId' => $contentStreamId->value,
                'originNodeAggregateId' => $originNodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes()
            ],
            [
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        );
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function removeAllRestrictionRelationsUnderneathNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId
    ): void {
        $this->getDatabaseConnection()->executeUpdate(
            '
                -- GraphProjector::removeAllRestrictionRelationsUnderneathNodeAggregate

                delete r.* from
                    ' . $this->getTableNamePrefix() . '_restrictionrelation r
                    join
                     (
                        -- we build a recursive tree
                        with recursive tree as (
                             -- --------------------------------
                             -- INITIAL query: select the root nodes of the tree
                             -- --------------------------------
                             select
                                n.relationanchorpoint,
                                n.nodeaggregateid,
                                h.dimensionspacepointhash
                             from
                                ' . $this->getTableNamePrefix() . '_node n
                             -- we need to join with the hierarchy relation,
                             -- because we need the dimensionspacepointhash.
                             inner join ' . $this->getTableNamePrefix() . '_hierarchyrelation h
                                on h.childnodeanchor = n.relationanchorpoint
                             where
                                n.nodeaggregateid = :entryNodeAggregateId
                                and h.contentstreamid = :contentStreamId
                        union
                             -- --------------------------------
                             -- RECURSIVE query: do one "child" query step
                             -- --------------------------------
                             select
                                c.relationanchorpoint,
                                c.nodeaggregateid,
                                h.dimensionspacepointhash
                             from
                                tree p
                             inner join ' . $this->getTableNamePrefix() . '_hierarchyrelation h
                                on h.parentnodeanchor = p.relationanchorpoint
                             inner join ' . $this->getTableNamePrefix() . '_node c
                                on h.childnodeanchor = c.relationanchorpoint
                             where
                                h.contentstreamid = :contentStreamId
                        )
                        select * from tree
                     ) as tree

                -- the "tree" CTE now contains a list of tuples (nodeAggregateId,dimensionSpacePointHash)
                -- which are *descendants* of the starting NodeAggregateId in ALL DimensionSpacePointHashes
                where
                    r.contentstreamid = :contentStreamId
                    and r.dimensionspacepointhash = tree.dimensionspacepointhash
                    and r.affectednodeaggregateid = tree.nodeaggregateid
            ',
            [
                'entryNodeAggregateId' => $nodeAggregateId->value,
                'contentStreamId' => $contentStreamId->value,
            ]
        );
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function removeAllRestrictionRelationsInSubtreeImposedByAncestors(
        ContentStreamId $contentStreamId,
        NodeAggregateId $entryNodeAggregateId,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        $projectionContentGraph = $this->getProjectionContentGraph();
        $descendantNodeAggregateIds = $projectionContentGraph->findDescendantNodeAggregateIds(
            $contentStreamId,
            $entryNodeAggregateId,
            $affectedDimensionSpacePoints
        );

        $this->getDatabaseConnection()->executeUpdate(
            '
                -- GraphProjector::removeAllRestrictionRelationsInSubtreeImposedByAncestors

                DELETE r.*
                    FROM ' . $this->getTableNamePrefix() . '_restrictionrelation r
                    WHERE r.contentstreamid = :contentStreamId
                    AND r.originnodeaggregateid NOT IN (:descendantNodeAggregateIds)
                    AND r.affectednodeaggregateid IN (:descendantNodeAggregateIds)
                    AND r.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)',
            [
                'contentStreamId' => $contentStreamId->value,
                'descendantNodeAggregateIds' => array_keys($descendantNodeAggregateIds),
                'affectedDimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes()
            ],
            [
                'descendantNodeAggregateIds' => Connection::PARAM_STR_ARRAY,
                'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        );
    }

    abstract protected function getDatabaseConnection(): Connection;
}
