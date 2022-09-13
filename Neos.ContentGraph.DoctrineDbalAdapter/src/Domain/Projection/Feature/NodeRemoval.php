<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelationRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\RecursionMode;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateCoverageWasRestored;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Psr\Log\LoggerInterface;

/**
 * The NodeRemoval projection feature trait
 *
 * Requires RestrictionRelations to work
 *
 * @internal
 */
trait NodeRemoval
{
    abstract protected function getProjectionContentGraph(): ProjectionContentGraph;

    abstract protected function getTableNamePrefix(): string;

    protected LoggerInterface $systemLogger;

    /**
     * @throws \Throwable
     */
    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        // the focus here is to be correct; that's why the method is not overly performant (for now at least). We might
        // lateron find tricks to improve performance
        $this->transactional(function () use ($event) {
            $this->removeOutgoingRestrictionRelationsOfNodeAggregateInDimensionSpacePoints(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->affectedCoveredDimensionSpacePoints
            );

            $ingoingRelations = $this->getProjectionContentGraph()->findIngoingHierarchyRelationsForNodeAggregate(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->affectedCoveredDimensionSpacePoints
            );

            foreach ($ingoingRelations as $ingoingRelation) {
                $this->removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes($ingoingRelation);
            }
        });
    }

    /**
     * @param HierarchyRelationRecord $ingoingRelation
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes(
        HierarchyRelationRecord $ingoingRelation
    ): void {
        $ingoingRelation->removeFromDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

        foreach (
            $this->getProjectionContentGraph()->findOutgoingHierarchyRelationsForNode(
                $ingoingRelation->childNodeAnchor,
                $ingoingRelation->contentStreamId,
                new DimensionSpacePointSet([$ingoingRelation->dimensionSpacePoint])
            ) as $outgoingRelation
        ) {
            $this->removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes($outgoingRelation);
        }

        // remove node itself if it does not have any incoming hierarchy relations anymore
        // also remove outbound reference relations
        $this->getDatabaseConnection()->executeStatement(
            '
            DELETE n, r FROM ' . $this->getTableNamePrefix() . '_node n
                LEFT JOIN ' . $this->getTableNamePrefix() . '_referencerelation r
                    ON r.nodeanchorpoint = n.relationanchorpoint
                LEFT JOIN
                    ' . $this->getTableNamePrefix() . '_hierarchyrelation h
                        ON h.childnodeanchor = n.relationanchorpoint
                WHERE
                    n.relationanchorpoint = :anchorPointForNode
                    -- the following line means "left join leads to NO MATCHING hierarchyrelation"
                    AND h.contentstreamid IS NULL
                ',
            [
                'anchorPointForNode' => (string)$ingoingRelation->childNodeAnchor,
            ]
        );
    }

    public function whenNodeAggregateCoverageWasRestored(NodeAggregateCoverageWasRestored $event): void
    {
        $nodeRecord = $this->projectionContentGraph->findNodeInAggregate(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->sourceDimensionSpacePoint
        );
        if (!$nodeRecord instanceof NodeRecord) {
            throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
        }

        // create a hierarchy relation to the restoration's parent
        foreach ($event->affectedCoveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $parentNodeRecord = $this->projectionContentGraph->findParentNodeRecordByOriginInDimensionSpacePoint(
                $event->contentStreamId,
                $event->sourceDimensionSpacePoint,
                $coveredDimensionSpacePoint,
                $event->nodeAggregateId
            );
            if (!$parentNodeRecord instanceof NodeRecord) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(get_class($event));
            }
            $position = $this->projectionContentGraph->determineHierarchyRelationPosition(
                $parentNodeRecord->relationAnchorPoint,
                null,
                null,
                $event->contentStreamId,
                $coveredDimensionSpacePoint
            );
            $this->getDatabaseConnection()->executeStatement(
                '
            INSERT INTO ' . $this->getTableNamePrefix() . '_hierarchyrelation (
                  parentnodeanchor,
                  childnodeanchor,
                  `name`,
                  position,
                  dimensionspacepoint,
                  dimensionspacepointhash,
                  contentstreamidentifier
                )
                VALUES(
                    :parentRelationAnchor,
                    :childRelationAnchorPoint,
                    :name,
                    :position,
                    :dimensionSpacePoint,
                    :dimensionSpacePointHash,
                    :contentStreamIdentifier
                )',
                [
                    'parentRelationAnchor' => (string)$parentNodeRecord->relationAnchorPoint,
                    'childRelationAnchorPoint' => (string)$nodeRecord->relationAnchorPoint,
                    'name' => $nodeRecord->nodeName?->value,
                    'position' => $position,
                    'dimensionSpacePoint' => json_encode($coveredDimensionSpacePoint),
                    'dimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
                    'contentStreamIdentifier' => (string)$event->contentStreamId
                ]
            );
        }

        // cascade to all descendants

        // prepare a static UNION SELECT statement to join the affected dimension space points in the INSERT query
        $dimensionSpacePointsToJoin = 'SELECT "' . implode(
            ' UNION SELECT "',
            array_map(
                fn (DimensionSpacePoint $dimensionSpacePoint): string
                => \str_replace('"', '\"', json_encode($dimensionSpacePoint, JSON_THROW_ON_ERROR))
                    . '" AS dimensionspacepoint, "' . $dimensionSpacePoint->hash . '" AS dimensionspacepointhash',
                $event->affectedCoveredDimensionSpacePoints->points
            )
        );

        $this->getDatabaseConnection()->executeStatement(
            /** @lang MariaDB */
            '
            INSERT INTO ' . $this->getTableNamePrefix() . '_hierarchyrelation (
                name,
                position,
                contentstreamidentifier,
                dimensionspacepoint,
                dimensionspacepointhash,
                parentnodeanchor,
                childnodeanchor
            )
            SELECT name, position, :contentStreamIdentifier AS contentstreamidentifier,
                dimensionspacepoint, dimensionspacepointhash, parentnodeanchor, childnodeanchor FROM (
                    /**
                     * First, we collect all hierarchy relations to be copied in the restoration process.
                     * These are the descendant relations in the origin to be used:
                     * parentnodeanchor and childnodeanchor, name and position only, the rest will be filled with the
                     * affected dimension space point data
                     */
                    WITH RECURSIVE descendantNodes(
                        relationanchorpoint, parentnodeanchor, name, position, childnodeanchor
                    ) AS (
                        /**
                         * Iteration query: find all outgoing tethered child node relations
                         * from the parent node in its origin;
                         * which ones are resolved depends on the recursion mode.
                         */
                        SELECT
                            n.relationanchorpoint,
                            h.parentnodeanchor,
                            h.name,
                            h.position,
                            h.childnodeanchor
                        FROM neos_contentgraph_node n
                             JOIN neos_contentgraph_hierarchyrelation h ON n.relationanchorpoint = h.childnodeanchor
                        WHERE h.parentnodeanchor = :relationAnchorPoint
                          AND h.contentstreamidentifier = :contentStreamIdentifier
                          AND h.dimensionspacepointhash = :originDimensionSpacePointHash
                          ' . ($event->recursionMode === RecursionMode::MODE_ONLY_TETHERED_DESCENDANTS
                ? ' AND n.classification = :classification '
                : '') . '

                        UNION ALL
                            /**
                             * Iteration query: find all outgoing tethered child node relations
                             * from the parent node in its origin;
                             * which ones are resolved depends on the recursion mode.
                             */
                            SELECT
                                c.relationanchorpoint,
                                h.parentnodeanchor,
                                h.name,
                                h.position,
                                h.childnodeanchor
                            FROM
                                descendantNodes p
                                    JOIN neos_contentgraph_hierarchyrelation h
                                        ON h.parentnodeanchor = p.relationanchorpoint
                                    JOIN neos_contentgraph_node c ON c.relationanchorpoint = h.childnodeanchor
                            WHERE h.contentstreamidentifier = :contentStreamIdentifier
                              AND h.dimensionspacepointhash = :originDimensionSpacePointHash
                              ' . ($event->recursionMode === RecursionMode::MODE_ONLY_TETHERED_DESCENDANTS
                ? ' AND c.classification = :classification '
                : '') . '
                    ) SELECT name, position, parentnodeanchor, childnodeanchor,
                        dimensionspacepoint, dimensionspacepointhash
                        FROM descendantNodes
                        JOIN (
                            /** Here we join the affected dimension space points to actually create the new edges */
                            ' . $dimensionSpacePointsToJoin . '
                        ) AS dimensionSpacePoints
                    ) targetHierarchyRelation
            ',
            [
                'contentStreamIdentifier' => (string)$event->contentStreamId,
                'classification' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value,
                'relationAnchorPoint' => (string)$nodeRecord->relationAnchorPoint,
                'originDimensionSpacePointHash' => $event->sourceDimensionSpacePoint->hash
            ]
        );
    }

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(\Closure $operations): void;
}
