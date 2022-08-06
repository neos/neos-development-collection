<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
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
     * @param HierarchyRelation $ingoingRelation
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes(
        HierarchyRelation $ingoingRelation
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
                LEFT JOIN ' . ReferenceRelation::TABLE_NAME .' r ON r.nodeanchorpoint = n.relationanchorpoint
                LEFT JOIN ' . HierarchyRelation::TABLE_NAME .' h ON h.childnodeanchor = n.relationanchorpoint
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

        foreach ($event->affectedCoveredDimensionSpacePoints as $dimensionSpacePoint) {
            $this->getDatabaseConnection()->executeStatement(
                '
            INSERT INTO ' . HierarchyRelation::TABLE_NAME .' (
                  parentnodeanchor,
                  childnodeanchor,
                  `name`,
                  position,
                  dimensionspacepoint,
                  dimensionspacepointhash,
                  contentstreamidentifier
                )
                SELECT
                  h.childnodeanchor,
                  :childRelationAnchorPoint,
                  :name,
                  128, # @todo fetch best matching position
                  :dimensionSpacePoint AS dimensionspacepoint,
                  :dimensionSpacePointHash AS dimensionspacepointhash,
                  h.contentstreamidentifier
                FROM
                    ' . HierarchyRelation::TABLE_NAME . ' h
                    JOIN ' . NodeRecord::TABLE_NAME . ' n ON h.childnodeanchor = n.relationanchorpoint
                WHERE h.contentstreamidentifier = :contentStreamIdentifier
                    AND h.dimensionspacepointhash = :dimensionSpacePointHash
                    AND n.nodeaggregateidentifier = (
                        /**
                         * First, c
                         */
                        SELECT orgp.nodeaggregateidentifier FROM '  . NodeRecord::TABLE_NAME . ' orgp
                            JOIN ' . HierarchyRelation::TABLE_NAME . ' orgh ON orgh.parentnodeanchor = orgp.relationanchorpoint
                            JOIN '  . NodeRecord::TABLE_NAME . ' orgn ON orgh.childnodeanchor = orgn.relationanchorpoint
                        WHERE orgh.contentstreamidentifier = :contentStreamIdentifier
                            AND orgh.dimensionspacepointhash = :originDimensionSpacePointHash
                            AND orgn.nodeaggregateidentifier = :nodeAggregateIdentifier
                    )
                ',
                [
                    'dimensionSpacePoint' => json_encode($dimensionSpacePoint),
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                    'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                    'originDimensionSpacePointHash' => $event->originDimensionSpacePoint->hash,
                    'nodeAggregateIdentifier' => (string)$event->nodeAggregateIdentifier,
                    'childRelationAnchorPoint' => (string)$nodeRecord->relationAnchorPoint,
                    'name' => $nodeRecord->nodeName?->value
                ]
            );
        }

        if ($event->recursive) {

        } else {
            $this->getDatabaseConnection()->executeStatement(
                /** @lang MariaDB */ '
                INSERT INTO ' . HierarchyRelation::TABLE_NAME . ' (
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
                        WITH RECURSIVE descendantNodes(relationanchorpoint, parentnodeanchor, name, position, childnodeanchor) AS (
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
                            WHERE n.classification = :classification
                              AND h.parentnodeanchor = :relationAnchorPoint
                              AND h.contentstreamidentifier = :contentStreamIdentifier
                              AND h.dimensionspacepointhash = :originDimensionSpacePointHash

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
                                        JOIN neos_contentgraph_hierarchyrelation h ON h.parentnodeanchor = p.relationanchorpoint
                                        JOIN neos_contentgraph_node c ON c.relationanchorpoint = h.childnodeanchor
                                WHERE c.classification = :classification
                                  AND h.contentstreamidentifier = :contentStreamIdentifier
                                  AND h.dimensionspacepointhash = :originDimensionSpacePointHash
                        ) SELECT name, position, parentnodeanchor, childnodeanchor, dimensionspacepoint, dimensionspacepointhash FROM descendantNodes
                            JOIN (
                                SELECT "' . implode(
                                    ' UNION SELECT "',
                                    array_map(
                                        fn (DimensionSpacePoint $dimensionSpacePoint): string
                                            => \str_replace('"', '\"', json_encode($dimensionSpacePoint)) . '" AS dimensionspacepoint, "' . $dimensionSpacePoint->hash . '" AS dimensionspacepointhash',
                                        $event->affectedCoveredDimensionSpacePoints->points
                                    )
                                ) . '
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
    }

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(\Closure $operations): void;
}
