<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelationRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ReferenceRelation;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateCoverageWasRestored;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Psr\Log\LoggerInterface;

/**
 * The NodeRemoval projection feature trait
 *
 * Requires RestrictionRelations to work
 */
trait NodeRemoval
{
    protected LoggerInterface $systemLogger;

    /**
     * @throws \Throwable
     */
    public function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        // the focus here is to be correct; that's why the method is not overly performant (for now at least). We might
        // lateron find tricks to improve performance
        $this->transactional(function () use ($event) {
            $this->removeOutgoingRestrictionRelationsOfNodeAggregateInDimensionSpacePoints(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->affectedCoveredDimensionSpacePoints
            );

            $ingoingRelations = $this->projectionContentGraph->findIngoingHierarchyRelationsForNodeAggregate(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
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
        $ingoingRelation->removeFromDatabase($this->getDatabaseConnection());

        foreach (
            $this->projectionContentGraph->findOutgoingHierarchyRelationsForNode(
                $ingoingRelation->childNodeAnchor,
                $ingoingRelation->contentStreamIdentifier,
                new DimensionSpacePointSet([$ingoingRelation->dimensionSpacePoint])
            ) as $outgoingRelation
        ) {
            $this->removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes($outgoingRelation);
        }

        // remove node itself if it does not have any incoming hierarchy relations anymore
        // also remove outbound reference relations
        $this->getDatabaseConnection()->executeStatement(
            '
            DELETE n, r FROM neos_contentgraph_node n
                LEFT JOIN ' . ReferenceRelation::TABLE_NAME . ' r ON r.nodeanchorpoint = n.relationanchorpoint
                LEFT JOIN ' . HierarchyRelationRecord::TABLE_NAME . ' h ON h.childnodeanchor = n.relationanchorpoint
                WHERE
                    n.relationanchorpoint = :anchorPointForNode
                    AND h.contentstreamidentifier IS NULL
                ',
            [
                'anchorPointForNode' => (string)$ingoingRelation->childNodeAnchor,
            ]
        );
    }

    public function whenNodeAggregateCoverageWasRestored(NodeAggregateCoverageWasRestored $event): void
    {
        $nodeRecord = $this->projectionContentGraph->findNodeByIdentifiers(
            $event->contentStreamIdentifier,
            $event->nodeAggregateIdentifier,
            $event->originDimensionSpacePoint
        );
        if (!$nodeRecord instanceof NodeRecord) {
            throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
        }

        // create a hierarchy relation to the restoration's parent
        foreach ($event->affectedCoveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $parentNodeRecord = $this->projectionContentGraph->findParentNodeRecordByOriginInDimensionSpacePoint(
                $event->contentStreamIdentifier,
                $event->originDimensionSpacePoint,
                $coveredDimensionSpacePoint,
                $event->nodeAggregateIdentifier
            );
            if (!$parentNodeRecord instanceof NodeRecord) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(get_class($event));
            }
            $position = $this->projectionContentGraph->determineHierarchyRelationPosition(
                $parentNodeRecord->relationAnchorPoint,
                null,
                null,
                $event->contentStreamIdentifier,
                $coveredDimensionSpacePoint
            );
            $this->getDatabaseConnection()->executeStatement(
                '
            INSERT INTO ' . HierarchyRelationRecord::TABLE_NAME . ' (
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
                    'dimensionSpacePoint' => json_encode($coveredDimensionSpacePoint),
                    'dimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
                    'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                    'originDimensionSpacePointHash' => $event->originDimensionSpacePoint->hash,
                    'nodeAggregateIdentifier' => (string)$event->nodeAggregateIdentifier,
                    'childRelationAnchorPoint' => (string)$nodeRecord->relationAnchorPoint,
                    'name' => $nodeRecord->nodeName?->value,
                    'parentRelationAnchor' => (string)$parentNodeRecord->relationAnchorPoint,
                    'position' => $position
                ]
            );
        }

        // cascade to all descendants

        $dimensionSpacePointsToJoin = '"' . implode(
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
            INSERT INTO ' . HierarchyRelationRecord::TABLE_NAME . ' (
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
                     * This provides a list of all hierarchy relations to be copied:
                     * parentnodeanchor and childnodeanchor, name and position only, the rest will be changed
                     */
                    WITH RECURSIVE descendantNodes(
                        relationanchorpoint, parentnodeanchor, name, position, childnodeanchor
                    ) AS (
                        /**
                         * Initial query: find all outgoing tethered child node relations
                         * from the starting node in its origin
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
                          ' . ($event->recursive ? '' : ' AND n.classification = :classification ') . '

                        UNION ALL
                            /**
                             * Iteration query: find all outgoing tethered child node relations
                             * from the parent node in its origin
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
                              ' . ($event->recursive ? '' : ' AND c.classification = :classification ') . '
                    ) SELECT name, position, parentnodeanchor, childnodeanchor,
                        dimensionspacepoint, dimensionspacepointhash
                        FROM descendantNodes
                        JOIN (
                            SELECT ' . $dimensionSpacePointsToJoin . '
                        ) AS dimensionSpacePoints
                    ) targetHierarchyRelation
            ',
            [
                'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                'classification' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value,
                'relationAnchorPoint' => (string)$nodeRecord->relationAnchorPoint,
                'originDimensionSpacePointHash' => $event->originDimensionSpacePoint->hash
            ]
        );
    }

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(\Closure $operations): void;
}
