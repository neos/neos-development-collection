<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Common;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;

/**
 * @internal implementation details of command handlers
 */
trait TetheredNodeInternals
{
    use NodeVariationInternals;

    abstract protected function createEventsForVariations(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        NodeAggregate $nodeAggregate,
        UserIdentifier $initiatingUserIdentifier,
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
        Node $parentNode,
        NodeName $tetheredNodeName,
        ?NodeAggregateIdentifier $tetheredNodeAggregateIdentifier,
        NodeType $expectedTetheredNodeType,
        UserIdentifier $initiatingUserIdentifier,
        ContentRepository $contentRepository
    ): Events {
        $childNodeAggregates = $contentRepository->getContentGraph()->findChildNodeAggregatesByName(
            $parentNode->subgraphIdentity->contentStreamIdentifier,
            $parentNode->nodeAggregateIdentifier,
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
            return Events::with(
                new NodeAggregateWithNodeWasCreated(
                    $parentNode->subgraphIdentity->contentStreamIdentifier,
                    $tetheredNodeAggregateIdentifier ?: NodeAggregateIdentifier::create(),
                    NodeTypeName::fromString($expectedTetheredNodeType->getName()),
                    $parentNode->originDimensionSpacePoint,
                    $parentNodeAggregate->getCoverageByOccupant($parentNode->originDimensionSpacePoint),
                    $parentNode->nodeAggregateIdentifier,
                    $tetheredNodeName,
                    SerializedPropertyValues::defaultFromNodeType($expectedTetheredNodeType),
                    NodeAggregateClassification::CLASSIFICATION_TETHERED,
                    $initiatingUserIdentifier
                )
            );
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
                $parentNode->subgraphIdentity->contentStreamIdentifier,
                $childNodeSource->originDimensionSpacePoint,
                $parentNode->originDimensionSpacePoint,
                $parentNodeAggregate,
                $initiatingUserIdentifier,
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
