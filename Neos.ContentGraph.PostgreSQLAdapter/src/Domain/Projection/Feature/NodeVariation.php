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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
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
            if (is_null($oldCoveringNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing((get_class($event)));
            }
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
        NodeRelationAnchorPoint $destinationRelationAnchorPoint
    ): void {
        // we don't care whether the target node aggregate covers the variant's origin
        // since if it doesn't, it already didn't match the source's coverage before

        $this->getDatabaseConnection()->executeStatement('
                INSERT INTO ' . ReferenceRelationRecord::TABLE_NAME . ' (
                  originnodeanchor,
                  name,
                  position,
                  properties,
                  destinationnodeaggregateidentifier
                )
                SELECT
                  :destinationRelationAnchorPoint AS originnodeanchor,
                  ref.name,
                  ref.position,
                  ref.properties,
                  ref.destinationnodeaggregateidentifier
                FROM
                    ' . ReferenceRelationRecord::TABLE_NAME . ' ref
                    WHERE ref.originnodeanchor = :sourceNodeAnchorPoint
            ', [
            'sourceNodeAnchorPoint' => $sourceRelationAnchorPoint->value,
            'destinationRelationAnchorPoint' => $destinationRelationAnchorPoint->value
        ]);
    }
}
