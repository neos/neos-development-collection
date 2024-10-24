<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\VariantType;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\Feature\NodeCreation\Dto\NodeAggregateIdsByNodePaths;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Dto\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\TetheredNodeTypeDefinition;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\CoverageByOrigin;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;

/**
 * @internal implementation details of command handlers
 */
trait TetheredNodeInternals
{
    use NodeVariationInternals;

    abstract protected function getPropertyConverter(): PropertyConverter;

    abstract protected function createEventsForVariations(
        ContentGraphInterface $contentGraph,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate
    ): Events;

    /**
     * This is the remediation action for non-existing tethered nodes.
     * It handles two cases:
     * - there is no tethered node IN ANY DimensionSpacePoint -> we can simply create it
     * - there is a tethered node already in some DimensionSpacePoint
     *   -> we need to specialize/generalize/... the other Tethered Node.
     * @throws \Exception
     */
    protected function createEventsForMissingTetheredNode(
        ContentGraphInterface $contentGraph,
        NodeAggregate $parentNodeAggregate,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        TetheredNodeTypeDefinition $tetheredNodeTypeDefinition,
        ?NodeAggregateId $tetheredNodeAggregateId
    ): Events {
        $childNodeAggregate = $contentGraph->findChildNodeAggregateByName(
            $parentNodeAggregate->nodeAggregateId,
            $tetheredNodeTypeDefinition->name
        );

        $expectedTetheredNodeType = $this->nodeTypeManager->getNodeType($tetheredNodeTypeDefinition->nodeTypeName);
        $defaultProperties = $expectedTetheredNodeType
            ? SerializedPropertyValues::defaultFromNodeType($expectedTetheredNodeType, $this->getPropertyConverter())
            : SerializedPropertyValues::createEmpty();

        if ($childNodeAggregate === null) {
            // there is no tethered child node aggregate already; let's create it!
            $nodeType = $this->nodeTypeManager->getNodeType($parentNodeAggregate->nodeTypeName);
            if ($nodeType?->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)) {
                $events = [];
                $tetheredNodeAggregateId = $tetheredNodeAggregateId ?: NodeAggregateId::create();
                // we create in one origin DSP and vary in the others
                $creationOriginDimensionSpacePoint = null;
                foreach ($this->getInterDimensionalVariationGraph()->getRootGeneralizations() as $rootGeneralization) {
                    $rootGeneralizationOrigin = OriginDimensionSpacePoint::fromDimensionSpacePoint($rootGeneralization);
                    if ($creationOriginDimensionSpacePoint) {
                        $events[] = new NodePeerVariantWasCreated(
                            $contentGraph->getWorkspaceName(),
                            $contentGraph->getContentStreamId(),
                            $tetheredNodeAggregateId,
                            $creationOriginDimensionSpacePoint,
                            $rootGeneralizationOrigin,
                            InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                                $this->getInterDimensionalVariationGraph()->getSpecializationSet($rootGeneralization),
                            )
                        );
                    } else {
                        $events[] = new NodeAggregateWithNodeWasCreated(
                            $contentGraph->getWorkspaceName(),
                            $contentGraph->getContentStreamId(),
                            $tetheredNodeAggregateId,
                            $tetheredNodeTypeDefinition->nodeTypeName,
                            $rootGeneralizationOrigin,
                            InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                                $this->getInterDimensionalVariationGraph()->getSpecializationSet($rootGeneralization)
                            ),
                            $parentNodeAggregate->nodeAggregateId,
                            $tetheredNodeTypeDefinition->name,
                            $defaultProperties,
                            NodeAggregateClassification::CLASSIFICATION_TETHERED,
                        );
                        $creationOriginDimensionSpacePoint = $rootGeneralizationOrigin;
                    }
                }
                return Events::fromArray($events);
            }
            return Events::with(
                new NodeAggregateWithNodeWasCreated(
                    $contentGraph->getWorkspaceName(),
                    $contentGraph->getContentStreamId(),
                    $tetheredNodeAggregateId ?: NodeAggregateId::create(),
                    $tetheredNodeTypeDefinition->nodeTypeName,
                    $originDimensionSpacePoint,
                    InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                        $parentNodeAggregate->getCoverageByOccupant($originDimensionSpacePoint)
                    ),
                    $parentNodeAggregate->nodeAggregateId,
                    $tetheredNodeTypeDefinition->name,
                    $defaultProperties,
                    NodeAggregateClassification::CLASSIFICATION_TETHERED,
                )
            );
        }
        if (!$childNodeAggregate->classification->isTethered()) {
            throw new \RuntimeException(
                'We found a child node aggregate through the given node path; but it is not tethered.'
                    . ' We do not support re-tethering yet'
                    . ' (as this case should happen very rarely as far as we think).'
            );
        }

        $childNodeSource = null;
        foreach ($childNodeAggregate->getNodes() as $node) {
            $childNodeSource = $node;
            break;
        }
        /** @var Node $childNodeSource Node aggregates are never empty */
        return $this->createEventsForVariations(
            $contentGraph,
            $childNodeSource->originDimensionSpacePoint,
            $originDimensionSpacePoint,
            $parentNodeAggregate
        );
    }

    protected function createEventsForMissingTetheredNodeAggregate(
        ContentGraphInterface $contentGraph,
        TetheredNodeTypeDefinition $tetheredNodeTypeDefinition,
        OriginDimensionSpacePointSet $affectedOriginDimensionSpacePoints,
        CoverageByOrigin $coverageByOrigin,
        NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        NodeAggregateIdsByNodePaths $nodeAggregateIdsByNodePaths,
        NodePath $currentNodePath,
    ): Events {
        $events = [];
        $tetheredNodeType = $this->requireNodeType($tetheredNodeTypeDefinition->nodeTypeName);
        $nodeAggregateId = $nodeAggregateIdsByNodePaths->getNodeAggregateId($currentNodePath) ?? NodeAggregateId::create();
        $defaultValues = SerializedPropertyValues::defaultFromNodeType(
            $tetheredNodeType,
            $this->getPropertyConverter()
        );
        $creationOrigin = null;
        foreach ($affectedOriginDimensionSpacePoints as $originDimensionSpacePoint) {
            $coverage = $coverageByOrigin->getCoverage($originDimensionSpacePoint);
            if (!$coverage) {
                throw new \RuntimeException('Missing coverage for origin dimension space point ' . \json_encode($originDimensionSpacePoint));
            }
            $interdimensionalSiblings = InterdimensionalSiblings::fromDimensionSpacePointSetWithSingleSucceedingSiblings(
                $coverage,
                $succeedingSiblingNodeAggregateId,
            );
            $events[] = $creationOrigin
                ? match (
                    $this->interDimensionalVariationGraph->getVariantType(
                        $originDimensionSpacePoint->toDimensionSpacePoint(),
                        $creationOrigin->toDimensionSpacePoint(),
                    )
                ) {
                    VariantType::TYPE_SPECIALIZATION => new NodeSpecializationVariantWasCreated(
                        $contentGraph->getWorkspaceName(),
                        $contentGraph->getContentStreamId(),
                        $nodeAggregateId,
                        $creationOrigin,
                        $originDimensionSpacePoint,
                        $interdimensionalSiblings,
                    ),
                    VariantType::TYPE_GENERALIZATION => new NodeGeneralizationVariantWasCreated(
                        $contentGraph->getWorkspaceName(),
                        $contentGraph->getContentStreamId(),
                        $nodeAggregateId,
                        $creationOrigin,
                        $originDimensionSpacePoint,
                        $interdimensionalSiblings,
                    ),
                    default => new NodePeerVariantWasCreated(
                        $contentGraph->getWorkspaceName(),
                        $contentGraph->getContentStreamId(),
                        $nodeAggregateId,
                        $creationOrigin,
                        $originDimensionSpacePoint,
                        $interdimensionalSiblings,
                    ),
                }
                : new NodeAggregateWithNodeWasCreated(
                    $contentGraph->getWorkspaceName(),
                    $contentGraph->getContentStreamId(),
                    $nodeAggregateId,
                    $tetheredNodeTypeDefinition->nodeTypeName,
                    $originDimensionSpacePoint,
                    $interdimensionalSiblings,
                    $parentNodeAggregateId,
                    $tetheredNodeTypeDefinition->name,
                    $defaultValues,
                    NodeAggregateClassification::CLASSIFICATION_TETHERED,
                );

            $creationOrigin ??= $originDimensionSpacePoint;
        }

        foreach ($tetheredNodeType->tetheredNodeTypeDefinitions as $childTetheredNodeTypeDefinition) {
            $events = array_merge(
                $events,
                iterator_to_array(
                    $this->createEventsForMissingTetheredNodeAggregate(
                        $contentGraph,
                        $childTetheredNodeTypeDefinition,
                        $affectedOriginDimensionSpacePoints,
                        $coverageByOrigin,
                        $nodeAggregateId,
                        null,
                        $nodeAggregateIdsByNodePaths,
                        $currentNodePath->appendPathSegment($childTetheredNodeTypeDefinition->name),
                    )
                )
            );
        }

        return Events::fromArray($events);
    }

    protected function createEventsForWronglyTypedNodeAggregate(
        ContentGraphInterface $contentGraph,
        NodeAggregate $nodeAggregate,
        NodeTypeName $newNodeTypeName,
        NodeAggregateIdsByNodePaths $nodeAggregateIdsByNodePaths,
        NodePath $currentNodePath,
        NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy $conflictResolutionStrategy,
        NodeAggregateIds $alreadyRemovedNodeAggregateIds,
    ): Events {
        $events = [];

        $tetheredNodeType = $this->requireNodeType($newNodeTypeName);

        $events[] = new NodeAggregateTypeWasChanged(
            $contentGraph->getWorkspaceName(),
            $contentGraph->getContentStreamId(),
            $nodeAggregate->nodeAggregateId,
            $newNodeTypeName,
        );

        # Handle property adjustments
        foreach ($nodeAggregate->getNodes() as $node) {
            $presentPropertyKeys = array_keys(iterator_to_array($node->properties->serialized()));
            $complementaryPropertyValues = SerializedPropertyValues::defaultFromNodeType(
                $tetheredNodeType,
                $this->propertyConverter
            )
                ->unsetProperties(PropertyNames::fromArray($presentPropertyKeys));
            $obsoletePropertyNames = PropertyNames::fromArray(
                array_diff(
                    $presentPropertyKeys,
                    array_keys($tetheredNodeType->getProperties()),
                )
            );

            if (count($complementaryPropertyValues->values) > 0 || count($obsoletePropertyNames) > 0) {
                $events[] = new NodePropertiesWereSet(
                    $contentGraph->getWorkspaceName(),
                    $contentGraph->getContentStreamId(),
                    $nodeAggregate->nodeAggregateId,
                    $node->originDimensionSpacePoint,
                    $nodeAggregate->getCoverageByOccupant($node->originDimensionSpacePoint),
                    $complementaryPropertyValues,
                    $obsoletePropertyNames
                );
            }
        }

        // remove disallowed nodes
        if ($conflictResolutionStrategy === NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_DELETE) {
            array_push($events, ...iterator_to_array(
                $this->deleteDisallowedNodesWhenChangingNodeType(
                    $contentGraph,
                    $nodeAggregate,
                    $tetheredNodeType,
                    $alreadyRemovedNodeAggregateIds
                )
            ));
            array_push($events, ...iterator_to_array(
                $this->deleteObsoleteTetheredNodesWhenChangingNodeType(
                    $contentGraph,
                    $nodeAggregate,
                    $tetheredNodeType,
                    $alreadyRemovedNodeAggregateIds
                )
            ));
        }

        # Handle descendant nodes
        foreach ($tetheredNodeType->tetheredNodeTypeDefinitions as $childTetheredNodeTypeDefinition) {
            $tetheredChildNodeAggregate = $contentGraph->findChildNodeAggregateByName(
                $nodeAggregate->nodeAggregateId,
                $childTetheredNodeTypeDefinition->name
            );
            if ($tetheredChildNodeAggregate === null) {
                $events = array_merge(
                    $events,
                    iterator_to_array($this->createEventsForMissingTetheredNodeAggregate(
                        $contentGraph,
                        $childTetheredNodeTypeDefinition,
                        $nodeAggregate->occupiedDimensionSpacePoints,
                        $nodeAggregate->coverageByOccupant,
                        $nodeAggregate->nodeAggregateId,
                        null,
                        $nodeAggregateIdsByNodePaths,
                        $currentNodePath->appendPathSegment($childTetheredNodeTypeDefinition->name),
                    ))
                );
            } elseif (!$tetheredChildNodeAggregate->nodeTypeName->equals($childTetheredNodeTypeDefinition->nodeTypeName)) {
                $events = array_merge($events, iterator_to_array(
                    $this->createEventsForWronglyTypedNodeAggregate(
                        $contentGraph,
                        $tetheredChildNodeAggregate,
                        $childTetheredNodeTypeDefinition->nodeTypeName,
                        $nodeAggregateIdsByNodePaths,
                        $currentNodePath->appendPathSegment($childTetheredNodeTypeDefinition->name),
                        $conflictResolutionStrategy,
                        $alreadyRemovedNodeAggregateIds,
                    )
                ));
            }
        }

        return Events::fromArray($events);
    }
}
