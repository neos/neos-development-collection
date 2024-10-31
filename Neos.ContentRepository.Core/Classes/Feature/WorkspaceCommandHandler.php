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
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasReopened;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
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
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceHasNoBaseWorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\EventType;
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

    public function canHandle(CommandInterface $command): bool
    {
        return method_exists($this, 'handle' . (new \ReflectionClass($command))->getShortName());
    }

    public function handle(CommandInterface $command, CommandHandlingDependencies $commandHandlingDependencies): \Generator
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

        // When the workspace is created, we first have to fork the content stream
        yield $this->forkContentStream(
            $command->newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $commandHandlingDependencies
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

        $newContentStreamId = $command->newContentStreamId;
        yield $this->createContentStream(
            $newContentStreamId,
            $commandHandlingDependencies
        );

        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
            Events::with(
                new RootWorkspaceWasCreated(
                    $command->workspaceName,
                    $newContentStreamId
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
        $this->requireContentStreamToNotBeClosed($baseWorkspace->currentContentStreamId, $commandHandlingDependencies);
        $baseContentStreamVersion = $commandHandlingDependencies->getContentStreamVersion($baseWorkspace->currentContentStreamId);

        yield $this->closeContentStream(
            $workspace->currentContentStreamId,
            $commandHandlingDependencies
        );

        $rebaseableCommands = RebaseableCommands::extractFromEventStream(
            $this->eventStore->load(
                ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)
                    ->getEventStreamName()
            )
        );

        try {
            yield from $this->publishWorkspace(
                $workspace,
                $baseWorkspace,
                $command->newContentStreamId,
                $baseContentStreamVersion,
                $rebaseableCommands,
                $commandHandlingDependencies
            );
        } catch (WorkspaceRebaseFailed $workspaceRebaseFailed) {
            yield $this->reopenContentStream(
                $workspace->currentContentStreamId,
                $commandHandlingDependencies
            );
            throw $workspaceRebaseFailed;
        }
    }

    private function publishWorkspace(
        Workspace $workspace,
        Workspace $baseWorkspace,
        ContentStreamId $newContentStreamId,
        Version $baseContentStreamVersion,
        RebaseableCommands $rebaseableCommands,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        $commandSimulator = $this->commandSimulatorFactory->createSimulatorForWorkspace($baseWorkspace->workspaceName);

        $commandSimulator->run(
            static function ($handle) use ($rebaseableCommands): void {
                foreach ($rebaseableCommands as $rebaseableCommand) {
                    $handle($rebaseableCommand);
                }
            }
        );

        if ($commandSimulator->hasCommandsThatFailed()) {
            throw WorkspaceRebaseFailed::duringPublish($commandSimulator->getCommandsThatFailed());
        }

        yield new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($baseWorkspace->currentContentStreamId)
                ->getEventStreamName(),
            $this->getCopiedEventsOfEventStream(
                $baseWorkspace->workspaceName,
                $baseWorkspace->currentContentStreamId,
                $commandSimulator->eventStream(),
            ),
            ExpectedVersion::fromVersion($baseContentStreamVersion)
        );

        yield $this->forkContentStream(
            $newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $commandHandlingDependencies
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

        yield $this->removeContentStream($workspace->currentContentStreamId, $commandHandlingDependencies);
    }

    private function rebaseWorkspaceWithoutChanges(
        Workspace $workspace,
        Workspace $baseWorkspace,
        ContentStreamId $newContentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        yield $this->forkContentStream(
            $newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $commandHandlingDependencies
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

        yield $this->removeContentStream($workspace->currentContentStreamId, $commandHandlingDependencies);
    }

    /**
     * Copy all events from the passed event stream which implement the {@see PublishableToOtherContentStreamsInterface}
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

        if (
            $workspace->status === WorkspaceStatus::UP_TO_DATE
            && $command->rebaseErrorHandlingStrategy !== RebaseErrorHandlingStrategy::STRATEGY_FORCE
        ) {
            // no-op if workspace is not outdated and not forcing it
            return;
        }

        yield $this->closeContentStream(
            $workspace->currentContentStreamId,
            $commandHandlingDependencies
        );

        if (!$workspace->hasPublishableChanges()) {
            // if we have no changes in the workspace we can fork from the base directly
            yield from $this->rebaseWorkspaceWithoutChanges(
                $workspace,
                $baseWorkspace,
                $command->rebasedContentStreamId,
                $commandHandlingDependencies
            );
            return;
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
            && $commandSimulator->hasCommandsThatFailed()
        ) {
            yield $this->reopenContentStream(
                $workspace->currentContentStreamId,
                $commandHandlingDependencies
            );

            // throw an exception that contains all the information about what exactly failed
            throw WorkspaceRebaseFailed::duringRebase($commandSimulator->getCommandsThatFailed());
        }

        // if we got so far without an exception (or if we don't care), we can switch the workspace's active content stream.
        yield from $this->forkNewContentStreamAndApplyEvents(
            $command->rebasedContentStreamId,
            $baseWorkspace->currentContentStreamId,
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
            ),
            $commandHandlingDependencies
        );

        yield $this->removeContentStream($workspace->currentContentStreamId, $commandHandlingDependencies);
    }

    /**
     * This method is like a combined Rebase and Publish!
     *
     * @throws BaseWorkspaceDoesNotExist
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     * @throws WorkspaceDoesNotExist
     * @throws \Exception
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

        $this->requireContentStreamToNotBeClosed($baseWorkspace->currentContentStreamId, $commandHandlingDependencies);
        $baseContentStreamVersion = $commandHandlingDependencies->getContentStreamVersion($baseWorkspace->currentContentStreamId);

        yield $this->closeContentStream(
            $workspace->currentContentStreamId,
            $commandHandlingDependencies
        );

        $rebaseableCommands = RebaseableCommands::extractFromEventStream(
            $this->eventStore->load(
                ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)
                    ->getEventStreamName()
            )
        );

        [$matchingCommands, $remainingCommands] = $rebaseableCommands->separateMatchingAndRemainingCommands($command->nodesToPublish);

        if ($matchingCommands->isEmpty()) {
            // almost a noop (e.g. random node ids were specified) ;)
            yield $this->reopenContentStream(
                $workspace->currentContentStreamId,
                $commandHandlingDependencies
            );
            return;
        }

        if ($remainingCommands->isEmpty()) {
            try {
                // do a full publish, this is simpler for the projections to handle
                yield from $this->publishWorkspace(
                    $workspace,
                    $baseWorkspace,
                    $command->contentStreamIdForRemainingPart,
                    $baseContentStreamVersion,
                    $matchingCommands,
                    $commandHandlingDependencies
                );
                return;
            } catch (WorkspaceRebaseFailed $workspaceRebaseFailed) {
                yield $this->reopenContentStream(
                    $workspace->currentContentStreamId,
                    $commandHandlingDependencies
                );
                throw $workspaceRebaseFailed;
            }
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

        if ($commandSimulator->hasCommandsThatFailed()) {
            yield $this->reopenContentStream(
                $workspace->currentContentStreamId,
                $commandHandlingDependencies
            );

            throw WorkspaceRebaseFailed::duringPublish($commandSimulator->getCommandsThatFailed());
        }

        // this could be a no-op for the rare case when a command returns empty events e.g. the node was already tagged with this subtree tag, meaning we actually just rebase
        yield new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($baseWorkspace->currentContentStreamId)
                ->getEventStreamName(),
            $this->getCopiedEventsOfEventStream(
                $baseWorkspace->workspaceName,
                $baseWorkspace->currentContentStreamId,
                $commandSimulator->eventStream()->withMaximumSequenceNumber($highestSequenceNumberForMatching),
            ),
            ExpectedVersion::fromVersion($baseContentStreamVersion)
        );

        yield from $this->forkNewContentStreamAndApplyEvents(
            $command->contentStreamIdForRemainingPart,
            $baseWorkspace->currentContentStreamId,
            new EventsToPublish(
                WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
                Events::fromArray([
                    new WorkspaceWasPartiallyPublished(
                        $command->workspaceName,
                        $baseWorkspace->workspaceName,
                        $command->contentStreamIdForRemainingPart,
                        $workspace->currentContentStreamId,
                        $command->nodesToPublish
                    )
                ]),
                ExpectedVersion::ANY()
            ),
            $this->getCopiedEventsOfEventStream(
                $command->workspaceName,
                $command->contentStreamIdForRemainingPart,
                $commandSimulator->eventStream()->withMinimumSequenceNumber($highestSequenceNumberForMatching->next())
            ),
            $commandHandlingDependencies
        );

        yield $this->removeContentStream($workspace->currentContentStreamId, $commandHandlingDependencies);
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

        yield $this->closeContentStream(
            $workspace->currentContentStreamId,
            $commandHandlingDependencies
        );

        // filter commands, only keeping the ones NOT MATCHING the nodes from the command (i.e. the modifications we want to keep)
        $rebaseableCommands = RebaseableCommands::extractFromEventStream(
            $this->eventStore->load(
                ContentStreamEventStreamName::fromContentStreamId($workspace->currentContentStreamId)
                    ->getEventStreamName()
            )
        );
        [$commandsToDiscard, $commandsToKeep] = $rebaseableCommands->separateMatchingAndRemainingCommands($command->nodesToDiscard);

        if ($commandsToDiscard->isEmpty()) {
            // if we have nothing to discard, we can just keep all. (e.g. random node ids were specified) It's almost a noop ;)
            yield $this->reopenContentStream(
                $workspace->currentContentStreamId,
                $commandHandlingDependencies
            );
            return;
        }

        if ($commandsToKeep->isEmpty()) {
            // quick path everything was discarded
            yield from $this->discardWorkspace(
                $workspace,
                $baseWorkspace,
                $command->newContentStreamId,
                $commandHandlingDependencies
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

        if ($commandSimulator->hasCommandsThatFailed()) {
            yield $this->reopenContentStream(
                $workspace->currentContentStreamId,
                $commandHandlingDependencies
            );
            throw WorkspaceRebaseFailed::duringDiscard($commandSimulator->getCommandsThatFailed());
        }

        yield from $this->forkNewContentStreamAndApplyEvents(
            $command->newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            new EventsToPublish(
                WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
                Events::with(
                    new WorkspaceWasPartiallyDiscarded(
                        $command->workspaceName,
                        $command->newContentStreamId,
                        $workspace->currentContentStreamId,
                        $command->nodesToDiscard,
                    )
                ),
                ExpectedVersion::ANY()
            ),
            $this->getCopiedEventsOfEventStream(
                $command->workspaceName,
                $command->newContentStreamId,
                $commandSimulator->eventStream(),
            ),
            $commandHandlingDependencies
        );

        yield $this->removeContentStream($workspace->currentContentStreamId, $commandHandlingDependencies);
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

        yield from $this->discardWorkspace(
            $workspace,
            $baseWorkspace,
            $command->newContentStreamId,
            $commandHandlingDependencies
        );
    }

    /**
     * @param Workspace $workspace
     * @param Workspace $baseWorkspace
     * @param ContentStreamId $newContentStream
     * @param CommandHandlingDependencies $commandHandlingDependencies
     * @phpstan-pure this method is pure, to persist the events they must be handled outside
     */
    private function discardWorkspace(
        Workspace $workspace,
        Workspace $baseWorkspace,
        ContentStreamId $newContentStream,
        CommandHandlingDependencies $commandHandlingDependencies
    ): \Generator {
        yield $this->forkContentStream(
            $newContentStream,
            $baseWorkspace->currentContentStreamId,
            $commandHandlingDependencies
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

        yield $this->removeContentStream($workspace->currentContentStreamId, $commandHandlingDependencies);
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

        if ($currentBaseWorkspace->workspaceName->equals($command->baseWorkspaceName)) {
            // no-op
            return;
        }

        $this->requireEmptyWorkspace($workspace);
        $newBaseWorkspace = $this->requireWorkspace($command->baseWorkspaceName, $commandHandlingDependencies);

        $this->requireNonCircularRelationBetweenWorkspaces($workspace, $newBaseWorkspace, $commandHandlingDependencies);

        yield $this->forkContentStream(
            $command->newContentStreamId,
            $newBaseWorkspace->currentContentStreamId,
            $commandHandlingDependencies
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

        yield $this->removeContentStream(
            $workspace->currentContentStreamId,
            $commandHandlingDependencies
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
        EventsToPublish $pointWorkspaceToNewContentStream,
        Events $eventsToApplyOnNewContentStream,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        yield $this->forkContentStream(
            $newContentStreamId,
            $sourceContentStreamId,
            $commandHandlingDependencies
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
