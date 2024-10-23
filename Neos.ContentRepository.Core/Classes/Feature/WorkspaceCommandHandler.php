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
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\EventPersister;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\EventStore\EventsToPublishFailed;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Command\CloseContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Command\ReopenContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Command\CreateContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Command\ForkContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Command\RemoveContentStream;
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
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Exception\BaseWorkspaceHasBeenModifiedInTheMeantime;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\CommandsThatFailedDuringRebase;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\CommandThatFailedDuringRebase;
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
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\EventStore\Model\Event\EventType;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 */
final readonly class WorkspaceCommandHandler implements CommandHandlerInterface
{
    use ContentStreamHandling;

    public function __construct(
        private EventPersister $eventPersister,
        private EventStoreInterface $eventStore,
        private EventNormalizer $eventNormalizer,
    ) {
    }

    public function canHandle(CommandInterface $command): bool
    {
        return method_exists($this, 'handle' . (new \ReflectionClass($command))->getShortName());
    }

    public function handle(CommandInterface $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish|\Generator
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
    ): EventsToPublish {
        $this->requireWorkspaceToNotExist($command->workspaceName, $commandHandlingDependencies);
        if ($commandHandlingDependencies->findWorkspaceByName($command->baseWorkspaceName) === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf(
                'The workspace %s (base workspace of %s) does not exist',
                $command->baseWorkspaceName->value,
                $command->workspaceName->value
            ), 1513890708);
        }

        $baseWorkspaceContentGraph = $commandHandlingDependencies->getContentGraph($command->baseWorkspaceName);
        // When the workspace is created, we first have to fork the content stream
        $commandHandlingDependencies->handle(
            ForkContentStream::create(
                $command->newContentStreamId,
                $baseWorkspaceContentGraph->getContentStreamId(),
            )
        );

        $events = Events::with(
            new WorkspaceWasCreated(
                $command->workspaceName,
                $command->baseWorkspaceName,
                $command->newContentStreamId,
            )
        );

        return new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
            $events,
            ExpectedVersion::ANY()
        );
    }

    /**
     * @param CreateRootWorkspace $command
     * @return EventsToPublish
     * @throws WorkspaceAlreadyExists
     * @throws ContentStreamAlreadyExists
     */
    private function handleCreateRootWorkspace(
        CreateRootWorkspace $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        $this->requireWorkspaceToNotExist($command->workspaceName, $commandHandlingDependencies);

        $newContentStreamId = $command->newContentStreamId;
        $commandHandlingDependencies->handle(
            CreateContentStream::create(
                $newContentStreamId,
            )
        );

        $events = Events::with(
            new RootWorkspaceWasCreated(
                $command->workspaceName,
                $newContentStreamId
            )
        );

        return new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
            $events,
            ExpectedVersion::ANY()
        );
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws BaseWorkspaceHasBeenModifiedInTheMeantime
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     * @throws WorkspaceDoesNotExist
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     * @throws WorkspaceHasNoBaseWorkspaceName
     */
    private function handlePublishWorkspace(
        PublishWorkspace $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);

        $publishResult = yield $this->getCopiedEventsToPublishForContentStream(
            $workspace->currentContentStreamId,
            $baseWorkspace->workspaceName,
            $baseWorkspace->currentContentStreamId
        );

        if ($publishResult instanceof EventsToPublishFailed) {
            throw new BaseWorkspaceHasBeenModifiedInTheMeantime(sprintf(
                'The base workspace has been modified in the meantime; please rebase.'
                . ' Expected version %d of source content stream %s',
                $publishResult->expectedVersion->value,
                $baseWorkspace->currentContentStreamId
            ));
        }

        // After publishing a workspace, we need to again fork from Base.
        yield $this->forkContentStream(
            $command->newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $commandHandlingDependencies
        );

        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
            Events::with(
                new WorkspaceWasPublished(
                    $command->workspaceName,
                    $baseWorkspace->workspaceName,
                    $command->newContentStreamId,
                    $workspace->currentContentStreamId,
                )
            ),
            ExpectedVersion::ANY()
        );
    }

    /**
     * @throws BaseWorkspaceHasBeenModifiedInTheMeantime
     * @throws \Exception
     */
    private function getCopiedEventsToPublishForContentStream(
        ContentStreamId $contentStreamId,
        WorkspaceName $baseWorkspaceName,
        ContentStreamId $baseContentStreamId,
    ): EventsToPublish {
        $baseWorkspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamId(
            $baseContentStreamId
        );

        // TODO: please check the code below in-depth. it does:
        // - copy all events from the "user" content stream which implement @see{}"PublishableToOtherContentStreamsInterface"
        // - extract the initial ContentStreamWasForked event,
        //   to read the version of the source content stream when the fork occurred
        // - ensure that no other changes have been done in the meantime in the base content stream

        $workspaceContentStream = iterator_to_array($this->eventStore->load(
            ContentStreamEventStreamName::fromContentStreamId($contentStreamId)->getEventStreamName()
        ));

        $events = [];
        $contentStreamWasForkedEvent = null;
        foreach ($workspaceContentStream as $eventEnvelope) {
            $event = $this->eventNormalizer->denormalize($eventEnvelope->event);

            if ($event instanceof ContentStreamWasForked) {
                if ($contentStreamWasForkedEvent !== null) {
                    throw new \RuntimeException(
                        'Invariant violation: The content stream "' . $contentStreamId->value
                        . '" has two forked events.',
                        1658740373
                    );
                }
                $contentStreamWasForkedEvent = $event;
            } elseif ($event instanceof PublishableToWorkspaceInterface) {
                /** @var EventInterface $copiedEvent */
                $copiedEvent = $event->withWorkspaceNameAndContentStreamId($baseWorkspaceName, $baseContentStreamId);
                // We need to add the event metadata here for rebasing in nested workspace situations
                // (and for exporting)
                $events[] = DecoratedEvent::create($copiedEvent, metadata: $eventEnvelope->event->metadata, causationId: $eventEnvelope->event->causationId, correlationId: $eventEnvelope->event->correlationId);
            }
        }

        if ($contentStreamWasForkedEvent === null) {
            throw new \RuntimeException('Invariant violation: The content stream "' . $contentStreamId->value
                . '" has NO forked event.', 1658740407);
        }

        return new EventsToPublish(
            $baseWorkspaceContentStreamName->getEventStreamName(),
            Events::fromArray($events),
            ExpectedVersion::fromVersion($contentStreamWasForkedEvent->versionOfSourceContentStream)
        );
    }

    /**
     * @deprecated FIXME REMOVE with https://github.com/neos/neos-development-collection/pull/5301
     * @throws BaseWorkspaceHasBeenModifiedInTheMeantime
     * @throws \Exception
     */
    private function publishContentStream(
        CommandHandlingDependencies $commandHandlingDependencies,
        ContentStreamId $contentStreamId,
        WorkspaceName $baseWorkspaceName,
        ContentStreamId $baseContentStreamId,
    ): void {
        $baseWorkspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamId(
            $baseContentStreamId
        );

        // TODO: please check the code below in-depth. it does:
        // - copy all events from the "user" content stream which implement @see{}"PublishableToOtherContentStreamsInterface"
        // - extract the initial ContentStreamWasForked event,
        //   to read the version of the source content stream when the fork occurred
        // - ensure that no other changes have been done in the meantime in the base content stream

        $workspaceContentStream = iterator_to_array($this->eventStore->load(
            ContentStreamEventStreamName::fromContentStreamId($contentStreamId)->getEventStreamName()
        ));

        $events = [];
        $contentStreamWasForkedEvent = null;
        foreach ($workspaceContentStream as $eventEnvelope) {
            $event = $this->eventNormalizer->denormalize($eventEnvelope->event);

            if ($event instanceof ContentStreamWasForked) {
                if ($contentStreamWasForkedEvent !== null) {
                    throw new \RuntimeException(
                        'Invariant violation: The content stream "' . $contentStreamId->value
                        . '" has two forked events.',
                        1658740373
                    );
                }
                $contentStreamWasForkedEvent = $event;
            } elseif ($event instanceof PublishableToWorkspaceInterface) {
                /** @var EventInterface $copiedEvent */
                $copiedEvent = $event->withWorkspaceNameAndContentStreamId($baseWorkspaceName, $baseContentStreamId);
                // We need to add the event metadata here for rebasing in nested workspace situations
                // (and for exporting)
                $events[] = DecoratedEvent::create($copiedEvent, metadata: $eventEnvelope->event->metadata, causationId: $eventEnvelope->event->causationId, correlationId: $eventEnvelope->event->correlationId);
            }
        }

        if ($contentStreamWasForkedEvent === null) {
            throw new \RuntimeException('Invariant violation: The content stream "' . $contentStreamId->value
                . '" has NO forked event.', 1658740407);
        }

        if (count($events) === 0) {
            return;
        }
        try {
            $this->eventPersister->publishEvents(
                $commandHandlingDependencies->getContentRepository(),
                new EventsToPublish(
                    $baseWorkspaceContentStreamName->getEventStreamName(),
                    Events::fromArray($events),
                    ExpectedVersion::fromVersion($contentStreamWasForkedEvent->versionOfSourceContentStream)
                )
            );
        } catch (ConcurrencyException $e) {
            throw new BaseWorkspaceHasBeenModifiedInTheMeantime(sprintf(
                'The base workspace has been modified in the meantime; please rebase.'
                . ' Expected version %d of source content stream %s',
                $contentStreamWasForkedEvent->versionOfSourceContentStream->value,
                $baseContentStreamId->value
            ));
        }
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws WorkspaceRebaseFailed
     */
    private function handleRebaseWorkspace(
        RebaseWorkspace $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);
        $oldWorkspaceContentStreamId = $workspace->currentContentStreamId;
        if (!$commandHandlingDependencies->contentStreamExists($oldWorkspaceContentStreamId)) {
            throw new \DomainException('Cannot rebase a workspace with a stateless content stream', 1711718314);
        }
        $oldWorkspaceContentStreamIdState = $commandHandlingDependencies->getContentStreamStatus($oldWorkspaceContentStreamId);

        // 0) close old content stream
        $commandHandlingDependencies->handle(
            CloseContentStream::create($oldWorkspaceContentStreamId)
        );

        // 1) fork a new content stream
        $rebasedContentStreamId = $command->rebasedContentStreamId;
        $commandHandlingDependencies->handle(
            ForkContentStream::create(
                $command->rebasedContentStreamId,
                $baseWorkspace->currentContentStreamId,
            )
        );

        $workspaceStreamName = WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName();
        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamId(
            $workspace->currentContentStreamId
        );

        // 2) extract the commands from the to-be-rebased content stream; and applies them on the new content stream
        $originalCommands = $this->extractCommandsFromContentStreamMetadata($workspaceContentStreamName);
        $commandsThatFailed = new CommandsThatFailedDuringRebase();
        $commandHandlingDependencies->overrideContentStreamId(
            $command->workspaceName,
            $command->rebasedContentStreamId,
            function () use ($originalCommands, $commandHandlingDependencies, &$commandsThatFailed): void {
                foreach ($originalCommands as $sequenceNumber => $originalCommand) {
                    // We no longer need to adjust commands as the workspace stays the same
                    try {
                        $commandHandlingDependencies->handle($originalCommand);
                        // if we came this far, we know the command was applied successfully.
                    } catch (\Exception $e) {
                        $commandsThatFailed = $commandsThatFailed->add(
                            new CommandThatFailedDuringRebase(
                                $sequenceNumber,
                                $originalCommand,
                                $e
                            )
                        );
                    }
                }
            }
        );

        // 3) if we got so far without an exception (or if we don't care), we can switch the workspace's active content stream.
        if ($command->rebaseErrorHandlingStrategy === RebaseErrorHandlingStrategy::STRATEGY_FORCE || $commandsThatFailed->isEmpty()) {
            $events = Events::with(
                new WorkspaceWasRebased(
                    $command->workspaceName,
                    $rebasedContentStreamId,
                    $workspace->currentContentStreamId,
                ),
            );

            return new EventsToPublish(
                $workspaceStreamName,
                $events,
                ExpectedVersion::ANY()
            );
        }

        // 3.E) In case of an exception, reopen the old content stream...
        $commandHandlingDependencies->handle(
            ReopenContentStream::create(
                $oldWorkspaceContentStreamId,
                $oldWorkspaceContentStreamIdState,
            )
        );

        // ... remove the newly created one...
        $commandHandlingDependencies->handle(RemoveContentStream::create(
            $rebasedContentStreamId
        ));

        // ...and throw an exception that contains all the information about what exactly failed
        throw new WorkspaceRebaseFailed($commandsThatFailed, 'Rebase failed', 1711713880);
    }

    /**
     * @return array<int,CommandInterface&RebasableToOtherWorkspaceInterface>
     */
    private function extractCommandsFromContentStreamMetadata(
        ContentStreamEventStreamName $workspaceContentStreamName,
    ): array {
        $workspaceContentStream = $this->eventStore->load($workspaceContentStreamName->getEventStreamName());

        $commands = [];
        foreach ($workspaceContentStream as $eventEnvelope) {
            $metadata = $eventEnvelope->event->metadata?->value ?? [];
            // TODO: Add this logic to the NodeAggregateCommandHandler;
            // so that we can be sure these can be parsed again.
            if (isset($metadata['commandClass'])) {
                $commandToRebaseClass = $metadata['commandClass'];
                $commandToRebasePayload = $metadata['commandPayload'];

                if (array_diff(class_implements($commandToRebaseClass) ?: [], [CommandInterface::class, RebasableToOtherWorkspaceInterface::class]) === []) {
                    throw new \RuntimeException(sprintf(
                        'Command "%s" can\'t be rebased because it does not implement %s',
                        $commandToRebaseClass,
                        RebasableToOtherWorkspaceInterface::class
                    ), 1547815341);
                }
                /** @var class-string<CommandInterface&RebasableToOtherWorkspaceInterface> $commandToRebaseClass */
                /** @var CommandInterface&RebasableToOtherWorkspaceInterface $commandInstance */
                $commandInstance = $commandToRebaseClass::fromArray($commandToRebasePayload);
                $commands[$eventEnvelope->sequenceNumber->value] = $commandInstance;
            }
        }

        return $commands;
    }

    /**
     * This method is like a combined Rebase and Publish!
     *
     * @throws BaseWorkspaceDoesNotExist
     * @throws BaseWorkspaceHasBeenModifiedInTheMeantime
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     * @throws WorkspaceDoesNotExist
     * @throws \Exception
     */
    private function handlePublishIndividualNodesFromWorkspace(
        PublishIndividualNodesFromWorkspace $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $oldWorkspaceContentStreamId = $workspace->currentContentStreamId;
        if (!$commandHandlingDependencies->contentStreamExists($oldWorkspaceContentStreamId)) {
            throw new \DomainException('Cannot publish nodes on a workspace with a stateless content stream', 1710410114);
        }
        $oldWorkspaceContentStreamIdState = $commandHandlingDependencies->getContentStreamStatus($oldWorkspaceContentStreamId);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);

        // 1) close old content stream
        $commandHandlingDependencies->handle(
            CloseContentStream::create($contentGraph->getContentStreamId())
        );

        // 2) separate commands in two parts - the ones MATCHING the nodes from the command, and the REST
        /** @var RebasableToOtherWorkspaceInterface[] $matchingCommands */
        $matchingCommands = [];
        $remainingCommands = [];
        $this->separateMatchingAndRemainingCommands($command, $workspace, $matchingCommands, $remainingCommands);

        // 3) fork a new contentStream, based on the base WS, and apply MATCHING
        $commandHandlingDependencies->handle(
            ForkContentStream::create(
                $command->contentStreamIdForMatchingPart,
                $baseWorkspace->currentContentStreamId,
            )
        );

        try {
            // 4) using the new content stream, apply the matching commands
            $commandHandlingDependencies->overrideContentStreamId(
                $baseWorkspace->workspaceName,
                $command->contentStreamIdForMatchingPart,
                function () use ($matchingCommands, $commandHandlingDependencies, $baseWorkspace): void {
                    foreach ($matchingCommands as $matchingCommand) {
                        if (!($matchingCommand instanceof RebasableToOtherWorkspaceInterface)) {
                            throw new \RuntimeException(
                                'ERROR: The command ' . get_class($matchingCommand)
                                . ' does not implement ' . RebasableToOtherWorkspaceInterface::class . '; but it should!'
                            );
                        }

                        $commandHandlingDependencies->handle($matchingCommand->createCopyForWorkspace(
                            $baseWorkspace->workspaceName,
                        ));
                    }
                }
            );

            // 5) take EVENTS(MATCHING) and apply them to base WS.
            $this->publishContentStream(
                $commandHandlingDependencies,
                $command->contentStreamIdForMatchingPart,
                $baseWorkspace->workspaceName,
                $baseWorkspace->currentContentStreamId
            );

            // 6) fork a new content stream, based on the base WS, and apply REST
            $commandHandlingDependencies->handle(
                ForkContentStream::create(
                    $command->contentStreamIdForRemainingPart,
                    $baseWorkspace->currentContentStreamId
                )
            );


            // 7) apply REMAINING commands to the workspace's new content stream
            $commandHandlingDependencies->overrideContentStreamId(
                $command->workspaceName,
                $command->contentStreamIdForRemainingPart,
                function () use ($commandHandlingDependencies, $remainingCommands) {
                    foreach ($remainingCommands as $remainingCommand) {
                        $commandHandlingDependencies->handle($remainingCommand);
                    }
                }
            );
        } catch (\Exception $exception) {
            // 4.E) In case of an exception, reopen the old content stream and remove the newly created
            $commandHandlingDependencies->handle(
                ReopenContentStream::create(
                    $oldWorkspaceContentStreamId,
                    $oldWorkspaceContentStreamIdState,
                )
            );

            $commandHandlingDependencies->handle(RemoveContentStream::create(
                $command->contentStreamIdForMatchingPart
            ));

            try {
                $commandHandlingDependencies->handle(RemoveContentStream::create(
                    $command->contentStreamIdForRemainingPart
                ));
            } catch (ContentStreamDoesNotExistYet $contentStreamDoesNotExistYet) {
                // in case the exception was thrown before 6), this does not exist
            }

            throw $exception;
        }

        // 8) to avoid dangling content streams, we need to remove our temporary content stream (whose events
        // have already been published) as well as the old one
        $commandHandlingDependencies->handle(RemoveContentStream::create(
            $command->contentStreamIdForMatchingPart
        ));
        $commandHandlingDependencies->handle(RemoveContentStream::create(
            $oldWorkspaceContentStreamId
        ));

        $streamName = WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::fromArray([
                new WorkspaceWasPartiallyPublished(
                    $command->workspaceName,
                    $baseWorkspace->workspaceName,
                    $command->contentStreamIdForRemainingPart,
                    $oldWorkspaceContentStreamId,
                    $command->nodesToPublish
                )
            ]),
            ExpectedVersion::ANY()
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
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $oldWorkspaceContentStreamId = $contentGraph->getContentStreamId();
        if (!$commandHandlingDependencies->contentStreamExists($contentGraph->getContentStreamId())) {
            throw new \DomainException('Cannot discard nodes on a workspace with a stateless content stream', 1710408112);
        }
        $oldWorkspaceContentStreamIdState = $commandHandlingDependencies->getContentStreamStatus($contentGraph->getContentStreamId());
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);

        // 1) close old content stream
        $commandHandlingDependencies->handle(
            CloseContentStream::create($oldWorkspaceContentStreamId)
        );

        // 2) filter commands, only keeping the ones NOT MATCHING the nodes from the command
        // (i.e. the modifications we want to keep)
        $commandsToDiscard = [];
        $commandsToKeep = [];
        $this->separateMatchingAndRemainingCommands($command, $workspace, $commandsToDiscard, $commandsToKeep);

        // 3) fork a new contentStream, based on the base WS, and apply the commands to keep
        $commandHandlingDependencies->handle(
            ForkContentStream::create(
                $command->newContentStreamId,
                $baseWorkspace->currentContentStreamId,
            )
        );

        // 4) using the new content stream, apply the commands to keep
        try {
            $commandHandlingDependencies->overrideContentStreamId(
                $baseWorkspace->workspaceName,
                $command->newContentStreamId,
                function () use ($commandsToKeep, $commandHandlingDependencies, $baseWorkspace): void {
                    foreach ($commandsToKeep as $matchingCommand) {
                        $commandHandlingDependencies->handle($matchingCommand->createCopyForWorkspace(
                            $baseWorkspace->workspaceName,
                        ));
                    }
                }
            );
        } catch (\Exception $exception) {
            // 4.E) In case of an exception, reopen the old content stream and remove the newly created
            $commandHandlingDependencies->handle(
                ReopenContentStream::create(
                    $oldWorkspaceContentStreamId,
                    $oldWorkspaceContentStreamIdState,
                )
            );

            $commandHandlingDependencies->handle(RemoveContentStream::create(
                $command->newContentStreamId
            ));

            throw $exception;
        }

        // 5) If everything worked, to avoid dangling content streams, we need to remove the old content stream
        $commandHandlingDependencies->handle(RemoveContentStream::create(
            $oldWorkspaceContentStreamId
        ));

        $streamName = WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new WorkspaceWasPartiallyDiscarded(
                    $command->workspaceName,
                    $command->newContentStreamId,
                    $workspace->currentContentStreamId,
                    $command->nodesToDiscard,
                )
            ),
            ExpectedVersion::ANY()
        );
    }


    /**
     * @param array<int,RebasableToOtherWorkspaceInterface&CommandInterface> &$matchingCommands
     * @param array<int,RebasableToOtherWorkspaceInterface&CommandInterface> &$remainingCommands
     * @param-out array<int,RebasableToOtherWorkspaceInterface&CommandInterface> $matchingCommands
     * @param-out array<int,RebasableToOtherWorkspaceInterface&CommandInterface> $remainingCommands
     */
    private function separateMatchingAndRemainingCommands(
        PublishIndividualNodesFromWorkspace|DiscardIndividualNodesFromWorkspace $command,
        Workspace $workspace,
        array &$matchingCommands,
        array &$remainingCommands
    ): void {
        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamId(
            $workspace->currentContentStreamId
        );

        $originalCommands = $this->extractCommandsFromContentStreamMetadata($workspaceContentStreamName);

        foreach ($originalCommands as $originalCommand) {
            if (!$originalCommand instanceof MatchableWithNodeIdToPublishOrDiscardInterface) {
                throw new \Exception(
                    'Command class ' . get_class($originalCommand) . ' does not implement '
                    . MatchableWithNodeIdToPublishOrDiscardInterface::class,
                    1645393655
                );
            }
            $nodeIds = $command instanceof PublishIndividualNodesFromWorkspace
                ? $command->nodesToPublish
                : $command->nodesToDiscard;
            if ($this->commandMatchesAtLeastOneNode($originalCommand, $nodeIds)) {
                $matchingCommands[] = $originalCommand;
            } else {
                $remainingCommands[] = $originalCommand;
            }
        }
    }

    private function commandMatchesAtLeastOneNode(
        MatchableWithNodeIdToPublishOrDiscardInterface $command,
        NodeIdsToPublishOrDiscard $nodeIds,
    ): bool {
        foreach ($nodeIds as $nodeId) {
            if ($command->matchesNodeId($nodeId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws WorkspaceHasNoBaseWorkspaceName
     */
    private function handleDiscardWorkspace(
        DiscardWorkspace $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);

        $newContentStream = $command->newContentStreamId;
        $commandHandlingDependencies->handle(
            ForkContentStream::create(
                $newContentStream,
                $baseWorkspace->currentContentStreamId,
            )
        );

        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $streamName = WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName();
        $events = Events::with(
            new WorkspaceWasDiscarded(
                $command->workspaceName,
                $newContentStream,
                $workspace->currentContentStreamId,
            )
        );

        return new EventsToPublish(
            $streamName,
            $events,
            ExpectedVersion::ANY()
        );
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
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $this->requireEmptyWorkspace($workspace);
        $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);

        $this->requireNonCircularRelationBetweenWorkspaces($workspace, $baseWorkspace, $commandHandlingDependencies);

        $commandHandlingDependencies->handle(
            ForkContentStream::create(
                $command->newContentStreamId,
                $baseWorkspace->currentContentStreamId,
            )
        );

        $streamName = WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName();
        $events = Events::with(
            new WorkspaceBaseWorkspaceWasChanged(
                $command->workspaceName,
                $command->baseWorkspaceName,
                $command->newContentStreamId,
            )
        );

        return new EventsToPublish(
            $streamName,
            $events,
            ExpectedVersion::ANY()
        );
    }

    /**
     * @throws WorkspaceDoesNotExist
     */
    private function handleDeleteWorkspace(
        DeleteWorkspace $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);

        $commandHandlingDependencies->handle(
            RemoveContentStream::create(
                $workspace->currentContentStreamId
            )
        );

        $events = Events::with(
            new WorkspaceWasRemoved(
                $command->workspaceName,
            )
        );

        $streamName = WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName();
        return new EventsToPublish(
            $streamName,
            $events,
            ExpectedVersion::ANY()
        );
    }

    private function requireWorkspaceToNotExist(WorkspaceName $workspaceName, CommandHandlingDependencies $commandHandlingDependencies): void
    {
        try {
            $commandHandlingDependencies->getContentGraph($workspaceName);
        } catch (WorkspaceDoesNotExist) {
            // Desired outcome
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
        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamId(
            $workspace->currentContentStreamId
        );
        if ($this->hasEventsInContentStreamExceptForking($workspaceContentStreamName)) {
            throw new WorkspaceIsNotEmptyException('The user workspace needs to be empty before switching the base workspace.', 1681455989);
        }
    }

    /**
     * @return bool
     */
    private function hasEventsInContentStreamExceptForking(
        ContentStreamEventStreamName $workspaceContentStreamName,
    ): bool {
        $workspaceContentStream = $this->eventStore->load($workspaceContentStreamName->getEventStreamName());

        $fullQualifiedEventClassName = ContentStreamWasForked::class;
        $shortEventClassName = substr($fullQualifiedEventClassName, strrpos($fullQualifiedEventClassName, '\\') + 1);

        foreach ($workspaceContentStream as $eventEnvelope) {
            if ($eventEnvelope->event->type->value === EventType::fromString($shortEventClassName)->value) {
                continue;
            }
            return true;
        }

        return false;
    }
}
