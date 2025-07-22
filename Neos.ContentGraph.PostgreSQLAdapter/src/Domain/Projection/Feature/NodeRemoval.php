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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyRelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionWriteQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ReferenceRelationRecord;
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
            $nodeRecord = $this->getReadQueries()->findNodeRecordByCoverage(
                $event->getContentStreamId(),
                $dimensionSpacePoint,
                $event->getNodeAggregateId()
            );
            if (is_null($nodeRecord)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }

            /** @var HierarchyRelationRecord $ingoingHierarchyRelation */
            $ingoingHierarchyRelation = $this->getReadQueries()
                ->findHierarchyHyperrelationRecordByChildNodeAnchor(
                    $event->getContentStreamId(),
                    $dimensionSpacePoint,
                    $nodeRecord->relationAnchorPoint
                );
            $ingoingHierarchyRelation->removeChildNodeAnchor(
                $nodeRecord->relationAnchorPoint,
                $this->getDatabaseConnection(),
                $this->tableNamePrefix
            );
            $this->removeFromRestrictions(
                $event->getContentStreamId(),
                $dimensionSpacePoint,
                $event->getNodeAggregateId()
            );

            $affectedRelationAnchorPoints[] = $nodeRecord->relationAnchorPoint;

            $this->cascadeHierarchy(
                $event->getContentStreamId(),
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
                        AND h.contentstreamid IS NULL
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
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @param array<int,NodeRelationAnchorPoint> &$affectedRelationAnchorPoints
     */
    private function cascadeHierarchy(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $nodeRelationAnchorPoint,
        array &$affectedRelationAnchorPoints
    ): void {
        $childHierarchyRelation = $this->getReadQueries()->findHierarchyHyperrelationRecordByParentNodeAnchor(
            $contentStreamId,
            $dimensionSpacePoint,
            $nodeRelationAnchorPoint
        );
        if ($childHierarchyRelation) {
            $childHierarchyRelation->removeFromDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

            foreach ($childHierarchyRelation->childNodeAnchors as $childNodeAnchor) {
                /** @var NodeRecord $nodeRecord */
                $nodeRecord = $this->getReadQueries()
                    ->findNodeRecordByRelationAnchorPoint($childNodeAnchor);
                $ingoingHierarchyRelations = $this->getReadQueries()
                    ->findHierarchyHyperrelationRecordsByChildNodeAnchor($childNodeAnchor);
                if (empty($ingoingHierarchyRelations)) {
                    $this->getWriteQueries()->removeReferenceFromDatabaseForSource(
                        $this->getDatabaseConnection(),
                        $nodeRecord->relationAnchorPoint
                    );
                    $affectedRelationAnchorPoints[] = $nodeRecord->relationAnchorPoint;
                }
                $this->removeFromRestrictions(
                    $contentStreamId,
                    $dimensionSpacePoint,
                    $nodeRecord->nodeAggregateId
                );
                $this->cascadeHierarchy(
                    $contentStreamId,
                    $dimensionSpacePoint,
                    $nodeRecord->relationAnchorPoint,
                    $affectedRelationAnchorPoints
                );
            }
        }
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param NodeAggregateId $nodeAggregateId
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function removeFromRestrictions(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateId
    ): void {
        foreach (
            $this->getReadQueries()->findIngoingRestrictionRelations(
                $contentStreamId,
                $dimensionSpacePoint,
                $nodeAggregateId
            ) as $restrictionRelation
        ) {
            $restrictionRelation->removeAffectedNodeAggregateId(
                $nodeAggregateId,
                $this->getDatabaseConnection(),
                $this->tableNamePrefix
            );
        }
    }

    abstract protected function getReadQueries(): ProjectionReadQueries;
    abstract protected function getWriteQueries(): ProjectionWriteQueries;

    abstract protected function getDatabaseConnection(): Connection;
}
