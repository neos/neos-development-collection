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

namespace Neos\ContentRepository\Feature;

use Neos\ContentRepository\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\EventStore\DecoratedEvent;
use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\EventStore\EventsToPublish;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Command\DiscardWorkspace;
use Neos\ContentRepository\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Feature\WorkspaceRebase\WorkspaceRebaseStatistics;
use Neos\ContentRepository\Feature\ContentStreamCreation\Command\CreateContentStream;
use Neos\ContentRepository\Feature\ContentStreamForking\Command\ForkContentStream;
use Neos\ContentRepository\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamAlreadyExists;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\Feature\Common\RebasableToOtherContentStreamsInterface;
use Neos\ContentRepository\Feature\Common\PublishableToOtherContentStreamsInterface;
use Neos\ContentRepository\Feature\Common\MatchableWithNodeAddressInterface;
use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist;
use Neos\ContentRepository\Feature\WorkspacePublication\Exception\BaseWorkspaceHasBeenModifiedInTheMeantime;
use Neos\ContentRepository\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Feature\Common\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Feature\Common\Exception\WorkspaceHasNoBaseWorkspaceName;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\ContentRepository\EventStore\EventInterface;
use Neos\EventStore\Model\Event\StreamName;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * WorkspaceCommandHandler
 */
final class WorkspaceCommandHandler implements CommandHandlerInterface
{
    protected WorkspaceFinder $workspaceFinder;

    protected ReadSideMemoryCacheManager $readSideMemoryCacheManager;

    public function __construct(
        WorkspaceFinder $workspaceFinder,
        ReadSideMemoryCacheManager $readSideMemoryCacheManager,
    ) {
        $this->workspaceFinder = $workspaceFinder;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
    }


    public function canHandle(CommandInterface $command): bool
    {
        return $command instanceof CreateWorkspace
            || $command instanceof CreateRootWorkspace
            || $command instanceof PublishWorkspace
            || $command instanceof RebaseWorkspace
            || $command instanceof PublishIndividualNodesFromWorkspace
            || $command instanceof DiscardIndividualNodesFromWorkspace
            || $command instanceof DiscardWorkspace;
    }

    public function handle(CommandInterface $command, ContentRepository $contentRepository): EventsToPublish
    {
        $this->readSideMemoryCacheManager->disableCache();

        if ($command instanceof CreateWorkspace) {
            return $this->handleCreateWorkspace($command, $contentRepository);
        } elseif ($command instanceof CreateRootWorkspace) {
            return $this->handleCreateRootWorkspace($command, $contentRepository);
        } elseif ($command instanceof PublishWorkspace) {
            return $this->handlePublishWorkspace($command, $contentRepository);
        } elseif ($command instanceof RebaseWorkspace) {
            return $this->handleRebaseWorkspace($command, $contentRepository);
        } elseif ($command instanceof PublishIndividualNodesFromWorkspace) {
            return $this->handlePublishIndividualNodesFromWorkspace($command, $contentRepository);
        } elseif ($command instanceof DiscardIndividualNodesFromWorkspace) {
            return $this->handleDiscardIndividualNodesFromWorkspace($command, $contentRepository);
        } elseif ($command instanceof DiscardWorkspace) {
            return $this->handleDiscardWorkspace($command, $contentRepository);
        }

        throw new \RuntimeException('invalid command');
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     * @throws WorkspaceAlreadyExists
     */
    private function handleCreateWorkspace(CreateWorkspace $command, ContentRepository $contentRepository): EventsToPublish
    {
        $existingWorkspace = $this->workspaceFinder->findOneByName($command->workspaceName);
        if ($existingWorkspace !== null) {
            throw new WorkspaceAlreadyExists(sprintf(
                'The workspace %s already exists',
                $command->workspaceName
            ), 1505830958921);
        }

        $baseWorkspace = $this->workspaceFinder->findOneByName($command->baseWorkspaceName);
        if ($baseWorkspace === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf(
                'The workspace %s (base workspace of %s) does not exist',
                $command->baseWorkspaceName,
                $command->workspaceName
            ), 1513890708);
        }

        // When the workspace is created, we first have to fork the content stream
        $contentRepository->handle(
            new ForkContentStream(
                $command->newContentStreamIdentifier,
                $baseWorkspace->getCurrentContentStreamIdentifier(),
                $command->initiatingUserIdentifier
            )
        )->block(); // TODO: we did not block here before, but we need to do this now. Is this a problem? I guess not

        $events = Events::with(
            new WorkspaceWasCreated(
                $command->workspaceName,
                $command->baseWorkspaceName,
                $command->workspaceTitle,
                $command->workspaceDescription,
                $command->initiatingUserIdentifier,
                $command->newContentStreamIdentifier,
                $command->workspaceOwner
            ),
        );

        return new EventsToPublish(
            StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->workspaceName),
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
    public function handleCreateRootWorkspace(CreateRootWorkspace $command, ContentRepository $contentRepository): EventsToPublish
    {
        $existingWorkspace = $this->workspaceFinder->findOneByName($command->workspaceName);
        if ($existingWorkspace !== null) {
            throw new WorkspaceAlreadyExists(sprintf(
                'The workspace %s already exists',
                $command->workspaceName
            ), 1505848624450);
        }

        $contentStreamIdentifier = $command->newContentStreamIdentifier;
        $contentRepository->handle(
            new CreateContentStream(
                $contentStreamIdentifier,
                $command->initiatingUserIdentifier
            )
        )->block(); // TODO: we did not block here before, but we need to do this now. Is this a problem? I guess not

        $events = Events::with(
            new RootWorkspaceWasCreated(
                $command->workspaceName,
                $command->workspaceTitle,
                $command->workspaceDescription,
                $command->initiatingUserIdentifier,
                $contentStreamIdentifier
            )
        );

        return new EventsToPublish(
            StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->workspaceName),
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
    public function handlePublishWorkspace(PublishWorkspace $command, ContentRepository $contentRepository): EventsToPublish
    {
        $workspace = $this->requireWorkspace($command->getWorkspaceName());
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        // TODO!!!
        $commandResult = $this->publishContentStream(
            $workspace->getCurrentContentStreamIdentifier(),
            $baseWorkspace->getCurrentContentStreamIdentifier()
        );

        // After publishing a workspace, we need to again fork from Base.
        $newContentStream = ContentStreamIdentifier::create();
        $contentRepository->handle(
            new ForkContentStream(
                $newContentStream,
                $baseWorkspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        )->block();

        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $events = Events::with(
            new WorkspaceWasPublished(
                $command->getWorkspaceName(),
                $baseWorkspace->getWorkspaceName(),
                $newContentStream,
                $workspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        );
        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        return new EventsToPublish(
            $streamName,
            $events,
            ExpectedVersion::ANY()
        );
    }

    /**
     * @throws BaseWorkspaceHasBeenModifiedInTheMeantime
     * @throws \Exception
     */
    private function publishContentStream(
        ContentStreamIdentifier $contentStreamIdentifier,
        ContentStreamIdentifier $baseContentStreamIdentifier,
        ContentRepository $contentRepository
    ): void {
        $contentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);
        $baseWorkspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $baseContentStreamIdentifier
        );

        // TODO: please check the code below in-depth. it does:
        // - copy all events from the "user" content stream which implement "PublishableToOtherContentStreamsInterface"
        // - extract the initial ContentStreamWasForked event,
        //   to read the version of the source content stream when the fork occurred
        // - ensure that no other changes have been done in the meantime in the base content stream


        $streamName = $contentStreamName->getEventStreamName();

        /** @var array<int,EventEnvelope> $workspaceContentStream */
        // TODO: HOW TO DO THIS? (EVENT STORE LOAD!)
        $workspaceContentStream = iterator_to_array($this->eventStore->load($streamName));

        $events = [];
        foreach ($workspaceContentStream as $eventEnvelope) {
            assert($eventEnvelope instanceof EventEnvelope);
            // TODO: How to trigger deserialization here properly?
            $event = $this->eventNormalizer->denormalize($eventEnvelope->event);
            if ($event instanceof PublishableToOtherContentStreamsInterface) {
                /** @var EventInterface $copiedEvent */
                $copiedEvent = $event->createCopyForContentStream($baseContentStreamIdentifier);
                // We need to add the event metadata here for rebasing in nested workspace situations
                // (and for exporting)
                $events[] = DecoratedEvent::withMetadata(
                    $copiedEvent,
                    $eventEnvelope->event->metadata
                );
            }
        }

        $contentStreamWasForked = self::extractSingleForkedContentStreamEvent($workspaceContentStream);
        try {
            // TODO: HOW TO PERSIST TO EVENT STORE??
            // TODO: We cannot easily return EventsToPublish, because publishContentStream() is the low-level
            // TODO: API which is used MULTIPLE times during a publishing. So we'd need a way to trigger
            // TODO: ContentRepository::handle WITHOUT the actual command handling logic.
            // TODO:
            // TODO: It's also not really practical to create a new command just for this usecase, because
            // TODO: we are operating directly on events here and copy them over to the base event stream
            // TODO: if nothing has changed in the meantime.
            $this->eventStore->commit(
                $baseWorkspaceContentStreamName->getEventStreamName(),
                $events,
                $contentStreamWasForked->versionOfSourceContentStream
            );
            return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
        } catch (ConcurrencyException $e) {
            // TODO: and I'd love to keep this useful exception in here.
            throw new BaseWorkspaceHasBeenModifiedInTheMeantime(sprintf(
                'The base workspace has been modified in the meantime; please rebase.'
                    . ' Expected version %d of source content stream %s',
                $contentStreamWasForked->versionOfSourceContentStream,
                $baseContentStreamIdentifier
            ));
        }
    }

    /**
     * @param array<int,EventEnvelope> $stream
     * @throws \Exception
     */
    private static function extractSingleForkedContentStreamEvent(array $stream): ContentStreamWasForked
    {
        /** @var array<int,EventEnvelope> $contentStreamWasForkedEvents */
        $contentStreamWasForkedEvents = array_filter($stream, function (EventEnvelope $eventEnvelope) {
            // TODO: is this so good? at least it is performant
            return $eventEnvelope->event->type->value === 'ContentStreamWasForked';
        });

        if (count($contentStreamWasForkedEvents) !== 1) {
            throw new \Exception(sprintf(
                'TODO: only can publish a content stream which has exactly one ContentStreamWasForked; we found %d',
                count($contentStreamWasForkedEvents)
            ));
        }

        /** @var EventEnvelope $primaryEventEnvelope cannot be false because there is exactly one event inside */
        $primaryEventEnvelope = reset($contentStreamWasForkedEvents);
        /** @var ContentStreamWasForked $primaryDomainEvent */
        // TODO: somehow convert to the domain event
        $primaryDomainEvent = $primaryEventEnvelope->getDomainEvent();

        $firstEventEnvelope = reset($stream);

        if (!$firstEventEnvelope || $primaryDomainEvent !== $firstEventEnvelope->getDomainEvent()) {
            throw new \Exception(sprintf(
                'TODO: stream has to start with a single ContentStreamWasForked event, found %s',
                $firstEventEnvelope ? get_class($firstEventEnvelope->getDomainEvent()) : 'nothing'
            ));
        }

        return $primaryDomainEvent;
    }

    /**
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws \Exception
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function handleRebaseWorkspace(RebaseWorkspace $command, ContentRepository $contentRepository): EventsToPublish
    {
        $workspace = $this->requireWorkspace($command->getWorkspaceName());
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        // TODO: please check the code below in-depth. it does:
        // - fork a new content stream
        // - extract the commands from the to-be-rebased content stream; and applies them on the new content stream
        $rebasedContentStream = $command->getRebasedContentStreamIdentifier();
        $contentRepository->handle(
            new ForkContentStream(
                $rebasedContentStream,
                $baseWorkspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        )->block();

        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $workspace->getCurrentContentStreamIdentifier()
        );

        $originalCommands = $this->extractCommandsFromContentStreamMetadata($workspaceContentStreamName);
        $rebaseStatistics = new WorkspaceRebaseStatistics();
        foreach ($originalCommands as $i => $originalCommand) {
            if (!($originalCommand instanceof RebasableToOtherContentStreamsInterface)) {
                throw new \RuntimeException(
                    'ERROR: The command ' . get_class($originalCommand)
                        . ' does not implement RebasableToOtherContentStreamsInterface; but it should!'
                );
            }

            // try to apply the command on the rebased content stream
            $commandToRebase = $originalCommand->createCopyForContentStream($rebasedContentStream);
            try {
                $contentRepository->handle($commandToRebase)->block();
                // if we came this far, we know the command was applied successfully.
                $rebaseStatistics->commandRebaseSuccess();
            } catch (\Exception $e) {
                $fullCommandListSoFar = '';
                for ($a = 0; $a <= $i; $a++) {
                    $fullCommandListSoFar .= "\n - " . get_class($originalCommands[$a]);

                    if ($originalCommands[$a] instanceof \JsonSerializable) {
                        $fullCommandListSoFar .= ' ' . json_encode($originalCommands[$a]);
                    }
                }

                $rebaseStatistics->commandRebaseError(sprintf(
                    "The content stream %s cannot be rebased. Error with command %d (%s)"
                        . " - see nested exception for details.\n\n The base workspace %s is at content stream %s."
                        . "\n The full list of commands applied so far is: %s",
                    $workspaceContentStreamName,
                    $i,
                    get_class($commandToRebase),
                    $baseWorkspace->getWorkspaceName(),
                    $baseWorkspace->getCurrentContentStreamIdentifier(),
                    $fullCommandListSoFar
                ), $e);
            }
        }

        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());

        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        if (!$rebaseStatistics->hasErrors()) {
            $events = Events::with(
                new WorkspaceWasRebased(
                    $command->getWorkspaceName(),
                    $rebasedContentStream,
                    $workspace->getCurrentContentStreamIdentifier(),
                    $command->getInitiatingUserIdentifier()
                ),
            );

            return new EventsToPublish(
                $streamName,
                $events,
                ExpectedVersion::ANY()
            );
        } else {
            // an error occurred during the rebase; so we need to record this using a "WorkspaceRebaseFailed" event.

            $event = Events::with(
                new WorkspaceRebaseFailed(
                    $command->getWorkspaceName(),
                    $rebasedContentStream,
                    $workspace->getCurrentContentStreamIdentifier(),
                    $command->getInitiatingUserIdentifier(),
                    $rebaseStatistics->getErrors()
                )
            );

            return new EventsToPublish(
                $streamName,
                $event,
                ExpectedVersion::ANY()
            );
        }
    }

    /**
     * @return array<int,object>
     */
    private function extractCommandsFromContentStreamMetadata(
        ContentStreamEventStreamName $workspaceContentStreamName
    ): array {
        $workspaceContentStream = $this->eventStore->load($workspaceContentStreamName->getEventStreamName());

        $commands = [];
        foreach ($workspaceContentStream as $eventAndRawEvent) {
            $metadata = $eventAndRawEvent->getRawEvent()->getMetadata();
            // TODO: Add this logic to the NodeAggregateCommandHandler;
            // so that we can be sure these can be parsed again.
            if (isset($metadata['commandClass'])) {
                $commandToRebaseClass = $metadata['commandClass'];
                $commandToRebasePayload = $metadata['commandPayload'];
                if (!method_exists($commandToRebaseClass, 'fromArray')) {
                    throw new \RuntimeException(sprintf(
                        'Command "%s" can\'t be rebased because it does not implement a static "fromArray" constructor',
                        $commandToRebaseClass
                    ), 1547815341);
                }
                $commands[] = $commandToRebaseClass::fromArray($commandToRebasePayload);
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
    public function handlePublishIndividualNodesFromWorkspace(
        \Neos\ContentRepository\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->getWorkspaceName());
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        // 1) separate commands in two halves - the ones MATCHING the nodes from the command, and the REST
        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $workspace->getCurrentContentStreamIdentifier()
        );

        $originalCommands = $this->extractCommandsFromContentStreamMetadata($workspaceContentStreamName);
        /** @var RebasableToOtherContentStreamsInterface[] $matchingCommands */
        $matchingCommands = [];
        /** @var RebasableToOtherContentStreamsInterface[] $remainingCommands */
        $remainingCommands = [];

        foreach ($originalCommands as $originalCommand) {
            if (!$originalCommand instanceof MatchableWithNodeAddressInterface) {
                throw new \Exception(
                    'Command class ' . get_class($originalCommand) . ' does not implement '
                        . MatchableWithNodeAddressInterface::class,
                    1645393655
                );
            }
            if ($this->commandMatchesNodeAddresses($originalCommand, $command->getNodeAddresses())) {
                $matchingCommands[] = $originalCommand;
            } else {
                $remainingCommands[] = $originalCommand;
            }
        }

        // 2) fork a new contentStream, based on the base WS, and apply MATCHING
        $matchingContentStream = $command->getContentStreamIdentifierForMatchingPart();
        $contentRepository->handle(
            new ForkContentStream(
                $matchingContentStream,
                $baseWorkspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        )->block();

        foreach ($matchingCommands as $matchingCommand) {
            if (!($matchingCommand instanceof RebasableToOtherContentStreamsInterface)) {
                throw new \RuntimeException(
                    'ERROR: The command ' . get_class($matchingCommand)
                        . ' does not implement RebasableToOtherContentStreamsInterface; but it should!'
                );
            }

            $contentRepository->handle($matchingCommand->createCopyForContentStream($matchingContentStream))->block();
        }

        // 3) fork a new contentStream, based on the matching content stream, and apply REST
        $remainingContentStream = $command->getContentStreamIdentifierForRemainingPart();
        $contentRepository->handle(
            new ForkContentStream(
                $remainingContentStream,
                $matchingContentStream,
                $command->getInitiatingUserIdentifier()
            )
        )->block();

        foreach ($remainingCommands as $remainingCommand) {
            if (!$remainingCommand instanceof RebasableToOtherContentStreamsInterface) {
                throw new \Exception(
                    'Command class ' . get_class($remainingCommand) . ' does not implement '
                        . RebasableToOtherContentStreamsInterface::class,
                    1645393626
                );
            }
            $contentRepository->handle($remainingCommand->createCopyForContentStream($remainingContentStream))
                ->block();
        }

        // 4) if that all worked out, take EVENTS(MATCHING) and apply them to base WS.
        // TODO - MAKE THIS WORK!!
        $commandResult = $this->publishContentStream(
            $matchingContentStream,
            $baseWorkspace->getCurrentContentStreamIdentifier()
        );

        // 5) TODO Re-target base workspace

        // 6) switch content stream to forked WS.
        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $events = Events::with(
            new WorkspaceWasPartiallyPublished(
                $command->getWorkspaceName(),
                $baseWorkspace->getWorkspaceName(),
                $remainingContentStream,
                $workspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            ),
        );

        // It is safe to only return the last command result,
        // as the commands which were rebased are already executed "synchronously"
        return new EventsToPublish(
            $streamName,
            $events,
            ExpectedVersion::ANY()
        );
    }

    /**
     * This method is like a Rebase while dropping some modifications!
     *
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws WorkspaceHasNoBaseWorkspaceName
     * @throws \Neos\ContentRepository\Feature\Common\NodeConstraintException
     * @throws \Neos\ContentRepository\Feature\Common\NodeTypeNotFoundException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function handleDiscardIndividualNodesFromWorkspace(
        DiscardIndividualNodesFromWorkspace $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $workspace = $this->requireWorkspace($command->getWorkspaceName());
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        // 1) filter commands, only keeping the ones NOT MATCHING the nodes from the command
        // (i.e. the modifications we want to keep)
        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $workspace->getCurrentContentStreamIdentifier()
        );

        $originalCommands = $this->extractCommandsFromContentStreamMetadata($workspaceContentStreamName);
        $commandsToKeep = [];

        foreach ($originalCommands as $originalCommand) {
            if (!$originalCommand instanceof MatchableWithNodeAddressInterface) {
                throw new \Exception(
                    'Command class ' . get_class($originalCommand) . ' does not implement '
                    . MatchableWithNodeAddressInterface::class,
                    1645393476
                );
            }
            if (!$this->commandMatchesNodeAddresses($originalCommand, $command->getNodeAddresses())) {
                $commandsToKeep[] = $originalCommand;
            }
        }

        // 2) fork a new contentStream, based on the base WS, and apply the commands to keep
        $newContentStream = $command->getNewContentStreamIdentifier();
        $contentRepository->handle(
            new ForkContentStream(
                $newContentStream,
                $baseWorkspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        )->block();

        foreach ($commandsToKeep as $commandToKeep) {
            if (!$commandToKeep instanceof RebasableToOtherContentStreamsInterface) {
                throw new \Exception(
                    'Command class ' . get_class($commandToKeep) . ' does not implement '
                        . RebasableToOtherContentStreamsInterface::class,
                    1645393476
                );
            }
            $contentRepository->handle($commandToKeep->createCopyForContentStream($newContentStream))
                ->block();
        }

        // 3) switch content stream to forked WS.
        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $events = Events::with(
            new WorkspaceWasPartiallyDiscarded(
                $command->getWorkspaceName(),
                $newContentStream,
                $workspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        );

        // It is safe to only return the last command result,
        // as the commands which were rebased are already executed "synchronously"
        return new EventsToPublish(
            $streamName,
            $events,
            ExpectedVersion::ANY()
        );
    }

    /**
     * @param array<int,NodeAddress> $nodeAddresses
     */
    private function commandMatchesNodeAddresses(MatchableWithNodeAddressInterface $command, array $nodeAddresses): bool
    {
        foreach ($nodeAddresses as $nodeAddress) {
            if ($command->matchesNodeAddress($nodeAddress)) {
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
    public function handleDiscardWorkspace(DiscardWorkspace $command, ContentRepository $contentRepository): EventsToPublish
    {
        $workspace = $this->requireWorkspace($command->getWorkspaceName());
        $baseWorkspace = $this->requireBaseWorkspace($workspace);

        $newContentStream = $command->getNewContentStreamIdentifier();
        $contentRepository->handle(
            new ForkContentStream(
                $newContentStream,
                $baseWorkspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        )->block();

        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $events = Events::with(
            new WorkspaceWasDiscarded(
                $command->getWorkspaceName(),
                $newContentStream,
                $workspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        );

        // It is safe to only return the last command result,
        // as the commands which were rebased are already executed "synchronously"
        return new EventsToPublish(
            $streamName,
            $events,
            ExpectedVersion::ANY()
        );
    }

    /**
     * @throws WorkspaceDoesNotExist
     */
    private function requireWorkspace(WorkspaceName $workspaceName): Workspace
    {
        $workspace = $this->workspaceFinder->findOneByName($workspaceName);
        if (is_null($workspace)) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }

        return $workspace;
    }

    /**
     * @throws WorkspaceHasNoBaseWorkspaceName
     * @throws BaseWorkspaceDoesNotExist
     */
    private function requireBaseWorkspace(Workspace $workspace): Workspace
    {
        if (is_null($workspace->getBaseWorkspaceName())) {
            throw WorkspaceHasNoBaseWorkspaceName::butWasSupposedTo($workspace->getWorkspaceName());
        }

        $baseWorkspace = $this->workspaceFinder->findOneByName($workspace->getBaseWorkspaceName());
        if ($baseWorkspace === null) {
            throw BaseWorkspaceDoesNotExist::butWasSupposedTo($workspace->getWorkspaceName());
        }

        return $baseWorkspace;
    }
}
