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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;

/**
 * The node disabling feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeVariation
{
    abstract protected function getProjectionHyperGraph(): ProjectionHypergraph;

    abstract protected function transactional(\Closure $operations): void;

    abstract protected function getDatabaseConnection(): Connection;

    /**
     * @throws \Throwable
     */
    private function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            $sourceNode = $this->getProjectionHyperGraph()->findNodeRecordByOrigin(
                $event->contentStreamId,
                $event->sourceOrigin,
                $event->nodeAggregateId
            );
            if (is_null($sourceNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing((get_class($event)));
            }
            $specializedNode = $this->copyNodeToOriginDimensionSpacePoint(
                $sourceNode,
                $event->specializationOrigin
            );

            $oldCoveringNode = $this->projectionHypergraph->findNodeRecordByCoverage(
                $event->contentStreamId,
                $event->specializationOrigin->toDimensionSpacePoint(),
                $event->nodeAggregateId
            );
            if ($oldCoveringNode instanceof NodeRecord) {
                $this->assignNewChildNodeToAffectedHierarchyRelations(
                    $event->contentStreamId,
                    $oldCoveringNode->relationAnchorPoint,
                    $specializedNode->relationAnchorPoint,
                    $event->specializationCoverage
                );
                $this->assignNewParentNodeToAffectedHierarchyRelations(
                    $event->contentStreamId,
                    $oldCoveringNode->relationAnchorPoint,
                    $specializedNode->relationAnchorPoint,
                    $event->specializationCoverage
                );
            } else {
                // the dimension space point is not yet covered by the node aggregate,
                // but it is known that the source's parent node aggregate does
                $sourceParent = $this->projectionHypergraph->findParentNodeRecordByOrigin(
                    $event->contentStreamId,
                    $event->sourceOrigin,
                    $event->nodeAggregateId
                );
                if (is_null($sourceParent)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing(
                        (get_class($event))
                    );
                }
                foreach ($event->specializationCoverage as $specializedDimensionSpacePoint) {
                    $parentNode = $this->projectionHypergraph->findNodeRecordByCoverage(
                        $event->contentStreamId,
                        $specializedDimensionSpacePoint,
                        $sourceParent->nodeAggregateId
                    );
                    if (is_null($parentNode)) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                            (get_class($event))
                        );
                    }
                    $parentRelation = $this->projectionHypergraph->findHierarchyHyperrelationRecordByParentNodeAnchor(
                        $event->contentStreamId,
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
                        $this->getDatabaseConnection(),
                        $this->tableNamePrefix
                    );
                }
            }

            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $specializedNode->relationAnchorPoint
            );
        });
    }

    private function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            $sourceNode = $this->getProjectionHyperGraph()->findNodeRecordByOrigin(
                $event->contentStreamId,
                $event->sourceOrigin,
                $event->nodeAggregateId
            );
            if (!$sourceNode) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }
            $generalizedNode = $this->copyNodeToOriginDimensionSpacePoint(
                $sourceNode,
                $event->generalizationOrigin
            );

            $this->replaceNodeRelationAnchorPoint(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->generalizationCoverage,
                $generalizedNode->relationAnchorPoint
            );
            $this->addMissingHierarchyRelations(
                $event->contentStreamId,
                $event->nodeAggregateId,
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

    private function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            $sourceNode = $this->getProjectionHyperGraph()->findNodeRecordByOrigin(
                $event->contentStreamId,
                $event->sourceOrigin,
                $event->nodeAggregateId
            );
            if (!$sourceNode) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }
            $peerNode = $this->copyNodeToOriginDimensionSpacePoint(
                $sourceNode,
                $event->peerOrigin
            );

            $this->replaceNodeRelationAnchorPoint(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->peerCoverage,
                $peerNode->relationAnchorPoint
            );
            $this->addMissingHierarchyRelations(
                $event->contentStreamId,
                $event->nodeAggregateId,
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function copyNodeToOriginDimensionSpacePoint(
        NodeRecord $sourceNode,
        OriginDimensionSpacePoint $targetOrigin
    ): NodeRecord {
        $copy = new NodeRecord(
            NodeRelationAnchorPoint::create(),
            $sourceNode->nodeAggregateId,
            $targetOrigin,
            $targetOrigin->hash,
            $sourceNode->properties,
            $sourceNode->nodeTypeName,
            $sourceNode->classification,
            $sourceNode->nodeName
        );
        $copy->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

        return $copy;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function replaceNodeRelationAnchorPoint(
        ContentStreamId $contentStreamId,
        NodeAggregateId $affectedNodeAggregateId,
        DimensionSpacePointSet $affectedDimensionSpacePointSet,
        NodeRelationAnchorPoint $newNodeRelationAnchorPoint
    ): void {
        $currentNodeAnchorPointStatement = '
            WITH currentNodeAnchorPoint AS (
                SELECT relationanchorpoint FROM ' . $this->tableNamePrefix . '_node n
                    JOIN ' . $this->tableNamePrefix . '_hierarchyhyperrelation p
                    ON n.relationanchorpoint = ANY(p.childnodeanchors)
                WHERE p.contentstreamid = :contentStreamId
                AND p.dimensionspacepointhash = :affectedDimensionSpacePointHash
                AND n.nodeaggregateid = :affectedNodeAggregateId
            )';
        $parameters = [
            'contentStreamId' => (string)$contentStreamId,
            'newNodeRelationAnchorPoint' => (string)$newNodeRelationAnchorPoint,
            'affectedNodeAggregateId' => (string)$affectedNodeAggregateId
        ];
        foreach ($affectedDimensionSpacePointSet as $affectedDimensionSpacePoint) {
            $parentStatement = /** @lang PostgreSQL */
                $currentNodeAnchorPointStatement . '
                UPDATE ' . $this->tableNamePrefix . '_hierarchyhyperrelation
                    SET parentnodeanchor = :newNodeRelationAnchorPoint
                    WHERE contentstreamid = :contentStreamId
                        AND dimensionspacepointhash = :affectedDimensionSpacePointHash
                        AND parentnodeanchor = (SELECT relationanchorpoint FROM currentNodeAnchorPoint)
                ';
            $childStatement = /** @lang PostgreSQL */
                $currentNodeAnchorPointStatement . '
                UPDATE ' . $this->tableNamePrefix . '_hierarchyhyperrelation
                    SET childnodeanchors = array_replace(
                        childnodeanchors,
                        (SELECT relationanchorpoint FROM currentNodeAnchorPoint),
                        :newNodeRelationAnchorPoint
                    )
                    WHERE contentstreamid = :contentStreamId
                        AND dimensionspacepointhash = :affectedDimensionSpacePointHash
                        AND (SELECT relationanchorpoint FROM currentNodeAnchorPoint) = ANY(childnodeanchors)
                ';
            $parameters['affectedDimensionSpacePointHash'] = $affectedDimensionSpacePoint->hash;
            $this->getDatabaseConnection()->executeStatement($parentStatement, $parameters);
            $this->getDatabaseConnection()->executeStatement($childStatement, $parameters);
        }
    }

    protected function addMissingHierarchyRelations(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $sourceOrigin,
        NodeRelationAnchorPoint $targetRelationAnchor,
        DimensionSpacePointSet $coverage,
        string $eventClassName
    ): void {
        $missingCoverage = $coverage->getDifference(
            $this->getProjectionHyperGraph()->findCoverageByNodeAggregateId(
                $contentStreamId,
                $nodeAggregateId
            )
        );
        if ($missingCoverage->count() > 0) {
            $sourceParentNode = $this->getProjectionHyperGraph()->findParentNodeRecordByOrigin(
                $contentStreamId,
                $sourceOrigin,
                $nodeAggregateId
            );
            if (!$sourceParentNode) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing($eventClassName);
            }
            $parentNodeAggregateId = $sourceParentNode->nodeAggregateId;
            $sourceSucceedingSiblingNode = $this->getProjectionHyperGraph()->findParentNodeRecordByOrigin(
                $contentStreamId,
                $sourceOrigin,
                $nodeAggregateId
            );
            foreach ($missingCoverage as $uncoveredDimensionSpacePoint) {
                // The parent node aggregate might be varied as well,
                // so we need to find a parent node for each covered dimension space point

                // First we check for an already existing hyperrelation
                $hierarchyRelation = $this->getProjectionHyperGraph()->findChildHierarchyHyperrelationRecord(
                    $contentStreamId,
                    $uncoveredDimensionSpacePoint,
                    $parentNodeAggregateId
                );

                if ($hierarchyRelation && $sourceSucceedingSiblingNode) {
                    // If it exists, we need to look for a succeeding sibling to keep some order of nodes
                    $targetSucceedingSibling = $this->getProjectionHyperGraph()->findNodeRecordByCoverage(
                        $contentStreamId,
                        $uncoveredDimensionSpacePoint,
                        $sourceSucceedingSiblingNode->nodeAggregateId
                    );

                    $hierarchyRelation->addChildNodeAnchor(
                        $targetRelationAnchor,
                        $targetSucceedingSibling?->relationAnchorPoint,
                        $this->getDatabaseConnection(),
                        $this->tableNamePrefix
                    );
                } else {
                    $targetParentNode = $this->getProjectionHyperGraph()->findNodeRecordByCoverage(
                        $contentStreamId,
                        $uncoveredDimensionSpacePoint,
                        $parentNodeAggregateId
                    );
                    if (!$targetParentNode) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                            $eventClassName
                        );
                    }
                    $hierarchyRelation = new HierarchyHyperrelationRecord(
                        $contentStreamId,
                        $targetParentNode->relationAnchorPoint,
                        $uncoveredDimensionSpacePoint,
                        NodeRelationAnchorPoints::fromArray([$targetRelationAnchor])
                    );
                    $hierarchyRelation->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
                }
            }
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function assignNewChildNodeToAffectedHierarchyRelations(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $oldChildAnchor,
        NodeRelationAnchorPoint $newChildAnchor,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        foreach (
            $this->getProjectionHyperGraph()->findIngoingHierarchyHyperrelationRecords(
                $contentStreamId,
                $oldChildAnchor,
                $affectedDimensionSpacePoints
            ) as $ingoingHierarchyHyperrelationRecord
        ) {
            $ingoingHierarchyHyperrelationRecord->replaceChildNodeAnchor(
                $oldChildAnchor,
                $newChildAnchor,
                $this->getDatabaseConnection(),
                $this->tableNamePrefix
            );
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function assignNewParentNodeToAffectedHierarchyRelations(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $oldParentAnchor,
        NodeRelationAnchorPoint $newParentAnchor,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        foreach (
            $this->getProjectionHyperGraph()->findOutgoingHierarchyHyperrelationRecords(
                $contentStreamId,
                $oldParentAnchor,
                $affectedDimensionSpacePoints
            ) as $outgoingHierarchyHyperrelationRecord
        ) {
            $outgoingHierarchyHyperrelationRecord->replaceParentNodeAnchor(
                $newParentAnchor,
                $this->getDatabaseConnection(),
                $this->tableNamePrefix
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
                INSERT INTO ' . $this->tableNamePrefix . '_referencerelation (
                  sourcenodeanchor,
                  name,
                  position,
                  properties,
                  targetnodeaggregateid
                )
                SELECT
                  :newSourceRelationAnchorPoint AS sourcenodeanchor,
                  ref.name,
                  ref.position,
                  ref.properties,
                  ref.targetnodeaggregateid
                FROM
                    ' . $this->tableNamePrefix . '_referencerelation ref
                    WHERE ref.sourcenodeanchor = :sourceNodeAnchorPoint
            ', [
            'sourceNodeAnchorPoint' => $sourceRelationAnchorPoint->value,
            'newSourceRelationAnchorPoint' => $newSourceRelationAnchorPoint->value
        ]);
    }
}
