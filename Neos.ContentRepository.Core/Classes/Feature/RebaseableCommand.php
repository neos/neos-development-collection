<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\InitiatingEventMetadata;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\AddDimensionShineThrough;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Command\MoveDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\DisableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Command\EnableNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeDuplication\Command\CopyNodesRecursively;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetSerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\TagSubtree;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Command\UntagSubtree;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\Event\EventId;
use Neos\EventStore\Model\Event\EventMetadata;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @internal
 */
final readonly class RebaseableCommand
{
    public function __construct(
        public RebasableToOtherWorkspaceInterface $originalCommand,
        public Event $originalEvent,
        public EventMetadata $initiatingMetaData,
        public SequenceNumber $originalSequenceNumber
    ) {
    }

    public function getClosestAffectedNodeAggregateId(): NodeAggregateId|null
    {
        return match ($this->originalCommand::class) {
            CreateRootNodeAggregateWithNode::class => null,
            CreateNodeAggregateWithNodeAndSerializedProperties::class => $this->originalCommand->parentNodeAggregateId,
            DisableNodeAggregate::class,
            EnableNodeAggregate::class,
            SetSerializedNodeProperties::class,
            MoveNodeAggregate::class, // todo moving behaves wrong and cant be published
            RemoveNodeAggregate::class,
            ChangeNodeAggregateName::class,
            ChangeNodeAggregateType::class,
            CreateNodeVariant::class,
            TagSubtree::class,
            UntagSubtree::class,
            UpdateRootNodeAggregateDimensions::class,
            => $this->originalCommand->nodeAggregateId,
            CopyNodesRecursively::class => null,
            SetSerializedNodeReferences::class => $this->originalCommand->sourceNodeAggregateId,
            // for non node-aggregate-changes we return null, so they are kept as remainder:
            AddDimensionShineThrough::class,
            MoveDimensionSpacePoint::class => null,
            default => throw new \RuntimeException(sprintf('Command %s does not have matching strategy. Partial workspace rebase not possible.', $this->originalCommand::class), 1645393655)
        };
    }


    public static function extractFromEventEnvelope(EventEnvelope $eventEnvelope): self
    {
        $commandToRebaseClass = $eventEnvelope->event->metadata?->value['commandClass'] ?? null;
        $commandToRebasePayload = $eventEnvelope->event->metadata?->value['commandPayload'] ?? null;

        if ($commandToRebaseClass === null || $commandToRebasePayload === null || $eventEnvelope->event->metadata === null) {
            throw new \RuntimeException('Command cannot be extracted from metadata, missing commandClass or commandPayload.', 1729847804);
        }

        if (!in_array(RebasableToOtherWorkspaceInterface::class, class_implements($commandToRebaseClass) ?: [], true)) {
            throw new \RuntimeException(sprintf(
                'Command "%s" can\'t be rebased because it does not implement %s',
                $commandToRebaseClass,
                RebasableToOtherWorkspaceInterface::class
            ), 1547815341);
        }
        /** @var class-string<RebasableToOtherWorkspaceInterface> $commandToRebaseClass */
        /** @var RebasableToOtherWorkspaceInterface $commandInstance */
        $commandInstance = $commandToRebaseClass::fromArray($commandToRebasePayload);
        return new self(
            $commandInstance,
            $eventEnvelope->event,
            InitiatingEventMetadata::extractInitiatingMetadata($eventEnvelope->event->metadata),
            $eventEnvelope->sequenceNumber
        );
    }

    /**
     * Stores the command in the event's metadata for events on a content stream. This is an important prerequisite
     * for the rebase functionality-
     */
    public static function enrichWithCommand(
        RebasableToOtherWorkspaceInterface $command,
        Events $events,
    ): Events {
        $processedEvents = [];
        $causationId = null;
        $i = 0;
        foreach ($events->items as $event) {
            if ($event instanceof DecoratedEvent) {
                $undecoratedEvent = $event->innerEvent;
                if (!$undecoratedEvent instanceof PublishableToWorkspaceInterface) {
                    throw new \RuntimeException(sprintf(
                        'TODO: Event %s has to implement PublishableToOtherContentStreamsInterface',
                        get_class($event)
                    ));
                }
            } elseif (!$event instanceof PublishableToWorkspaceInterface) {
                throw new \RuntimeException(sprintf(
                    'TODO: Event %s has to implement PublishableToOtherContentStreamsInterface',
                    get_class($event)
                ));
            }

            if ($i === 0) {
                if (!$command instanceof \JsonSerializable) {
                    throw new \RuntimeException(sprintf(
                        'Command %s must be JSON Serializable to be rebase able.',
                        get_class($command)
                    ));
                }
                $commandPayload = $command->jsonSerialize();

                if (!isset($commandPayload['contentStreamId']) && !isset($commandPayload['workspaceName'])) {
                    throw new \RuntimeException(sprintf(
                        'TODO: Command %s does not have a property "contentStreamId" or "workspaceName" (which is required).',
                        get_class($command)
                    ));
                }
                $metadata = EventMetadata::fromArray([
                    'commandClass' => get_class($command),
                    'commandPayload' => $commandPayload
                ]);
                $event = DecoratedEvent::create($event, eventId: EventId::create(), metadata: $metadata);
                // we remember the 1st event's identifier as causation identifier for all the others
                $causationId = $event->eventId;
            } elseif ($causationId !== null) {
                // event 2,3,4,...n get a causation identifier set, as they all originate from the 1st event.
                $event = DecoratedEvent::create($event, causationId: $causationId);
            }
            $processedEvents[] = $event;
            $i++;
        }

        return Events::fromArray($processedEvents);
    }
}
