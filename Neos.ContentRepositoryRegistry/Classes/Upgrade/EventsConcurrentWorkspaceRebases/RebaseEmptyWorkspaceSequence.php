<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsConcurrentWorkspaceRebases;

use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

final readonly class RebaseEmptyWorkspaceSequence
{
    /**
     * @param array<string,EventEnvelope> $eventsByType
     */
    public function __construct(
        private array $eventsByType,
        public CorrelationId $correlationId,
        public WorkspaceName $workspaceName,
        public ContentStreamId $previousContentStreamId,
        public ContentStreamId $newContentStreamId,
    ) {
    }

    /**
     * @param array<EventEnvelope> $events
     */
    public static function fromEvents(array $events): self
    {
        $eventsByType = [];
        $workspaceName = null;
        $previousContentStreamId = null;
        $newContentStreamId = null;
        $correlationId = RebaseWorkspaceCorrelationId::fromEvents($events);
        $sequenceType = RebaseEmptyWorkspaceSequenceType::start();
        foreach ($events as $rebaseWorkspaceEvent) {
            if ($sequenceType === null) {
                throw new \RuntimeException(sprintf('Expected end of rebase workspace sequence %s. Got at %d type %s with %s', $correlationId->value, $rebaseWorkspaceEvent->sequenceNumber->value, $rebaseWorkspaceEvent->event->type->value, $rebaseWorkspaceEvent->event->correlationId?->value));
            }

            if ($rebaseWorkspaceEvent->event->type->value !== $sequenceType->value) {
                throw new \RuntimeException(sprintf('Stream %s: Invalid rebase workspace sequence %s, expected %s got %s at %s', $rebaseWorkspaceEvent->streamName->value, $correlationId->value, $sequenceType->value, $rebaseWorkspaceEvent->event->type->value, $rebaseWorkspaceEvent->sequenceNumber->value));
            }

            if ($sequenceType === RebaseEmptyWorkspaceSequenceType::WorkspaceWasRebased) {
                $workspaceName = WorkspaceName::fromString(
                    json_decode($rebaseWorkspaceEvent->event->data->value, true, flags: JSON_THROW_ON_ERROR)['workspaceName']
                );
            }

            if ($sequenceType === RebaseEmptyWorkspaceSequenceType::ContentStreamWasForked) {
                $newContentStreamId = ContentStreamId::fromString(
                    json_decode($rebaseWorkspaceEvent->event->data->value, true, flags: JSON_THROW_ON_ERROR)['newContentStreamId']
                );
                if (!$rebaseWorkspaceEvent->streamName->equals(ContentStreamEventStreamName::fromContentStreamId($newContentStreamId)->getEventStreamName())) {
                    // sanity check, if events were already modified
                    throw new \RuntimeException(sprintf('Illegal ContentStreamWasForked event on stream %s: Expected %s at %s', $rebaseWorkspaceEvent->streamName->value, $newContentStreamId->value, $rebaseWorkspaceEvent->sequenceNumber->value));
                }
            }

            if ($sequenceType === RebaseEmptyWorkspaceSequenceType::ContentStreamWasClosed) {
                $previousContentStreamId = ContentStreamId::fromString(
                    json_decode($rebaseWorkspaceEvent->event->data->value, true, flags: JSON_THROW_ON_ERROR)['contentStreamId']
                );
                if (!$rebaseWorkspaceEvent->streamName->equals(ContentStreamEventStreamName::fromContentStreamId($previousContentStreamId)->getEventStreamName())) {
                    // sanity check, if events were already modified
                    throw new \RuntimeException(sprintf('Illegal ContentStreamWasClosed event on stream %s: Expected %s at %s', $rebaseWorkspaceEvent->streamName->value, $previousContentStreamId->value, $rebaseWorkspaceEvent->sequenceNumber->value));
                }
            }

            if ($sequenceType === RebaseEmptyWorkspaceSequenceType::ContentStreamWasRemoved) {
                // sanity check, if events were already modified
                if (!$previousContentStreamId || !$rebaseWorkspaceEvent->streamName->equals(ContentStreamEventStreamName::fromContentStreamId($previousContentStreamId)->getEventStreamName())) {
                    throw new \RuntimeException(sprintf('Illegal ContentStreamWasClosed event on stream %s: Expected %s at %s', $rebaseWorkspaceEvent->streamName->value, $previousContentStreamId?->value, $rebaseWorkspaceEvent->sequenceNumber->value));
                }
            }

            $eventsByType[$sequenceType->value] = $rebaseWorkspaceEvent;

            $sequenceType = $sequenceType->next();
        }
        if ($sequenceType !== null || $workspaceName === null || $newContentStreamId === null || $previousContentStreamId === null) {
            throw new \RuntimeException(sprintf('Invalid end of rebase workspace sequence %s expected %s', $correlationId->value, $sequenceType?->value));
        }

        return new self(
            eventsByType: $eventsByType,
            correlationId: $correlationId,
            workspaceName: $workspaceName,
            previousContentStreamId: $previousContentStreamId,
            newContentStreamId: $newContentStreamId,
        );
    }

    public function get(RebaseEmptyWorkspaceSequenceType $type): EventEnvelope
    {
        return $this->eventsByType[$type->value] ?? throw new \RuntimeException(sprintf('Fatal cannot happen: %s', $type->value), 1784970866);
    }

    /**
     * @return array<SequenceNumber>
     */
    public function getSequenceNumbers(): array
    {
        $sequenceNumbers = [];
        foreach ($this->eventsByType as $event) {
            $sequenceNumbers[] = $event->sequenceNumber;
        }
        return $sequenceNumbers;
    }
}
