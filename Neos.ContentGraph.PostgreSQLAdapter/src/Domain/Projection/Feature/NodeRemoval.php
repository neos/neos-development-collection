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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ReferenceRelationRecord;
use Neos\ContentRepository\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;

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
                    $this->getDatabaseConnection(),
                    $this->tableNamePrefix
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
                    DELETE FROM ' . $this->tableNamePrefix . '_node n
                    WHERE n.relationanchorpoint IN (
                        SELECT relationanchorpoint FROM ' . $this->tableNamePrefix . '_node
                            LEFT JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation h
                                ON n.relationanchorpoint = ANY(h.childnodeanchors)
                        WHERE n.relationanchorpoint IN (:affectedRelationAnchorPoints)
                            AND h.contentstreamidentifier IS NULL
                    )
                    RETURNING relationanchorpoint
                )
                DELETE FROM ' . $this->tableNamePrefix . '_referencerelation r
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
     * @param array<int,NodeRelationAnchorPoint> &$affectedRelationAnchorPoints
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
            $childHierarchyRelation->removeFromDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

            foreach ($childHierarchyRelation->childNodeAnchors as $childNodeAnchor) {
                /** @var NodeRecord $nodeRecord */
                $nodeRecord = $this->getProjectionHypergraph()
                    ->findNodeRecordByRelationAnchorPoint($childNodeAnchor);
                $ingoingHierarchyRelations = $this->getProjectionHypergraph()
                    ->findHierarchyHyperrelationRecordsByChildNodeAnchor($childNodeAnchor);
                if (empty($ingoingHierarchyRelations)) {
                    ReferenceRelationRecord::removeFromDatabaseForSource(
                        $nodeRecord->relationAnchorPoint,
                        $this->getDatabaseConnection(),
                        $this->tableNamePrefix
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
