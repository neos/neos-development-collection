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

use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Command\CreateContentStream;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Command\ForkContentStream;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasForked;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\AddNodeToAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\ChangeNodeName;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\CreateNodeAggregateWithNode;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\CreateRootNode;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\HideNode;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\MoveNode;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\SetNodeProperty;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\ShowNode;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\TranslateNodeInAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\Node\CopyableAcrossContentStreamsInterface;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\CreateRootWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\CreateWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\PublishWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\RebaseWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasRebased;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\BaseWorkspaceDoesNotExist;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\BaseWorkspaceHasBeenModifiedInTheMeantime;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\WorkspaceAlreadyExists;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Exception\WorkspaceDoesNotExist;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcing\Event\Decorator\EventWithIdentifier;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventBus\EventBus;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddress;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\Exception\ConcurrencyException;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMapper;

/**
 * WorkspaceCommandHandler
 */
final class WorkspaceCommandHandler
{
    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var NodeCommandHandler
     */
    protected $nodeCommandHandler;

    /**
     * @Flow\Inject
     * @var ContentStreamCommandHandler
     */
    protected $contentStreamCommandHandler;

    /**
     * @Flow\Inject
     * @var EventStoreManager
     */
    protected $eventStoreManager;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var EventBus
     */
    protected $eventBus;


    /**
     * @param CreateWorkspace $command
     * @return CommandResult
     * @throws WorkspaceAlreadyExists
     * @throws BaseWorkspaceDoesNotExist
     */
    public function handleCreateWorkspace(CreateWorkspace $command): CommandResult
    {
        $existingWorkspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($existingWorkspace !== null) {
            throw new WorkspaceAlreadyExists(sprintf('The workspace %s already exists', $command->getWorkspaceName()), 1505830958921);
        }

        $baseWorkspace = $this->workspaceFinder->findOneByName($command->getBaseWorkspaceName());
        if ($baseWorkspace === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf('The workspace %s (base workspace of %s) does not exist', $command->getBaseWorkspaceName(), $command->getWorkspaceName()), 1513890708);
        }

        // TODO: CONCEPT OF EDITING SESSION IS TOTALLY MISSING SO FAR!!!!
        // When the workspace is created, we first have to fork the content stream

        $commandResult = CommandResult::createEmpty();
        $commandResult = $commandResult->merge($this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $command->getContentStreamIdentifier(),
                $baseWorkspace->getCurrentContentStreamIdentifier()
            )
        ));

        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $events = DomainEvents::withSingleEvent(
            EventWithIdentifier::create(
                new WorkspaceWasCreated(
                    $command->getWorkspaceName(),
                    $command->getBaseWorkspaceName(),
                    $command->getWorkspaceTitle(),
                    $command->getWorkspaceDescription(),
                    $command->getInitiatingUserIdentifier(),
                    $command->getContentStreamIdentifier(),
                    $command->getWorkspaceOwner()
                )
            )
        );

        $eventStore->commit($streamName, $events);
        $commandResult = $commandResult->merge(CommandResult::fromPublishedEvents($events));
        return $commandResult;
    }

    /**
     * @param CreateRootWorkspace $command
     * @return CommandResult
     * @throws WorkspaceAlreadyExists
     * @throws \Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamAlreadyExists
     */
    public function handleCreateRootWorkspace(CreateRootWorkspace $command): CommandResult
    {
        $existingWorkspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($existingWorkspace !== null) {
            throw new WorkspaceAlreadyExists(sprintf('The workspace %s already exists', $command->getWorkspaceName()), 1505848624450);
        }

        $commandResult = CommandResult::createEmpty();
        $contentStreamIdentifier = $command->getContentStreamIdentifier();
        $commandResult = $commandResult->merge($this->contentStreamCommandHandler->handleCreateContentStream(
            new CreateContentStream(
                $contentStreamIdentifier,
                $command->getInitiatingUserIdentifier()
            )
        ));

        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $events = DomainEvents::withSingleEvent(
            EventWithIdentifier::create(
                new RootWorkspaceWasCreated(
                    $command->getWorkspaceName(),
                    $command->getWorkspaceTitle(),
                    $command->getWorkspaceDescription(),
                    $command->getInitiatingUserIdentifier(),
                    $contentStreamIdentifier
                )
            )
        );

        $eventStore->commit($streamName, $events);
        $commandResult = $commandResult->merge(CommandResult::fromPublishedEvents($events));

        $commandResult = $commandResult->merge($this->nodeCommandHandler->handleCreateRootNode(
            new CreateRootNode(
                $contentStreamIdentifier,
                $command->getRootNodeIdentifier(),
                $command->getRootNodeTypeName(),
                $command->getInitiatingUserIdentifier()
            )
        ));

        return $commandResult;
    }

    /**
     * @param PublishWorkspace $command
     * @return CommandResult
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws \Neos\EventSourcing\EventStore\Exception\EventStreamNotFoundException
     * @throws \Exception
     */
    public function handlePublishWorkspace(PublishWorkspace $command): CommandResult
    {
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
                    $baseWorkspace->getCurrentContentStreamIdentifier()
                )
            )
        );

        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        // TODO: "Workspace was rebased" is probably the wrong name. We can rebase a content stream,
        // but not really a workspace. We can just change the ContentStream for a workspace.
        $events = DomainEvents::withSingleEvent(
            EventWithIdentifier::create(
                new WorkspaceWasRebased(
                    $command->getWorkspaceName(),
                    $newContentStream
                )
            )
        );
        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $eventStore->commit($streamName, $events);
        $commandResult = $commandResult->merge(CommandResult::fromPublishedEvents($events));
        return $commandResult;
    }

    private function publishContentStream(ContentStreamIdentifier $contentStreamIdentifier, ContentStreamIdentifier $baseContentStreamIdentifier): CommandResult
    {

        $contentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier);
        $baseWorkspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($baseContentStreamIdentifier);

        // TODO: please check the code below in-depth. it does:
        // - copy all events from the "user" content stream which implement "CopyableAcrossContentStreamsInterface"
        // - extract the initial ContentStreamWasForked event, to read the version of the source content stream when the fork occurred
        // - ensure that no other changes have been done in the meantime in the base content stream


        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($contentStreamName->getEventStreamName());

        /* @var $workspaceContentStream EventEnvelope[] */
        $workspaceContentStream = iterator_to_array($eventStore->load($contentStreamName->getEventStreamName()));

        $events = DomainEvents::createEmpty();
        foreach ($workspaceContentStream as $eventEnvelope) {
            $event = $eventEnvelope->getDomainEvent();
            if ($event instanceof CopyableAcrossContentStreamsInterface) {
                $events = $events->appendEvent(
                    EventWithIdentifier::create(
                        $event->createCopyForContentStream($baseContentStreamIdentifier)
                    )
                );
            }
        }

        // TODO: maybe we should also emit a "WorkspaceWasPublished" event? But on which content stream?
        $contentStreamWasForked = self::extractSingleForkedContentStreamEvent($workspaceContentStream);
        try {
            $eventStore = $this->eventStoreManager->getEventStoreForStreamName($baseWorkspaceContentStreamName->getEventStreamName());
            $eventStore->commit($baseWorkspaceContentStreamName->getEventStreamName(), $events, $contentStreamWasForked->getVersionOfSourceContentStream());
            return CommandResult::fromPublishedEvents($events);
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
     * @throws \Neos\EventSourcedContentRepository\Exception\NodeException
     * @throws \Neos\EventSourcedContentRepository\Exception\NodeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function handleRebaseWorkspace(RebaseWorkspace $command): CommandResult
    {
        $workspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($workspace === null) {
            throw new WorkspaceDoesNotExist(sprintf('The source workspace %s does not exist', $command->getWorkspaceName()), 1513924741);
        }
        $baseWorkspace = $this->workspaceFinder->findOneByName($workspace->getBaseWorkspaceName());

        if ($baseWorkspace === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf('The workspace %s (base workspace of %s) does not exist', $command->getBaseWorkspaceName(), $command->getWorkspaceName()), 1513924882);
        }

        // TODO: please check the code below in-depth. it does:
        // - fork a new content stream
        // - extract the commands from the to-be-rebased content stream; and applies them on the new content stream
        $rebasedContentStream = ContentStreamIdentifier::create();
        $this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $rebasedContentStream,
                $baseWorkspace->getCurrentContentStreamIdentifier()
            )
        )->blockUntilProjectionsAreUpToDate();

        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($workspace->getCurrentContentStreamIdentifier());

        $originalCommands = $this->extractCommandsFromContentStreamMetadata($workspaceContentStreamName);
        foreach ($originalCommands as $originalCommand) {
            if (!($originalCommand instanceof CopyableAcrossContentStreamsInterface)) {
                throw new \RuntimeException('ERROR: The command ' . get_class($originalCommand) . ' does not implement CopyableAcrossContentStreamsInterface; but it should!');
            }

            // try to apply the command on the rebased content stream
            $commandToRebase = $originalCommand->createCopyForContentStream($rebasedContentStream);
            $this->applyCommand($commandToRebase)->blockUntilProjectionsAreUpToDate();
        }

        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $events = DomainEvents::withSingleEvent(
            EventWithIdentifier::create(
                new WorkspaceWasRebased(
                    $command->getWorkspaceName(),
                    $rebasedContentStream
                )
            )
        );
        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $eventStore->commit($streamName, $events);

        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param ContentStreamEventStreamName $workspaceContentStreamName
     * @return array
     */
    private function extractCommandsFromContentStreamMetadata(ContentStreamEventStreamName $workspaceContentStreamName): array
    {
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($workspaceContentStreamName->getEventStreamName());

        $workspaceContentStream = $eventStore->load($workspaceContentStreamName->getEventStreamName());

        $commands = [];
        foreach ($workspaceContentStream as $eventAndRawEvent) {
            $metadata = $eventAndRawEvent->getRawEvent()->getMetadata();
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

    private function applyCommand($command): CommandResult
    {
        // TODO: use a more clever dispatching mechanism than the hard coded switch!!
        // TODO: add all commands!!
        switch (get_class($command)) {
            case AddNodeToAggregate::class:
                return $this->nodeCommandHandler->handleAddNodeToAggregate($command);
                break;
            case ChangeNodeName::class:
                return $this->nodeCommandHandler->handleChangeNodeName($command);
                break;
            case CreateNodeAggregateWithNode::class:
                return $this->nodeCommandHandler->handleCreateNodeAggregateWithNode($command);
                break;
            case CreateRootNode::class:
                return $this->nodeCommandHandler->handleCreateRootNode($command);
                break;
            case MoveNode::class:
                return $this->nodeCommandHandler->handleMoveNode($command);
                break;
            case SetNodeProperty::class:
                return $this->nodeCommandHandler->handleSetNodeProperty($command);
                break;
            case HideNode::class:
                return $this->nodeCommandHandler->handleHideNode($command);
                break;
            case ShowNode::class:
                return $this->nodeCommandHandler->handleShowNode($command);
                break;
            case TranslateNodeInAggregate::class:
                return $this->nodeCommandHandler->handleTranslateNodeInAggregate($command);
                break;
            default:
                throw new \Exception(sprintf('TODO: Command %s is not supported by handleRebaseWorkspace() currently... Please implement it there.', get_class($command)));
        }
    }

    /**
     * This method is like a combined Rebase and Publish!
     *
     * @param Command\PublishIndividualNodesFromWorkspace $command
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     */
    public function handlePublishIndividualNodesFromWorkspace(Command\PublishIndividualNodesFromWorkspace $command)
    {
        $workspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($workspace === null) {
            throw new WorkspaceDoesNotExist(sprintf('The source workspace %s does not exist', $command->getWorkspaceName()), 1513924741);
        }
        $baseWorkspace = $this->workspaceFinder->findOneByName($workspace->getBaseWorkspaceName());

        if ($baseWorkspace === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf('The workspace %s (base workspace of %s) does not exist', $command->getBaseWorkspaceName(), $command->getWorkspaceName()), 1513924882);
        }

        // 1) separate commands in two halves - the ones MATCHING the nodes from the command, and the REST
        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($workspace->getCurrentContentStreamIdentifier());

        $originalCommands = $this->extractCommandsFromContentStreamMetadata($workspaceContentStreamName);
        $matchingCommands = [];
        $remainingCommands = [];

        foreach ($originalCommands as $originalCommand) {
            // TODO: the Node Address Bounded Context MUST be moved to the CR core. This is the smoking gun why we need this ;)
            if (self::commandMatchesNodeAddresses($originalCommand, $command->getNodeAddresses())) {
                $matchingCommands[] = $originalCommand;
            } else {
                $remainingCommands[] = $originalCommand;
            }
        }

        // 2) fork a new contentStream, based on the base WS, and apply MATCHING
        $matchingContentStream = ContentStreamIdentifier::create();
        $this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $matchingContentStream,
                $baseWorkspace->getCurrentContentStreamIdentifier()
            )
        );

        $this->eventBus->flush();
        sleep(1);

        foreach ($matchingCommands as $matchingCommand) {
            /* @var $matchingCommand \Neos\EventSourcedContentRepository\Domain\Context\Node\CopyableAcrossContentStreamsInterface */
            $this->applyCommand($matchingCommand->createCopyForContentStream($matchingContentStream))->blockUntilProjectionsAreUpToDate();
        }

        // 3) fork a new contentStream, based on the matching content stream, and apply REST
        $remainingContentStream = ContentStreamIdentifier::create();
        $this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $remainingContentStream,
                $matchingContentStream
            )
        )->blockUntilProjectionsAreUpToDate();

        foreach ($remainingCommands as $remainingCommand) {
            /* @var $remainingCommand \Neos\EventSourcedContentRepository\Domain\Context\Node\CopyableAcrossContentStreamsInterface */
            $this->applyCommand($remainingCommand->createCopyForContentStream($remainingContentStream))->blockUntilProjectionsAreUpToDate();
        }

        // 4) if that all worked out, take EVENTS(MATCHING) and apply them to base WS.
        $commandResult = $this->publishContentStream($matchingContentStream, $baseWorkspace->getCurrentContentStreamIdentifier());

        // 5) TODO Re-target base workspace

        // 6) switch content stream to forked WS.
        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $events = DomainEvents::withSingleEvent(
            EventWithIdentifier::create(
                new WorkspaceWasRebased(
                    $command->getWorkspaceName(),
                    $remainingContentStream
                )
            )
        );
        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $eventStore->commit($streamName, $events);

        // It is safe to only return the last command result, as the commands which were rebased are already executed "synchronously"
        return $commandResult->merge(CommandResult::fromPublishedEvents($events));
    }

    /**
     * @param object $command
     * @param NodeAddress[] $nodeAddresses
     * @return bool
     * @throws \Neos\ContentRepository\Exception\NodeException
     */
    private static function commandMatchesNodeAddresses(object $command, array $nodeAddresses): bool
    {
        // TODO: use a more clever dispatching mechanism than the hard coded switch!!
        // TODO: add all commands!!
        switch (get_class($command)) {
            //case AddNodeToAggregate::class:
            //    $this->nodeCommandHandler->handleAddNodeToAggregate($command);
            //    break;
            //case ChangeNodeName::class:
            //    $this->nodeCommandHandler->handleChangeNodeName($command);
            //    break;
            //case CreateNodeAggregateWithNode::class:
            //    $this->nodeCommandHandler->handleCreateNodeAggregateWithNode($command);
            //    break;
            //case CreateRootNode::class:
            //    $this->nodeCommandHandler->handleCreateRootNode($command);
            //    break;
            //case MoveNode::class:
            //    $this->nodeCommandHandler->handleMoveNode($command);
            //    break;
            case SetNodeProperty::class:
                /* @var $command \Neos\EventSourcedContentRepository\Domain\Context\Node\Command\SetNodeProperty */
                foreach ($nodeAddresses as $nodeAddress) {
                    if (
                        (string)$command->getContentStreamIdentifier() === (string)$nodeAddress->getContentStreamIdentifier()
                        && $command->getOriginDimensionSpacePoint()->equals($nodeAddress->getDimensionSpacePoint())
                        && $command->getNodeAggregateIdentifier()->equals($nodeAddress->getNodeAggregateIdentifier())
                    ) {
                        return true;
                    }
                }
                return false;
                break;
            //case HideNode::class:
            //    $this->nodeCommandHandler->handleHideNode($command);
            //    break;
            //case ShowNode::class:
            //    $this->nodeCommandHandler->handleShowNode($command);
            //    break;
            //case TranslateNodeInAggregate::class:
            //    $this->nodeCommandHandler->handleTranslateNodeInAggregate($command);
            //    break;
            default:
                throw new \Exception(sprintf('TODO: Command %s is not supported by handleRebaseWorkspace() currently... Please implement it there.', get_class($command)));
        }

        return false;
    }
}
