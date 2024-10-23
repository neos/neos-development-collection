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

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandSimulatorFactory;
use Neos\ContentRepository\Core\CommandHandler\ControlFlowAwareCommandHandlerInterface;
use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\DecoratedEvent;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\EventStore\EventsToPublishFailed;
use Neos\ContentRepository\Core\Feature\Common\MatchableWithNodeIdToPublishOrDiscardInterface;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;
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
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 */
final readonly class WorkspaceCommandHandler implements ControlFlowAwareCommandHandlerInterface
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
        if ($commandHandlingDependencies->findWorkspaceByName($command->baseWorkspaceName) === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf(
                'The workspace %s (base workspace of %s) does not exist',
                $command->baseWorkspaceName->value,
                $command->workspaceName->value
            ), 1513890708);
        }

        $baseWorkspaceContentGraph = $commandHandlingDependencies->getContentGraph($command->baseWorkspaceName);
        // When the workspace is created, we first have to fork the content stream
        yield $this->forkContentStream(
            $command->newContentStreamId,
            $baseWorkspaceContentGraph->getContentStreamId(),
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
        if (!$commandHandlingDependencies->contentStreamExists($workspace->currentContentStreamId)) {
            throw new \RuntimeException('Cannot publish nodes on a workspace with a stateless content stream', 1729711258);
        }
        $oldWorkspaceContentStreamIdState = $commandHandlingDependencies->getContentStreamStatus($workspace->currentContentStreamId);

        // todo if workspace is outdated do an implicit rebase

        yield $this->closeContentStream(
            $workspace->currentContentStreamId,
            $commandHandlingDependencies
        );

        $publishContentStream = $this->getCopiedEventsToPublishForContentStream(
            $workspace->currentContentStreamId,
            $baseWorkspace->workspaceName,
            $baseWorkspace->currentContentStreamId
        );

        if ($publishContentStream->events->isEmpty()) {
            // if there are no events this is almost a noop
            yield $this->reopenContentStream(
                $workspace->currentContentStreamId,
                $oldWorkspaceContentStreamIdState,
                $commandHandlingDependencies
            );

            return;
        }

        try {
            yield $publishContentStream;
        } catch (ConcurrencyException $exception) {
            yield $this->reopenContentStream(
                $workspace->currentContentStreamId,
                $oldWorkspaceContentStreamIdState,
                $commandHandlingDependencies
            );

            throw new BaseWorkspaceHasBeenModifiedInTheMeantime(sprintf(
                'The base workspace has been modified in the meantime; please rebase.'
                . ' Expected version %d of source content stream %s',
                $publishContentStream->expectedVersion->value,
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
     * 1.) Copy all events from the passed event stream which implement the {@see PublishableToOtherContentStreamsInterface}
     * 2.) Extract the initial ContentStreamWasForked event, to read the version of the source content stream when the fork occurred
     * 3.) Use the {@see ContentStreamWasForked::$versionOfSourceContentStream} to ensure that no other changes have been done in the meantime in the base content stream
     *
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
                /** @var EventInterface $copiedEvent */
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
        $oldWorkspaceContentStreamId = $workspace->currentContentStreamId;
        if (!$commandHandlingDependencies->contentStreamExists($oldWorkspaceContentStreamId)) {
            throw new \DomainException('Cannot rebase a workspace with a stateless content stream', 1711718314);
        }
        $oldWorkspaceContentStreamIdState = $commandHandlingDependencies->getContentStreamStatus($oldWorkspaceContentStreamId);

        // FIXME no-op if workspace is not outdated and not RebaseErrorHandlingStrategy::STRATEGY_FORCE.

        // 0) close old content stream
        yield $this->closeContentStream(
            $oldWorkspaceContentStreamId,
            $commandHandlingDependencies
        );

        // 2) extract the commands from the to-be-rebased content stream; and applies them on the new content stream
        $originalCommands = $this->extractCommandsFromContentStreamMetadata(
            ContentStreamEventStreamName::fromContentStreamId(
                $workspace->currentContentStreamId
            )
        );

        if ($originalCommands === []) {
            // if we have no changes in the workspace we can fork from the base directly
            yield $this->forkContentStream(
                $command->rebasedContentStreamId,
                $baseWorkspace->currentContentStreamId,
                $commandHandlingDependencies
            );

            yield new EventsToPublish(
                WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
                Events::with(
                    new WorkspaceWasRebased(
                        $command->workspaceName,
                        $command->rebasedContentStreamId,
                        $workspace->currentContentStreamId,
                    ),
                ),
                ExpectedVersion::ANY()
            );

            return;
        }

        $commandSimulator = $this->commandSimulatorFactory->createSimulator($baseWorkspace->workspaceName);

        $commandsThatFailed = $commandSimulator->run(
            static function () use ($commandSimulator, $originalCommands): CommandsThatFailedDuringRebase {
                $commandsThatFailed = new CommandsThatFailedDuringRebase();
                foreach ($originalCommands as $sequenceNumber => $originalCommand) {
                    // We no longer need to adjust commands as the workspace stays the same
                    try {
                        // We rebase here, but we apply the commands in the simulation on the base workspace so the constraint checks work
                        $commandSimulator->handle($originalCommand);
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

                return $commandsThatFailed;
            }
        );


        // 3) if we got so far without an exception (or if we don't care), we can switch the workspace's active content stream.
        if ($command->rebaseErrorHandlingStrategy === RebaseErrorHandlingStrategy::STRATEGY_FORCE || $commandsThatFailed->isEmpty()) {
            $forkContentStream = $this->forkContentStream(
                $command->rebasedContentStreamId,
                $baseWorkspace->currentContentStreamId,
                $commandHandlingDependencies
            );

            yield new EventsToPublish(
                $forkContentStream->streamName,
                $forkContentStream->events->merge(
                    $this->getCopiedEventsOfEventStream(
                        $command->workspaceName,
                        $command->rebasedContentStreamId,
                        $commandSimulator->eventStream(),
                    )
                ),
                $forkContentStream->expectedVersion
            );

            yield new EventsToPublish(
                WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
                Events::with(
                    new WorkspaceWasRebased(
                        $command->workspaceName,
                        $command->rebasedContentStreamId,
                        $workspace->currentContentStreamId,
                    ),
                ),
                ExpectedVersion::ANY()
            );

            return null;
        }

        // 3.E) In case of an exception, reopen the old content stream...
        yield $this->reopenContentStream(
            $oldWorkspaceContentStreamId,
            $oldWorkspaceContentStreamIdState,
            $commandHandlingDependencies
        );

        // ...and throw an exception that contains all the information about what exactly failed toto improve message!
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
            /**
             * the metadata will be added to all readable commands via
             * @see \Neos\ContentRepository\Core\Feature\Common\NodeAggregateEventPublisher::enrichWithCommand
             */
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
    ): \Generator {
        if ($command->nodesToPublish->isEmpty()) {
            // noop
            return;
        }

        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $oldWorkspaceContentStreamId = $workspace->currentContentStreamId;
        if (!$commandHandlingDependencies->contentStreamExists($oldWorkspaceContentStreamId)) {
            throw new \DomainException('Cannot publish nodes on a workspace with a stateless content stream', 1710410114);
        }
        $oldWorkspaceContentStreamIdState = $commandHandlingDependencies->getContentStreamStatus($oldWorkspaceContentStreamId);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);
        $baseContentStreamVersion = $commandHandlingDependencies->getContentStreamVersion($baseWorkspace->currentContentStreamId);

        // 1) close old content stream
        yield $this->closeContentStream(
            $contentGraph->getContentStreamId(),
            $commandHandlingDependencies
        );

        // 2) separate commands in two parts - the ones MATCHING the nodes from the command, and the REST
        $matchingCommands = [];
        $remainingCommands = [];
        $this->separateMatchingAndRemainingCommands($command, $workspace, $matchingCommands, $remainingCommands);

        if ($matchingCommands === []) {
            // almost noop (e.g. random node ids were specified) ;)
            yield $this->reopenContentStream(
                $oldWorkspaceContentStreamId,
                $oldWorkspaceContentStreamIdState,
                $commandHandlingDependencies
            );
            return null;
        }

        // TODO if $remainingCommands === [] try to do a full publish, but we need to rebase if the workspace is outdated!
        $commandSimulator = $this->commandSimulatorFactory->createSimulator($baseWorkspace->workspaceName);

        try {
            // 4) using the new content stream, apply the matching commands
            $highestVersionForMatching = $commandSimulator->run(
                static function () use ($commandSimulator, $matchingCommands, $remainingCommands): SequenceNumber {
                    foreach ($matchingCommands as $matchingCommand) {
                        $commandSimulator->handle($matchingCommand);
                    }

                    $highestVersionForMatching = $commandSimulator->currentSequenceNumber();

                    foreach ($remainingCommands as $remainingCommand) {
                        $commandSimulator->handle($remainingCommand);
                    }

                    return $highestVersionForMatching;
                }
            );


            // 5) take EVENTS(MATCHING) and apply them to base WS.


            // // 7) apply REMAINING commands to the workspace's new content stream

        } catch (\Exception $exception) {
            // 4.E) In case of an exception, reopen the old content stream and remove the newly created
            yield $this->reopenContentStream(
                $oldWorkspaceContentStreamId,
                $oldWorkspaceContentStreamIdState,
                $commandHandlingDependencies
            );

            throw $exception;
        }

        if ($highestVersionForMatching->equals(SequenceNumber::none())) {
            // still a noop ;) (for example when a command returns empty events e.g. the node was already tagged with this subtree tag)
            yield $this->reopenContentStream(
                $oldWorkspaceContentStreamId,
                $oldWorkspaceContentStreamIdState,
                $commandHandlingDependencies
            );

            return null;
        }

        yield new EventsToPublish(
            ContentStreamEventStreamName::fromContentStreamId($baseWorkspace->currentContentStreamId)
                ->getEventStreamName(),
            $this->getCopiedEventsOfEventStream(
                $baseWorkspace->workspaceName,
                $baseWorkspace->currentContentStreamId,
                $commandSimulator->eventStream()->withMaximumSequenceNumber($highestVersionForMatching),
            ),
            ExpectedVersion::fromVersion($baseContentStreamVersion)
        );

        // 6) fork a new content stream, based on the base WS, and apply REST
        $forkContentStream = $this->forkContentStream(
            $command->contentStreamIdForRemainingPart,
            $baseWorkspace->currentContentStreamId,
            $commandHandlingDependencies
        );

        yield new EventsToPublish(
            $forkContentStream->streamName,
            $forkContentStream->events->merge(
                $this->getCopiedEventsOfEventStream(
                    $command->workspaceName,
                    $command->contentStreamIdForRemainingPart,
                    $commandSimulator->eventStream()->withMinimumSequenceNumber($highestVersionForMatching->previous()),
                )
            ),
            $forkContentStream->expectedVersion
        );

        // 8) to avoid dangling content streams, we need to remove our temporary content stream (whose events
        // have already been published) as well as the old one
        yield $this->removeContentStream($oldWorkspaceContentStreamId, $commandHandlingDependencies);

        yield new EventsToPublish(
            WorkspaceEventStreamName::fromWorkspaceName($command->workspaceName)->getEventStreamName(),
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
    ): \Generator {
        if ($command->nodesToDiscard->isEmpty()) {
            // noop
            return;
        }

        $contentGraph = $commandHandlingDependencies->getContentGraph($command->workspaceName);
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $oldWorkspaceContentStreamId = $contentGraph->getContentStreamId();
        if (!$commandHandlingDependencies->contentStreamExists($contentGraph->getContentStreamId())) {
            throw new \DomainException('Cannot discard nodes on a workspace with a stateless content stream', 1710408112);
        }
        $oldWorkspaceContentStreamIdState = $commandHandlingDependencies->getContentStreamStatus($contentGraph->getContentStreamId());
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);

        // 1) close old content stream
        yield $this->closeContentStream(
            $oldWorkspaceContentStreamId,
            $commandHandlingDependencies
        );

        // 2) filter commands, only keeping the ones NOT MATCHING the nodes from the command
        // (i.e. the modifications we want to keep)
        $commandsToDiscard = [];
        $commandsToKeep = [];
        $this->separateMatchingAndRemainingCommands($command, $workspace, $commandsToDiscard, $commandsToKeep);

        if ($commandsToDiscard === []) {
            // if we have nothing to discard, we can just keep all. (e.g. random node ids were specified) It's almost a noop ;)
            yield $this->reopenContentStream(
                $oldWorkspaceContentStreamId,
                $oldWorkspaceContentStreamIdState,
                $commandHandlingDependencies
            );
            return;
        }

        if ($commandsToKeep === []) {
            // quick path everything was discarded we just branch of from the base

            yield from $this->discardWorkspace(
                $workspace,
                $baseWorkspace,
                $command->newContentStreamId,
                $commandHandlingDependencies
            );
            return;
        }

        $commandSimulator = $this->commandSimulatorFactory->createSimulator($baseWorkspace->workspaceName);

        // 4) using the new content stream, apply the commands to keep
        try {
            $commandSimulator->run(
                static function () use ($commandSimulator, $commandsToKeep): void {
                    foreach ($commandsToKeep as $matchingCommand) {
                        $commandSimulator->handle($matchingCommand);
                    }
                }
            );
        } catch (\Exception $exception) {
            // 4.E) In case of an exception, reopen the old content stream and remove the newly created
            yield $this->reopenContentStream(
                $oldWorkspaceContentStreamId,
                $oldWorkspaceContentStreamIdState,
                $commandHandlingDependencies
            );
            throw $exception;
        }

        // 5) If everything worked, to avoid dangling content streams, we need to remove the old content stream

        // 3) fork a new contentStream, based on the base WS, and apply the commands to keep
        $forkContentStream = $this->forkContentStream(
            $command->newContentStreamId,
            $baseWorkspace->currentContentStreamId,
            $commandHandlingDependencies
        );

        yield new EventsToPublish(
            $forkContentStream->streamName,
            $forkContentStream->events->merge(
                $this->getCopiedEventsOfEventStream(
                    $command->workspaceName,
                    $command->newContentStreamId,
                    $commandSimulator->eventStream(),
                )
            ),
            $forkContentStream->expectedVersion
        );


        yield new EventsToPublish(
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
    ): \Generator {
        $workspace = $this->requireWorkspace($command->workspaceName, $commandHandlingDependencies);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);

        return $this->discardWorkspace(
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
        // todo only discard if changes, needs new changes flag on the Workspace model
        yield $this->forkContentStream(
            $newContentStream,
            $baseWorkspace->currentContentStreamId,
            $commandHandlingDependencies
        );

        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
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
        $this->requireEmptyWorkspace($workspace);
        $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);
        $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);

        $this->requireNonCircularRelationBetweenWorkspaces($workspace, $baseWorkspace, $commandHandlingDependencies);

        yield $this->forkContentStream(
            $command->newContentStreamId,
            $baseWorkspace->currentContentStreamId,
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
