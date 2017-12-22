<?php
namespace Neos\ContentRepository\Domain\Context\Workspace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\ContentStream\Command\CreateContentStream;
use Neos\ContentRepository\Domain\Context\ContentStream\Command\ForkContentStream;
use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\ContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasForked;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateRootNode;
use Neos\ContentRepository\Domain\Context\Node\Event\CopyableAcrossContentStreamsInterface;
use Neos\ContentRepository\Domain\Context\Node\NodeCommandHandler;
use Neos\ContentRepository\Domain\Context\Workspace\Command\CreateRootWorkspace;
use Neos\ContentRepository\Domain\Context\Workspace\Command\CreateWorkspace;
use Neos\ContentRepository\Domain\Context\Workspace\Command\PublishWorkspace;
use Neos\ContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Domain\Context\Workspace\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Domain\Context\Workspace\Exception\BaseWorkspaceDoesNotExist;
use Neos\ContentRepository\Domain\Context\Workspace\Exception\BaseWorkspaceHasBeenModifiedInTheMeantime;
use Neos\ContentRepository\Domain\Context\Workspace\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Domain\Context\Workspace\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcing\Event\EventPublisher;
use Neos\EventSourcing\EventStore\EventAndRawEvent;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\Exception\ConcurrencyException;
use Neos\EventSourcing\EventStore\StreamNameFilter;
use Neos\Flow\Annotations as Flow;

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
        $workspaceContentStreamName = ContentStreamCommandHandler::getStreamNameForContentStream($workspace->getCurrentContentStreamIdentifier());
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
            $baseWorkspaceContentStreamName = ContentStreamCommandHandler::getStreamNameForContentStream($baseWorkspace->getCurrentContentStreamIdentifier());
            $this->eventPublisher->publishMany($baseWorkspaceContentStreamName, $events, $contentStreamWasForked->getVersionOfSourceContentStream());
        } catch (ConcurrencyException $e) {
            throw new BaseWorkspaceHasBeenModifiedInTheMeantime('The base workspace has been modified in the meantime; please rebase');
        }
    }

    /**
     * @param array $stream
     * @return ContentStreamWasForked
     * @throws \Exception
     */
    protected static function extractSingleForkedContentStreamEvent(array $stream) : ContentStreamWasForked
    {
        $contentStreamWasForkedEvents = array_filter($stream, function(EventAndRawEvent $eventAndRawEvent) {
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
}
