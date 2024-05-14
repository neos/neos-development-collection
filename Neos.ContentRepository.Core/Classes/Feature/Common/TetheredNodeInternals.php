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
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\TetheredNodeTypeDefinition;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

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
                            $parentNodeAggregate->contentStreamId,
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
            } else {
                return Events::with(
                    new NodeAggregateWithNodeWasCreated(
                        $parentNodeAggregate->contentStreamId,
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
        } else {
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
    }
}
