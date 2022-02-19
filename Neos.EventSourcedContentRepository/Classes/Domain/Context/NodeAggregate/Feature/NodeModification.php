<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetSerializedNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePropertiesWereSet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Ramsey\Uuid\Uuid;

trait NodeModification
{
    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function requireNodeType(NodeTypeName $nodeTypeName): NodeType;

    abstract protected function requireProjectedNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ReadableNodeAggregateInterface;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

    public function handleSetNodeProperties(SetNodeProperties $command): CommandResult
    {
        $nodeTypeName = $this->contentGraph->findNodeAggregateByIdentifier(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier
        )->getNodeTypeName();

        $this->validateProperties($command->propertyValues, $nodeTypeName);

        $lowLevelCommand = new SetSerializedNodeProperties(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
            $command->originDimensionSpacePoint,
            $this->getPropertyConverter()->serializePropertyValues(
                $command->propertyValues,
                $this->requireNodeType($nodeTypeName)
            ),
            $command->initiatingUserIdentifier
        );

        return $this->handleSetSerializedNodeProperties($lowLevelCommand);
    }

    public function handleSetSerializedNodeProperties(SetSerializedNodeProperties $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $events = null;
        $this->getNodeAggregateEventPublisher()->withCommand($command, function () use ($command, &$events) {
            // Check if node exists
            $nodeAggregate = $this->requireProjectedNodeAggregate(
                $command->contentStreamIdentifier,
                $command->nodeAggregateIdentifier
            );
            $this->requireNodeAggregateToOccupyDimensionSpacePoint($nodeAggregate, $command->originDimensionSpacePoint);

            $events = DomainEvents::withSingleEvent(
                DecoratedEvent::addIdentifier(
                    new NodePropertiesWereSet(
                        $command->contentStreamIdentifier,
                        $command->nodeAggregateIdentifier,
                        $command->originDimensionSpacePoint,
                        $command->propertyValues,
                        $command->initiatingUserIdentifier
                    ),
                    Uuid::uuid4()->toString()
                )
            );

            $this->getNodeAggregateEventPublisher()->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
                    ->getEventStreamName(),
                $events
            );
        });

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }
}
