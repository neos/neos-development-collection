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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @internal implementation details of command handlers
 */
trait TetheredNodeInternals
{
    use NodeVariationInternals;

    abstract protected function getPropertyConverter(): PropertyConverter;

    abstract protected function createEventsForVariations(
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        ContentRepository $contentRepository
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
        NodeAggregate $parentNodeAggregate,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        NodeName $tetheredNodeName,
        ?NodeAggregateId $tetheredNodeAggregateId,
        NodeType $expectedTetheredNodeType,
        ContentRepository $contentRepository
    ): Events {
        $childNodeAggregates = $contentRepository->getContentGraph()->findChildNodeAggregatesByName(
            $parentNodeAggregate->contentStreamId,
            $parentNodeAggregate->nodeAggregateId,
            $tetheredNodeName
        );

        $tmp = [];
        foreach ($childNodeAggregates as $childNodeAggregate) {
            $tmp[] = $childNodeAggregate;
        }
        /** @var array<int,NodeAggregate> $childNodeAggregates */
        $childNodeAggregates = $tmp;

        if (count($childNodeAggregates) === 0) {
            // there is no tethered child node aggregate already; let's create it!
            $nodeType = $this->nodeTypeManager->requireNodeType($parentNodeAggregate->nodeTypeName);
            if ($nodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)) {
                $events = [];
                $tetheredNodeAggregateId = $tetheredNodeAggregateId ?: NodeAggregateId::create();
                // we create in one origin DSP and vary in the others
                $creationOriginDimensionSpacePoint = null;
                foreach ($this->getInterDimensionalVariationGraph()->getRootGeneralizations() as $rootGeneralization) {
                    $rootGeneralizationOrigin = OriginDimensionSpacePoint::fromDimensionSpacePoint($rootGeneralization);
                    if ($creationOriginDimensionSpacePoint) {
                        $events[] = new NodePeerVariantWasCreated(
                            WorkspaceName::fromString('todo'), // TODO read from $parentNodeAggregate
                            $parentNodeAggregate->contentStreamId,
                            $tetheredNodeAggregateId,
                            $creationOriginDimensionSpacePoint,
                            $rootGeneralizationOrigin,
                            InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                                $this->getInterDimensionalVariationGraph()->getSpecializationSet($rootGeneralization),
                            )
                        );
                    } else {
                        $events[] = new NodeAggregateWithNodeWasCreated(
                            WorkspaceName::fromString('todo'), // TODO read from $parentNodeAggregate
                            $parentNodeAggregate->contentStreamId,
                            $tetheredNodeAggregateId,
                            $expectedTetheredNodeType->name,
                            $rootGeneralizationOrigin,
                            InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                                $this->getInterDimensionalVariationGraph()->getSpecializationSet($rootGeneralization)
                            ),
                            $parentNodeAggregate->nodeAggregateId,
                            $tetheredNodeName,
                            SerializedPropertyValues::defaultFromNodeType($expectedTetheredNodeType, $this->getPropertyConverter()),
                            NodeAggregateClassification::CLASSIFICATION_TETHERED,
                        );
                        $creationOriginDimensionSpacePoint = $rootGeneralizationOrigin;
                    }
                }
                return Events::fromArray($events);
            } else {
                return Events::with(
                    new NodeAggregateWithNodeWasCreated(
                        WorkspaceName::fromString('todo'), // TODO read from $parentNodeAggregate
                        $parentNodeAggregate->contentStreamId,
                        $tetheredNodeAggregateId ?: NodeAggregateId::create(),
                        $expectedTetheredNodeType->name,
                        $originDimensionSpacePoint,
                        InterdimensionalSiblings::fromDimensionSpacePointSetWithoutSucceedingSiblings(
                            $parentNodeAggregate->getCoverageByOccupant($originDimensionSpacePoint)
                        ),
                        $parentNodeAggregate->nodeAggregateId,
                        $tetheredNodeName,
                        SerializedPropertyValues::defaultFromNodeType($expectedTetheredNodeType, $this->getPropertyConverter()),
                        NodeAggregateClassification::CLASSIFICATION_TETHERED,
                    )
                );
            }
        } elseif (count($childNodeAggregates) === 1) {
            /** @var NodeAggregate $childNodeAggregate */
            $childNodeAggregate = current($childNodeAggregates);
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
                WorkspaceName::fromString('todo'), // TODO read from $parentNodeAggregate
                $parentNodeAggregate->contentStreamId,
                $childNodeSource->originDimensionSpacePoint,
                $originDimensionSpacePoint,
                $parentNodeAggregate,
                $contentRepository
            );
        } else {
            throw new \RuntimeException(
                'There is >= 2 ChildNodeAggregates with the same name reachable from the parent' .
                    '- this is ambiguous and we should analyze how this may happen. That is very likely a bug.'
            );
        }
    }
}
