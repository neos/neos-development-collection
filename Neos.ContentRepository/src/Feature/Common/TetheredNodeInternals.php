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

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait TetheredNodeInternals
{
    use NodeVariationInternals;

    abstract protected function getContentGraph(): ContentGraphInterface;

    abstract protected function createEventsForVariations(
        ContentStreamIdentifier $contentStreamIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        OriginDimensionSpacePoint $targetOrigin,
        ReadableNodeAggregateInterface $nodeAggregate,
        UserIdentifier $initiatingUserIdentifier
    ): DomainEvents;

    /**
     * This is the remediation action for non-existing tethered nodes.
     * It handles two cases:
     * - there is no tethered node IN ANY DimensionSpacePoint -> we can simply create it
     * - there is a tethered node already in some DimensionSpacePoint
     *   -> we need to specialize/generalize/... the other Tethered Node.
     * @throws \Exception
     */
    protected function createEventsForMissingTetheredNode(
        ReadableNodeAggregateInterface $parentNodeAggregate,
        NodeInterface $parentNode,
        NodeName $tetheredNodeName,
        ?NodeAggregateIdentifier $tetheredNodeAggregateIdentifier,
        NodeType $expectedTetheredNodeType,
        UserIdentifier $initiatingUserIdentifier
    ): DomainEvents {
        $childNodeAggregates = $this->getContentGraph()->findChildNodeAggregatesByName(
            $parentNode->getContentStreamIdentifier(),
            $parentNode->getNodeAggregateIdentifier(),
            $tetheredNodeName
        );

        $tmp = [];
        foreach ($childNodeAggregates as $childNodeAggregate) {
            $tmp[] = $childNodeAggregate;
        }
        /** @var array<int,ReadableNodeAggregateInterface> $childNodeAggregates */
        $childNodeAggregates = $tmp;

        if (count($childNodeAggregates) === 0) {
            // there is no tethered child node aggregate already; let's create it!
            $events = DomainEvents::withSingleEvent(
                DecoratedEvent::addIdentifier(
                    new NodeAggregateWithNodeWasCreated(
                        $parentNode->getContentStreamIdentifier(),
                        $tetheredNodeAggregateIdentifier ?: NodeAggregateIdentifier::create(),
                        NodeTypeName::fromString($expectedTetheredNodeType->getName()),
                        $parentNode->getOriginDimensionSpacePoint(),
                        $parentNodeAggregate->getCoverageByOccupant($parentNode->getOriginDimensionSpacePoint()),
                        $parentNode->getNodeAggregateIdentifier(),
                        $tetheredNodeName,
                        SerializedPropertyValues::defaultFromNodeType($expectedTetheredNodeType),
                        NodeAggregateClassification::CLASSIFICATION_TETHERED,
                        $initiatingUserIdentifier
                    ),
                    Uuid::uuid4()->toString()
                )
            );
        } elseif (count($childNodeAggregates) === 1) {
            /** @var ReadableNodeAggregateInterface $childNodeAggregate */
            $childNodeAggregate = current($childNodeAggregates);
            if (!$childNodeAggregate->isTethered()) {
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
            /** @var NodeInterface $childNodeSource Node aggregates are never empty */
            $events = $this->createEventsForVariations(
                $parentNode->getContentStreamIdentifier(),
                $childNodeSource->getOriginDimensionSpacePoint(),
                $parentNode->getOriginDimensionSpacePoint(),
                $parentNodeAggregate,
                $initiatingUserIdentifier
            );
        } else {
            throw new \RuntimeException(
                'There is >= 2 ChildNodeAggregates with the same name reachable from the parent' .
                    '- this is ambiguous and we should analyze how this may happen. That is very likely a bug.'
            );
        }

        return $events;
    }
}
