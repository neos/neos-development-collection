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

use Neos\ContentRepository\Domain\Context\Workspace\Command\CreateWorkspace;
use Neos\ContentRepository\Domain\Context\Workspace\Event\WorkspaceHasBeenCreated;
use Neos\ContentRepository\Domain\Context\Workspace\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
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

        $this->eventPublisher->publish(
            'Neos.ContentRepository:Workspace:' . $command->getWorkspaceName(),
            new WorkspaceHasBeenCreated(
                $command->getWorkspaceName(),
                $command->getBaseWorkspaceName(),
                $command->getWorkspaceTitle(),
                $command->getWorkspaceDescription(),
                $command->getWorkspaceOwner()
            )
        );
    }
}
