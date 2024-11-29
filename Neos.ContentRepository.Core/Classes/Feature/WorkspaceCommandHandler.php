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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\EmbedsAffectedDimensionSpacePoints;
use Neos\ContentRepository\Core\Feature\Common\EmbedsNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
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
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Exception\WorkspaceIsNotEmptyException;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamAlreadyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamIsClosed;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceHasNoBaseWorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 */
final readonly class WorkspaceCommandHandler implements CommandHandlerInterface
{
    use ContentStreamHandling;

    public function __construct(
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
    public function handle(CommandInterface|RebasableToOtherWorkspaceInterface $command, CommandHandlingDependencies $commandHandlingDependencies): \Generator
    {
        /** @phpstan-ignore-next-line */
        return match ($command::class) {
            CreateWorkspace::class => $this->handleCreateWorkspace($command, $commandHandlingDependencies),
            CreateRootWorkspace::class => $this->handleCreateRootWorkspace($command, $commandHandlingDependencies),
            PublishWorkspace::class => $this->handlePublishWorkspace($command, $commandHandlingDependencies),
            RebaseWorkspace::class => $this->handleRebaseWorkspace($command, $commandHandlingDependencies),
            PublishIndividualNodesFromWorkspace::class => $this->handlePublishIndividualNodesFromWorkspace($command, $commandHandlingDependencies),
            DiscardIndividualNodesFromWorkspace::class => $this->handleDiscardIndividualNodesFromWorkspace($command, $commandHandlingDependencies),
            DiscardWorkspace::class => $this->handleDiscardWorkspace($command, $commandHandlingDependencies),
            DeleteWorkspace::class => $this->handleDeleteWorkspace($command, $commandHandlingDependencies),
            ChangeBaseWorkspace::class => $this->handleChangeBaseWorkspace($command, $commandHandlingDependencies),
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
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        $this->requireWorkspaceToNotExist($command->workspaceName, $commandHandlingDependencies);
        $baseWorkspace = $commandHandlingDependencies->findWorkspaceByName($command->baseWorkspaceName);
        if ($baseWorkspace === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf(
                'The workspace %s (base workspace of %s) does not exist',
                $command->baseWorkspaceName->value,
                $command->workspaceName->value
            ), 1513890708);
        }
        $sourceContentStreamVersion = $commandHandlingDependencies->getContentStreamVersion($baseWorkspace->currentContentStreamId);
        $this->requireContentStreamToNotBeClosed($baseWorkspace->currentContentStreamId, $commandHandlingDependencies);
        $this->requireContentStreamToNotExistYet($command->newContentStreamId, $commandHandlingDependencies);

        // When the workspace is created, we first have to fork the content stream
        yield $this->forkContentStream(
            $command->newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $sourceContentStreamVersion
        );

        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
            Events::with(
                new WorkspaceWasCreated(
                    $command->workspaceName,
                    $command->baseWorkspaceName,
                    $command->newContentStreamId,
                )
            ),
            ExpectedVersion::ANY()
        );
    }

    /**
     * @param CreateRootWorkspace $command
     * @throws WorkspaceAlreadyExists
     * @throws ContentStreamAlreadyExists
     */
    private function handleCreateRootWorkspace(
        CreateRootWorkspace $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        $this->requireWorkspaceToNotExist($command->workspaceName, $commandHandlingDependencies);
        $this->requireContentStreamToNotExistYet($command->newContentStreamId, $commandHandlingDependencies);

        yield new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($command->newContentStreamId)->getEventStreamName(),
            Events::with(
                new ContentStreamWasCreated(
                    $command->newContentStreamId,
                )
            ),
            ExpectedVersion::NO_STREAM()
        );

        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
            Events::with(
                new RootWorkspaceWasCreated(
                    $command->workspaceName,
                    $command->newContentStreamId
                )
            ),
            ExpectedVersion::ANY()
        );
    }

    private function handlePublishWorkspace(
        PublishWorkspace $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);
        if (!$workspace->hasPublishableChanges()) {
            // no-op
            return;
        }
        $workspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($workspace, $commandHandlingDependencies);
        $baseWorkspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($baseWorkspace, $commandHandlingDependencies);

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

        yield from $this->publishWorkspace(
            $workspace,
            $baseWorkspace,
            $baseWorkspaceContentStreamVersion,
            $command->newContentStreamId,
            $rebaseableCommands
        );
    }

    /**
     * Note that the workspaces content stream must be closed beforehand.
     * It will be reopened here in case of error.
     */
    private function publishWorkspace(
        Workspace $workspace,
        Workspace $baseWorkspace,
        Version $baseWorkspaceContentStreamVersion,
        ContentStreamId $newContentStreamId,
        RebaseableCommands $rebaseableCommands
    ): \Generator {
        $commandSimulator = $this->commandSimulatorFactory->createSimulatorForWorkspace($baseWorkspace->workspaceName);

        $commandSimulator->run(
            static function ($handle) use ($rebaseableCommands): void {
                foreach ($rebaseableCommands as $rebaseableCommand) {
                    $handle($rebaseableCommand);
                }
            }
        );

        if ($commandSimulator->hasConflicts()) {
            yield $this->reopenContentStreamWithoutConstraintChecks(
                $workspace->currentContentStreamId
            );
            throw WorkspaceRebaseFailed::duringPublish($commandSimulator->getConflictingEvents());
        }

        $eventsOfWorkspaceToPublish = $this->getCopiedEventsOfEventStream(
            $baseWorkspace->workspaceName,
            $baseWorkspace->currentContentStreamId,
            $commandSimulator->eventStream(),
        );

        try {
            yield new EventsToPublish(
                ContentStreamEventStreamName::fromContentStreamId($baseWorkspace->currentContentStreamId)
                    ->getEventStreamName(),
                $eventsOfWorkspaceToPublish,
                ExpectedVersion::fromVersion($baseWorkspaceContentStreamVersion)
            );
        } catch (ConcurrencyException $concurrencyException) {
            yield $this->reopenContentStreamWithoutConstraintChecks(
                $workspace->currentContentStreamId
            );
            throw $concurrencyException;
        }

        yield $this->forkContentStream(
            $newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            Version::fromInteger($baseWorkspaceContentStreamVersion->value + $eventsOfWorkspaceToPublish->count())
        );

        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($workspace->workspaceName)->getEventStreamName(),
            Events::with(
                new WorkspaceWasPublished(
                    $workspace->workspaceName,
                    $baseWorkspace->workspaceName,
                    $newContentStreamId,
                    $workspace->currentContentStreamId,
                )
            ),
            ExpectedVersion::ANY()
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
            $baseWorkspaceContentStreamVersion
        );

        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($workspace->workspaceName)->getEventStreamName(),
            Events::with(
                new WorkspaceWasRebased(
                    $workspace->workspaceName,
                    $newContentStreamId,
                    $workspace->currentContentStreamId,
                ),
            ),
            ExpectedVersion::ANY()
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
    ): Events {
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

        return Events::fromArray($events);
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws WorkspaceRebaseFailed
     */
    private function handleRebaseWorkspace(
        RebaseWorkspace $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);

        $workspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($workspace, $commandHandlingDependencies);
        $baseWorkspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($baseWorkspace, $commandHandlingDependencies);

        if (
            $workspace->status === WorkspaceStatus::UP_TO_DATE
            && $command->rebaseErrorHandlingStrategy !== RebaseErrorHandlingStrategy::STRATEGY_FORCE
        ) {
            // no-op if workspace is not outdated and not forcing it
            return;
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
            yield $this->reopenContentStreamWithoutConstraintChecks(
                $workspace->currentContentStreamId
            );

            // throw an exception that contains all the information about what exactly failed
            throw WorkspaceRebaseFailed::duringRebase($commandSimulator->getConflictingEvents());
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
                    ),
                ),
                ExpectedVersion::ANY()
            ),
            $this->getCopiedEventsOfEventStream(
                $command->workspaceName,
                $command->rebasedContentStreamId,
                $commandSimulator->eventStream(),
            )
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
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);
        if ($command->nodesToPublish->isEmpty() || !$workspace->hasPublishableChanges()) {
            // noop
            return;
        }

        $workspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($workspace, $commandHandlingDependencies);
        $baseWorkspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($baseWorkspace, $commandHandlingDependencies);

        $rebaseableCommands = RebaseableCommands::extractFromEventStream(
            $this->eventStore->load(
                ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)
                    ->getEventStreamName()
            )
        );

        [$matchingCommands, $remainingCommands] = $rebaseableCommands->separateMatchingAndRemainingCommands($command->nodesToPublish);

        if ($matchingCommands->isEmpty()) {
            // almost a noop (e.g. random node ids were specified) ;)
            return;
        }

        // todo we should - similar to discard - also add the node aggregate IDs with dimension point to the event again. The question is if we use the old events to analyse that or the newly emitted (today) events?
        // could they come to a different result like MORE or less dimensions are suddenly affected because of a change in configuration? And would it make sense to use the old or new events then? Probably the old
        // to make it consistent with discard.
        $nodesToPublish = $this->extractNodeAggregateIdsAndAffectedDimensionSpacePoints($matchingCommands);

        yield $this->closeContentStream(
            $workspace->currentContentStreamId,
            $workspaceContentStreamVersion
        );

        if ($remainingCommands->isEmpty()) {
            // do a full publish, this is simpler for the projections to handle
            yield from $this->publishWorkspace(
                $workspace,
                $baseWorkspace,
                $baseWorkspaceContentStreamVersion,
                $command->contentStreamIdForRemainingPart,
                $matchingCommands
            );
            return;
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
            yield $this->reopenContentStreamWithoutConstraintChecks(
                $workspace->currentContentStreamId
            );

            throw WorkspaceRebaseFailed::duringPublish($commandSimulator->getConflictingEvents());
        }

        // this could empty and a no-op for the rare case when a command returns empty events e.g. the node was already tagged with this subtree tag
        $selectedEventsOfWorkspaceToPublish = $this->getCopiedEventsOfEventStream(
            $baseWorkspace->workspaceName,
            $baseWorkspace->currentContentStreamId,
            $commandSimulator->eventStream()->withMaximumSequenceNumber($highestSequenceNumberForMatching),
        );

        try {
            yield new EventsToPublish(
                ContentStreamEventStreamName::fromContentStreamId($baseWorkspace->currentContentStreamId)
                    ->getEventStreamName(),
                $selectedEventsOfWorkspaceToPublish,
                ExpectedVersion::fromVersion($baseWorkspaceContentStreamVersion)
            );
        } catch (ConcurrencyException $concurrencyException) {
            yield $this->reopenContentStreamWithoutConstraintChecks(
                $workspace->currentContentStreamId
            );
            throw $concurrencyException;
        }

        yield from $this->forkNewContentStreamAndApplyEvents(
            $command->contentStreamIdForRemainingPart,
            $baseWorkspace->currentContentStreamId,
            Version::fromInteger($baseWorkspaceContentStreamVersion->value + $selectedEventsOfWorkspaceToPublish->count()),
            new EventsToPublish(
                WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
                Events::fromArray([
                    new WorkspaceWasPartiallyPublished(
                        $command->workspaceName,
                        $baseWorkspace->workspaceName,
                        $command->contentStreamIdForRemainingPart,
                        $workspace->currentContentStreamId,
                        $nodesToPublish
                    )
                ]),
                ExpectedVersion::ANY()
            ),
            $this->getCopiedEventsOfEventStream(
                $command->workspaceName,
                $command->contentStreamIdForRemainingPart,
                $commandSimulator->eventStream()->withMinimumSequenceNumber($highestSequenceNumberForMatching->next())
            )
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
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);

        if ($command->nodesToDiscard->isEmpty() || !$workspace->hasPublishableChanges()) {
            // noop
            return;
        }

        $workspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($workspace, $commandHandlingDependencies);
        $baseWorkspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($baseWorkspace, $commandHandlingDependencies);

        $rebaseableCommands = RebaseableCommands::extractFromEventStream(
            $this->eventStore->load(
                ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)
                    ->getEventStreamName()
            )
        );

        // filter commands, only keeping the ones NOT MATCHING the nodes from the command (i.e. the modifications we want to keep)
        [$commandsToDiscard, $commandsToKeep] = $rebaseableCommands->separateMatchingAndRemainingCommands($command->nodesToDiscard);

        if ($commandsToDiscard->isEmpty()) {
            // if we have nothing to discard, we can just keep all. (e.g. random node ids were specified) It's almost a noop ;)
            return;
        }

        $nodesToDiscard = $this->extractNodeAggregateIdsAndAffectedDimensionSpacePoints($commandsToDiscard);

        yield $this->closeContentStream(
            $workspace->currentContentStreamId,
            $workspaceContentStreamVersion
        );

        if ($commandsToKeep->isEmpty()) {
            // quick path everything was discarded
            yield from $this->discardWorkspace(
                $workspace,
                $workspaceContentStreamVersion,
                $baseWorkspace,
                $baseWorkspaceContentStreamVersion,
                $command->newContentStreamId
            );
            return;
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
            yield $this->reopenContentStreamWithoutConstraintChecks(
                $workspace->currentContentStreamId
            );
            throw WorkspaceRebaseFailed::duringDiscard($commandSimulator->getConflictingEvents());
        }

        yield from $this->forkNewContentStreamAndApplyEvents(
            $command->newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $baseWorkspaceContentStreamVersion,
            new EventsToPublish(
                WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
                Events::with(
                    new WorkspaceWasPartiallyDiscarded(
                        $command->workspaceName,
                        $command->newContentStreamId,
                        $workspace->currentContentStreamId,
                        $nodesToDiscard,
                    )
                ),
                ExpectedVersion::ANY()
            ),
            $this->getCopiedEventsOfEventStream(
                $command->workspaceName,
                $command->newContentStreamId,
                $commandSimulator->eventStream(),
            )
        );

        yield $this->removeContentStreamWithoutConstraintChecks($workspace->currentContentStreamId);
    }

    private function extractNodeAggregateIdsAndAffectedDimensionSpacePoints(RebaseableCommands $commands): NodeIdsToPublishOrDiscard
    {
        /** @var array<string,DimensionSpacePointSet> $affectedDimensionSpacePointsByNodeAggregateId */
        $affectedDimensionSpacePointsByNodeAggregateId = [];
        foreach ($commands as $command) {
            $event = $this->eventNormalizer->denormalize($command->originalEvent);
            assert($event instanceof EmbedsNodeAggregateId);
            if (!$event instanceof EmbedsAffectedDimensionSpacePoints) {
                // todo fix change node type and rename case!!! either event must contain all dsp or we need to use the variation graph here?
                // -> all dimension would be forward compatible if we ever allow node type change for one dsp ...
                continue;
            }
            $affectedDimensionSpacePoints = $affectedDimensionSpacePointsByNodeAggregateId[$event->getNodeAggregateId()->value] ?? null;

            $affectedDimensionSpacePointsByNodeAggregateId[$event->getNodeAggregateId()->value] =
                $affectedDimensionSpacePoints !== null
                    ? $affectedDimensionSpacePoints->getUnion($event->getAffectedDimensionSpacePoints())
                    : $event->getAffectedDimensionSpacePoints();

        }

        $nodeAggregateIdsWithDimension = [];
        foreach ($affectedDimensionSpacePointsByNodeAggregateId as $nodeId => $affectedDimensionSpacePoints) {
            foreach ($affectedDimensionSpacePoints as $dimensionSpacePoint) {
                $nodeAggregateIdsWithDimension[] = new NodeIdToPublishOrDiscard(
                    NodeAggregateId::fromString($nodeId),
                    $dimensionSpacePoint
                );
            }
        }

        // todo optimise format like: [{NodeAggregateId -> DimensionSpacePointSet}] and rename collection to NodeAggregateIdsWithDimensionSpacePoints (less clumsy) ... in the event this happened already ... no TO ...!
        return NodeIdsToPublishOrDiscard::create(...$nodeAggregateIdsWithDimension);
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws WorkspaceHasNoBaseWorkspaceName
     */
    private function handleDiscardWorkspace(
        DiscardWorkspace $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);

        if (!$workspace->hasPublishableChanges()) {
            return;
        }

        $workspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($workspace, $commandHandlingDependencies);
        $baseWorkspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($baseWorkspace, $commandHandlingDependencies);

        yield from $this->discardWorkspace(
            $workspace,
            $workspaceContentStreamVersion,
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
        Version $workspaceContentStreamVersion,
        Workspace $baseWorkspace,
        Version $baseWorkspaceContentStreamVersion,
        ContentStreamId $newContentStream
    ): \Generator {
        yield $this->forkContentStream(
            $newContentStream,
            $baseWorkspace->currentContentStreamId,
            $baseWorkspaceContentStreamVersion
        );

        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($workspace->workspaceName)->getEventStreamName(),
            Events::with(
                new WorkspaceWasDiscarded(
                    $workspace->workspaceName,
                    $newContentStream,
                    $workspace->currentContentStreamId,
                )
            ),
            ExpectedVersion::ANY()
        );

        yield $this->removeContentStreamWithoutConstraintChecks($workspace->currentContentStreamId);
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws WorkspaceHasNoBaseWorkspaceName
     * @throws WorkspaceIsNotEmptyException
     * @throws BaseWorkspaceEqualsWorkspaceException
     * @throws CircularRelationBetweenWorkspacesException
     */
    private function handleChangeBaseWorkspace(
        ChangeBaseWorkspace $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $currentBaseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);

        $this->requireContentStreamToNotBeClosed($workspace->currentContentStreamId, $commandHandlingDependencies);

        if ($currentBaseWorkspace->workspaceName->equals($command->baseWorkspaceName)) {
            // no-op
            return;
        }

        $this->requireEmptyWorkspace($workspace);
        $newBaseWorkspace = $this->requireWorkspace($command->baseWorkspaceName, $commandHandlingDependencies);
        $this->requireNonCircularRelationBetweenWorkspaces($workspace, $newBaseWorkspace, $commandHandlingDependencies);

        $newBaseWorkspaceContentStreamVersion = $this->requireOpenContentStreamAndVersion($newBaseWorkspace, $commandHandlingDependencies);

        yield $this->forkContentStream(
            $command->newContentStreamId,
            $newBaseWorkspace->currentContentStreamId,
            $newBaseWorkspaceContentStreamVersion
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
            ExpectedVersion::ANY()
        );
    }

    /**
     * @throws WorkspaceDoesNotExist
     */
    private function handleDeleteWorkspace(
        DeleteWorkspace $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $contentStreamVersion = $commandHandlingDependencies->getContentStreamVersion($workspace->currentContentStreamId);

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
            ExpectedVersion::ANY()
        );
    }

    private function forkNewContentStreamAndApplyEvents(
        ContentStreamId $newContentStreamId,
        ContentStreamId $sourceContentStreamId,
        Version $sourceContentStreamVersion,
        EventsToPublish $pointWorkspaceToNewContentStream,
        Events $eventsToApplyOnNewContentStream,
    ): \Generator {
        yield $this->forkContentStream(
            $newContentStreamId,
            $sourceContentStreamId,
            $sourceContentStreamVersion
        )->withAppendedEvents(Events::with(
            new ContentStreamWasClosed(
                $newContentStreamId
            )
        ));

        yield $pointWorkspaceToNewContentStream;

        yield new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($newContentStreamId)
                ->getEventStreamName(),
            $eventsToApplyOnNewContentStream->withAppendedEvents(
                Events::with(
                    new ContentStreamWasReopened(
                        $newContentStreamId
                    )
                )
            ),
            ExpectedVersion::fromVersion(Version::first()->next())
        );
    }

    private function requireWorkspaceToNotExist(WorkspaceName $workspaceName, CommandHandlingDependencies $commandHandlingDependencies): void
    {
        if ($commandHandlingDependencies->findWorkspaceByName($workspaceName) === null) {
            return;
        }

        throw new WorkspaceAlreadyExists(sprintf(
            'The workspace %s already exists',
            $workspaceName->value
        ), 1715341085);
    }

    private function requireOpenContentStreamAndVersion(Workspace $workspace, CommandHandlingDependencies $commandHandlingDependencies): Version
    {
        if ($commandHandlingDependencies->isContentStreamClosed($workspace->currentContentStreamId)) {
            throw new ContentStreamIsClosed(
                'Content stream "' . $workspace->currentContentStreamId . '" is closed.',
                1730730516
            );
        }
        return $commandHandlingDependencies->getContentStreamVersion($workspace->currentContentStreamId);
    }

    /**
     * @throws WorkspaceDoesNotExist
     */
    private function requireWorkspace(WorkspaceName $workspaceName, CommandHandlingDependencies $commandHandlingDependencies): Workspace
    {
        $workspace = $commandHandlingDependencies->findWorkspaceByName($workspaceName);
        if (is_null($workspace)) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }

        return $workspace;
    }

    /**
     * @throws WorkspaceHasNoBaseWorkspaceName
     * @throws BaseWorkspaceDoesNotExist
     */
    private function requireBaseWorkspace(Workspace $workspace, CommandHandlingDependencies $commandHandlingDependencies): Workspace
    {
        if (is_null($workspace->baseWorkspaceName)) {
            throw WorkspaceHasNoBaseWorkspaceName::butWasSupposedTo($workspace->workspaceName);
        }
        $baseWorkspace = $commandHandlingDependencies->findWorkspaceByName($workspace->baseWorkspaceName);
        if (is_null($baseWorkspace)) {
            throw BaseWorkspaceDoesNotExist::butWasSupposedTo($workspace->workspaceName);
        }
        return $baseWorkspace;
    }

    /**
     * @throws BaseWorkspaceEqualsWorkspaceException
     * @throws CircularRelationBetweenWorkspacesException
     */
    private function requireNonCircularRelationBetweenWorkspaces(Workspace $workspace, Workspace $baseWorkspace, CommandHandlingDependencies $commandHandlingDependencies): void
    {
        if ($workspace->workspaceName->equals($baseWorkspace->workspaceName)) {
            throw new BaseWorkspaceEqualsWorkspaceException(sprintf('The base workspace of the target must be different from the given workspace "%s".', $workspace->workspaceName->value));
        }
        $nextBaseWorkspace = $baseWorkspace;
        while (!is_null($nextBaseWorkspace->baseWorkspaceName)) {
            if ($workspace->workspaceName->equals($nextBaseWorkspace->baseWorkspaceName)) {
                throw new CircularRelationBetweenWorkspacesException(sprintf('The workspace "%s" is already on the path of the target workspace "%s".', $workspace->workspaceName->value, $baseWorkspace->workspaceName->value));
            }
            $nextBaseWorkspace = $this->requireBaseWorkspace($nextBaseWorkspace, $commandHandlingDependencies);
        }
    }

    /**
     * @throws WorkspaceIsNotEmptyException
     */
    private function requireEmptyWorkspace(Workspace $workspace): void
    {
        if ($workspace->hasPublishableChanges()) {
            throw new WorkspaceIsNotEmptyException('The user workspace needs to be empty before switching the base workspace.', 1681455989);
        }
    }
}
