<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelationId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\EventStore\Model\EventEnvelope;

/**
 * The NodeVariation projection feature trait
 *
 * @internal
 */
trait NodeVariation
{
    private function createNodeSpecializationVariant(ContentStreamLayers $contentStreamLayers, NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint $sourceOrigin, OriginDimensionSpacePoint $specializationOrigin, InterdimensionalSiblings $specializationSiblings, EventEnvelope $eventEnvelope): void
    {
        // Do the actual specialization
        $sourceNode = $this->projectionContentGraph->findNodeInAggregate(
            $contentStreamLayers,
            $nodeAggregateId,
            $sourceOrigin->toDimensionSpacePoint()
        );
        if (is_null($sourceNode)) {
            throw new \RuntimeException(sprintf('Failed to create node specialization variant for node "%s" in sub graph %s@%s because the source node is missing', $nodeAggregateId->value, $sourceOrigin->toJson(), $contentStreamLayers->toDebugString()), 1716498651);
        }

        $specializedNode = $this->copyNodeToDimensionSpacePoint(
            $sourceNode,
            $specializationOrigin,
            $eventEnvelope
        );

        $uncoveredDimensionSpacePoints = $specializationSiblings->toDimensionSpacePointSet()->points;
        foreach (
            $this->projectionContentGraph->findIngoingHierarchyRelationsForNodeAggregate(
                $contentStreamLayers,
                $sourceNode->nodeAggregateId,
                $specializationSiblings->toDimensionSpacePointSet()
            ) as $hierarchyRelation
        ) {
            if ($contentStreamLayers->getWriteLayer()->equals($hierarchyRelation->contentStreamLayer)) {
                $hierarchyRelation->assignNewChildNode(
                    $specializedNode->relationAnchorPoint,
                    $this->dbal,
                    $this->tableNames
                );
            } else {
                $copiedHierarchyRelation = $hierarchyRelation->with(
                    childNodeAnchor: $specializedNode->relationAnchorPoint,
                    contentStreamLayer: $contentStreamLayers->getWriteLayer(),
                );
                $copiedHierarchyRelation->addToDatabase(
                    $this->dbal,
                    $this->tableNames
                );
            }
            unset($uncoveredDimensionSpacePoints[$hierarchyRelation->dimensionSpacePointHash]);
        }
        if (!empty($uncoveredDimensionSpacePoints)) {
            $sourceParent = $this->projectionContentGraph->findParentNode(
                $contentStreamLayers,
                $nodeAggregateId,
                $sourceOrigin,
            );
            if (is_null($sourceParent)) {
                throw new \RuntimeException(sprintf('Failed to create node specialization variant for node "%s" in sub graph %s@%s because the source parent node is missing', $nodeAggregateId->value, $sourceOrigin->toJson(), $contentStreamLayers->toDebugString()), 1716498695);
            }
            foreach ($uncoveredDimensionSpacePoints as $uncoveredDimensionSpacePoint) {
                $parentNode = $this->projectionContentGraph->findNodeInAggregate(
                    $contentStreamLayers,
                    $sourceParent->nodeAggregateId,
                    $uncoveredDimensionSpacePoint
                );
                if (is_null($parentNode)) {
                    throw new \RuntimeException(sprintf('Failed to create node specialization variant for node "%s" in sub graph %s@%s because the target parent node "%s" is missing', $nodeAggregateId->value, $sourceOrigin->toJson(), $contentStreamLayers->toDebugString(), $sourceParent->nodeAggregateId->value), 1716498734);
                }
                $parentSubtreeTags = $this->subtreeTagsForHierarchyRelation($contentStreamLayers, $parentNode->relationAnchorPoint, $uncoveredDimensionSpacePoint);

                $specializationSucceedingSiblingNodeAggregateId = $specializationSiblings
                    ->getSucceedingSiblingIdForDimensionSpacePoint($uncoveredDimensionSpacePoint);
                $specializationSucceedingSiblingNode = $specializationSucceedingSiblingNodeAggregateId
                    ? $this->projectionContentGraph->findNodeInAggregate(
                        $contentStreamLayers,
                        $specializationSucceedingSiblingNodeAggregateId,
                        $uncoveredDimensionSpacePoint
                    )
                    : null;

                $hierarchyRelation = new HierarchyRelation(
                    HierarchyRelationId::createAutoIncremented(),
                    $contentStreamLayers->getWriteLayer(),
                    $parentNode->relationAnchorPoint,
                    $specializedNode->relationAnchorPoint,
                    $uncoveredDimensionSpacePoint,
                    $uncoveredDimensionSpacePoint->hash,
                    $this->projectionContentGraph->determineHierarchyRelationPosition(
                        $parentNode->relationAnchorPoint,
                        $specializedNode->relationAnchorPoint,
                        $specializationSucceedingSiblingNode?->relationAnchorPoint,
                        $contentStreamLayers,
                        $uncoveredDimensionSpacePoint
                    ),
                    NodeTags::create(SubtreeTags::createEmpty(), $parentSubtreeTags->all()),
                );
                $hierarchyRelation->addToDatabase($this->dbal, $this->tableNames);
            }
        }

        foreach (
            $this->projectionContentGraph->findOutgoingHierarchyRelationsForNodeAggregate(
                $contentStreamLayers,
                $sourceNode->nodeAggregateId,
                $specializationSiblings->toDimensionSpacePointSet()
            ) as $hierarchyRelation
        ) {
            if ($contentStreamLayers->getWriteLayer()->equals($hierarchyRelation->contentStreamLayer)) {
                $hierarchyRelation->assignNewParentNode(
                    $specializedNode->relationAnchorPoint,
                    null,
                    $this->dbal,
                    $this->tableNames
                );
            } else {
                $copiedHierarchyRelation = $hierarchyRelation->with(
                    parentNodeAnchor: $specializedNode->relationAnchorPoint,
                    contentStreamLayer: $contentStreamLayers->getWriteLayer(),
                );
                $copiedHierarchyRelation->addToDatabase(
                    $this->dbal,
                    $this->tableNames
                );
            }
        }

        // Copy Reference Edges
        $this->copyReferenceRelations(
            $sourceNode->relationAnchorPoint,
            $specializedNode->relationAnchorPoint
        );
    }

    public function createNodeGeneralizationVariant(ContentStreamLayers $contentStreamLayers, NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint $sourceOrigin, OriginDimensionSpacePoint $generalizationOrigin, InterdimensionalSiblings $variantSucceedingSiblings, EventEnvelope $eventEnvelope): void
    {
        // do the generalization
        $sourceNode = $this->projectionContentGraph->findNodeInAggregate(
            $contentStreamLayers,
            $nodeAggregateId,
            $sourceOrigin->toDimensionSpacePoint()
        );
        if (is_null($sourceNode)) {
            throw new \RuntimeException(sprintf('Failed to create node generalization variant for node "%s" in sub graph %s@%s because the source node is missing', $nodeAggregateId->value, $sourceOrigin->toJson(), $contentStreamLayers->toDebugString()), 1716498802);
        }
        $sourceParentNode = $this->projectionContentGraph->findParentNode(
            $contentStreamLayers,
            $nodeAggregateId,
            $sourceOrigin
        );
        if (is_null($sourceParentNode)) {
            throw new \RuntimeException(sprintf('Failed to create node generalization variant for node "%s" in sub graph %s@%s because the source parent node is missing', $nodeAggregateId->value, $sourceOrigin->toJson(), $contentStreamLayers->toDebugString()), 1716498857);
        }
        $generalizedNode = $this->copyNodeToDimensionSpacePoint(
            $sourceNode,
            $generalizationOrigin,
            $eventEnvelope
        );

        $unassignedIngoingDimensionSpacePoints = $variantSucceedingSiblings->toDimensionSpacePointSet();
        foreach (
            $this->projectionContentGraph->findIngoingHierarchyRelationsForNodeAggregate(
                $contentStreamLayers,
                $nodeAggregateId,
                $variantSucceedingSiblings->toDimensionSpacePointSet()
            ) as $existingIngoingHierarchyRelation
        ) {
            if ($contentStreamLayers->getWriteLayer()->equals($existingIngoingHierarchyRelation->contentStreamLayer)) {
                $existingIngoingHierarchyRelation->assignNewChildNode(
                    $generalizedNode->relationAnchorPoint,
                    $this->dbal,
                    $this->tableNames
                );
            } else {
                $copiedHierarchyRelation = $existingIngoingHierarchyRelation->with(
                    childNodeAnchor: $generalizedNode->relationAnchorPoint,
                    contentStreamLayer: $contentStreamLayers->getWriteLayer(),
                );
                $copiedHierarchyRelation->addToDatabase(
                    $this->dbal,
                    $this->tableNames
                );
            }
            $unassignedIngoingDimensionSpacePoints = $unassignedIngoingDimensionSpacePoints->getDifference(
                new DimensionSpacePointSet([
                    $existingIngoingHierarchyRelation->dimensionSpacePoint
                ])
            );
        }

        foreach (
            $this->projectionContentGraph->findOutgoingHierarchyRelationsForNodeAggregate(
                $contentStreamLayers,
                $nodeAggregateId,
                $variantSucceedingSiblings->toDimensionSpacePointSet()
            ) as $existingOutgoingHierarchyRelation
        ) {
            if ($contentStreamLayers->getWriteLayer()->equals($existingOutgoingHierarchyRelation->contentStreamLayer)) {
                $existingOutgoingHierarchyRelation->assignNewParentNode(
                    $generalizedNode->relationAnchorPoint,
                    null,
                    $this->dbal,
                    $this->tableNames
                );
            } else {
                $copiedHierarchyRelation = $existingOutgoingHierarchyRelation->with(
                    parentNodeAnchor: $generalizedNode->relationAnchorPoint,
                    contentStreamLayer: $contentStreamLayers->getWriteLayer(),
                );
                $copiedHierarchyRelation->addToDatabase(
                    $this->dbal,
                    $this->tableNames
                );
            }
        }

        if (count($unassignedIngoingDimensionSpacePoints) > 0) {
            $ingoingSourceHierarchyRelation = $this->projectionContentGraph->findIngoingHierarchyRelationsForNode(
                $sourceNode->relationAnchorPoint,
                $contentStreamLayers,
                new DimensionSpacePointSet([$sourceOrigin->toDimensionSpacePoint()])
            )[$sourceOrigin->hash] ?? null;
            if (is_null($ingoingSourceHierarchyRelation)) {
                throw new \RuntimeException(sprintf('Failed to create node generalization variant for node "%s" in sub graph %s@%s because the ingoing hierarchy relation is missing', $nodeAggregateId->value, $sourceOrigin->toJson(), $contentStreamLayers->toDebugString()), 1716498940);
            }
            // the null case is caught by the NodeAggregate or its command handler
            foreach ($unassignedIngoingDimensionSpacePoints as $unassignedDimensionSpacePoint) {
                // The parent node aggregate might be varied as well,
                // so we need to find a parent node for each covered dimension space point
                $generalizationParentNode = $this->projectionContentGraph->findNodeInAggregate(
                    $contentStreamLayers,
                    $sourceParentNode->nodeAggregateId,
                    $unassignedDimensionSpacePoint
                );
                if (is_null($generalizationParentNode)) {
                    throw new \RuntimeException(sprintf(
                        'Failed to assign node generalization relation for node "%s" from dimension space point %s to dimension space point %s in content stream %s because the target parent node "%s" is missing',
                        $nodeAggregateId->value,
                        $sourceOrigin->toJson(),
                        $unassignedDimensionSpacePoint->toJson(),
                        $contentStreamLayers->toDebugString(),
                        $sourceParentNode->nodeAggregateId->value
                    ), 1716498961);
                }

                $generalizationSucceedingSiblingNodeAggregateId = $variantSucceedingSiblings
                    ->getSucceedingSiblingIdForDimensionSpacePoint($unassignedDimensionSpacePoint);
                $generalizationSucceedingSiblingNode = $generalizationSucceedingSiblingNodeAggregateId
                    ? $this->projectionContentGraph->findNodeInAggregate(
                        $contentStreamLayers,
                        $generalizationSucceedingSiblingNodeAggregateId,
                        $unassignedDimensionSpacePoint
                    )
                    : null;

                $this->copyHierarchyRelationToDimensionSpacePoint(
                    $ingoingSourceHierarchyRelation,
                    $contentStreamLayers,
                    $unassignedDimensionSpacePoint,
                    $generalizationParentNode->relationAnchorPoint,
                    $generalizedNode->relationAnchorPoint,
                    $generalizationSucceedingSiblingNode?->relationAnchorPoint
                );
            }
        }

        // Copy Reference Edges
        $this->copyReferenceRelations(
            $sourceNode->relationAnchorPoint,
            $generalizedNode->relationAnchorPoint
        );
    }

    public function createNodePeerVariant(ContentStreamLayers $contentStreamLayers, NodeAggregateId $nodeAggregateId, OriginDimensionSpacePoint $sourceOrigin, OriginDimensionSpacePoint $peerOrigin, InterdimensionalSiblings $peerSucceedingSiblings, EventEnvelope $eventEnvelope): void
    {
        // Do the peer variant creation itself
        $sourceNode = $this->projectionContentGraph->findNodeInAggregate(
            $contentStreamLayers,
            $nodeAggregateId,
            $sourceOrigin->toDimensionSpacePoint()
        );
        if (is_null($sourceNode)) {
            throw new \RuntimeException(sprintf('Failed to create node peer variant for node "%s" in sub graph %s@%s because the source node is missing', $nodeAggregateId->value, $sourceOrigin->toJson(), $contentStreamLayers->toDebugString()), 1716498802);
        }
        $peerNode = $this->copyNodeToDimensionSpacePoint(
            $sourceNode,
            $peerOrigin,
            $eventEnvelope
        );

        $unassignedIngoingDimensionSpacePoints = $peerSucceedingSiblings->toDimensionSpacePointSet();
        foreach (
            $this->projectionContentGraph->findIngoingHierarchyRelationsForNodeAggregate(
                $contentStreamLayers,
                $nodeAggregateId,
                $peerSucceedingSiblings->toDimensionSpacePointSet()
            ) as $existingIngoingHierarchyRelation
        ) {
            if ($contentStreamLayers->getWriteLayer()->equals($existingIngoingHierarchyRelation->contentStreamLayer)) {
                $existingIngoingHierarchyRelation->assignNewChildNode(
                    $peerNode->relationAnchorPoint,
                    $this->dbal,
                    $this->tableNames
                );
            } else {
                $copiedHierarchyRelation = $existingIngoingHierarchyRelation->with(
                    childNodeAnchor: $peerNode->relationAnchorPoint,
                    contentStreamLayer: $contentStreamLayers->getWriteLayer(),
                );
                $copiedHierarchyRelation->addToDatabase(
                    $this->dbal,
                    $this->tableNames
                );
            }
            $unassignedIngoingDimensionSpacePoints = $unassignedIngoingDimensionSpacePoints->getDifference(
                new DimensionSpacePointSet([
                    $existingIngoingHierarchyRelation->dimensionSpacePoint
                ])
            );
        }

        foreach (
            $this->projectionContentGraph->findOutgoingHierarchyRelationsForNodeAggregate(
                $contentStreamLayers,
                $nodeAggregateId,
                $peerSucceedingSiblings->toDimensionSpacePointSet()
            ) as $existingOutgoingHierarchyRelation
        ) {
            if ($contentStreamLayers->getWriteLayer()->equals($existingOutgoingHierarchyRelation->contentStreamLayer)) {
                $existingOutgoingHierarchyRelation->assignNewParentNode(
                    $peerNode->relationAnchorPoint,
                    null,
                    $this->dbal,
                    $this->tableNames
                );
            } else {
                $copiedHierarchyRelation = $existingOutgoingHierarchyRelation->with(
                    parentNodeAnchor: $peerNode->relationAnchorPoint,
                    contentStreamLayer: $contentStreamLayers->getWriteLayer(),
                );
                $copiedHierarchyRelation->addToDatabase(
                    $this->dbal,
                    $this->tableNames
                );
            }
        }

        $sourceParentNode = $this->projectionContentGraph->findParentNode(
            $contentStreamLayers,
            $nodeAggregateId,
            $sourceOrigin
        );
        if (is_null($sourceParentNode)) {
            throw new \RuntimeException(sprintf('Failed to create node peer variant for node "%s" in sub graph %s@%s because the source parent node is missing', $nodeAggregateId->value, $sourceOrigin->toJson(), $contentStreamLayers->toDebugString()), 1716498881);
        }
        foreach ($unassignedIngoingDimensionSpacePoints as $coveredDimensionSpacePoint) {
            // The parent node aggregate might be varied as well,
            // so we need to find a parent node for each covered dimension space point
            $peerParentNode = $this->projectionContentGraph->findNodeInAggregate(
                $contentStreamLayers,
                $sourceParentNode->nodeAggregateId,
                $coveredDimensionSpacePoint
            );
            if (is_null($peerParentNode)) {
                throw new \RuntimeException(sprintf('Failed to create node peer variant for node "%s" in sub graph %s@%s because the target parent node "%s" is missing', $nodeAggregateId->value, $sourceOrigin->toJson(), $contentStreamLayers->toDebugString(), $sourceParentNode->nodeAggregateId->value), 1716499016);
            }
            $peerSucceedingSiblingNodeAggregateId = $peerSucceedingSiblings
                ->getSucceedingSiblingIdForDimensionSpacePoint($coveredDimensionSpacePoint);
            $peerSucceedingSiblingNode = $peerSucceedingSiblingNodeAggregateId
                ? $this->projectionContentGraph->findNodeInAggregate(
                    $contentStreamLayers,
                    $peerSucceedingSiblingNodeAggregateId,
                    $coveredDimensionSpacePoint
                )
                : null;

            $this->connectHierarchy(
                $contentStreamLayers,
                $peerParentNode->relationAnchorPoint,
                $peerNode->relationAnchorPoint,
                new DimensionSpacePointSet([$coveredDimensionSpacePoint]),
                $peerSucceedingSiblingNode?->relationAnchorPoint,
            );
        }

        // Copy Reference Edges
        $this->copyReferenceRelations(
            $sourceNode->relationAnchorPoint,
            $peerNode->relationAnchorPoint
        );
    }
}
