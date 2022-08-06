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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoints;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ReferenceRelationRecord;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateCoverageWasRestored;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;

/**
 * The node removal feature set for the hypergraph projector
 */
trait NodeRemoval
{
    /**
     * @throws \Throwable
     */
    public function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->transactional(function () use ($event) {
            $affectedRelationAnchorPoints = [];
            // first step: remove hierarchy relations
            foreach ($event->affectedCoveredDimensionSpacePoints as $dimensionSpacePoint) {
                $nodeRecord = $this->getProjectionHypergraph()->findNodeRecordByCoverage(
                    $event->getContentStreamIdentifier(),
                    $dimensionSpacePoint,
                    $event->getNodeAggregateIdentifier()
                );
                if (is_null($nodeRecord)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
                }

                /** @var HierarchyHyperrelationRecord $ingoingHierarchyRelation */
                $ingoingHierarchyRelation = $this->getProjectionHypergraph()
                    ->findHierarchyHyperrelationRecordByChildNodeAnchor(
                        $event->getContentStreamIdentifier(),
                        $dimensionSpacePoint,
                        $nodeRecord->relationAnchorPoint
                    );
                $ingoingHierarchyRelation->removeChildNodeAnchor(
                    $nodeRecord->relationAnchorPoint,
                    $this->getDatabaseConnection()
                );
                $this->removeFromRestrictions(
                    $event->getContentStreamIdentifier(),
                    $dimensionSpacePoint,
                    $event->getNodeAggregateIdentifier()
                );

                $affectedRelationAnchorPoints[] = $nodeRecord->relationAnchorPoint;

                $this->cascadeHierarchy(
                    $event->getContentStreamIdentifier(),
                    $dimensionSpacePoint,
                    $nodeRecord->relationAnchorPoint,
                    $affectedRelationAnchorPoints
                );
            }

            // second step: remove orphaned nodes
            $this->getDatabaseConnection()->executeStatement(
                /** @lang PostgreSQL */
                '
                WITH deletedNodes AS (
                    DELETE FROM ' . NodeRecord::TABLE_NAME . ' n
                    WHERE n.relationanchorpoint IN (
                        SELECT relationanchorpoint FROM ' . NodeRecord::TABLE_NAME . '
                            LEFT JOIN ' . HierarchyHyperrelationRecord::TABLE_NAME . ' h
                                ON n.relationanchorpoint = ANY(h.childnodeanchors)
                        WHERE n.relationanchorpoint IN (:affectedRelationAnchorPoints)
                            AND h.contentstreamidentifier IS NULL
                    )
                    RETURNING relationanchorpoint
                )
                DELETE FROM ' . ReferenceRelationRecord::TABLE_NAME . ' r
                    WHERE sourcenodeanchor IN (SELECT relationanchorpoint FROM deletedNodes)
                ',
                [
                    'affectedRelationAnchorPoints' => $affectedRelationAnchorPoints
                ],
                [
                    'affectedRelationAnchorPoints' => Connection::PARAM_STR_ARRAY
                ]
            );
        });
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function cascadeHierarchy(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $nodeRelationAnchorPoint,
        array &$affectedRelationAnchorPoints
    ): void {
        $childHierarchyRelation = $this->getProjectionHypergraph()->findHierarchyHyperrelationRecordByParentNodeAnchor(
            $contentStreamIdentifier,
            $dimensionSpacePoint,
            $nodeRelationAnchorPoint
        );
        if ($childHierarchyRelation) {
            $childHierarchyRelation->removeFromDatabase($this->getDatabaseConnection());

            foreach ($childHierarchyRelation->childNodeAnchors as $childNodeAnchor) {
                /** @var NodeRecord $nodeRecord */
                $nodeRecord = $this->getProjectionHypergraph()
                    ->findNodeRecordByRelationAnchorPoint($childNodeAnchor);
                $ingoingHierarchyRelations = $this->getProjectionHypergraph()
                    ->findHierarchyHyperrelationRecordsByChildNodeAnchor($childNodeAnchor);
                if (empty($ingoingHierarchyRelations)) {
                    ReferenceRelationRecord::removeFromDatabaseForSource(
                        $nodeRecord->relationAnchorPoint,
                        $this->getDatabaseConnection()
                    );
                    $affectedRelationAnchorPoints[] = $nodeRecord->relationAnchorPoint;
                }
                $this->removeFromRestrictions(
                    $contentStreamIdentifier,
                    $dimensionSpacePoint,
                    $nodeRecord->nodeAggregateIdentifier
                );
                $this->cascadeHierarchy(
                    $contentStreamIdentifier,
                    $dimensionSpacePoint,
                    $nodeRecord->relationAnchorPoint,
                    $affectedRelationAnchorPoints
                );
            }
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function removeFromRestrictions(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): void {
        foreach (
            $this->getProjectionHypergraph()->findIngoingRestrictionRelations(
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $nodeAggregateIdentifier
            ) as $restrictionRelation
        ) {
            $restrictionRelation->removeAffectedNodeAggregateIdentifier(
                $nodeAggregateIdentifier,
                $this->getDatabaseConnection()
            );
        }
    }

    public function whenNodeAggregateCoverageWasRestored(NodeAggregateCoverageWasRestored $event): void
    {
        $nodeRecord = $this->projectionHypergraph->findNodeRecordByOrigin(
            $event->contentStreamIdentifier,
            $event->originDimensionSpacePoint,
            $event->nodeAggregateIdentifier
        );
        if (!$nodeRecord instanceof NodeRecord) {
            throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
        }

        // create or adjust the target parent's child hierarchy hyperrelations
        foreach ($event->affectedCoveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
            $hierarchyRelation
                = $this->projectionHypergraph->findParentHierarchyHyperrelationRecordByOriginInDimensionSpacePoint(
                    $event->contentStreamIdentifier,
                    $event->originDimensionSpacePoint,
                    $coveredDimensionSpacePoint,
                    $event->nodeAggregateIdentifier
                );

            if ($hierarchyRelation instanceof HierarchyHyperrelationRecord) {
                $succeedingSiblingCandidates = $this->projectionHypergraph
                    ->findSucceedingSiblingRelationAnchorPointsByOriginInDimensionSpacePoint(
                        $event->contentStreamIdentifier,
                        $event->originDimensionSpacePoint,
                        $coveredDimensionSpacePoint,
                        $event->nodeAggregateIdentifier
                    );
                $hierarchyRelation->addChildNodeAnchorAfterFirstCandidate(
                    $nodeRecord->relationAnchorPoint,
                    $succeedingSiblingCandidates,
                    $this->getDatabaseConnection()
                );
            } else {
                $parentNodeRecord = $this->projectionHypergraph->findParentNodeRecordByOriginInDimensionSpacePoint(
                    $event->contentStreamIdentifier,
                    $event->originDimensionSpacePoint,
                    $coveredDimensionSpacePoint,
                    $event->nodeAggregateIdentifier
                );
                if (!$parentNodeRecord instanceof NodeRecord) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(get_class($event));
                }

                (new HierarchyHyperrelationRecord(
                    $event->contentStreamIdentifier,
                    $parentNodeRecord->relationAnchorPoint,
                    $coveredDimensionSpacePoint,
                    new NodeRelationAnchorPoints(
                        $nodeRecord->relationAnchorPoint
                    )
                ))->addToDatabase($this->getDatabaseConnection());
            }
        }

        // cascade to all descendants
        $this->getDatabaseConnection()->executeStatement(
            /** @lang PostgreSQL */
            '
            /**
             * This provides a list of all hierarchy relations to be copied:
             * parentnodeanchor and childnodeanchors only, the rest will be changed
             */
            WITH RECURSIVE descendantNodes(relationanchorpoint) AS (
                /**
                 * Initial query: find all outgoing tethered child node relations
                 * from the starting node in its origin
                 */
                SELECT
                    n.relationanchorpoint,
                    h.parentnodeanchor
                FROM  ' . NodeRecord::TABLE_NAME . ' n
                    JOIN ' . HierarchyHyperrelationRecord::TABLE_NAME . ' h
                        ON n.relationanchorpoint = ANY(h.childnodeanchors)
                WHERE h.parentnodeanchor = :relationAnchorPoint
                  AND h.contentstreamidentifier = :contentStreamIdentifier
                  AND h.dimensionspacepointhash = :originDimensionSpacePointHash
                  ' . ($event->recursive ? '' : ' AND n.classification = :classification') . '

                UNION ALL
                /**
                 * Iteration query: find all outgoing tethered child node relations
                 * from the parent node in its origin
                 */
                SELECT
                    c.relationanchorpoint,
                    h.parentnodeanchor
                FROM
                    descendantNodes p
                    JOIN ' . HierarchyHyperrelationRecord::TABLE_NAME . ' h
                        ON h.parentnodeanchor = p.relationanchorpoint
                    JOIN ' . NodeRecord::TABLE_NAME . ' c ON c.relationanchorpoint = ANY(h.childnodeanchors)
                WHERE h.contentstreamidentifier = :contentStreamIdentifier
                    AND h.dimensionspacepointhash = :originDimensionSpacePointHash
                    ' . ($event->recursive ? '' : ' AND c.classification = :classification') . '
            )
            INSERT INTO ' . HierarchyHyperrelationRecord::TABLE_NAME . '
                SELECT
                    :contentStreamIdentifier AS contentstreamidentifier,
                    parentnodeanchor,
                    CAST(dimensionspacepoint AS json),
                    dimensionspacepointhash,
                    array_agg(relationanchorpoint) AS childnodeanchors
                FROM descendantNodes
                    /**
                     * Finally, we join the relations to be copied
                     * with all dimension space points they are to be copied to
                     */
                    JOIN (
                        SELECT unnest(ARRAY[:dimensionSpacePoints]) AS dimensionspacepoint,
                        unnest(ARRAY[:dimensionSpacePointHashes]) AS dimensionspacepointhash
                    ) dimensionSpacePoints ON true
                GROUP BY parentnodeanchor, dimensionspacepoint, dimensionspacepointhash
            ',
            [
                'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                'classification' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value,
                'relationAnchorPoint' => (string)$nodeRecord->relationAnchorPoint,
                'originDimensionSpacePointHash' => $event->originDimensionSpacePoint->hash,
                'dimensionSpacePoints' => array_map(
                    fn(DimensionSpacePoint $dimensionSpacePoint): string
                        => json_encode($dimensionSpacePoint, JSON_THROW_ON_ERROR),
                    $event->affectedCoveredDimensionSpacePoints->points
                ),
                'dimensionSpacePointHashes' => $event->affectedCoveredDimensionSpacePoints->getPointHashes()
            ],
            [
                'dimensionSpacePoints' => Connection::PARAM_STR_ARRAY,
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        );
    }

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(\Closure $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
