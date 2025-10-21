<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceEventStreamName;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Service\ContentStreamPruner\ContentStreamForPruning;
use Neos\ContentRepository\Core\Service\ContentStreamPruner\ContentStreamStatus;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\CorrelationId;
use Neos\EventStore\Model\Event\EventType;
use Neos\EventStore\Model\Event\EventTypes;
use Neos\EventStore\Model\EventStream\EventStreamFilter;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\EventStore\Model\EventStream\VirtualStreamName;

/**
 * For implementation details of the content stream states and removed state, see {@see ContentStreamForPruning}.
 *
 * @api
 */
class ContentStreamPruner implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly EventStoreInterface $eventStore,
        private readonly EventNormalizer $eventNormalizer,
        private readonly SubscriptionEngine $subscriptionEngine,
    ) {
    }

    /**
     * Detects if dangling content streams exists and which content streams could be pruned from the event stream
     *
     * Dangling content streams
     * ------------------------
     *
     * Content streams that are not removed via the event ContentStreamWasRemoved and are not in use by a workspace
     * (not a current's workspace content stream).
     *
     * Previously before Neos 9 beta 15 (#5301), dangling content streams were not removed during publishing, discard or rebase.
     *
     * {@see removeDanglingContentStreams}
     *
     * Pruneable content streams
     * -------------------------
     *
     * Content streams that were removed ContentStreamWasRemoved e.g. after publishing, and are not required for a full
     * replay to reconstruct the current projections state. The ability to reconstitute a previous state will be lost.
     *
     * {@see pruneRemovedFromEventStream}
     *
     * @return bool false if dangling content streams exist because they should not
     */
    public function outputStatus(\Closure $outputFn): bool
    {
        $allContentStreams = $this->findAllContentStreams();

        $danglingContentStreamPresent = false;
        foreach ($allContentStreams as $contentStream) {
            if (!$contentStream->isDangling()) {
                continue;
            }
            if ($danglingContentStreamPresent === false) {
                $outputFn(sprintf('Dangling content streams that are not removed (ContentStreamWasRemoved) and not %s:', ContentStreamStatus::IN_USE_BY_WORKSPACE->value));
            }

            if ($contentStream->status->isTemporary()) {
                $outputFn(sprintf('  id: %s temporary %s at %s', $contentStream->id->value, $contentStream->status->value, $contentStream->created->format('Y-m-d H:i')));
            } else {
                $outputFn(sprintf('  id: %s %s', $contentStream->id->value, $contentStream->status->value));
            }

            $danglingContentStreamPresent = true;
        }

        if ($danglingContentStreamPresent === true) {
            $outputFn('To remove the dangling streams from the projections please run ./flow contentStream:removeDangling');
            $outputFn('Then they are ready for removal from the event stream');
            $outputFn();
        } else {
            $outputFn('Okay. No dangling streams found');
            $outputFn();
        }

        $pruneableContentStreams = $this->findRemovedContentStreamsThatAreUnused($allContentStreams);

        $pruneableContentStreamPresent = false;
        foreach ($pruneableContentStreams as $pruneableContentStream) {
            if ($pruneableContentStreamPresent === false) {
                $outputFn('Removed content streams that can be pruned from the event stream');
            }
            $pruneableContentStreamPresent = true;
            $outputFn(sprintf('  id: %s previous state: %s', $pruneableContentStream->id->value, $pruneableContentStream->status->value));
        }

        if ($pruneableContentStreamPresent === true) {
            $outputFn('To prune the removed streams from the event stream run ./flow contentStream:pruneRemovedFromEventstream');
        } else {
            $outputFn('Okay. No pruneable streams in the event stream');
        }

        return !$danglingContentStreamPresent;
    }

    /**
     * Removes all nodes, hierarchy relations and content stream entries which are not needed anymore from the projections.
     *
     * NOTE: This still **keeps** the event stream as is; so it would be possible to re-construct the content stream at a later point in time.
     *
     * To prune the removed content streams from the event stream, call {@see ContentStreamPruner::pruneRemovedFromEventStream()} afterwards.
     *
     * @param \DateTimeImmutable $removeTemporaryBefore includes all temporary content streams like FORKED or CREATED older than that in the removal
     */
    public function removeDanglingContentStreams(\Closure $outputFn, \DateTimeImmutable $removeTemporaryBefore): void
    {
        $allContentStreams = $this->findAllContentStreams();

        $correlationId = CorrelationId::fromString(sprintf('ContentStreamPruner_%s', bin2hex(random_bytes(9))));
        $danglingContentStreamsPresent = false;
        foreach ($allContentStreams as $contentStream) {
            if (!$contentStream->isDangling()) {
                continue;
            }
            if (
                $contentStream->status->isTemporary()
                && $removeTemporaryBefore < $contentStream->created
            ) {
                $outputFn(sprintf('Did not remove %s temporary %s at %s', $contentStream->id->value, $contentStream->status->value, $contentStream->created->format('Y-m-d H:i')));
                continue;
            }

            $this->eventStore->commit(
                ContentStreamEventStreamName::fromContentStreamId($contentStream->id)->getEventStreamName(),
                $this->eventNormalizer->normalize(
                    DecoratedEvent::create(
                        new ContentStreamWasRemoved(
                            $contentStream->id
                        ),
                        metadata: ['debug_reason' => sprintf('Removed dangling content stream with status %s', $contentStream->status->value)],
                        correlationId: $correlationId
                    )
                ),
                ExpectedVersion::STREAM_EXISTS()
            );

            $outputFn(sprintf('Removed %s with status %s', $contentStream->id, $contentStream->status->value));

            $danglingContentStreamsPresent = true;
        }

        if ($danglingContentStreamsPresent) {
            $result = $this->subscriptionEngine->catchUpActive();
            if ($result->hadErrors()) {
                $outputFn('Catchup after removing unused content streams led to errors. You might need to use ./flow contentstream:pruneremovedfromeventstream and replay.');
            }
        } else {
            $outputFn('Okay. No pruneable streams in the event stream');
        }
    }

    /**
     * Prune removed content streams that are unused from the event stream; effectively REMOVING information completely.
     *
     * Note that replaying to only a previous point in time would not be possible anymore as workspace would reference non-existing content streams.
     *
     * @see findRemovedContentStreamsThatAreUnused for implementation
     */
    public function pruneRemovedFromEventStream(\Closure $outputFn): void
    {
        $allContentStreams = $this->findAllContentStreams();

        $pruneableContentStreams = $this->findRemovedContentStreamsThatAreUnused($allContentStreams);

        $pruneableContentStreamsPresent = false;
        foreach ($pruneableContentStreams as $pruneableContentStream) {
            $this->eventStore->deleteStream(
                ContentStreamEventStreamName::fromContentStreamId(
                    $pruneableContentStream->id
                )->getEventStreamName()
            );
            $pruneableContentStreamsPresent = true;
            $outputFn(sprintf('Removed events for %s', $pruneableContentStream->id->value));
        }

        if ($pruneableContentStreamsPresent === false) {
            $outputFn('Okay. There are no pruneable content streams.');
        }
    }


    /**
     * Find all removed content streams that are unused in the event stream
     *
     * This is not so easy for nested workspaces / content streams:
     * - As long as content streams are used as basis for others which are IN_USE_BY_WORKSPACE,
     *   these dependent Content Streams are not allowed to be removed in the event stream.
     * - Otherwise, we cannot replay the other content streams correctly (if the base content streams are missing).
     *
     * @param array<string, ContentStreamForPruning> $allContentStreams
     * @return list<ContentStreamForPruning>
     */
    private function findRemovedContentStreamsThatAreUnused(array $allContentStreams): array
    {
        /** @var array<string,bool> $transitiveUsedStreams */
        $transitiveUsedStreams = [];
        /**
         * Collection of content streams we iterate through to build up all streams that are in use transitively (by being a source content stream) or because it is in use
         * @var list<ContentStreamId> $contentStreamIdsStack
         */
        $contentStreamIdsStack = [];

        // Step 1: Find all content streams currently in direct use by a workspace
        foreach ($allContentStreams as $stream) {
            if ($stream->status === ContentStreamStatus::IN_USE_BY_WORKSPACE && !$stream->removed) {
                $contentStreamIdsStack[] = $stream->id;
            }
        }

        // Step 2: When a content stream is in use by a workspace, its source content stream is also "transitively" in use.
        while ($contentStreamIdsStack !== []) {
            $currentStreamId = array_pop($contentStreamIdsStack);
            if (!array_key_exists($currentStreamId->value, $transitiveUsedStreams)) {
                $transitiveUsedStreams[$currentStreamId->value] = true;

                // Find source content streams for the current stream
                foreach ($allContentStreams as $stream) {
                    if ($stream->id === $currentStreamId && $stream->sourceContentStreamId !== null) {
                        $sourceStreamId = $stream->sourceContentStreamId;
                        if (!array_key_exists($sourceStreamId->value, $transitiveUsedStreams)) {
                            $contentStreamIdsStack[] = $sourceStreamId;
                        }
                    }
                }
            }
        }

        // Step 3: Check for removed content streams which we do not need anymore transitively
        $removedContentStreamsThatAreUnused = [];
        foreach ($allContentStreams as $contentStream) {
            if ($contentStream->removed && !array_key_exists($contentStream->id->value, $transitiveUsedStreams)) {
                $removedContentStreamsThatAreUnused[] = $contentStream;
            }
        }

        return $removedContentStreamsThatAreUnused;
    }

    /**
     * @return array<string, ContentStreamForPruning>
     */
    private function findAllContentStreams(): array
    {
        $events = $this->eventStore->load(
            VirtualStreamName::forCategory(ContentStreamEventStreamName::EVENT_STREAM_NAME_PREFIX),
            EventStreamFilter::create(
                EventTypes::create(
                    EventType::fromString('ContentStreamWasCreated'),
                    EventType::fromString('ContentStreamWasForked'),
                    EventType::fromString('ContentStreamWasRemoved'),
                )
            )
        );

        /** @var array<string,ContentStreamForPruning> $cs */
        $cs = [];
        foreach ($events as $eventEnvelope) {
            $domainEvent = $this->eventNormalizer->denormalize($eventEnvelope->event);

            switch ($domainEvent::class) {
                case ContentStreamWasCreated::class:
                    $cs[$domainEvent->contentStreamId->value] = ContentStreamForPruning::create(
                        $domainEvent->contentStreamId,
                        ContentStreamStatus::CREATED,
                        null,
                        $eventEnvelope->recordedAt
                    );
                    break;
                case ContentStreamWasForked::class:
                    $cs[$domainEvent->newContentStreamId->value] = ContentStreamForPruning::create(
                        $domainEvent->newContentStreamId,
                        ContentStreamStatus::FORKED,
                        $domainEvent->sourceContentStreamId,
                        $eventEnvelope->recordedAt
                    );
                    break;
                case ContentStreamWasRemoved::class:
                    if (isset($cs[$domainEvent->contentStreamId->value])) {
                        $cs[$domainEvent->contentStreamId->value] = $cs[$domainEvent->contentStreamId->value]
                            ->withRemoved();
                    }
                    break;
                default:
                    throw new \RuntimeException(sprintf('Unhandled event %s', $eventEnvelope->event->type->value));
            }
        }

        $workspaceEvents = $this->eventStore->load(
            VirtualStreamName::forCategory(WorkspaceEventStreamName::EVENT_STREAM_NAME_PREFIX),
            EventStreamFilter::create(
                EventTypes::create(
                    EventType::fromString('RootWorkspaceWasCreated'),
                    EventType::fromString('WorkspaceWasCreated'),
                    EventType::fromString('WorkspaceWasDiscarded'),
                    // we must include these two legacy events in the query for they exist and will be upcasted at runtime
                    EventType::fromString('WorkspaceWasPartiallyDiscarded'),
                    EventType::fromString('WorkspaceWasPartiallyPublished'),
                    EventType::fromString('WorkspaceWasPublished'),
                    EventType::fromString('WorkspaceWasRebased'),
                    EventType::fromString('WorkspaceRebaseFailed'),
                    // we don't need to track WorkspaceWasRemoved as a ContentStreamWasRemoved event would be emitted before
                )
            )
        );
        foreach ($workspaceEvents as $eventEnvelope) {
            $domainEvent = $this->eventNormalizer->denormalize($eventEnvelope->event);

            switch ($domainEvent::class) {
                case RootWorkspaceWasCreated::class:
                    if (isset($cs[$domainEvent->newContentStreamId->value])) {
                        $cs[$domainEvent->newContentStreamId->value] = $cs[$domainEvent->newContentStreamId->value]
                                ->withStatus(ContentStreamStatus::IN_USE_BY_WORKSPACE);
                    }
                    break;
                case WorkspaceWasCreated::class:
                    if (isset($cs[$domainEvent->newContentStreamId->value])) {
                        $cs[$domainEvent->newContentStreamId->value] = $cs[$domainEvent->newContentStreamId->value]
                                ->withStatus(ContentStreamStatus::IN_USE_BY_WORKSPACE);
                    }
                    break;
                case WorkspaceWasDiscarded::class:
                    if (isset($cs[$domainEvent->newContentStreamId->value])) {
                        $cs[$domainEvent->newContentStreamId->value] = $cs[$domainEvent->newContentStreamId->value]
                            ->withStatus(ContentStreamStatus::IN_USE_BY_WORKSPACE);
                    }
                    if (isset($cs[$domainEvent->previousContentStreamId->value])) {
                        $cs[$domainEvent->previousContentStreamId->value] = $cs[$domainEvent->previousContentStreamId->value]
                            ->withStatus(ContentStreamStatus::NO_LONGER_IN_USE);
                    }
                    break;
                case WorkspaceWasPublished::class:
                    if (isset($cs[$domainEvent->newSourceContentStreamId->value])) {
                        $cs[$domainEvent->newSourceContentStreamId->value] = $cs[$domainEvent->newSourceContentStreamId->value]
                            ->withStatus(ContentStreamStatus::IN_USE_BY_WORKSPACE);
                    }
                    if (isset($cs[$domainEvent->previousSourceContentStreamId->value])) {
                        $cs[$domainEvent->previousSourceContentStreamId->value] = $cs[$domainEvent->previousSourceContentStreamId->value]
                            ->withStatus(ContentStreamStatus::NO_LONGER_IN_USE);
                    }
                    break;
                case WorkspaceWasRebased::class:
                    if (isset($cs[$domainEvent->newContentStreamId->value])) {
                        $cs[$domainEvent->newContentStreamId->value] = $cs[$domainEvent->newContentStreamId->value]
                            ->withStatus(ContentStreamStatus::IN_USE_BY_WORKSPACE);
                    }
                    if (isset($cs[$domainEvent->previousContentStreamId->value])) {
                        $cs[$domainEvent->previousContentStreamId->value] = $cs[$domainEvent->previousContentStreamId->value]
                            ->withStatus(ContentStreamStatus::NO_LONGER_IN_USE);
                    }
                    break;
                case WorkspaceRebaseFailed::class:
                    // legacy handling, as we previously kept failed candidateContentStreamId we make it behave like a ContentStreamWasRemoved event to clean up:
                    if (isset($cs[$domainEvent->candidateContentStreamId->value])) {
                        $cs[$domainEvent->candidateContentStreamId->value] = $cs[$domainEvent->candidateContentStreamId->value]
                            ->withRemoved();
                    }
                    break;
                default:
                    throw new \RuntimeException(sprintf('Unhandled event %s', $eventEnvelope->event->type->value));
            }
        }
        return $cs;
    }
}
