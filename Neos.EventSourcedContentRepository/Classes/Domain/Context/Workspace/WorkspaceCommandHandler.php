<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\Workspace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Command\CreateContentStream;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Command\ForkContentStream;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasForked;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamAlreadyExists;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateType;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNodeAndSerializedProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\DisableNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RebasableToOtherContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetNodeReferences;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\EnableNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\SetSerializedNodeProperties;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\PublishableToOtherContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeNameIsAlreadyOccupied;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\MatchableWithNodeAddressInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\MoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\CopyNodesRecursively;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\NodeDuplicationCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\CreateRootWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\CreateWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\PublishWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\RebaseWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasDiscarded;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasPartiallyDiscarded;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasPartiallyPublished;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasPublished;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceRebaseFailed;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasRebased;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\BaseWorkspaceDoesNotExist;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\BaseWorkspaceHasBeenModifiedInTheMeantime;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\WorkspaceAlreadyExists;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\WorkspaceDoesNotExist;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\Exception\ConcurrencyException;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Annotations as Flow;
use Ramsey\Uuid\Uuid;

/**
 * WorkspaceCommandHandler
 * @Flow\Scope("singleton")
 */
final class WorkspaceCommandHandler
{
    protected EventStore $eventStore;

    protected WorkspaceFinder $workspaceFinder;

    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    protected ContentStreamCommandHandler $contentStreamCommandHandler;

    protected NodeDuplicationCommandHandler $nodeDuplicationCommandHandler;

    protected ReadSideMemoryCacheManager $readSideMemoryCacheManager;

    protected ContentGraphInterface $contentGraph;

    protected RuntimeBlocker $runtimeBlocker;

    public function __construct(
        EventStore $eventStore,
        WorkspaceFinder $workspaceFinder,
        NodeAggregateCommandHandler $nodeAggregateCommandHandler,
        ContentStreamCommandHandler $contentStreamCommandHandler,
        NodeDuplicationCommandHandler $nodeDuplicationCommandHandler,
        ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        ContentGraphInterface $contentGraph,
        RuntimeBlocker $runtimeBlocker
    ) {
        $this->eventStore = $eventStore;
        $this->workspaceFinder = $workspaceFinder;
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
        $this->contentStreamCommandHandler = $contentStreamCommandHandler;
        $this->nodeDuplicationCommandHandler = $nodeDuplicationCommandHandler;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
        $this->contentGraph = $contentGraph;
        $this->runtimeBlocker = $runtimeBlocker;
    }

    /**
     * @param CreateWorkspace $command
     * @return CommandResult
     * @throws BaseWorkspaceDoesNotExist
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     * @throws WorkspaceAlreadyExists
     */
    public function handleCreateWorkspace(CreateWorkspace $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $existingWorkspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($existingWorkspace !== null) {
            throw new WorkspaceAlreadyExists(sprintf('The workspace %s already exists', $command->getWorkspaceName()), 1505830958921);
        }

        $baseWorkspace = $this->workspaceFinder->findOneByName($command->getBaseWorkspaceName());
        if ($baseWorkspace === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf('The workspace %s (base workspace of %s) does not exist', $command->getBaseWorkspaceName(), $command->getWorkspaceName()), 1513890708);
        }

        // When the workspace is created, we first have to fork the content stream
        $commandResult = CommandResult::createEmpty();
        $commandResult = $commandResult->merge($this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $command->getNewContentStreamIdentifier(),
                $baseWorkspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        ));

        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new WorkspaceWasCreated(
                    $command->getWorkspaceName(),
                    $command->getBaseWorkspaceName(),
                    $command->getWorkspaceTitle(),
                    $command->getWorkspaceDescription(),
                    $command->getInitiatingUserIdentifier(),
                    $command->getNewContentStreamIdentifier(),
                    $command->getWorkspaceOwner()
                ),
                Uuid::uuid4()->toString()
            )
        );

        $this->eventStore->commit($streamName, $events);
        $commandResult = $commandResult->merge(CommandResult::fromPublishedEvents($events, $this->runtimeBlocker));
        return $commandResult;
    }

    /**
     * @param CreateRootWorkspace $command
     * @return CommandResult
     * @throws WorkspaceAlreadyExists
     * @throws ContentStreamAlreadyExists
     */
    public function handleCreateRootWorkspace(CreateRootWorkspace $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $existingWorkspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($existingWorkspace !== null) {
            throw new WorkspaceAlreadyExists(sprintf('The workspace %s already exists', $command->getWorkspaceName()), 1505848624450);
        }

        $commandResult = CommandResult::createEmpty();
        $contentStreamIdentifier = $command->getNewContentStreamIdentifier();
        $commandResult = $commandResult->merge($this->contentStreamCommandHandler->handleCreateContentStream(
            new CreateContentStream(
                $contentStreamIdentifier,
                $command->getInitiatingUserIdentifier()
            )
        ));

        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new RootWorkspaceWasCreated(
                    $command->getWorkspaceName(),
                    $command->getWorkspaceTitle(),
                    $command->getWorkspaceDescription(),
                    $command->getInitiatingUserIdentifier(),
                    $contentStreamIdentifier
                ),
                Uuid::uuid4()->toString()
            )
        );

        $this->eventStore->commit($streamName, $events);
        $commandResult = $commandResult->merge(CommandResult::fromPublishedEvents($events, $this->runtimeBlocker));

        return $commandResult;
    }

    /**
     * @param PublishWorkspace $command
     * @return CommandResult
     * @throws BaseWorkspaceDoesNotExist
     * @throws BaseWorkspaceHasBeenModifiedInTheMeantime
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     * @throws WorkspaceDoesNotExist
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function handlePublishWorkspace(PublishWorkspace $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $workspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($workspace === null) {
            throw new WorkspaceDoesNotExist(sprintf('The workspace %s does not exist', $command->getWorkspaceName()), 1513924741);
        }
        $baseWorkspace = $this->workspaceFinder->findOneByName($workspace->getBaseWorkspaceName());
        if ($baseWorkspace === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf('The workspace %s (base workspace of %s) does not exist', $workspace->getBaseWorkspaceName(), $command->getWorkspaceName()), 1513924882);
        }
        $commandResult = $this->publishContentStream($workspace->getCurrentContentStreamIdentifier(), $baseWorkspace->getCurrentContentStreamIdentifier());

        // After publishing a workspace, we need to again fork from Base.
        $newContentStream = ContentStreamIdentifier::create();
        $commandResult = $commandResult->merge(
            $this->contentStreamCommandHandler->handleForkContentStream(
                new ForkContentStream(
                    $newContentStream,
                    $baseWorkspace->getCurrentContentStreamIdentifier(),
                    $command->getInitiatingUserIdentifier()
                )
            )
        );
        $commandResult->blockUntilProjectionsAreUpToDate();

        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new WorkspaceWasPublished(
                    $command->getWorkspaceName(),
                    $workspace->getBaseWorkspaceName(),
                    $newContentStream,
                    $workspace->getCurrentContentStreamIdentifier(),
                    $command->getInitiatingUserIdentifier()
                ),
                Uuid::uuid4()->toString()
            )
        );
        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $this->eventStore->commit($streamName, $events);
        return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param ContentStreamIdentifier $baseContentStreamIdentifier
     * @return CommandResult
     * @throws BaseWorkspaceHasBeenModifiedInTheMeantime
     * @throws \Exception
     */
    private function publishContentStream(ContentStreamIdentifier $contentStreamIdentifier, ContentStreamIdentifier $baseContentStreamIdentifier): CommandResult
    {
        $contentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);
        $baseWorkspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($baseContentStreamIdentifier);

        // TODO: please check the code below in-depth. it does:
        // - copy all events from the "user" content stream which implement "PublishableToOtherContentStreamsInterface"
        // - extract the initial ContentStreamWasForked event, to read the version of the source content stream when the fork occurred
        // - ensure that no other changes have been done in the meantime in the base content stream


        $streamName = StreamName::fromString((string)$contentStreamName->getEventStreamName());

        /* @var $workspaceContentStream EventEnvelope[] */
        $workspaceContentStream = iterator_to_array($this->eventStore->load($streamName));

        $events = DomainEvents::createEmpty();
        foreach ($workspaceContentStream as $eventEnvelope) {
            $event = $eventEnvelope->getDomainEvent();
            if ($event instanceof PublishableToOtherContentStreamsInterface) {
                $events = $events->appendEvent(
                // We need to add the event metadata here for rebasing in nested workspace situations (and for exporting)
                    DecoratedEvent::addIdentifier(
                        DecoratedEvent::addMetadata(
                            $event->createCopyForContentStream($baseContentStreamIdentifier),
                            $eventEnvelope->getRawEvent()->getMetadata()
                        ),
                        Uuid::uuid4()->toString()
                    )
                );
            }
        }

        $contentStreamWasForked = self::extractSingleForkedContentStreamEvent($workspaceContentStream);
        try {
            $this->eventStore->commit($baseWorkspaceContentStreamName->getEventStreamName(), $events, $contentStreamWasForked->getVersionOfSourceContentStream());
            return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
        } catch (ConcurrencyException $e) {
            throw new BaseWorkspaceHasBeenModifiedInTheMeantime(sprintf('The base workspace has been modified in the meantime; please rebase. Expected version %d of source content stream %s', $contentStreamWasForked->getVersionOfSourceContentStream(), $baseContentStreamIdentifier));
        }
    }

    /**
     * @param array $stream
     * @return ContentStreamWasForked
     * @throws \Exception
     */
    private static function extractSingleForkedContentStreamEvent(array $stream): ContentStreamWasForked
    {
        $contentStreamWasForkedEvents = array_filter($stream, function (EventEnvelope $eventEnvelope) {
            return $eventEnvelope->getDomainEvent() instanceof ContentStreamWasForked;
        });

        if (count($contentStreamWasForkedEvents) !== 1) {
            throw new \Exception(sprintf('TODO: only can publish a content stream which has exactly one ContentStreamWasForked; we found %d', count($contentStreamWasForkedEvents)));
        }

        if (reset($contentStreamWasForkedEvents)->getDomainEvent() !== reset($stream)->getDomainEvent()) {
            throw new \Exception(sprintf('TODO: stream has to start with a single ContentStreamWasForked event, found %s', get_class(reset($stream)->getDomainEvent())));
        }

        return reset($contentStreamWasForkedEvents)->getDomainEvent();
    }

    /**
     * @param RebaseWorkspace $command
     * @return CommandResult
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws \Exception
     * @throws \Neos\EventSourcedContentRepository\Exception
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function handleRebaseWorkspace(RebaseWorkspace $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $workspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($workspace === null) {
            throw new WorkspaceDoesNotExist(sprintf('The source workspace %s does not exist', $command->getWorkspaceName()), 1513924741);
        }
        $baseWorkspace = $this->workspaceFinder->findOneByName($workspace->getBaseWorkspaceName());

        if ($baseWorkspace === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf('The workspace %s (base workspace of %s) does not exist', $workspace->getBaseWorkspaceName(), $command->getWorkspaceName()), 1513924882);
        }

        // TODO: please check the code below in-depth. it does:
        // - fork a new content stream
        // - extract the commands from the to-be-rebased content stream; and applies them on the new content stream
        $rebasedContentStream = $command->getRebasedContentStreamIdentifier();
        $this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $rebasedContentStream,
                $baseWorkspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        )->blockUntilProjectionsAreUpToDate();

        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($workspace->getCurrentContentStreamIdentifier());

        $originalCommands = $this->extractCommandsFromContentStreamMetadata($workspaceContentStreamName);
        $rebaseStatistics = new WorkspaceRebaseStatistics();
        foreach ($originalCommands as $i => $originalCommand) {
            if (!($originalCommand instanceof RebasableToOtherContentStreamsInterface)) {
                throw new \RuntimeException('ERROR: The command ' . get_class($originalCommand) . ' does not implement RebasableToOtherContentStreamsInterface; but it should!');
            }

            // try to apply the command on the rebased content stream
            $commandToRebase = $originalCommand->createCopyForContentStream($rebasedContentStream);
            try {
                $this->applyCommand($commandToRebase)->blockUntilProjectionsAreUpToDate();
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
                    "The content stream %s cannot be rebased. Error with command %d (%s) - see nested exception for details.\n\n The base workspace %s is at content stream %s.\n The full list of commands applied so far is: %s",
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
            $events = DomainEvents::withSingleEvent(
                DecoratedEvent::addIdentifier(
                    new WorkspaceWasRebased(
                        $command->getWorkspaceName(),
                        $rebasedContentStream,
                        $workspace->getCurrentContentStreamIdentifier(),
                        $command->getInitiatingUserIdentifier()
                    ),
                    Uuid::uuid4()->toString()
                )
            );
            $this->eventStore->commit($streamName, $events);

            return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
        } else {
            // an error occurred during the rebase; so we need to record this using a "WorkspaceRebaseFailed" event.

            $event = DomainEvents::withSingleEvent(
                DecoratedEvent::addIdentifier(
                    new WorkspaceRebaseFailed(
                        $command->getWorkspaceName(),
                        $rebasedContentStream,
                        $workspace->getCurrentContentStreamIdentifier(),
                        $command->getInitiatingUserIdentifier(),
                        $rebaseStatistics->getErrors()
                    ),
                    Uuid::uuid4()->toString()
                )
            );
            $this->eventStore->commit($streamName, $event);

            return CommandResult::fromPublishedEvents($event, $this->runtimeBlocker);
        }
    }

    /**
     * @param ContentStreamEventStreamName $workspaceContentStreamName
     * @return array
     */
    private function extractCommandsFromContentStreamMetadata(ContentStreamEventStreamName $workspaceContentStreamName): array
    {
        $workspaceContentStream = $this->eventStore->load($workspaceContentStreamName->getEventStreamName());

        $commands = [];
        foreach ($workspaceContentStream as $eventAndRawEvent) {
            $metadata = $eventAndRawEvent->getRawEvent()->getMetadata();
            // TODO: Add this logic to the NodeAggregateCommandHandler; so that we can be sure these can be parsed again.
            if (isset($metadata['commandClass'])) {
                $commandToRebaseClass = $metadata['commandClass'];
                $commandToRebasePayload = $metadata['commandPayload'];
                if (!method_exists($commandToRebaseClass, 'fromArray')) {
                    throw new \RuntimeException(sprintf('Command "%s" can\'t be rebased because it does not implement a static "fromArray" constructor', $commandToRebaseClass), 1547815341);
                }
                $commands[] = $commandToRebaseClass::fromArray($commandToRebasePayload);
            }
        }

        return $commands;
    }

    /**
     * @param $command
     * @return CommandResult
     * @throws \Neos\ContentRepository\Exception\NodeConstraintException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeNameIsAlreadyOccupied
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws DimensionSpacePointNotFound
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    private function applyCommand($command): CommandResult
    {
        // TODO: try catch logic around applyCommand -> blockUntilProjectionsAreUpToDate.
        // TODO: then put it into special error stream; and be sure to ABORT the outer logic correctly!

        // TODO: Add this logic to the NodeAggregateCommandHandler; so that we the command can be applied.
        switch (get_class($command)) {
            case ChangeNodeAggregateName::class:
                return $this->nodeAggregateCommandHandler->handleChangeNodeAggregateName($command);
            case CreateNodeAggregateWithNodeAndSerializedProperties::class:
                return $this->nodeAggregateCommandHandler->handleCreateNodeAggregateWithNodeAndSerializedProperties($command);
            case MoveNodeAggregate::class:
                return $this->nodeAggregateCommandHandler->handleMoveNodeAggregate($command);
            case SetSerializedNodeProperties::class:
                return $this->nodeAggregateCommandHandler->handleSetSerializedNodeProperties($command);
            case DisableNodeAggregate::class:
                return $this->nodeAggregateCommandHandler->handleDisableNodeAggregate($command);
            case EnableNodeAggregate::class:
                return $this->nodeAggregateCommandHandler->handleEnableNodeAggregate($command);
            case SetNodeReferences::class:
                return $this->nodeAggregateCommandHandler->handleSetNodeReferences($command);
            case RemoveNodeAggregate::class:
                return $this->nodeAggregateCommandHandler->handleRemoveNodeAggregate($command);
            case ChangeNodeAggregateType::class:
                return $this->nodeAggregateCommandHandler->handleChangeNodeAggregateType($command);
            case CopyNodesRecursively::class:
                return $this->nodeDuplicationCommandHandler->handleCopyNodesRecursively($command);
            default:
                throw new \Exception(sprintf('TODO: Command %s is not supported by handleRebaseWorkspace() currently... Please implement it there.', get_class($command)));
        }
    }

    /**
     * This method is like a combined Rebase and Publish!
     *
     * @param Command\PublishIndividualNodesFromWorkspace $command
     * @return CommandResult
     * @throws BaseWorkspaceDoesNotExist
     * @throws BaseWorkspaceHasBeenModifiedInTheMeantime
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     * @throws WorkspaceDoesNotExist
     * @throws \Exception
     */
    public function handlePublishIndividualNodesFromWorkspace(Command\PublishIndividualNodesFromWorkspace $command)
    {
        $this->readSideMemoryCacheManager->disableCache();

        $workspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($workspace === null) {
            throw new WorkspaceDoesNotExist(sprintf('The source workspace %s does not exist', $command->getWorkspaceName()), 1513924741);
        }
        $baseWorkspace = $this->workspaceFinder->findOneByName($workspace->getBaseWorkspaceName());

        if ($baseWorkspace === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf('No base workspace exists for given workspace %s', $command->getWorkspaceName()), 1513924882);
        }

        // 1) separate commands in two halves - the ones MATCHING the nodes from the command, and the REST
        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($workspace->getCurrentContentStreamIdentifier());

        $originalCommands = $this->extractCommandsFromContentStreamMetadata($workspaceContentStreamName);
        /** @var RebasableToOtherContentStreamsInterface[] $matchingCommands */
        $matchingCommands = [];
        /** @var RebasableToOtherContentStreamsInterface[] $remainingCommands */
        $remainingCommands = [];

        foreach ($originalCommands as $originalCommand) {
            if ($this->commandMatchesNodeAddresses($originalCommand, $command->getNodeAddresses())) {
                $matchingCommands[] = $originalCommand;
            } else {
                $remainingCommands[] = $originalCommand;
            }
        }

        // 2) fork a new contentStream, based on the base WS, and apply MATCHING
        $matchingContentStream = $command->getContentStreamIdentifierForMatchingPart();
        $this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $matchingContentStream,
                $baseWorkspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        )->blockUntilProjectionsAreUpToDate();

        foreach ($matchingCommands as $matchingCommand) {
            if (!($matchingCommand instanceof RebasableToOtherContentStreamsInterface)) {
                throw new \RuntimeException('ERROR: The command ' . get_class($matchingCommand) . ' does not implement RebasableToOtherContentStreamsInterface; but it should!');
            }

            $this->applyCommand($matchingCommand->createCopyForContentStream($matchingContentStream))
                ->blockUntilProjectionsAreUpToDate();
        }

        // 3) fork a new contentStream, based on the matching content stream, and apply REST
        $remainingContentStream = $command->getContentStreamIdentifierForRemainingPart();
        $this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $remainingContentStream,
                $matchingContentStream,
                $command->getInitiatingUserIdentifier()
            )
        )->blockUntilProjectionsAreUpToDate();

        foreach ($remainingCommands as $remainingCommand) {
            $this->applyCommand($remainingCommand->createCopyForContentStream($remainingContentStream))->blockUntilProjectionsAreUpToDate();
        }

        // 4) if that all worked out, take EVENTS(MATCHING) and apply them to base WS.
        $commandResult = $this->publishContentStream($matchingContentStream, $baseWorkspace->getCurrentContentStreamIdentifier());

        // 5) TODO Re-target base workspace

        // 6) switch content stream to forked WS.
        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new WorkspaceWasPartiallyPublished(
                    $command->getWorkspaceName(),
                    $workspace->getBaseWorkspaceName(),
                    $remainingContentStream,
                    $workspace->getCurrentContentStreamIdentifier(),
                    $command->getInitiatingUserIdentifier()
                ),
                Uuid::uuid4()->toString()
            )
        );
        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $this->eventStore->commit($streamName, $events);

        // It is safe to only return the last command result, as the commands which were rebased are already executed "synchronously"
        return $commandResult->merge(CommandResult::fromPublishedEvents($events, $this->runtimeBlocker));
    }

    /**
     * This method is like a Rebase while dropping some modifications!
     *
     * @param Command\DiscardIndividualNodesFromWorkspace $command
     * @return CommandResult
     * @throws BaseWorkspaceDoesNotExist
     * @throws BaseWorkspaceHasBeenModifiedInTheMeantime
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     * @throws WorkspaceDoesNotExist
     * @throws \Exception
     */
    public function handleDiscardIndividualNodesFromWorkspace(Command\DiscardIndividualNodesFromWorkspace $command)
    {
        $this->readSideMemoryCacheManager->disableCache();

        $workspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($workspace === null) {
            throw new WorkspaceDoesNotExist(sprintf('The source workspace %s does not exist', $command->getWorkspaceName()), 1513924741);
        }
        $baseWorkspace = $this->workspaceFinder->findOneByName($workspace->getBaseWorkspaceName());

        if ($baseWorkspace === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf('No base workspace exists for given workspace workspace %s', $command->getWorkspaceName()), 1513924882);
        }

        $workspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($workspace === null) {
            throw new WorkspaceDoesNotExist(sprintf('The source workspace %s does not exist', $command->getWorkspaceName()), 1513924741);
        }

        // 1) filter commands, only keeping the ones NOT MATCHING the nodes from the command (i.e. the modifications we want to keep)
        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($workspace->getCurrentContentStreamIdentifier());

        $originalCommands = $this->extractCommandsFromContentStreamMetadata($workspaceContentStreamName);
        /** @var RebasableToOtherContentStreamsInterface[] $commandsToKeep */
        $commandsToKeep = [];

        foreach ($originalCommands as $originalCommand) {
            if (!$this->commandMatchesNodeAddresses($originalCommand, $command->getNodeAddresses())) {
                $commandsToKeep[] = $originalCommand;
            }
        }

        // 2) fork a new contentStream, based on the base WS, and apply the commands to keep
        $newContentStream = $command->getNewContentStreamIdentifier();
        $this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $newContentStream,
                $baseWorkspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        )->blockUntilProjectionsAreUpToDate();

        foreach ($commandsToKeep as $commandToKeep) {
            $this->applyCommand($commandToKeep->createCopyForContentStream($newContentStream))
                ->blockUntilProjectionsAreUpToDate();
        }

        // 3) switch content stream to forked WS.
        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new WorkspaceWasPartiallyDiscarded(
                    $command->getWorkspaceName(),
                    $newContentStream,
                    $workspace->getCurrentContentStreamIdentifier(),
                    $command->getInitiatingUserIdentifier()
                ),
                Uuid::uuid4()->toString()
            )
        );
        $this->eventStore->commit($streamName, $events);

        // It is safe to only return the last command result, as the commands which were rebased are already executed "synchronously"
        return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
    }

    /**
     * @param object $command
     * @param \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress[] $nodeAddresses
     * @return bool
     * @throws \Exception
     */
    private function commandMatchesNodeAddresses(object $command, array $nodeAddresses): bool
    {
        if (!$command instanceof MatchableWithNodeAddressInterface) {
            throw new \Exception(sprintf('Command %s needs to implement MatchableWithNodeAddressInterface in order to be published individually.', get_class($command)));
        }

        foreach ($nodeAddresses as $nodeAddress) {
            if ($command->matchesNodeAddress($nodeAddress)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Command\DiscardWorkspace $command
     * @return CommandResult
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     */
    public function handleDiscardWorkspace(Command\DiscardWorkspace $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $workspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        $baseWorkspace = $this->workspaceFinder->findOneByName($workspace->getBaseWorkspaceName());

        $newContentStream = $command->getNewContentStreamIdentifier();
        $this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $newContentStream,
                $baseWorkspace->getCurrentContentStreamIdentifier(),
                $command->getInitiatingUserIdentifier()
            )
        )->blockUntilProjectionsAreUpToDate();

        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new WorkspaceWasDiscarded(
                    $command->getWorkspaceName(),
                    $newContentStream,
                    $workspace->getCurrentContentStreamIdentifier(),
                    $command->getInitiatingUserIdentifier()
                ),
                Uuid::uuid4()->toString()
            )
        );

        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $this->eventStore->commit($streamName, $events);

        // It is safe to only return the last command result, as the commands which were rebased are already executed "synchronously"
        return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
    }
}
