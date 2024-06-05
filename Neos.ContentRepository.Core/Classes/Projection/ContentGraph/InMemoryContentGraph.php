<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\VariantType;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSibling;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @api To be used by external services
 */
final class InMemoryContentGraph
{
    /**
     * @param array<string,array<string,Node>> $parentByDimensionSpacePointAndChild
     * @param array<string,array<string,Nodes>> $childrenByDimensionSpacePointAndParent
     */
    public function __construct(
        private readonly WorkspaceName $workspaceName,
        private readonly ContentStreamId $contentStreamId,
        private NodeAggregates $rootNodeAggregates,
        private array $parentByDimensionSpacePointAndChild,
        private array $childrenByDimensionSpacePointAndParent,
    ) {
    }

    public function toMinimalConstitutingEvents(InterDimensionalVariationGraph $variationGraph): Events
    {
        $events = [];
        foreach ($this->rootNodeAggregates as $rootNodeAggregate) {
            $events[] = new RootNodeAggregateWithNodeWasCreated(
                $this->workspaceName,
                $this->contentStreamId,
                $rootNodeAggregate->nodeAggregateId,
                $rootNodeAggregate->nodeTypeName,
                $rootNodeAggregate->coveredDimensionSpacePoints,
                NodeAggregateClassification::CLASSIFICATION_ROOT
            );
        }

        /** @var array<string,OriginDimensionSpacePointSet> $nodeOccupation occupied dimension space points by node aggregate id */
        $nodeOccupation = [];
        foreach ($this->rootNodeAggregates as $rootNodeAggregate) {
            foreach ($variationGraph->getRootGeneralizations() as $rootGeneralization) {
                foreach ($variationGraph->getSpecializationSet($rootGeneralization) as $specialization) {
                    $events = $this->traverseDimensionSpacePoint(
                        $rootNodeAggregate->nodeAggregateId,
                        $specialization,
                        $events,
                        $variationGraph,
                        $nodeOccupation,
                    );
                }
            }
        }

        return Events::fromArray($events);
    }

    /**
     * @param array<int,EventInterface> $events
     * @param array<string,OriginDimensionSpacePointSet> $nodeOccupation,
     * @return array<int,EventInterface>
     */
    private function traverseDimensionSpacePoint(
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint,
        array $events,
        InterDimensionalVariationGraph $variationGraph,
        array &$nodeOccupation
    ): array {
        $childNodes = $this->childrenByDimensionSpacePointAndParent[$dimensionSpacePoint->hash][$nodeAggregateId->value];
        foreach ($childNodes as $childNode) {
            if ($childNode->originDimensionSpacePoint->equals($childNode->dimensionSpacePoint)) {
                $childNodeOccupation = $nodeOccupation[$childNode->aggregateId->value] ?? OriginDimensionSpacePointSet::fromArray([]);
                $interdimensionalSiblings = $this->resolveInterdimensionalSiblings($childNode, $variationGraph, $nodeOccupation);
                if ($childNodeOccupation->count() === 0) {
                    $events[] = new NodeAggregateWithNodeWasCreated(
                        $this->workspaceName,
                        $this->contentStreamId,
                        $childNode->aggregateId,
                        $childNode->nodeTypeName,
                        $childNode->originDimensionSpacePoint,
                        $interdimensionalSiblings,
                        $nodeAggregateId,
                        $childNode->name,
                        $childNode->properties->serialized(),
                        $childNode->classification
                    );
                } else {
                    $referenceDimensionSpacePoints = $childNodeOccupation->getPoints();
                    $referenceDimensionSpacePoint = reset($referenceDimensionSpacePoints);
                    assert($referenceDimensionSpacePoint instanceof OriginDimensionSpacePoint);

                    $variantType = $variationGraph->getVariantType(
                        $childNode->dimensionSpacePoint,
                        $referenceDimensionSpacePoint->toDimensionSpacePoint(),
                    );
                    $events[] = match ($variantType) {
                        VariantType::TYPE_SPECIALIZATION => new NodeSpecializationVariantWasCreated(
                            $this->workspaceName,
                            $this->contentStreamId,
                            $childNode->aggregateId,
                            $referenceDimensionSpacePoint,
                            $childNode->originDimensionSpacePoint,
                            $interdimensionalSiblings,
                        ),
                        VariantType::TYPE_PEER => new NodePeerVariantWasCreated(
                            $this->workspaceName,
                            $this->contentStreamId,
                            $childNode->aggregateId,
                            $referenceDimensionSpacePoint,
                            $childNode->originDimensionSpacePoint,
                            $interdimensionalSiblings,
                        ),
                        default => throw new \Exception('Usupported variant type ' . $variantType->value),
                    };
                    $events[] = new NodePropertiesWereSet(
                        $this->workspaceName,
                        $this->contentStreamId,
                        $childNode->aggregateId,
                        $childNode->originDimensionSpacePoint,
                        $interdimensionalSiblings->toDimensionSpacePointSet(),
                        $childNode->properties->serialized(),
                        PropertyNames::createEmpty(),
                    );
                }
                $nodeOccupation[$childNode->aggregateId->value] = $childNodeOccupation->getUnion(new OriginDimensionSpacePointSet([$childNode->originDimensionSpacePoint]));

                $events = $this->traverseDimensionSpacePoint(
                    $childNode->aggregateId,
                    $dimensionSpacePoint,
                    $events,
                    $variationGraph,
                    $nodeOccupation
                );
            }
        }

        return $events;
    }

    /**
     * @param array<string,OriginDimensionSpacePointSet> $nodeOccupation ,
     */
    private function resolveInterdimensionalSiblings(Node $node, InterDimensionalVariationGraph $variationGraph, array $nodeOccupation): InterdimensionalSiblings
    {
        $interdimensionalSiblings = [];
        foreach ($variationGraph->getSpecializationSet($node->dimensionSpacePoint) as $specialization) {
            $siblings = $this->childrenByDimensionSpacePointAndParent[$specialization->hash][$this->parentByDimensionSpacePointAndChild[$specialization->hash][$node->aggregateId->value]->aggregateId->value];
            $succeedingSibling = null;
            $nodeFound = false;
            foreach ($siblings as $sibling) {
                if ($nodeFound && ($nodeOccupation[$sibling->aggregateId->value] ?? null)?->contains($sibling->originDimensionSpacePoint)) {
                    // we only assign succeeding siblings that have already been created
                    $succeedingSibling = $sibling;
                    break;
                }
                if ($sibling->aggregateId->equals($node->aggregateId)) {
                    $nodeFound = true;
                }
            }
            $interdimensionalSiblings[] = new InterdimensionalSibling(
                $specialization,
                $succeedingSibling?->aggregateId
            );
        }
        return new InterdimensionalSiblings(...$interdimensionalSiblings);
    }
}
