<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;

trait RestrictionRelations
{
    protected ProjectionContentGraph $projectionContentGraph;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $originNodeAggregateIdentifier
     * @param DimensionSpacePointSet $affectedDimensionSpacePoints
     * @throws \Doctrine\DBAL\DBALException
     */
    private function removeOutgoingRestrictionRelationsOfNodeAggregateInDimensionSpacePoints(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $originNodeAggregateIdentifier,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        $this->getDatabaseConnection()->executeUpdate(
            '
-- GraphProjector::removeOutgoingRestrictionRelationsOfNodeAggregateInDimensionSpacePoints

DELETE r.*
FROM neos_contentgraph_restrictionrelation r
WHERE r.contentstreamidentifier = :contentStreamIdentifier
AND r.originnodeaggregateidentifier = :originNodeAggregateIdentifier
AND r.dimensionspacepointhash in (:dimensionSpacePointHashes)',
            [
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'originNodeAggregateIdentifier' => (string)$originNodeAggregateIdentifier,
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
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): void {
        $this->getDatabaseConnection()->executeUpdate(
            '
                -- GraphProjector::removeAllRestrictionRelationsUnderneathNodeAggregate

                delete r.* from
                    neos_contentgraph_restrictionrelation r
                    join
                     (
                        -- we build a recursive tree
                        with recursive tree as (
                             -- --------------------------------
                             -- INITIAL query: select the root nodes of the tree
                             -- --------------------------------
                             select
                                n.relationanchorpoint,
                                n.nodeaggregateidentifier,
                                h.dimensionspacepointhash
                             from
                                neos_contentgraph_node n
                             -- we need to join with the hierarchy relation,
                             -- because we need the dimensionspacepointhash.
                             inner join neos_contentgraph_hierarchyrelation h
                                on h.childnodeanchor = n.relationanchorpoint
                             where
                                n.nodeaggregateidentifier = :entryNodeAggregateIdentifier
                                and h.contentstreamidentifier = :contentStreamIdentifier
                        union
                             -- --------------------------------
                             -- RECURSIVE query: do one "child" query step
                             -- --------------------------------
                             select
                                c.relationanchorpoint,
                                c.nodeaggregateidentifier,
                                h.dimensionspacepointhash
                             from
                                tree p
                             inner join neos_contentgraph_hierarchyrelation h
                                on h.parentnodeanchor = p.relationanchorpoint
                             inner join neos_contentgraph_node c
                                on h.childnodeanchor = c.relationanchorpoint
                             where
                                h.contentstreamidentifier = :contentStreamIdentifier
                        )
                        select * from tree
                     ) as tree

                -- the "tree" CTE now contains a list of tuples (nodeAggregateIdentifier,dimensionSpacePointHash)
                -- which are *descendants* of the starting NodeAggregateIdentifier in ALL DimensionSpacePointHashes
                where
                    r.contentstreamidentifier = :contentStreamIdentifier
                    and r.dimensionspacepointhash = tree.dimensionspacepointhash
                    and r.affectednodeaggregateidentifier = tree.nodeaggregateidentifier
            ',
            [
                'entryNodeAggregateIdentifier' => (string)$nodeAggregateIdentifier,
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            ]
        );
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function removeAllRestrictionRelationsInSubtreeImposedByAncestors(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $entryNodeAggregateIdentifier,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        $descendantNodeAggregateIdentifiers = $this->projectionContentGraph->findDescendantNodeAggregateIdentifiers(
            $contentStreamIdentifier,
            $entryNodeAggregateIdentifier,
            $affectedDimensionSpacePoints
        );

        $this->getDatabaseConnection()->executeUpdate(
            '
                -- GraphProjector::removeAllRestrictionRelationsInSubtreeImposedByAncestors

                DELETE r.*
                    FROM neos_contentgraph_restrictionrelation r
                    WHERE r.contentstreamidentifier = :contentStreamIdentifier
                    AND r.originnodeaggregateidentifier NOT IN (:descendantNodeAggregateIdentifiers)
                    AND r.affectednodeaggregateidentifier IN (:descendantNodeAggregateIdentifiers)
                    AND r.dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)',
            [
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'descendantNodeAggregateIdentifiers' => array_keys($descendantNodeAggregateIdentifiers),
                'affectedDimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes()
            ],
            [
                'descendantNodeAggregateIdentifiers' => Connection::PARAM_STR_ARRAY,
                'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        );
    }

    abstract protected function getDatabaseConnection(): Connection;
}
