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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\RelationAnchorPointReplacementDirective;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeVariantWasReset;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;

/**
 * The node disabling feature set for the hypergraph projector
 */
trait NodeVariation
{
    abstract protected function getProjectionHyperGraph(): ProjectionHypergraph;

    abstract protected function transactional(\Closure $operations): void;

    abstract protected function getDatabaseConnection(): Connection;

    /**
     * @throws \Throwable
     */
    public function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            $sourceNode = $this->getProjectionHyperGraph()->findNodeRecordByOrigin(
                $event->contentStreamIdentifier,
                $event->sourceOrigin,
                $event->nodeAggregateIdentifier
            );
            if (is_null($sourceNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing((get_class($event)));
            }
            $specializedNode = $this->copyNodeToOriginDimensionSpacePoint(
                $sourceNode,
                $event->specializationOrigin
            );

            $oldCoveringNode = $this->projectionHypergraph->findNodeRecordByCoverage(
                $event->contentStreamIdentifier,
                $event->specializationOrigin->toDimensionSpacePoint(),
                $event->nodeAggregateIdentifier
            );
            if ($oldCoveringNode instanceof NodeRecord) {
                $this->assignNewChildNodeToAffectedHierarchyRelations(
                    $event->contentStreamIdentifier,
                    $oldCoveringNode->relationAnchorPoint,
                    $specializedNode->relationAnchorPoint,
                    $event->specializationCoverage
                );
                $this->assignNewParentNodeToAffectedHierarchyRelations(
                    $event->contentStreamIdentifier,
                    $oldCoveringNode->relationAnchorPoint,
                    $specializedNode->relationAnchorPoint,
                    $event->specializationCoverage
                );
            } else {
                // the dimension space point is not yet covered by the node aggregate,
                // but it is known that the source's parent node aggregate does
                $sourceParent = $this->projectionHypergraph->findParentNodeRecordByOrigin(
                    $event->contentStreamIdentifier,
                    $event->sourceOrigin,
                    $event->nodeAggregateIdentifier
                );
                if (is_null($sourceParent)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing(
                        (get_class($event))
                    );
                }
                foreach ($event->specializationCoverage as $specializedDimensionSpacePoint) {
                    $parentNode = $this->projectionHypergraph->findNodeRecordByCoverage(
                        $event->contentStreamIdentifier,
                        $specializedDimensionSpacePoint,
                        $sourceParent->nodeAggregateIdentifier
                    );
                    if (is_null($parentNode)) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                            (get_class($event))
                        );
                    }
                    $parentRelation = $this->projectionHypergraph->findHierarchyHyperrelationRecordByParentNodeAnchor(
                        $event->contentStreamIdentifier,
                        $specializedDimensionSpacePoint,
                        $parentNode->relationAnchorPoint
                    );
                    if (is_null($parentRelation)) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheIngoingSourceHierarchyRelationIsMissing(
                            (get_class($event))
                        );
                    }

                    $parentRelation->addChildNodeAnchor(
                        $specializedNode->relationAnchorPoint,
                        null,
                        $this->getDatabaseConnection()
                    );
                }
            }

            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $specializedNode->relationAnchorPoint
            );
        });
    }

    public function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            $sourceNode = $this->getProjectionHyperGraph()->findNodeRecordByOrigin(
                $event->contentStreamIdentifier,
                $event->sourceOrigin,
                $event->nodeAggregateIdentifier
            );
            if (!$sourceNode) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }
            $generalizedNode = $this->copyNodeToOriginDimensionSpacePoint(
                $sourceNode,
                $event->generalizationOrigin
            );

            $this->replaceNodeRelationAnchorPoint(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->generalizationCoverage,
                $generalizedNode->relationAnchorPoint
            );
            $this->addMissingHierarchyRelations(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->sourceOrigin,
                $generalizedNode->relationAnchorPoint,
                $event->generalizationCoverage,
                get_class($event)
            );
            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $generalizedNode->relationAnchorPoint
            );
        });
    }

    public function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            $sourceNode = $this->getProjectionHyperGraph()->findNodeRecordByOrigin(
                $event->contentStreamIdentifier,
                $event->sourceOrigin,
                $event->nodeAggregateIdentifier
            );
            if (!$sourceNode) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }
            $peerNode = $this->copyNodeToOriginDimensionSpacePoint(
                $sourceNode,
                $event->peerOrigin
            );

            $this->replaceNodeRelationAnchorPoint(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->peerCoverage,
                $peerNode->relationAnchorPoint
            );
            $this->addMissingHierarchyRelations(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->sourceOrigin,
                $peerNode->relationAnchorPoint,
                $event->peerCoverage,
                get_class($event)
            );
            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $peerNode->relationAnchorPoint
            );
        });
    }

    public function whenNodeVariantWasReset(NodeVariantWasReset $event): void
    {
        $this->transactional(function () use ($event) {
            $replacements = RelationAnchorPointReplacementDirective::fromDatabaseRows(
                $this->getDatabaseConnection()->executeQuery(
                /** @lang PostgreSQL */
                    '
                /**
                 * This provides a list of all relation anchor points to be replaced in hierarchy relations
                 * and potentially be deleted as well as their replacements
                 */
                WITH RECURSIVE replacements(tobereplaced, replacement) AS (
                    /**
                     * Initial query: find all replacement pairs from origin anchor to generalization anchor
                     */
                    SELECT n.relationanchorpoint AS tobereplaced, gen.relationanchorpoint AS replacement
                    FROM neos_contentgraph_node n
                        JOIN neos_contentgraph_hierarchyhyperrelation h
                            ON n.relationanchorpoint = ANY(h.childnodeanchors)
                        JOIN neos_contentgraph_node gen ON gen.nodeaggregateidentifier = n.nodeaggregateidentifier
                        JOIN neos_contentgraph_hierarchyhyperrelation genh
                            ON gen.relationanchorpoint = ANY(genh.childnodeanchors)
                    WHERE h.contentstreamidentifier = :contentStreamIdentifier
                        AND h.dimensionspacepointhash = :sourceOriginDimensionSpacePointHash
                        AND n.nodeaggregateidentifier = :nodeAggregateIdentifier
                        AND genh.contentstreamidentifier = :contentStreamIdentifier
                        AND genh.dimensionspacepointhash = :generalizationOriginDimensionSpacePointHash
                    UNION ALL
                        /**
                         * Iteration query: find all replacement pairs for tethered descendants
                         */
                        SELECT c.relationanchorpoint AS tobereplaced, genc.relationanchorpoint AS replacement
                        FROM replacements p
                            JOIN neos_contentgraph_hierarchyhyperrelation ch ON ch.parentnodeanchor = p.tobereplaced
                            JOIN neos_contentgraph_node c ON c.relationanchorpoint = ANY(ch.childnodeanchors)
                            JOIN neos_contentgraph_node genc ON genc.nodeaggregateidentifier = c.nodeaggregateidentifier
                            JOIN neos_contentgraph_hierarchyhyperrelation genh
                                ON genc.relationanchorpoint = ANY(genh.childnodeanchors)
                        WHERE ch.contentstreamidentifier = :contentStreamIdentifier
                          AND ch.dimensionspacepointhash = :sourceOriginDimensionSpacePointHash
                          AND genh.contentstreamidentifier = :contentStreamIdentifier
                          AND genh.dimensionspacepointhash = :generalizationOriginDimensionSpacePointHash
                          AND c.classification = :tetheredClassification
                ) SELECT * FROM replacements
                ',
                    [
                        'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                        'sourceOriginDimensionSpacePointHash' => $event->sourceOrigin->hash,
                        'nodeAggregateIdentifier' => (string)$event->nodeAggregateIdentifier,
                        'generalizationOriginDimensionSpacePointHash' => $event->generalizationOrigin->hash,
                        'tetheredClassification' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value
                    ]
                )->fetchAllAssociative()
            );

            // adjust the inbound hierarchy relations
            $this->getDatabaseConnection()->executeStatement(
                /** @lang PostgreSQL */
                '
                WITH replacements(tobereplaced, replacement) AS (
                    SELECT unnest(ARRAY[:toBeReplaced]) AS tobereplaced,
                           unnest(ARRAY[:replacements]) AS replacement
                )
                UPDATE neos_contentgraph_hierarchyhyperrelation h
                    SET childnodeanchors = array_replace(
                        childnodeanchors,
                        cast(replacements.tobereplaced AS uuid),
                        cast(replacements.replacement AS uuid)
                    )
                    FROM replacements
                    WHERE cast(replacements.tobereplaced AS uuid) = ANY(childnodeanchors)
                        AND contentstreamidentifier = :contentStreamIdentifier
                        AND dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)
                ',
                [
                    'toBeReplaced' => $replacements->toBeReplaced,
                    'replacements' => $replacements->replacements,
                    'contentStreamIdentifier' => $event->contentStreamIdentifier,
                    'affectedDimensionSpacePointHashes' => $event->affectedCoveredDimensionSpacePoints->getPointHashes()
                ],
                [
                    'toBeReplaced' => Connection::PARAM_STR_ARRAY,
                    'replacements' => Connection::PARAM_STR_ARRAY,
                    'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
                ]
            );

            // adjust the outbound hierarchy relations
            $this->getDatabaseConnection()->executeStatement(
                /** @lang PostgreSQL */
                '
                WITH replacements(tobereplaced, replacement) AS (
                    SELECT unnest(ARRAY[:toBeReplaced]) AS tobereplaced,
                           unnest(ARRAY[:replacements]) AS replacement
                )
                UPDATE neos_contentgraph_hierarchyhyperrelation
                    SET parentnodeanchor = cast(replacements.replacement AS uuid) FROM replacements
                WHERE parentnodeanchor = cast(replacements.tobereplaced AS uuid)
                    AND contentstreamidentifier = :contentStreamIdentifier
                    AND dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)
                ',
                [
                    'toBeReplaced' => $replacements->toBeReplaced,
                    'replacements' => $replacements->replacements,
                    'contentStreamIdentifier' => $event->contentStreamIdentifier,
                    'affectedDimensionSpacePointHashes' => $event->affectedCoveredDimensionSpacePoints->getPointHashes()
                ],
                [
                    'toBeReplaced' => Connection::PARAM_STR_ARRAY,
                    'replacements' => Connection::PARAM_STR_ARRAY,
                    'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
                ]
            );

            // remove orphaned nodes and their outbound reference relations
            $this->getDatabaseConnection()->executeStatement(
            /** @lang PostgreSQL */
                '
                WITH deletedNodes AS (
                    DELETE FROM neos_contentgraph_node n
                    WHERE n.relationanchorpoint IN (
                        SELECT relationanchorpoint FROM neos_contentgraph_node
                            LEFT JOIN neos_contentgraph_hierarchyhyperrelation h
                                ON n.relationanchorpoint = ANY(h.childnodeanchors)
                        WHERE n.relationanchorpoint IN (:replacedRelationAnchorPoints)
                            AND h.contentstreamidentifier IS NULL
                    )
                    RETURNING relationanchorpoint
                )
                DELETE FROM neos_contentgraph_referencerelation r
                    WHERE sourcenodeanchor IN (SELECT relationanchorpoint FROM deletedNodes)
                ',
                [
                    'replacedRelationAnchorPoints' => $replacements->toBeReplaced
                ],
                [
                    'replacedRelationAnchorPoints' => Connection::PARAM_STR_ARRAY
                ]
            );
        });
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function copyNodeToOriginDimensionSpacePoint(
        NodeRecord $sourceNode,
        OriginDimensionSpacePoint $targetOrigin
    ): NodeRecord {
        $copy = new NodeRecord(
            NodeRelationAnchorPoint::create(),
            $sourceNode->nodeAggregateIdentifier,
            $targetOrigin,
            $targetOrigin->hash,
            $sourceNode->properties,
            $sourceNode->nodeTypeName,
            $sourceNode->classification,
            $sourceNode->nodeName
        );
        $copy->addToDatabase($this->getDatabaseConnection());

        return $copy;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function replaceNodeRelationAnchorPoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $affectedNodeAggregateIdentifier,
        DimensionSpacePointSet $affectedDimensionSpacePointSet,
        NodeRelationAnchorPoint $newNodeRelationAnchorPoint
    ): void {
        $currentNodeAnchorPointStatement = '
            WITH currentNodeAnchorPoint AS (
                SELECT relationanchorpoint FROM ' . NodeRecord::TABLE_NAME . ' n
                    JOIN ' . HierarchyHyperrelationRecord::TABLE_NAME . ' p
                    ON n.relationanchorpoint = ANY(p.childnodeanchors)
                WHERE p.contentstreamidentifier = :contentStreamIdentifier
                AND p.dimensionspacepointhash = :affectedDimensionSpacePointHash
                AND n.nodeaggregateidentifier = :affectedNodeAggregateIdentifier
            )';
        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'newNodeRelationAnchorPoint' => (string)$newNodeRelationAnchorPoint,
            'affectedNodeAggregateIdentifier' => (string)$affectedNodeAggregateIdentifier
        ];
        foreach ($affectedDimensionSpacePointSet as $affectedDimensionSpacePoint) {
            $parentStatement = /** @lang PostgreSQL */
                $currentNodeAnchorPointStatement . '
                UPDATE ' . HierarchyHyperrelationRecord::TABLE_NAME . '
                    SET parentnodeanchor = :newNodeRelationAnchorPoint
                    WHERE contentstreamidentifier = :contentStreamIdentifier
                        AND dimensionspacepointhash = :affectedDimensionSpacePointHash
                        AND parentnodeanchor = (SELECT relationanchorpoint FROM currentNodeAnchorPoint)
                ';
            $childStatement = /** @lang PostgreSQL */
                $currentNodeAnchorPointStatement . '
                UPDATE ' . HierarchyHyperrelationRecord::TABLE_NAME . '
                    SET childnodeanchors = array_replace(
                        childnodeanchors,
                        (SELECT relationanchorpoint FROM currentNodeAnchorPoint),
                        :newNodeRelationAnchorPoint
                    )
                    WHERE contentstreamidentifier = :contentStreamIdentifier
                        AND dimensionspacepointhash = :affectedDimensionSpacePointHash
                        AND (SELECT relationanchorpoint FROM currentNodeAnchorPoint) = ANY(childnodeanchors)
                ';
            $parameters['affectedDimensionSpacePointHash'] = $affectedDimensionSpacePoint->hash;
            $this->getDatabaseConnection()->executeStatement($parentStatement, $parameters);
            $this->getDatabaseConnection()->executeStatement($childStatement, $parameters);
        }
    }

    protected function addMissingHierarchyRelations(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        NodeRelationAnchorPoint $targetRelationAnchor,
        DimensionSpacePointSet $coverage,
        string $eventClassName
    ): void {
        $missingCoverage = $coverage->getDifference(
            $this->getProjectionHyperGraph()->findCoverageByNodeAggregateIdentifier(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier
            )
        );
        if ($missingCoverage->count() > 0) {
            $sourceParentNode = $this->getProjectionHyperGraph()->findParentNodeRecordByOrigin(
                $contentStreamIdentifier,
                $sourceOrigin,
                $nodeAggregateIdentifier
            );
            if (!$sourceParentNode) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing($eventClassName);
            }
            $parentNodeAggregateIdentifier = $sourceParentNode->nodeAggregateIdentifier;
            $sourceSucceedingSiblingNode = $this->getProjectionHyperGraph()->findParentNodeRecordByOrigin(
                $contentStreamIdentifier,
                $sourceOrigin,
                $nodeAggregateIdentifier
            );
            foreach ($missingCoverage as $uncoveredDimensionSpacePoint) {
                // The parent node aggregate might be varied as well,
                // so we need to find a parent node for each covered dimension space point

                // First we check for an already existing hyperrelation
                $hierarchyRelation = $this->getProjectionHyperGraph()->findChildHierarchyHyperrelationRecord(
                    $contentStreamIdentifier,
                    $uncoveredDimensionSpacePoint,
                    $parentNodeAggregateIdentifier
                );

                if ($hierarchyRelation && $sourceSucceedingSiblingNode) {
                    // If it exists, we need to look for a succeeding sibling to keep some order of nodes
                    $targetSucceedingSibling = $this->getProjectionHyperGraph()->findNodeRecordByCoverage(
                        $contentStreamIdentifier,
                        $uncoveredDimensionSpacePoint,
                        $sourceSucceedingSiblingNode->nodeAggregateIdentifier
                    );

                    $hierarchyRelation->addChildNodeAnchor(
                        $targetRelationAnchor,
                        $targetSucceedingSibling?->relationAnchorPoint,
                        $this->getDatabaseConnection()
                    );
                } else {
                    $targetParentNode = $this->getProjectionHyperGraph()->findNodeRecordByCoverage(
                        $contentStreamIdentifier,
                        $uncoveredDimensionSpacePoint,
                        $parentNodeAggregateIdentifier
                    );
                    if (!$targetParentNode) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                            $eventClassName
                        );
                    }
                    $hierarchyRelation = new HierarchyHyperrelationRecord(
                        $contentStreamIdentifier,
                        $targetParentNode->relationAnchorPoint,
                        $uncoveredDimensionSpacePoint,
                        NodeRelationAnchorPoints::fromArray([$targetRelationAnchor])
                    );
                    $hierarchyRelation->addToDatabase($this->getDatabaseConnection());
                }
            }
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function assignNewChildNodeToAffectedHierarchyRelations(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeRelationAnchorPoint $oldChildAnchor,
        NodeRelationAnchorPoint $newChildAnchor,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        foreach (
            $this->getProjectionHyperGraph()->findIngoingHierarchyHyperrelationRecords(
                $contentStreamIdentifier,
                $oldChildAnchor,
                $affectedDimensionSpacePoints
            ) as $ingoingHierarchyHyperrelationRecord
        ) {
            $ingoingHierarchyHyperrelationRecord->replaceChildNodeAnchor(
                $oldChildAnchor,
                $newChildAnchor,
                $this->getDatabaseConnection()
            );
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function assignNewParentNodeToAffectedHierarchyRelations(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeRelationAnchorPoint $oldParentAnchor,
        NodeRelationAnchorPoint $newParentAnchor,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        foreach (
            $this->getProjectionHyperGraph()->findOutgoingHierarchyHyperrelationRecords(
                $contentStreamIdentifier,
                $oldParentAnchor,
                $affectedDimensionSpacePoints
            ) as $outgoingHierarchyHyperrelationRecord
        ) {
            $outgoingHierarchyHyperrelationRecord->replaceParentNodeAnchor(
                $newParentAnchor,
                $this->getDatabaseConnection()
            );
        }
    }

    protected function copyReferenceRelations(
        NodeRelationAnchorPoint $sourceRelationAnchorPoint,
        NodeRelationAnchorPoint $newSourceRelationAnchorPoint
    ): void {
        // we don't care whether the target node aggregate covers the variant's origin
        // since if it doesn't, it already didn't match the source's coverage before

        $this->getDatabaseConnection()->executeStatement('
                INSERT INTO ' . ReferenceRelationRecord::TABLE_NAME . ' (
                  sourcenodeanchor,
                  name,
                  position,
                  properties,
                  targetnodeaggregateidentifier
                )
                SELECT
                  :newSourceRelationAnchorPoint AS sourcenodeanchor,
                  ref.name,
                  ref.position,
                  ref.properties,
                  ref.targetnodeaggregateidentifier
                FROM
                    ' . ReferenceRelationRecord::TABLE_NAME . ' ref
                    WHERE ref.sourcenodeanchor = :sourceNodeAnchorPoint
            ', [
            'sourceNodeAnchorPoint' => $sourceRelationAnchorPoint->value,
            'newSourceRelationAnchorPoint' => $newSourceRelationAnchorPoint->value
        ]);
    }
}
