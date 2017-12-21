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
use Neos\ContentRepository\Domain\Context\Node\Command\CreateRootNode;
use Neos\ContentRepository\Domain\Context\Node\NodeCommandHandler;
use Neos\ContentRepository\Domain\Context\Workspace\Command\CreateRootWorkspace;
use Neos\ContentRepository\Domain\Context\Workspace\Command\CreateWorkspace;
use Neos\ContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Domain\Context\Workspace\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Domain\Context\Workspace\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\EventSourcing\Event\EventPublisher;
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
     * @param CreateWorkspace $command
     * @throws WorkspaceAlreadyExists
     */
    public function handleCreateWorkspace(CreateWorkspace $command)
    {
        $existingWorkspace = $this->workspaceFinder->findOneByName($command->getWorkspaceName());
        if ($existingWorkspace !== null) {
            throw new WorkspaceAlreadyExists(sprintf('The workspace %s already exists', $command->getWorkspaceName()), 1505830958921);
        }

        // TODO: CONCEPT OF EDITING SESSION IS TOTALLY MISSING SO FAR!!!!
        // When the workspace is created, we first have to fork the content stream
        $this->contentStreamCommandHandler->handleForkContentStream(
            new ForkContentStream(
                $command->getContentStreamIdentifier(),
                $existingWorkspace->getCurrentContentStreamIdentifier()
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
                $command->getWorkspaceOwner(),
                $command->getContentStreamIdentifier()
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
}
