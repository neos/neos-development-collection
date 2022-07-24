<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Psr\Log\LoggerInterface;

/**
 * The NodeVariation projection feature trait
 */
trait NodeVariation
{
    abstract protected function getProjectionContentGraph(): ProjectionContentGraph;

    abstract protected function getTableNamePrefix(): string;

    /**
     * @param NodeSpecializationVariantWasCreated $event
     * @throws \Exception
     * @throws \Throwable
     */
    private function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            // Do the actual specialization
            $sourceNode = $this->getProjectionContentGraph()->findNodeInAggregate(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->sourceOrigin->toDimensionSpacePoint()
            );
            if (is_null($sourceNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }

            $specializedNode = $this->copyNodeToDimensionSpacePoint(
                $sourceNode,
                $event->specializationOrigin
            );

            foreach (
                $this->getProjectionContentGraph()->findIngoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamIdentifier,
                    $sourceNode->nodeAggregateIdentifier,
                    $event->specializationCoverage
                ) as $hierarchyRelation
            ) {
                $hierarchyRelation->assignNewChildNode(
                    $specializedNode->relationAnchorPoint,
                    $this->getDatabaseConnection(),
                    $this->tableNamePrefix
                );
            }
            foreach (
                $this->getProjectionContentGraph()->findOutgoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamIdentifier,
                    $sourceNode->nodeAggregateIdentifier,
                    $event->specializationCoverage
                ) as $hierarchyRelation
            ) {
                $hierarchyRelation->assignNewParentNode(
                    $specializedNode->relationAnchorPoint,
                    null,
                    $this->getDatabaseConnection(),
                    $this->getTableNamePrefix()
                );
            }

            // Copy Reference Edges
            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $specializedNode->relationAnchorPoint
            );
        });
    }

    /**
     * @param NodeGeneralizationVariantWasCreated $event
     * @throws \Exception
     * @throws \Throwable
     */
    public function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            // do the generalization
            $sourceNode = $this->getProjectionContentGraph()->findNodeInAggregate(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->sourceOrigin->toDimensionSpacePoint()
            );
            if (is_null($sourceNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }
            $sourceParentNode = $this->getProjectionContentGraph()->findParentNode(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->sourceOrigin
            );
            if (is_null($sourceParentNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing(get_class($event));
            }
            $generalizedNode = $this->copyNodeToDimensionSpacePoint(
                $sourceNode,
                $event->generalizationOrigin
            );

            $unassignedIngoingDimensionSpacePoints = $event->generalizationCoverage;
            foreach (
                $this->getProjectionContentGraph()->findIngoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamIdentifier,
                    $event->nodeAggregateIdentifier,
                    $event->generalizationCoverage
                ) as $existingIngoingHierarchyRelation
            ) {
                $existingIngoingHierarchyRelation->assignNewChildNode(
                    $generalizedNode->relationAnchorPoint,
                    $this->getDatabaseConnection(),
                    $this->tableNamePrefix
                );
                $unassignedIngoingDimensionSpacePoints = $unassignedIngoingDimensionSpacePoints->getDifference(
                    new DimensionSpacePointSet([
                        $existingIngoingHierarchyRelation->dimensionSpacePoint
                    ])
                );
            }

            foreach (
                $this->getProjectionContentGraph()->findOutgoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamIdentifier,
                    $event->nodeAggregateIdentifier,
                    $event->generalizationCoverage
                ) as $existingOutgoingHierarchyRelation
            ) {
                $existingOutgoingHierarchyRelation->assignNewParentNode(
                    $generalizedNode->relationAnchorPoint,
                    null,
                    $this->getDatabaseConnection(),
                    $this->getTableNamePrefix()
                );
            }

            if (count($unassignedIngoingDimensionSpacePoints) > 0) {
                $ingoingSourceHierarchyRelation = $this->getProjectionContentGraph()->findIngoingHierarchyRelationsForNode(
                    $sourceNode->relationAnchorPoint,
                    $event->contentStreamIdentifier,
                    new DimensionSpacePointSet([$event->sourceOrigin->toDimensionSpacePoint()])
                )[$event->sourceOrigin->hash] ?? null;
                if (is_null($ingoingSourceHierarchyRelation)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheIngoingSourceHierarchyRelationIsMissing(
                        get_class($event)
                    );
                }
                // the null case is caught by the NodeAggregate or its command handler
                foreach ($unassignedIngoingDimensionSpacePoints as $unassignedDimensionSpacePoint) {
                    // The parent node aggregate might be varied as well,
                    // so we need to find a parent node for each covered dimension space point
                    $generalizationParentNode = $this->getProjectionContentGraph()->findNodeInAggregate(
                        $event->contentStreamIdentifier,
                        $sourceParentNode->nodeAggregateIdentifier,
                        $unassignedDimensionSpacePoint
                    );
                    if (is_null($generalizationParentNode)) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                            get_class($event)
                        );
                    }

                    $this->copyHierarchyRelationToDimensionSpacePoint(
                        $ingoingSourceHierarchyRelation,
                        $event->contentStreamIdentifier,
                        $unassignedDimensionSpacePoint,
                        $generalizationParentNode->relationAnchorPoint,
                        $generalizedNode->relationAnchorPoint
                    );
                }
            }

            // Copy Reference Edges
            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $generalizedNode->relationAnchorPoint
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            // Do the peer variant creation itself
            $sourceNode = $this->getProjectionContentGraph()->findNodeInAggregate(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->sourceOrigin->toDimensionSpacePoint()
            );
            if (is_null($sourceNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }
            $sourceParentNode = $this->getProjectionContentGraph()->findParentNode(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->sourceOrigin
            );
            if (is_null($sourceParentNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing(get_class($event));
            }
            $peerNode = $this->copyNodeToDimensionSpacePoint(
                $sourceNode,
                $event->peerOrigin
            );

            $unassignedIngoingDimensionSpacePoints = $event->peerCoverage;
            foreach (
                $this->getProjectionContentGraph()->findIngoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamIdentifier,
                    $event->nodeAggregateIdentifier,
                    $event->peerCoverage
                ) as $existingIngoingHierarchyRelation
            ) {
                $existingIngoingHierarchyRelation->assignNewChildNode(
                    $peerNode->relationAnchorPoint,
                    $this->getDatabaseConnection(),
                    $this->tableNamePrefix
                );
                $unassignedIngoingDimensionSpacePoints = $unassignedIngoingDimensionSpacePoints->getDifference(
                    new DimensionSpacePointSet([
                        $existingIngoingHierarchyRelation->dimensionSpacePoint
                    ])
                );
            }

            foreach (
                $this->getProjectionContentGraph()->findOutgoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamIdentifier,
                    $event->nodeAggregateIdentifier,
                    $event->peerCoverage
                ) as $existingOutgoingHierarchyRelation
            ) {
                $existingOutgoingHierarchyRelation->assignNewParentNode(
                    $peerNode->relationAnchorPoint,
                    null,
                    $this->getDatabaseConnection(),
                    $this->getTableNamePrefix()
                );
            }

            foreach ($unassignedIngoingDimensionSpacePoints as $coveredDimensionSpacePoint) {
                // The parent node aggregate might be varied as well,
                // so we need to find a parent node for each covered dimension space point
                $peerParentNode = $this->getProjectionContentGraph()->findNodeInAggregate(
                    $event->contentStreamIdentifier,
                    $sourceParentNode->nodeAggregateIdentifier,
                    $coveredDimensionSpacePoint
                );
                if (is_null($peerParentNode)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(get_class($event));
                }

                $this->connectHierarchy(
                    $event->contentStreamIdentifier,
                    $peerParentNode->relationAnchorPoint,
                    $peerNode->relationAnchorPoint,
                    new DimensionSpacePointSet([$coveredDimensionSpacePoint]),
                    null, // @todo fetch appropriate sibling
                    $sourceNode->nodeName
                );
            }

            // Copy Reference Edges
            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $peerNode->relationAnchorPoint
            );
        });
    }

    abstract protected function copyNodeToDimensionSpacePoint(
        NodeRecord $sourceNode,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): NodeRecord;

    abstract protected function copyHierarchyRelationToDimensionSpacePoint(
        HierarchyRelation $sourceHierarchyRelation,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        ?NodeRelationAnchorPoint $newParent = null,
        ?NodeRelationAnchorPoint $newChild = null
    ): HierarchyRelation;

    abstract protected function connectHierarchy(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeRelationAnchorPoint $parentNodeAnchorPoint,
        NodeRelationAnchorPoint $childNodeAnchorPoint,
        DimensionSpacePointSet $dimensionSpacePointSet,
        ?NodeRelationAnchorPoint $succeedingSiblingNodeAnchorPoint,
        NodeName $relationName = null
    ): void;

    abstract protected function copyReferenceRelations(
        NodeRelationAnchorPoint $sourceRelationAnchorPoint,
        NodeRelationAnchorPoint $destinationRelationAnchorPoint
    ): void;

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(\Closure $operations): void;
}
