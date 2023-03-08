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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HypergraphProjection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ReferenceRelationRecord;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateCoverageWasRestored;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;

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
                    $event->getContentStreamId(),
                    $dimensionSpacePoint,
                    $event->getNodeAggregateId()
                );
                if (is_null($nodeRecord)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
                }

                /** @var HierarchyHyperrelationRecord $ingoingHierarchyRelation */
                $ingoingHierarchyRelation = $this->getProjectionHypergraph()
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
        ContentStreamId $contentStreamIdentifier,
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
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function removeFromRestrictions(
        ContentStreamId $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateId $nodeAggregateIdentifier
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

    public function whenNodeAggregateCoverageWasRestored(NodeAggregateCoverageWasRestored $event): void
    {
        $this->transactional(function () use ($event) {
            $descendantAssignments = $this->projectionHypergraph->findDescendantAssignments(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->sourceDimensionSpacePoint,
                $event->affectedCoveredDimensionSpacePoints,
                $event->recursionMode
            );

            $hierarchyRecords = [];
            $restrictionRecords = [];
            foreach ($descendantAssignments as $descendantAssignment) {
                $dspHash = $descendantAssignment['dimensionspacepointhash'];
                $parentAnchor = $descendantAssignment['parentrelationanchorpoint'];
                $succeedingSiblingAnchor = $descendantAssignment['succeedingsiblingrelationanchorpoint'];
                if ($succeedingSiblingAnchor) {
                    if ($succeedingSiblingAnchor === HypergraphProjection::ANCHOR_POINT_SORT_FROM_RESULT) {
                        if (isset($hierarchyRecords[$dspHash][$parentAnchor])) {
                            $hierarchyRecords[$dspHash][$parentAnchor]['childnodeanchors'][]
                                = $descendantAssignment['childrelationanchorpoint'];
                        } else {
                            $hierarchyRecords[$dspHash][$parentAnchor] = [
                                'contentstreamidentifier' => $event->contentStreamId->getValue(),
                                'parentnodeanchor' => $descendantAssignment['parentrelationanchorpoint'],
                                'dimensionspacepointhash' => $descendantAssignment['dimensionspacepointhash'],
                                'dimensionspacepoint' => $descendantAssignment['dimensionspacepoint'],
                                'childnodeanchors' => [$descendantAssignment['childrelationanchorpoint']],
                            ];
                        }
                    } else {
                        $query = 'UPDATE ' . $this->getHierarchyRelationTableName() . '
                        SET childnodeanchors = (childnodeanchors[:array_position(childnodeanchors,\'' . $succeedingSiblingAnchor . '\')-1]
                            || \'' . $descendantAssignment['childrelationanchorpoint'] . '\'::uuid
                            || childnodeanchors[array_position(childnodeanchors,\'' . $succeedingSiblingAnchor . '\'):])
                        WHERE dimensionspacepointhash=\'' . $descendantAssignment['dimensionspacepointhash'] . '\'
                            AND contentstreamidentifier = \'' . $event->contentStreamId . '\'
                            AND parentnodeanchor = \'' . $descendantAssignment['parentrelationanchorpoint'] . '\'';
                        $this->getDatabaseConnection()->executeStatement($query);
                    }
                } else {
                    $hierarchyRecords[$dspHash][$parentAnchor] = [
                        'contentstreamidentifier' => $event->contentStreamId->getValue(),
                        'parentnodeanchor' => $descendantAssignment['parentrelationanchorpoint'],
                        'dimensionspacepointhash' => $descendantAssignment['dimensionspacepointhash'],
                        'dimensionspacepoint' => $descendantAssignment['dimensionspacepoint'],
                        'childnodeanchors' => [$descendantAssignment['childrelationanchorpoint']],
                    ];
                }
                 $restrictionRecords[$dspHash][] = [
                     'parentnodeaggregateid' => $descendantAssignment['parentnodeaggregateid'],
                     'childnodeaggregateid' => $descendantAssignment['childnodeaggregateid'],
                 ];
            }

            foreach ($hierarchyRecords as $hierarchyRecordsInDsp) {
                foreach ($hierarchyRecordsInDsp as $hierarchyRecord) {
                    $hierarchyRecord['childnodeanchors']
                        = '{"' . implode('","', $hierarchyRecord['childnodeanchors']) . '"}';
                    $this->getDatabaseConnection()->insert(
                        $this->getHierarchyRelationTableName(),
                        $hierarchyRecord
                    );
                }
            }

            foreach ($restrictionRecords as $dimensionSpacePointHash => $restrictionRecordsForDsp) {
                foreach ($restrictionRecordsForDsp as $restrictionRecord) {
                    $this->getDatabaseConnection()->executeQuery(/** @lang PostgreSQL */
                        'INSERT INTO ' . $this->tableNamePrefix . '_restrictionhyperrelation (
                        contentstreamidentifier,
                        dimensionspacepointhash,
                        originnodeaggregateidentifier,
                        affectednodeaggregateidentifiers
                    )
                    SELECT contentstreamidentifier, :dimensionSpacePointHash,
                        originnodeaggregateidentifier, :affectedNodeAggregateIds
                    FROM ' . $this->tableNamePrefix . '_restrictionhyperrelation source
                    WHERE source.contentstreamidentifier = :contentStreamId
                        AND source.dimensionspacepointhash = :sourceDimensionSpacePointHash
                        AND source.originnodeaggregateidentifier = :sourceNodeAggregateId',
                        [
                            'contentStreamId' => (string)$event->contentStreamId,
                            'sourceDimensionSpacePointHash' => $event->sourceDimensionSpacePoint->hash,
                            'dimensionSpacePointHash' => $dimensionSpacePointHash,
                            'sourceNodeAggregateId' => $restrictionRecord['childnodeaggregateid'],
                            'affectedNodeAggregateIds' => '{' . $restrictionRecord['childnodeaggregateid'] . '}'
                        ]
                    );
                    $this->getDatabaseConnection()->executeQuery(/** @lang PostgreSQL */
                        'UPDATE ' . $this->tableNamePrefix . '_restrictionhyperrelation
                        SET affectednodeaggregateidentifiers = array_append(
                            affectednodeaggregateidentifiers,
                            :childNodeAggregateId
                        )
                        WHERE contentstreamidentifier = :contentStreamId
                            AND dimensionspacepointhash = :dimensionSpacePointHash
                            AND :parentNodeAggregateId = ANY(affectednodeaggregateidentifiers)
                            AND NOT (:childNodeAggregateId = ANY(affectednodeaggregateidentifiers))',
                        [
                            'contentStreamId' => (string)$event->contentStreamId,
                            'dimensionSpacePointHash' => $dimensionSpacePointHash,
                            'childNodeAggregateId' => $restrictionRecord['childnodeaggregateid'],
                            'parentNodeAggregateId' => $restrictionRecord['parentnodeaggregateid']
                        ]
                    );
                }
            }
        });
    }

    abstract protected function getHierarchyRelationTableName(): string;

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(\Closure $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
