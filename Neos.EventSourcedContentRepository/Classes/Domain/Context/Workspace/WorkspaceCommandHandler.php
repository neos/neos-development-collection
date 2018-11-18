<?php
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
use Neos\EventSourcing\Event\EventPublisher;
use Neos\EventSourcing\EventStore\EventAndRawEvent;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\Exception\ConcurrencyException;
use Neos\EventSourcing\EventStore\StreamNameFilter;
use Neos\EventSourcing\Property\AllowAllPropertiesPropertyMappingConfiguration;
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
     * @var EventPublisher
     */
    protected $eventPublisher;

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

        $this->eventPublisher->publish(
            'Neos.ContentRepository:Workspace:' . $command->getWorkspaceName(),
            new WorkspaceWasCreated(
                $command->getWorkspaceName(),
                $command->getBaseWorkspaceName(),
                $command->getWorkspaceTitle(),
                $command->getWorkspaceDescription(),
                $command->getInitiatingUserIdentifier(),
                $command->getContentStreamIdentifier(),
                $command->getWorkspaceOwner()
            )
        );
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


        $this->eventPublisher->publish(
            'Neos.ContentRepository:Workspace:' . $command->getWorkspaceName(),
            new RootWorkspaceWasCreated(
                $command->getWorkspaceName(),
                $command->getWorkspaceTitle(),
                $command->getWorkspaceDescription(),
                $command->getInitiatingUserIdentifier(),
                $contentStreamIdentifier
            )
        );

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
            throw new BaseWorkspaceDoesNotExist(sprintf('The workspace %s (base workspace of %s) does not exist', $command->getBaseWorkspaceName(), $command->getWorkspaceName()), 1513924882);
        }


        // TODO: please check the code below in-depth. it does:
        // - copy all events from the "user" content stream which implement "CopyableAcrossContentStreamsInterface"
        // - extract the initial ContentStreamWasForked event, to read the version of the source content stream when the fork occurred
        // - ensure that no other changes have been done in the meantime in the base content stream
        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($workspace->getCurrentContentStreamIdentifier());
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($workspaceContentStreamName);

        /* @var $workspaceContentStream EventAndRawEvent[] */
        $workspaceContentStream = iterator_to_array($eventStore->get(new StreamNameFilter($workspaceContentStreamName)));

        $events = [];
        foreach ($workspaceContentStream as $eventAndRawEvent) {
            $event = $eventAndRawEvent->getEvent();
            if ($event instanceof CopyableAcrossContentStreamsInterface) {
                $events[] = $event->createCopyForContentStream($baseWorkspace->getCurrentContentStreamIdentifier());
            }
        }

        // TODO: maybe we should also emit a "WorkspaceWasPublished" event? But on which content stream?

        $contentStreamWasForked = $this->extractSingleForkedContentStreamEvent($workspaceContentStream);
        try {
            $baseWorkspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($baseWorkspace->getCurrentContentStreamIdentifier());
            $this->eventPublisher->publishMany($baseWorkspaceContentStreamName, $events, $contentStreamWasForked->getVersionOfSourceContentStream());
        } catch (ConcurrencyException $e) {
            throw new BaseWorkspaceHasBeenModifiedInTheMeantime(sprintf('The base workspace has been modified in the meantime; please rebase. Expected version %d of source content stream %s', $contentStreamWasForked->getVersionOfSourceContentStream(), $baseWorkspace->getCurrentContentStreamIdentifier()));
        }
    }

    /**
     * @param array $stream
     * @return ContentStreamWasForked
     * @throws \Exception
     */
    protected static function extractSingleForkedContentStreamEvent(array $stream) : ContentStreamWasForked
    {
        $contentStreamWasForkedEvents = array_filter($stream, function (EventAndRawEvent $eventAndRawEvent) {
            return $eventAndRawEvent->getEvent() instanceof ContentStreamWasForked;
        });

        if (count($contentStreamWasForkedEvents) !== 1) {
            throw new \Exception(sprintf('TODO: only can publish a content stream which has exactly one ContentStreamWasForked; we found %d', count($contentStreamWasForkedEvents)));
        }

        if (reset($contentStreamWasForkedEvents)->getEvent() !== reset($stream)->getEvent()) {
            throw new \Exception(sprintf('TODO: stream has to start with a single ContentStreamWasForked event, found %s', get_class(reset($stream)->getEvent())));
        }

        return reset($contentStreamWasForkedEvents)->getEvent();
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
        $rebasedContentStream = new ContentStreamIdentifier();
        $this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $rebasedContentStream,
                $baseWorkspace->getCurrentContentStreamIdentifier()
            )
        );

        $workspaceContentStreamName = ContentStreamEventStreamName::fromContentStreamIdentifier($workspace->getCurrentContentStreamIdentifier());
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($workspaceContentStreamName);

        $workspaceContentStream = $eventStore->get(new StreamNameFilter($workspaceContentStreamName));
        foreach ($workspaceContentStream as $eventAndRawEvent) {
            $metadata = $eventAndRawEvent->getRawEvent()->getMetadata();
            if (isset($metadata['commandClass'])) {
                $commandToRebaseClass = $metadata['commandClass'];
                $commandToRebasePayload = $metadata['commandPayload'];

                // try to apply the command on the rebased content stream
                $commandToRebasePayload['contentStreamIdentifier'] = (string)$rebasedContentStream;

                $commandToRebase = $this->propertyMapper->convert($commandToRebasePayload, $commandToRebaseClass, new AllowAllPropertiesPropertyMappingConfiguration());

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
            }
        }

        // if we got so far without an Exception, we can switch the Workspace's active Content stream.
        $this->eventPublisher->publish(
            'Neos.ContentRepository:Workspace:' . $command->getWorkspaceName(),
            new WorkspaceWasRebased(
                $command->getWorkspaceName(),
                $rebasedContentStream
            )
        );
    }
}
