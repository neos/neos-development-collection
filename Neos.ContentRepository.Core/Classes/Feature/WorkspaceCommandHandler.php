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

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandHandlingDependencies;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandSimulatorFactory;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\EventStore\InitiatingEventMetadata;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\Common\WorkspaceConstraintChecks;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasReopened;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\ChangeBaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceBaseWorkspaceWasChanged;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Exception\BaseWorkspaceEqualsWorkspaceException;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Exception\CircularRelationBetweenWorkspacesException;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\ConflictingEvent;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception\PartialWorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamAlreadyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamIsClosed;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceContainsPublishableChanges;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceHasNoBaseWorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\Event\EventType;
use Neos\EventStore\Model\Event\EventTypes;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\EventStream\EventStreamFilter;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 */
final readonly class WorkspaceCommandHandler implements CommandHandlerInterface
{
    use ContentStreamHandling;
    use WorkspaceConstraintChecks;

    public function __construct(
        private CommandHandlingDependencies $commandHandlingDependencies,
        private CommandSimulatorFactory $commandSimulatorFactory,
        private EventStoreInterface $eventStore,
        private EventNormalizer $eventNormalizer,
    ) {
    }

    public function canHandle(CommandInterface|RebasableToOtherWorkspaceInterface $command): bool
    {
        return method_exists($this, 'handle' . (new \ReflectionClass($command))->getShortName());
    }

    /**
     * @return \Generator<int, EventsToPublish>
     */
    public function handle(CommandInterface|RebasableToOtherWorkspaceInterface $command): \Generator
    {
        /** @phpstan-ignore-next-line */
        return match ($command::class) {
            CreateWorkspace::class => $this->handleCreateWorkspace($command),
            CreateRootWorkspace::class => $this->handleCreateRootWorkspace($command),
            PublishWorkspace::class => $this->handlePublishWorkspace($command),
            RebaseWorkspace::class => $this->handleRebaseWorkspace($command),
            PublishIndividualNodesFromWorkspace::class => $this->handlePublishIndividualNodesFromWorkspace($command),
            DiscardIndividualNodesFromWorkspace::class => $this->handleDiscardIndividualNodesFromWorkspace($command),
            DiscardWorkspace::class => $this->handleDiscardWorkspace($command),
            DeleteWorkspace::class => $this->handleDeleteWorkspace($command),
            ChangeBaseWorkspace::class => $this->handleChangeBaseWorkspace($command),
        };
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     * @throws WorkspaceAlreadyExists
     */
    private function handleCreateWorkspace(
        CreateWorkspace $command,
    ): \Generator {
        $this->requireWorkspaceToNotExist($command->workspaceName);
        $baseWorkspace = $this->commandHandlingDependencies->findWorkspaceByName($command->baseWorkspaceName);
        if ($baseWorkspace === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf(
                'The workspace %s (base workspace of %s) does not exist',
                $command->baseWorkspaceName->value,
                $command->workspaceName->value
            ), 1513890708);
        }
        $sourceContentStreamVersion = $this->commandHandlingDependencies->getContentStreamVersion($baseWorkspace->currentContentStreamId);
        $this->requireContentStreamToNotBeClosed($baseWorkspace->currentContentStreamId);
        $this->requireContentStreamToNotExistYet($command->newContentStreamId);

        // When the workspace is created, we first have to fork the content stream
        yield $this->forkContentStream(
            $command->newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $sourceContentStreamVersion,
            sprintf('Create workspace %s with base %s', $command->workspaceName->value, $baseWorkspace->workspaceName->value)
        );

        $workspaceStreamName = WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName);
        $expectedWorkspaceStreamVersion = $this->requireWorkspaceStreamVersionForCreation($workspaceStreamName);
        try {
            yield new EventsToPublish(
                $workspaceStreamName->getEventStreamName(),
                Events::with(
                    new WorkspaceWasCreated(
                        $command->workspaceName,
                        $command->baseWorkspaceName,
                        $command->newContentStreamId,
                    )
                ),
                $expectedWorkspaceStreamVersion,
            );
        } catch (ConcurrencyException) {
            yield $this->removeContentStreamWithoutConstraintChecks($command->newContentStreamId);
        }
    }

    /**
     * @param CreateRootWorkspace $command
     * @throws WorkspaceAlreadyExists
     * @throws ContentStreamAlreadyExists
     */
    private function handleCreateRootWorkspace(
        CreateRootWorkspace $command,
    ): \Generator {
        $this->requireWorkspaceToNotExist($command->workspaceName);
        $this->requireContentStreamToNotExistYet($command->newContentStreamId);

        yield new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($command->newContentStreamId)->getEventStreamName(),
            Events::with(
                new ContentStreamWasCreated(
                    $command->newContentStreamId,
                )
            ),
            ExpectedVersion::NO_STREAM()
        );

        $workspaceStreamName = WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName);
        $expectedWorkspaceStreamVersion = $this->requireWorkspaceStreamVersionForCreation($workspaceStreamName);
        try {
            yield new EventsToPublish(
                $workspaceStreamName->getEventStreamName(),
                Events::with(
                    new RootWorkspaceWasCreated(
                        $command->workspaceName,
                        $command->newContentStreamId
                    )
                ),
                $expectedWorkspaceStreamVersion,
            );
        } catch (ConcurrencyException) {
            yield $this->removeContentStreamWithoutConstraintChecks($command->newContentStreamId);
        }
    }

    private function handlePublishWorkspace(
        PublishWorkspace $command,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $baseWorkspace = $this->requireBaseWorkspace($workspace);
        if (!$workspace->hasPublishableChanges()) {
            throw WorkspaceCommandSkipped::becauseWorkspaceToPublishIsEmpty($command->workspaceName);
        }
        $workspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($workspace);
        $baseWorkspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($baseWorkspace);

        $rebaseableCommands = RebaseableCommands::extractFromEventStream(
            $this->eventStore->load(
                ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)
                    ->getEventStreamName()
            )
        );

        yield $this->closeContentStream(
            $workspace->currentContentStreamId,
            $workspaceContentStreamVersion
        );

        $commandSimulator = $this->commandSimulatorFactory->createSimulatorForWorkspace($baseWorkspace->workspaceName);

        try {
            $commandSimulator->run(
                static function ($handle) use ($rebaseableCommands): void {
                    foreach ($rebaseableCommands as $rebaseableCommand) {
                        $handle($rebaseableCommand);
                    }
                }
            );
        } catch (\Throwable $unexpectedException) {
            yield $this->reopenContentStreamWithoutConstraintChecks(
                $workspace->currentContentStreamId,
                sprintf('unexpected error %d: %s', $unexpectedException->getCode(), $unexpectedException->getMessage())
            );
            throw $unexpectedException;
        }

        if ($commandSimulator->hasConflicts()) {
            $workspaceRebaseFailed = WorkspaceRebaseFailed::duringPublish($commandSimulator->getConflictingEvents());
            yield $this->reopenContentStreamWithoutConstraintChecks(
                $workspace->currentContentStreamId,
                sprintf('conflicts %d: %s', $workspaceRebaseFailed->getCode(), $workspaceRebaseFailed->getMessage())
            );
            throw $workspaceRebaseFailed;
        }

        $eventsOfWorkspaceToPublish = $this->getCopiedEventsOfEventStream(
            $baseWorkspace->workspaceName,
            $baseWorkspace->currentContentStreamId,
            $commandSimulator->eventStream(),
        );

        if ($eventsOfWorkspaceToPublish !== null) {
            try {
                yield new EventsToPublish(
                    ContentStreamEventStreamName::fromContentStreamId($baseWorkspace->currentContentStreamId)
                        ->getEventStreamName(),
                    $eventsOfWorkspaceToPublish,
                    ExpectedVersion::fromVersion($baseWorkspaceContentStreamVersion)
                );
            } catch (ConcurrencyException $concurrencyException) {
                yield $this->reopenContentStreamWithoutConstraintChecks(
                    $workspace->currentContentStreamId,
                    sprintf('concurrency %d: %s', $concurrencyException->getCode(), $concurrencyException->getMessage())
                );
                throw $concurrencyException;
            }
        }

        yield $this->forkContentStream(
            $command->newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            Version::fromInteger($baseWorkspaceContentStreamVersion->value + ($eventsOfWorkspaceToPublish?->count() ?? 0)),
            sprintf('Publish workspace %s and fork base %s', $workspace->workspaceName->value, $baseWorkspace->workspaceName->value)
        );

        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($workspace->workspaceName)->getEventStreamName(),
            Events::with(
                new WorkspaceWasPublished(
                    $workspace->workspaceName,
                    $baseWorkspace->workspaceName,
                    $command->newContentStreamId,
                    $workspace->currentContentStreamId,
                    partial: false
                )
            ),
            ExpectedVersion::fromVersion($workspace->version),
        );

        yield $this->removeContentStreamWithoutConstraintChecks($workspace->currentContentStreamId);
    }

    private function rebaseWorkspaceWithoutChanges(
        Workspace $workspace,
        Workspace $baseWorkspace,
        Version $baseWorkspaceContentStreamVersion,
        ContentStreamId $newContentStreamId
    ): \Generator {
        yield $this->forkContentStream(
            $newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $baseWorkspaceContentStreamVersion,
            sprintf('Rebase empty workspace %s and fork base %s', $workspace->workspaceName->value, $baseWorkspace->workspaceName->value)
        );

        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($workspace->workspaceName)->getEventStreamName(),
            Events::with(
                new WorkspaceWasRebased(
                    $workspace->workspaceName,
                    $newContentStreamId,
                    $workspace->currentContentStreamId,
                    skippedEvents: []
                ),
            ),
            ExpectedVersion::fromVersion($workspace->version),
        );

        yield $this->removeContentStreamWithoutConstraintChecks($workspace->currentContentStreamId);
    }

    /**
     * Copy all events from the passed event stream which implement the {@see PublishableToWorkspaceInterface}
     */
    private function getCopiedEventsOfEventStream(
        WorkspaceName $targetWorkspaceName,
        ContentStreamId $targetContentStreamId,
        EventStreamInterface $eventStream
    ): Events|null {
        $events = [];
        foreach ($eventStream as $eventEnvelope) {
            $event = $this->eventNormalizer->denormalize($eventEnvelope->event);

            if ($event instanceof PublishableToWorkspaceInterface) {
                $copiedEvent = $event->withWorkspaceNameAndContentStreamId($targetWorkspaceName, $targetContentStreamId);
                // We need to add the event metadata here for rebasing in nested workspace situations
                // (and for exporting)
                $events[] = DecoratedEvent::create($copiedEvent, metadata: $eventEnvelope->event->metadata, causationId: $eventEnvelope->event->causationId, correlationId: $eventEnvelope->event->correlationId);
            }
        }

        // this could technically empty, but we handle it as a no-op
        return $events !== [] ? Events::fromArray($events) : null;
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws WorkspaceRebaseFailed
     */
    private function handleRebaseWorkspace(
        RebaseWorkspace $command,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        $workspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($workspace);
        $baseWorkspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($baseWorkspace);

        if (
            $workspace->status === WorkspaceStatus::UP_TO_DATE
            && $command->rebaseErrorHandlingStrategy !== RebaseErrorHandlingStrategy::STRATEGY_FORCE
        ) {
            // skipped rebase, when not forcing it
            throw WorkspaceCommandSkipped::becauseWorkspaceToRebaseIsNotOutdated($command->workspaceName);
        }

        if (!$workspace->hasPublishableChanges()) {
            // if we have no changes in the workspace we can fork from the base directly
            yield $this->closeContentStream(
                $workspace->currentContentStreamId,
                $workspaceContentStreamVersion
            );

            yield from $this->rebaseWorkspaceWithoutChanges(
                $workspace,
                $baseWorkspace,
                $baseWorkspaceContentStreamVersion,
                $command->rebasedContentStreamId
            );
            return;
        }

        $rebaseableCommands = RebaseableCommands::extractFromEventStream(
            $this->eventStore->load(
                ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)
                    ->getEventStreamName()
            )
        );

        yield $this->closeContentStream(
            $workspace->currentContentStreamId,
            $workspaceContentStreamVersion
        );

        $commandSimulator = $this->commandSimulatorFactory->createSimulatorForWorkspace($baseWorkspace->workspaceName);

        try {
            $commandSimulator->run(
                static function ($handle) use ($rebaseableCommands): void {
                    foreach ($rebaseableCommands as $rebaseableCommand) {
                        $handle($rebaseableCommand);
                    }
                }
            );
        } catch (\Throwable $unexpectedException) {
            yield $this->reopenContentStreamWithoutConstraintChecks(
                $workspace->currentContentStreamId,
                sprintf('unexpected error %d: %s', $unexpectedException->getCode(), $unexpectedException->getMessage())
            );
            throw $unexpectedException;
        }

        if (
            $command->rebaseErrorHandlingStrategy === RebaseErrorHandlingStrategy::STRATEGY_FAIL
            && $commandSimulator->hasConflicts()
        ) {
            // throw an exception that contains all the information about what exactly failed
            $workspaceRebaseFailed = WorkspaceRebaseFailed::duringRebase($commandSimulator->getConflictingEvents());
            yield $this->reopenContentStreamWithoutConstraintChecks(
                $workspace->currentContentStreamId,
                sprintf('conflicts %d: %s', $workspaceRebaseFailed->getCode(), $workspaceRebaseFailed->getMessage())
            );
            throw $workspaceRebaseFailed;
        }

        // if we got so far without an exception (or if we don't care), we can switch the workspace's active content stream.
        yield from $this->forkNewContentStreamAndApplyEvents(
            $command->rebasedContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $baseWorkspaceContentStreamVersion,
            new EventsToPublish(
                WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
                Events::with(
                    new WorkspaceWasRebased(
                        $command->workspaceName,
                        $command->rebasedContentStreamId,
                        $workspace->currentContentStreamId,
                        skippedEvents: $commandSimulator->getConflictingEvents()
                            ->map(fn (ConflictingEvent $conflictingEvent) => $conflictingEvent->getSequenceNumber())
                    ),
                ),
                ExpectedVersion::fromVersion($workspace->version),
            ),
            $this->getCopiedEventsOfEventStream(
                $command->workspaceName,
                $command->rebasedContentStreamId,
                $commandSimulator->eventStream(),
            ),
            sprintf('Rebase %s and fork base %s', $command->workspaceName->value, $baseWorkspace->workspaceName->value)
        );

        yield $this->removeContentStreamWithoutConstraintChecks($workspace->currentContentStreamId);
    }

    /**
     * This method is like a combined Rebase and Publish!
     *
     * @return \Generator<int, EventsToPublish>
     */
    private function handlePublishIndividualNodesFromWorkspace(
        PublishIndividualNodesFromWorkspace $command,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        if (!$workspace->hasPublishableChanges()) {
            throw WorkspaceCommandSkipped::becauseWorkspaceToPublishIsEmpty($command->workspaceName);
        }

        $workspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($workspace);
        $baseWorkspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($baseWorkspace);

        $rebaseableCommands = RebaseableCommands::extractFromEventStream(
            $this->eventStore->load(
                ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)
                    ->getEventStreamName()
            )
        );

        [$matchingCommands, $remainingCommands] = $rebaseableCommands->separateMatchingAndRemainingCommands($command->nodesToPublish);

        if ($matchingCommands->isEmpty()) {
            throw WorkspaceCommandSkipped::becauseFilterDidNotMatch($command->workspaceName, $command->nodesToPublish);
        }

        yield $this->closeContentStream(
            $workspace->currentContentStreamId,
            $workspaceContentStreamVersion
        );

        $commandSimulator = $this->commandSimulatorFactory->createSimulatorForWorkspace($baseWorkspace->workspaceName);

        try {
            $highestSequenceNumberForMatching = $commandSimulator->run(
                static function ($handle) use ($commandSimulator, $matchingCommands, $remainingCommands): SequenceNumber {
                    foreach ($matchingCommands as $matchingCommand) {
                        $handle($matchingCommand);
                    }
                    $highestSequenceNumberForMatching = $commandSimulator->currentSequenceNumber();
                    foreach ($remainingCommands as $remainingCommand) {
                        $handle($remainingCommand);
                    }
                    return $highestSequenceNumberForMatching;
                }
            );
        } catch (\Throwable $unexpectedException) {
            yield $this->reopenContentStreamWithoutConstraintChecks(
                $workspace->currentContentStreamId,
                sprintf('unexpected error %d: %s', $unexpectedException->getCode(), $unexpectedException->getMessage())
            );
            throw $unexpectedException;
        }

        if ($commandSimulator->hasConflicts()) {
            $workspaceRebaseFailed = match ($workspace->status) {
                // If the workspace is up-to-date it must be a problem regarding that the order of events cannot be changed
                WorkspaceStatus::UP_TO_DATE =>
                    PartialWorkspaceRebaseFailed::duringPartialPublish($commandSimulator->getConflictingEvents()),
                // If the workspace is outdated we cannot know for sure but suspect that the conflict arose due to changes in the base workspace.
                WorkspaceStatus::OUTDATED =>
                    WorkspaceRebaseFailed::duringPublish($commandSimulator->getConflictingEvents())
            };
            yield $this->reopenContentStreamWithoutConstraintChecks(
                $workspace->currentContentStreamId,
                sprintf('conflicts %d: %s', $workspaceRebaseFailed->getCode(), $workspaceRebaseFailed->getMessage())
            );
            throw $workspaceRebaseFailed;
        }

        $selectedEventsOfWorkspaceToPublish = $this->getCopiedEventsOfEventStream(
            $baseWorkspace->workspaceName,
            $baseWorkspace->currentContentStreamId,
            $commandSimulator->eventStream()->withMaximumSequenceNumber($highestSequenceNumberForMatching),
        );

        if ($selectedEventsOfWorkspaceToPublish !== null) {
            try {
                yield new EventsToPublish(
                    ContentStreamEventStreamName::fromContentStreamId($baseWorkspace->currentContentStreamId)
                        ->getEventStreamName(),
                    $selectedEventsOfWorkspaceToPublish,
                    ExpectedVersion::fromVersion($baseWorkspaceContentStreamVersion)
                );
            } catch (ConcurrencyException $concurrencyException) {
                yield $this->reopenContentStreamWithoutConstraintChecks(
                    $workspace->currentContentStreamId,
                    sprintf('concurrency %d: %s', $concurrencyException->getCode(), $concurrencyException->getMessage())
                );
                throw $concurrencyException;
            }
        }

        yield from $this->forkNewContentStreamAndApplyEvents(
            $command->contentStreamIdForRemainingPart,
            $baseWorkspace->currentContentStreamId,
            Version::fromInteger($baseWorkspaceContentStreamVersion->value + ($selectedEventsOfWorkspaceToPublish?->count() ?? 0)),
            new EventsToPublish(
                WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
                Events::fromArray([
                    new WorkspaceWasPublished(
                        $command->workspaceName,
                        $baseWorkspace->workspaceName,
                        $command->contentStreamIdForRemainingPart,
                        $workspace->currentContentStreamId,
                        partial: !$remainingCommands->isEmpty()
                    )
                ]),
                ExpectedVersion::fromVersion($workspace->version),
            ),
            $this->getCopiedEventsOfEventStream(
                $command->workspaceName,
                $command->contentStreamIdForRemainingPart,
                $commandSimulator->eventStream()->withMinimumSequenceNumber($highestSequenceNumberForMatching->next())
            ),
            sprintf('Partial publish workspace %s and fork base %s', $command->workspaceName->value, $baseWorkspace->workspaceName->value)
        );

        yield $this->removeContentStreamWithoutConstraintChecks($workspace->currentContentStreamId);
    }

    /**
     * This method is like a Rebase while dropping some modifications!
     *
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws WorkspaceHasNoBaseWorkspaceName
     * @throws \Neos\ContentRepository\Core\SharedModel\Exception\NodeConstraintException
     * @throws \Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    private function handleDiscardIndividualNodesFromWorkspace(
        DiscardIndividualNodesFromWorkspace $command,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        if (!$workspace->hasPublishableChanges()) {
            throw WorkspaceCommandSkipped::becauseWorkspaceToDiscardIsEmpty($command->workspaceName);
        }

        $workspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($workspace);
        $baseWorkspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($baseWorkspace);

        $rebaseableEvents = $this->extractRebaseableEventsFromEventStream(
            $this->eventStore->load(
                ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)
                    ->getEventStreamName()
            )
        );

        // filter events, only keeping the ones NOT MATCHING the nodes from the event (i.e. the modifications we want to keep)
        [$eventsToDiscard, $eventsToKeep] = $rebaseableEvents->separateMatchingAndRemainingEvents($command->nodesToDiscard);

        if ($eventsToDiscard->isEmpty()) {
            throw WorkspaceCommandSkipped::becauseFilterDidNotMatch($command->workspaceName, $command->nodesToDiscard);
        }

        yield $this->closeContentStream(
            $workspace->currentContentStreamId,
            $workspaceContentStreamVersion
        );

        if ($eventsToKeep->isEmpty()) {
            // quick path everything was discarded
            yield from $this->discardWorkspace(
                $workspace,
                $baseWorkspace,
                $baseWorkspaceContentStreamVersion,
                $command->newContentStreamId
            );
            return;
        }

        // TODO reimplement
        // if ($commandSimulator->hasConflicts()) {
        //     $workspaceRebaseFailed = match ($workspace->status) {
        //         // If the workspace is up-to-date it must be a problem regarding that the order of events cannot be changed
        //         WorkspaceStatus::UP_TO_DATE =>
        //             PartialWorkspaceRebaseFailed::duringPartialDiscard($commandSimulator->getConflictingEvents()),
        //         // If the workspace is outdated we cannot know for sure but suspect that the conflict arose due to changes in the base workspace.
        //         WorkspaceStatus::OUTDATED =>
        //             WorkspaceRebaseFailed::duringDiscard($commandSimulator->getConflictingEvents())
        //     };
        //     yield $this->reopenContentStreamWithoutConstraintChecks(
        //         $workspace->currentContentStreamId,
        //         sprintf('conflicts %d: %s', $workspaceRebaseFailed->getCode(), $workspaceRebaseFailed->getMessage())
        //     );
        //     throw $workspaceRebaseFailed;
        // }

        yield from $this->forkNewContentStreamAndApplyEvents(
            $command->newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $baseWorkspaceContentStreamVersion,
            new EventsToPublish(
                WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
                Events::with(
                    new WorkspaceWasDiscarded(
                        $command->workspaceName,
                        $command->newContentStreamId,
                        $workspace->currentContentStreamId,
                        partial: true
                    )
                ),
                ExpectedVersion::fromVersion($workspace->version),
            ),
            $this->getCopyOfRebaseableEventsForTargetWorkspace(
                $command->workspaceName,
                $command->newContentStreamId,
                $eventsToKeep
            ),
            sprintf('Partial discard workspace %s and fork base %s', $command->workspaceName->value, $baseWorkspace->workspaceName->value)
        );

        yield $this->removeContentStreamWithoutConstraintChecks($workspace->currentContentStreamId);
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws WorkspaceHasNoBaseWorkspaceName
     */
    private function handleDiscardWorkspace(
        DiscardWorkspace $command,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        if (!$workspace->hasPublishableChanges()) {
            throw WorkspaceCommandSkipped::becauseWorkspaceToDiscardIsEmpty($command->workspaceName);
        }

        $this->requireContentStreamToNotBeClosed($workspace->currentContentStreamId);
        $baseWorkspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($baseWorkspace);

        yield from $this->discardWorkspace(
            $workspace,
            $baseWorkspace,
            $baseWorkspaceContentStreamVersion,
            $command->newContentStreamId
        );
    }

    /**
     * @phpstan-pure this method is pure, to persist the events they must be handled outside
     */
    private function discardWorkspace(
        Workspace $workspace,
        Workspace $baseWorkspace,
        Version $baseWorkspaceContentStreamVersion,
        ContentStreamId $newContentStream
    ): \Generator {
        yield $this->forkContentStream(
            $newContentStream,
            $baseWorkspace->currentContentStreamId,
            $baseWorkspaceContentStreamVersion,
            sprintf('Discard %s and fork base %s', $workspace->workspaceName->value, $baseWorkspace->workspaceName->value)
        );

        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($workspace->workspaceName)->getEventStreamName(),
            Events::with(
                new WorkspaceWasDiscarded(
                    $workspace->workspaceName,
                    $newContentStream,
                    $workspace->currentContentStreamId,
                    partial: false
                )
            ),
            ExpectedVersion::fromVersion($workspace->version),
        );

        yield $this->removeContentStreamWithoutConstraintChecks($workspace->currentContentStreamId);
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws WorkspaceHasNoBaseWorkspaceName
     * @throws WorkspaceContainsPublishableChanges
     * @throws BaseWorkspaceEqualsWorkspaceException
     * @throws CircularRelationBetweenWorkspacesException
     */
    private function handleChangeBaseWorkspace(
        ChangeBaseWorkspace $command,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $currentBaseWorkspace = $this->requireBaseWorkspace($workspace);

        $this->requireContentStreamToNotBeClosed($workspace->currentContentStreamId);

        if ($currentBaseWorkspace->workspaceName->equals($command->baseWorkspaceName)) {
            throw WorkspaceCommandSkipped::becauseTheBaseWorkspaceIsUnchanged($command->baseWorkspaceName, $command->workspaceName);
        }

        if ($workspace->hasPublishableChanges()) {
            throw WorkspaceContainsPublishableChanges::butWasNotSupposedToForBaseWorkspaceChange($workspace->workspaceName);
        }

        $newBaseWorkspace = $this->requireWorkspace($command->baseWorkspaceName);
        $this->requireNonCircularRelationBetweenWorkspaces($workspace, $newBaseWorkspace);

        $newBaseWorkspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($newBaseWorkspace);

        yield $this->forkContentStream(
            $command->newContentStreamId,
            $newBaseWorkspace->currentContentStreamId,
            $newBaseWorkspaceContentStreamVersion,
            sprintf('Change base workspace of %s to %s', $workspace->workspaceName->value, $newBaseWorkspace->workspaceName->value)
        );

        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
            Events::with(
                new WorkspaceBaseWorkspaceWasChanged(
                    $command->workspaceName,
                    $command->baseWorkspaceName,
                    $command->newContentStreamId,
                )
            ),
            ExpectedVersion::fromVersion($workspace->version),
        );

        yield $this->removeContentStreamWithoutConstraintChecks($workspace->currentContentStreamId);
    }

    /**
     * @throws WorkspaceDoesNotExist
     */
    private function handleDeleteWorkspace(
        DeleteWorkspace $command,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $contentStreamVersion = $this->commandHandlingDependencies->getContentStreamVersion($workspace->currentContentStreamId);

        yield new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)->getEventStreamName(),
            Events::with(
                new ContentStreamWasRemoved(
                    $workspace->currentContentStreamId,
                ),
            ),
            ExpectedVersion::fromVersion($contentStreamVersion)
        );

        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
            Events::with(
                new WorkspaceWasRemoved(
                    $command->workspaceName,
                )
            ),
            ExpectedVersion::fromVersion($workspace->version),
        );
    }

    private function forkNewContentStreamAndApplyEvents(
        ContentStreamId $newContentStreamId,
        ContentStreamId $sourceContentStreamId,
        Version $sourceContentStreamVersion,
        EventsToPublish $pointWorkspaceToNewContentStream,
        Events|null $eventsToApplyOnNewContentStream,
        string $debugReasonForFork
    ): \Generator {
        yield $this->forkContentStream(
            $newContentStreamId,
            $sourceContentStreamId,
            $sourceContentStreamVersion,
            $debugReasonForFork . sprintf('; Apply %d events on new (temporary closed) content stream', $eventsToApplyOnNewContentStream?->count() ?? 0)
        )->withAppendedEvents(Events::with(
            new ContentStreamWasClosed(
                $newContentStreamId
            )
        ));

        yield $pointWorkspaceToNewContentStream;

        yield new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($newContentStreamId)
                ->getEventStreamName(),
            Events::fromArray([
                ...($eventsToApplyOnNewContentStream ?? []),
                new ContentStreamWasReopened(
                    $newContentStreamId
                )
            ]),
            ExpectedVersion::fromVersion(Version::first()->next())
        );
    }

    private function requireWorkspaceToNotExist(WorkspaceName $workspaceName): void
    {
        if ($this->commandHandlingDependencies->findWorkspaceByName($workspaceName) === null) {
            return;
        }

        throw new WorkspaceAlreadyExists(sprintf(
            'The workspace %s already exists',
            $workspaceName->value
        ), 1715341085);
    }

    private function requireOpenContentStreamAndVersion(Workspace $workspace): Version
    {
        if ($this->commandHandlingDependencies->isContentStreamClosed($workspace->currentContentStreamId)) {
            throw new ContentStreamIsClosed(
                'Content stream "' . $workspace->currentContentStreamId . '" is closed.',
                1730730516
            );
        }
        return $this->commandHandlingDependencies->getContentStreamVersion($workspace->currentContentStreamId);
    }

    /**
     * @throws BaseWorkspaceEqualsWorkspaceException
     * @throws CircularRelationBetweenWorkspacesException
     */
    private function requireNonCircularRelationBetweenWorkspaces(Workspace $workspace, Workspace $baseWorkspace): void
    {
        if ($workspace->workspaceName->equals($baseWorkspace->workspaceName)) {
            throw new BaseWorkspaceEqualsWorkspaceException(sprintf('The base workspace of the target must be different from the given workspace "%s".', $workspace->workspaceName->value));
        }
        $nextBaseWorkspace = $baseWorkspace;
        while (!is_null($nextBaseWorkspace->baseWorkspaceName)) {
            if ($workspace->workspaceName->equals($nextBaseWorkspace->baseWorkspaceName)) {
                throw new CircularRelationBetweenWorkspacesException(sprintf('The workspace "%s" is already on the path of the target workspace "%s".', $workspace->workspaceName->value, $baseWorkspace->workspaceName->value));
            }
            $nextBaseWorkspace = $this->requireBaseWorkspace($nextBaseWorkspace);
        }
    }

    /**
     * The workspace stream version from the even-store. We cannot use the constant NO_STREAM() as we allow recreation of workspaces.
     */
    private function requireWorkspaceStreamVersionForCreation(WorkspaceEventStreamName $workspaceStreamName): ExpectedVersion
    {
        // If an event exists, the only valid last event is a removal, otherwise we are not allowed to recreate the workspace.
        $workspaceStream = $this->eventStore->load(
            $workspaceStreamName->getEventStreamName(),
            EventStreamFilter::create(eventTypes:EventTypes::create(EventType::fromString('WorkspaceWasRemoved')))
        );
        foreach ($workspaceStream->backwards()->limit(1) as $eventEnvelope) {
            return ExpectedVersion::fromVersion($eventEnvelope->version);
        }
        return ExpectedVersion::NO_STREAM();
    }

    private function extractRebaseableEventsFromEventStream(EventStreamInterface $eventStream): RebaseableEvents
    {
        $events = [];
        $causationEvent = null;

        foreach ($eventStream as $eventEnvelope) {
            $event = $this->eventNormalizer->denormalize($eventEnvelope->event);
            if ($event instanceof PublishableToWorkspaceInterface) {
                if ($eventEnvelope->event->metadata === null) {
                    throw new \RuntimeException('Event metadata is missing.', 1729847804);
                }

                $rebaseableEvent = new RebaseableEvent(
                    $event,
                    $eventEnvelope->event,
                    InitiatingEventMetadata::extractInitiatingMetadata($eventEnvelope->event->metadata),
                    $eventEnvelope->sequenceNumber,
                    new RebaseableEvents()
                );

                if ($causationEvent !== null) {
                    if ($eventEnvelope->event->causationId !== null) {
                        $causationEvent = $causationEvent->withCausedEvent($rebaseableEvent);
                        continue;
                    } else {
                        $events[] = $causationEvent;
                    }
                }

                if ($eventEnvelope->event->causationId !== null) {
                    $causationEvent = $rebaseableEvent;
                } else {
                    $events[] = $rebaseableEvent;
                }
            }
        }

        if ($causationEvent !== null) {
            $events[] = $causationEvent;
        }

        return new RebaseableEvents(...$events);
    }

    private function getCopyOfRebaseableEventsForTargetWorkspace(
        WorkspaceName $targetWorkspaceName,
        ContentStreamId $targetContentStreamId,
        RebaseableEvents $rebaseableEvents
    ): Events|null {
        $events = [];
        foreach ($rebaseableEvents as $rebaseableEvent) {
            $copiedEvent = $rebaseableEvent->event->withWorkspaceNameAndContentStreamId($targetWorkspaceName, $targetContentStreamId);
            // TODO is this correct? We need to add the event metadata here for rebasing in nested workspace situations
            // (and for exporting)
            // todo create new metadata, causation ids, and correlation ids!!!!

            // todo handle and test $rebaseableEvent->causedEvents with tethered nodes!!!
            $events[] = DecoratedEvent::create($copiedEvent, metadata: $rebaseableEvent->originalEvent->metadata, causationId: $rebaseableEvent->originalEvent->causationId, correlationId: $rebaseableEvent->originalEvent->correlationId);
        }

        // this could technically empty, but we handle it as a no-op
        return $events !== [] ? Events::fromArray($events) : null;
    }
}
