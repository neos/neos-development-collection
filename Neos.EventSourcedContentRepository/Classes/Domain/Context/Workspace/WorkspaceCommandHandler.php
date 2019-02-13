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
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\CopyableAcrossContentStreamsInterface;
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
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventBus\EventBus;
use Neos\EventSourcing\EventStore\EventEnvelope;
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
     * @throws WorkspaceAlreadyExists
     * @throws BaseWorkspaceDoesNotExist
     */
    public function handleCreateWorkspace(CreateWorkspace $command)
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
        $this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $command->getContentStreamIdentifier(),
                $baseWorkspace->getCurrentContentStreamIdentifier()
            )
        );

        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $event = new WorkspaceWasCreated(
            $command->getWorkspaceName(),
            $command->getBaseWorkspaceName(),
            $command->getWorkspaceTitle(),
            $command->getWorkspaceDescription(),
            $command->getInitiatingUserIdentifier(),
            $command->getContentStreamIdentifier(),
            $command->getWorkspaceOwner()
        );
        $eventStore->commit($streamName, DomainEvents::withSingleEvent($event));
    }

    /**
     * @param CreateRootWorkspace $command
     * @throws WorkspaceAlreadyExists
     */
    public function handleCreateRootWorkspace(CreateRootWorkspace $command)
    {
        $existingWorkspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($existingWorkspace !== null) {
            throw new WorkspaceAlreadyExists(sprintf('The workspace %s already exists', $command->getWorkspaceName()), 1505848624450);
        }

        $contentStreamIdentifier = $command->getContentStreamIdentifier();
        $this->contentStreamCommandHandler->handleCreateContentStream(
            new CreateContentStream(
                $contentStreamIdentifier,
                $command->getInitiatingUserIdentifier()
            )
        );

        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $event = new RootWorkspaceWasCreated(
            $command->getWorkspaceName(),
            $command->getWorkspaceTitle(),
            $command->getWorkspaceDescription(),
            $command->getInitiatingUserIdentifier(),
            $contentStreamIdentifier
        );
        $eventStore->commit($streamName, DomainEvents::withSingleEvent($event));

        $this->nodeCommandHandler->handleCreateRootNode(
            new CreateRootNode(
                $contentStreamIdentifier,
                $command->getRootNodeIdentifier(),
                $command->getRootNodeTypeName(),
                $command->getInitiatingUserIdentifier()
            )
        );
    }

    /**
     * @param PublishWorkspace $command
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws \Neos\EventSourcing\EventStore\Exception\EventStreamNotFoundException
     * @throws \Exception
     */
    public function handlePublishWorkspace(PublishWorkspace $command)
    {
        $workspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($workspace === null) {
            throw new WorkspaceDoesNotExist(sprintf('The workspace %s does not exist', $command->getWorkspaceName()), 1513924741);
        }
        $baseWorkspace = $this->workspaceFinder->findOneByName($workspace->getBaseWorkspaceName());
        if ($baseWorkspace === null) {
            throw new BaseWorkspaceDoesNotExist(sprintf('The workspace %s (base workspace of %s) does not exist', $workspace->getBaseWorkspaceName(), $command->getWorkspaceName()), 1513924882);
        }
        // TODO this hack is currently needed in order to avoid the $workspace to return a previously cached content stream identifier ($workspace->getCurrentContentStreamIdentifier() did not work here in behat tests)
        $currentContentStreamIdentifier = $this->workspaceFinder->getContentStreamIdentifierForWorkspace($command->getWorkspaceName());
        $baseContentStreamIdentifier = $this->workspaceFinder->getContentStreamIdentifierForWorkspace($baseWorkspace->getWorkspaceName());


        // TODO: please check the code below in-depth. it does:
        // - copy all events from the "user" content stream which implement "CopyableAcrossContentStreamsInterface"
        // - extract the initial ContentStreamWasForked event, to read the version of the source content stream when the fork occurred
        // - ensure that no other changes have been done in the meantime in the base content stream
        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($currentContentStreamIdentifier)->getEventStreamName();
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($workspaceContentStreamName);

        /* @var $workspaceContentStream EventEnvelope[] */
        $workspaceContentStream = iterator_to_array($eventStore->load($workspaceContentStreamName));

        $events = [];
        foreach ($workspaceContentStream as $eventEnvelope) {
            $event = $eventEnvelope->getDomainEvent();
            if ($event instanceof CopyableAcrossContentStreamsInterface) {
                $events[] = $event->createCopyForContentStream($baseContentStreamIdentifier);
            }
        }

        // TODO: maybe we should also emit a "WorkspaceWasPublished" event? But on which content stream?

        $contentStreamWasForked = self::extractSingleForkedContentStreamEvent($workspaceContentStream);
        try {
            $baseWorkspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($baseContentStreamIdentifier)->getEventStreamName();
            $eventStore = $this->eventStoreManager->getEventStoreForStreamName($baseWorkspaceContentStreamName);
            $eventStore->commit($baseWorkspaceContentStreamName, DomainEvents::fromArray($events), $contentStreamWasForked->getVersionOfSourceContentStream());
        } catch (ConcurrencyException $e) {
            throw new BaseWorkspaceHasBeenModifiedInTheMeantime(sprintf('The base workspace "%s" (Content Stream "%s") has been modified in the meantime; please rebase workspace "%s". Expected version %d of source content stream "%s"', $baseWorkspace->getWorkspaceName(), $baseContentStreamIdentifier, $workspace->getWorkspaceName(), $contentStreamWasForked->getVersionOfSourceContentStream(), $currentContentStreamIdentifier), 1547823025);
        }
    }

    /**
     * @param array $stream
     * @return ContentStreamWasForked
     * @throws \Exception
     */
    private static function extractSingleForkedContentStreamEvent(array $stream) : ContentStreamWasForked
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
     * @throws BaseWorkspaceDoesNotExist
     * @throws WorkspaceDoesNotExist
     * @throws \Exception
     * @throws \Neos\EventSourcedContentRepository\Exception
     * @throws \Neos\EventSourcedContentRepository\Exception\NodeException
     * @throws \Neos\EventSourcedContentRepository\Exception\NodeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function handleRebaseWorkspace(RebaseWorkspace $command)
    {
        $workspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($workspace === null) {
            throw new WorkspaceDoesNotExist(sprintf('The workspace %s does not exist', $command->getWorkspaceName()), 1513924741);
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
        );
        // TODO hack!
        $this->eventBus->flush();

        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($workspace->getCurrentContentStreamIdentifier())->getEventStreamName();
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($workspaceContentStreamName);

        $workspaceContentStream = $eventStore->load($workspaceContentStreamName);
        foreach ($workspaceContentStream as $eventAndRawEvent) {
            $metadata = $eventAndRawEvent->getRawEvent()->getMetadata();
            if (!isset($metadata['commandClass'])) {
                continue;
            }
            $commandToRebaseClass = $metadata['commandClass'];
            $commandToRebasePayload = $metadata['commandPayload'];

            if (!method_exists($commandToRebaseClass, 'fromArray')) {
                throw new \RuntimeException(sprintf('Command "%s" can\'t be rebased because it does not implement a static "fromArray" constructor', $commandToRebaseClass), 1547815341);
            }
            // try to apply the command on the rebased content stream
            $commandToRebasePayload['contentStreamIdentifier'] = (string)$rebasedContentStream;

            $commandToRebase = $commandToRebaseClass::fromArray($commandToRebasePayload);

            // TODO: use a more clever dispatching mechanism than the hard coded switch!!
            switch (get_class($commandToRebase)) {
                case AddNodeToAggregate::class:
                    $this->nodeCommandHandler->handleAddNodeToAggregate($commandToRebase);
                    break;
                case ChangeNodeName::class:
                    $this->nodeCommandHandler->handleChangeNodeName($commandToRebase);
                    break;
                case CreateNodeAggregateWithNode::class:
                    $this->nodeCommandHandler->handleCreateNodeAggregateWithNode($commandToRebase);
                    break;
                case CreateRootNode::class:
                    $this->nodeCommandHandler->handleCreateRootNode($commandToRebase);
                    break;
                case MoveNode::class:
                    $this->nodeCommandHandler->handleMoveNode($commandToRebase);
                    break;
                case SetNodeProperty::class:
                    $this->nodeCommandHandler->handleSetNodeProperty($commandToRebase);
                    break;
                case HideNode::class:
                    $this->nodeCommandHandler->handleHideNode($commandToRebase);
                    break;
                case ShowNode::class:
                    $this->nodeCommandHandler->handleShowNode($commandToRebase);
                    break;
                case TranslateNodeInAggregate::class:
                    $this->nodeCommandHandler->handleTranslateNodeInAggregate($commandToRebase);
                    break;
                default:
                    throw new \Exception(sprintf('TODO: Command %s is not supported by handleRebaseWorkspace() currently... Please implement it there.', get_class($commandToRebase)));
            }
            // TODO hack!
            $this->eventBus->flush();
        }

        $streamName = StreamName::fromString('Neos.ContentRepository:Workspace:' . $command->getWorkspaceName());
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $event = new WorkspaceWasRebased(
            $command->getWorkspaceName(),
            $rebasedContentStream
        );
        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $eventStore->commit($streamName, DomainEvents::withSingleEvent($event));
    }
}
