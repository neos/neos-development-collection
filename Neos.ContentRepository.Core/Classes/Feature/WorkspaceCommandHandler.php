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
use Neos\ContentRepository\Core\EventStore\EventsToPublishTemplate;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\Common\WorkspaceConstraintChecks;
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
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceContainsPublishableChanges;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceHasNoBaseWorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use Neos\EventStore\EventStoreInterface;
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

    public function handle(CommandInterface|RebasableToOtherWorkspaceInterface $command): EventsToPublish
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
    ): EventsToPublish {
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
        $this->requireContentStreamToNotExistYet($command->newContentStreamId);

        // When the workspace is created, we first have to fork the content stream
        $eventsToPublish = $this->forkContentStream(
            $command->newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $sourceContentStreamVersion,
            sprintf('Create workspace %s with base %s', $command->workspaceName->value, $baseWorkspace->workspaceName->value)
        );

        $workspaceStreamName = WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName);
        $expectedWorkspaceStreamVersion = $this->requireWorkspaceStreamVersionForCreation($workspaceStreamName);
        return $eventsToPublish->withEventsForStreamAndExpectedVersion(
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
    }

    /**
     * @param CreateRootWorkspace $command
     * @throws WorkspaceAlreadyExists
     * @throws ContentStreamAlreadyExists
     */
    private function handleCreateRootWorkspace(
        CreateRootWorkspace $command,
    ): EventsToPublish {
        $this->requireWorkspaceToNotExist($command->workspaceName);
        $this->requireContentStreamToNotExistYet($command->newContentStreamId);

        $eventsToPublish = EventsToPublish::createEventsForStreamAndExpectedVersion(
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
        return $eventsToPublish->withEventsForStreamAndExpectedVersion(
            $workspaceStreamName->getEventStreamName(),
            Events::with(
                new RootWorkspaceWasCreated(
                    $command->workspaceName,
                    $command->newContentStreamId
                )
            ),
            $expectedWorkspaceStreamVersion,
        );
    }

    private function handlePublishWorkspace(
        PublishWorkspace $command,
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $baseWorkspace = $this->requireBaseWorkspace($workspace);
        if (!$workspace->hasPublishableChanges()) {
            throw WorkspaceCommandSkipped::becauseWorkspaceToPublishIsEmpty($command->workspaceName);
        }
        $workspaceContentStreamVersion = $this->requireContentStreamVersion($workspace);
        $baseWorkspaceContentStreamVersion = $this->requireContentStreamVersion($baseWorkspace);

        $rebaseableCommands = RebaseableCommands::extractFromEventStream(
            $this->eventStore->load(
                ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)
                    ->getEventStreamName()
            )
        );

        $commandSimulator = $this->commandSimulatorFactory->createSimulatorForWorkspace($baseWorkspace->workspaceName);

        $commandSimulator->run(
            static function ($handle) use ($rebaseableCommands): void {
                foreach ($rebaseableCommands as $rebaseableCommand) {
                    $handle($rebaseableCommand);
                }
            }
        );

        if ($commandSimulator->hasConflicts()) {
            $workspaceRebaseFailed = WorkspaceRebaseFailed::duringPublish($commandSimulator->getConflictingEvents());
            throw $workspaceRebaseFailed;
        }

        $eventsOfWorkspaceToPublish = $this->getCopiedEventsOfEventStream(
            $baseWorkspace->workspaceName,
            $baseWorkspace->currentContentStreamId,
            $commandSimulator->eventStream(),
        );

        $eventsToPublish = EventsToPublishTemplate::create();
        if ($eventsOfWorkspaceToPublish !== null) {
            $eventsToPublish = EventsToPublish::createEventsForStreamAndExpectedVersion(
                ContentStreamEventStreamName::fromContentStreamId($baseWorkspace->currentContentStreamId)
                    ->getEventStreamName(),
                $eventsOfWorkspaceToPublish,
                ExpectedVersion::fromVersion($baseWorkspaceContentStreamVersion)
            );
        }

        $eventsToPublish = $eventsToPublish->merge(
            $this->forkContentStream(
                $command->newContentStreamId,
                $baseWorkspace->currentContentStreamId,
                Version::fromInteger($baseWorkspaceContentStreamVersion->value + ($eventsOfWorkspaceToPublish?->count() ?? 0)),
                sprintf('Publish workspace %s and fork base %s', $workspace->workspaceName->value, $baseWorkspace->workspaceName->value)
            )
        );

        $eventsToPublish = $eventsToPublish->withEventsForStreamAndExpectedVersion(
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

        return $eventsToPublish->merge(
            $this->removeContentStream($workspace->currentContentStreamId, $workspaceContentStreamVersion)
        );
    }

    private function rebaseWorkspaceWithoutChanges(
        Workspace $workspace,
        Workspace $baseWorkspace,
        Version $workspaceContentStreamVersion,
        Version $baseWorkspaceContentStreamVersion,
        ContentStreamId $newContentStreamId
    ): EventsToPublish {
        $eventsToPublish = $this->forkContentStream(
            $newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $baseWorkspaceContentStreamVersion,
            sprintf('Rebase empty workspace %s and fork base %s', $workspace->workspaceName->value, $baseWorkspace->workspaceName->value)
        );

        $eventsToPublish = $eventsToPublish->withEventsForStreamAndExpectedVersion(
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

        return $eventsToPublish->merge(
            $this->removeContentStream($workspace->currentContentStreamId, $workspaceContentStreamVersion)
        );
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
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        $workspaceContentStreamVersion = $this->requireContentStreamVersion($workspace);
        $baseWorkspaceContentStreamVersion = $this->requireContentStreamVersion($baseWorkspace);

        if (
            $workspace->status === WorkspaceStatus::UP_TO_DATE
            && $command->rebaseErrorHandlingStrategy !== RebaseErrorHandlingStrategy::STRATEGY_FORCE
        ) {
            // skipped rebase, when not forcing it
            throw WorkspaceCommandSkipped::becauseWorkspaceToRebaseIsNotOutdated($command->workspaceName);
        }

        if (!$workspace->hasPublishableChanges()) {
            // if we have no changes in the workspace we can fork from the base directly
            return $this->rebaseWorkspaceWithoutChanges(
                $workspace,
                $baseWorkspace,
                $workspaceContentStreamVersion,
                $baseWorkspaceContentStreamVersion,
                $command->rebasedContentStreamId
            );
        }

        $rebaseableCommands = RebaseableCommands::extractFromEventStream(
            $this->eventStore->load(
                ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)
                    ->getEventStreamName()
            )
        );

        $commandSimulator = $this->commandSimulatorFactory->createSimulatorForWorkspace($baseWorkspace->workspaceName);

        $commandSimulator->run(
            static function ($handle) use ($rebaseableCommands): void {
                foreach ($rebaseableCommands as $rebaseableCommand) {
                    $handle($rebaseableCommand);
                }
            }
        );

        if (
            $command->rebaseErrorHandlingStrategy === RebaseErrorHandlingStrategy::STRATEGY_FAIL
            && $commandSimulator->hasConflicts()
        ) {
            // throw an exception that contains all the information about what exactly failed
            $workspaceRebaseFailed = WorkspaceRebaseFailed::duringRebase($commandSimulator->getConflictingEvents());
            throw $workspaceRebaseFailed;
        }

        // if we got so far without an exception (or if we don't care), we can switch the workspace's active content stream.
        $eventsToPublish = $this->forkNewContentStreamAndApplyEvents(
            $command->rebasedContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $baseWorkspaceContentStreamVersion,
            EventsToPublish::createEventsForStreamAndExpectedVersion(
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

        return $eventsToPublish->merge(
            $this->removeContentStream($workspace->currentContentStreamId, $workspaceContentStreamVersion)
        );
    }

    /**
     * This method is like a combined Rebase and Publish!
     */
    private function handlePublishIndividualNodesFromWorkspace(
        PublishIndividualNodesFromWorkspace $command,
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        if (!$workspace->hasPublishableChanges()) {
            throw WorkspaceCommandSkipped::becauseWorkspaceToPublishIsEmpty($command->workspaceName);
        }

        $workspaceContentStreamVersion = $this->requireContentStreamVersion($workspace);
        $baseWorkspaceContentStreamVersion = $this->requireContentStreamVersion($baseWorkspace);

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

        $commandSimulator = $this->commandSimulatorFactory->createSimulatorForWorkspace($baseWorkspace->workspaceName);

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

        if ($commandSimulator->hasConflicts()) {
            $workspaceRebaseFailed = match ($workspace->status) {
                // If the workspace is up-to-date it must be a problem regarding that the order of events cannot be changed
                WorkspaceStatus::UP_TO_DATE =>
                    PartialWorkspaceRebaseFailed::duringPartialPublish($commandSimulator->getConflictingEvents()),
                // If the workspace is outdated we cannot know for sure but suspect that the conflict arose due to changes in the base workspace.
                WorkspaceStatus::OUTDATED =>
                    WorkspaceRebaseFailed::duringPublish($commandSimulator->getConflictingEvents())
            };
            throw $workspaceRebaseFailed;
        }

        $selectedEventsOfWorkspaceToPublish = $this->getCopiedEventsOfEventStream(
            $baseWorkspace->workspaceName,
            $baseWorkspace->currentContentStreamId,
            $commandSimulator->eventStream()->withMaximumSequenceNumber($highestSequenceNumberForMatching),
        );

        $eventsToPublish = EventsToPublishTemplate::create();
        if ($selectedEventsOfWorkspaceToPublish !== null) {
            $eventsToPublish = EventsToPublish::createEventsForStreamAndExpectedVersion(
                ContentStreamEventStreamName::fromContentStreamId($baseWorkspace->currentContentStreamId)
                    ->getEventStreamName(),
                $selectedEventsOfWorkspaceToPublish,
                ExpectedVersion::fromVersion($baseWorkspaceContentStreamVersion)
            );
        }

        $eventsToPublish = $eventsToPublish->merge(
            $this->forkNewContentStreamAndApplyEvents(
                $command->contentStreamIdForRemainingPart,
                $baseWorkspace->currentContentStreamId,
                Version::fromInteger($baseWorkspaceContentStreamVersion->value + ($selectedEventsOfWorkspaceToPublish?->count() ?? 0)),
                EventsToPublish::createEventsForStreamAndExpectedVersion(
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
            )
        );

        return $eventsToPublish->merge(
            $this->removeContentStream($workspace->currentContentStreamId, $workspaceContentStreamVersion)
        );
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
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        if (!$workspace->hasPublishableChanges()) {
            throw WorkspaceCommandSkipped::becauseWorkspaceToDiscardIsEmpty($command->workspaceName);
        }

        $workspaceContentStreamVersion = $this->requireContentStreamVersion($workspace);
        $baseWorkspaceContentStreamVersion = $this->requireContentStreamVersion($baseWorkspace);

        $rebaseableCommands = RebaseableCommands::extractFromEventStream(
            $this->eventStore->load(
                ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)
                    ->getEventStreamName()
            )
        );

        // filter commands, only keeping the ones NOT MATCHING the nodes from the command (i.e. the modifications we want to keep)
        [$commandsToDiscard, $commandsToKeep] = $rebaseableCommands->separateMatchingAndRemainingCommands($command->nodesToDiscard);

        if ($commandsToDiscard->isEmpty()) {
            throw WorkspaceCommandSkipped::becauseFilterDidNotMatch($command->workspaceName, $command->nodesToDiscard);
        }

        if ($commandsToKeep->isEmpty()) {
            // quick path everything was discarded
            return $this->discardWorkspace(
                $workspace,
                $baseWorkspace,
                $workspaceContentStreamVersion,
                $baseWorkspaceContentStreamVersion,
                $command->newContentStreamId
            );
        }

        $commandSimulator = $this->commandSimulatorFactory->createSimulatorForWorkspace($baseWorkspace->workspaceName);

        $commandSimulator->run(
            static function ($handle) use ($commandsToKeep): void {
                foreach ($commandsToKeep as $matchingCommand) {
                    $handle($matchingCommand);
                }
            }
        );

        if ($commandSimulator->hasConflicts()) {
            $workspaceRebaseFailed = match ($workspace->status) {
                // If the workspace is up-to-date it must be a problem regarding that the order of events cannot be changed
                WorkspaceStatus::UP_TO_DATE =>
                    PartialWorkspaceRebaseFailed::duringPartialDiscard($commandSimulator->getConflictingEvents()),
                // If the workspace is outdated we cannot know for sure but suspect that the conflict arose due to changes in the base workspace.
                WorkspaceStatus::OUTDATED =>
                    WorkspaceRebaseFailed::duringDiscard($commandSimulator->getConflictingEvents())
            };
            throw $workspaceRebaseFailed;
        }

        $eventsToPublish = $this->forkNewContentStreamAndApplyEvents(
            $command->newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $baseWorkspaceContentStreamVersion,
            EventsToPublish::createEventsForStreamAndExpectedVersion(
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
            $this->getCopiedEventsOfEventStream(
                $command->workspaceName,
                $command->newContentStreamId,
                $commandSimulator->eventStream(),
            ),
            sprintf('Partial discard workspace %s and fork base %s', $command->workspaceName->value, $baseWorkspace->workspaceName->value)
        );

        return $eventsToPublish->merge(
            $this->removeContentStream($workspace->currentContentStreamId, $workspaceContentStreamVersion)
        );
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws WorkspaceHasNoBaseWorkspaceName
     */
    private function handleDiscardWorkspace(
        DiscardWorkspace $command,
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        if (!$workspace->hasPublishableChanges()) {
            throw WorkspaceCommandSkipped::becauseWorkspaceToDiscardIsEmpty($command->workspaceName);
        }

        $workspaceContentStreamVersion = $this->requireContentStreamVersion($workspace);
        $baseWorkspaceContentStreamVersion = $this->requireContentStreamVersion($baseWorkspace);

        return $this->discardWorkspace(
            $workspace,
            $baseWorkspace,
            $workspaceContentStreamVersion,
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
        Version $workspaceContentStreamVersion,
        Version $baseWorkspaceContentStreamVersion,
        ContentStreamId $newContentStream
    ): EventsToPublish {
        $eventsToPublish = $this->forkContentStream(
            $newContentStream,
            $baseWorkspace->currentContentStreamId,
            $baseWorkspaceContentStreamVersion,
            sprintf('Discard %s and fork base %s', $workspace->workspaceName->value, $baseWorkspace->workspaceName->value)
        );

        $eventsToPublish = $eventsToPublish->withEventsForStreamAndExpectedVersion(
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

        return $eventsToPublish->merge(
            $this->removeContentStream($workspace->currentContentStreamId, $workspaceContentStreamVersion)
        );
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
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $currentBaseWorkspace = $this->requireBaseWorkspace($workspace);

        if ($currentBaseWorkspace->workspaceName->equals($command->baseWorkspaceName)) {
            throw WorkspaceCommandSkipped::becauseTheBaseWorkspaceIsUnchanged($command->baseWorkspaceName, $command->workspaceName);
        }

        if ($workspace->hasPublishableChanges()) {
            throw WorkspaceContainsPublishableChanges::butWasNotSupposedToForBaseWorkspaceChange($workspace->workspaceName);
        }

        $newBaseWorkspace = $this->requireWorkspace($command->baseWorkspaceName);
        $this->requireNonCircularRelationBetweenWorkspaces($workspace, $newBaseWorkspace);

        $workspaceContentStreamVersion = $this->requireContentStreamVersion($workspace);
        $newBaseWorkspaceContentStreamVersion = $this->requireContentStreamVersion($newBaseWorkspace);

        $eventsToPublish = $this->forkContentStream(
            $command->newContentStreamId,
            $newBaseWorkspace->currentContentStreamId,
            $newBaseWorkspaceContentStreamVersion,
            sprintf('Change base workspace of %s to %s', $workspace->workspaceName->value, $newBaseWorkspace->workspaceName->value)
        );

        $eventsToPublish = $eventsToPublish->withEventsForStreamAndExpectedVersion(
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

        return $eventsToPublish->merge(
            $this->removeContentStream($workspace->currentContentStreamId, $workspaceContentStreamVersion)
        );
    }

    /**
     * @throws WorkspaceDoesNotExist
     */
    private function handleDeleteWorkspace(
        DeleteWorkspace $command,
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->workspaceName);
        $contentStreamVersion = $this->commandHandlingDependencies->getContentStreamVersion($workspace->currentContentStreamId);

        $eventsToPublish = EventsToPublish::createEventsForStreamAndExpectedVersion(
            ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)->getEventStreamName(),
            Events::with(
                new ContentStreamWasRemoved(
                    $workspace->currentContentStreamId,
                ),
            ),
            ExpectedVersion::fromVersion($contentStreamVersion)
        );

        return $eventsToPublish->withEventsForStreamAndExpectedVersion(
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
    ): EventsToPublish {
        $eventsToPublish = $this->forkContentStream(
            $newContentStreamId,
            $sourceContentStreamId,
            $sourceContentStreamVersion,
            $debugReasonForFork . sprintf('; Apply %d events on new content stream', $eventsToApplyOnNewContentStream?->count() ?? 0)
        );

        $eventsToPublish = $eventsToPublish->merge($pointWorkspaceToNewContentStream);

        if ($eventsToApplyOnNewContentStream !== null) {
            $eventsToPublish = $eventsToPublish->withEventsForStreamAndExpectedVersion(
                ContentStreamEventStreamName::fromContentStreamId($newContentStreamId)
                    ->getEventStreamName(),
                $eventsToApplyOnNewContentStream,
                ExpectedVersion::fromVersion(Version::first())
            );
        }

        return $eventsToPublish;
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

    private function requireContentStreamVersion(Workspace $workspace): Version
    {
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
}
