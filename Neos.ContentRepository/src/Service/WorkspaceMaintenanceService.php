<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Service;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

class WorkspaceMaintenanceService
{
    protected WorkspaceFinder $workspaceFinder;

    protected WorkspaceCommandHandler $workspaceCommandHandler;

    protected Connection $connection;

    protected ?CommandResult $lastCommandResult;

    public function __construct(
        WorkspaceFinder $workspaceFinder,
        WorkspaceCommandHandler $workspaceCommandHandler,
        DbalClientInterface $dbalClient
    ) {
        $this->workspaceFinder = $workspaceFinder;
        $this->workspaceCommandHandler = $workspaceCommandHandler;
        $this->connection = $dbalClient->getConnection();
    }

    /**
     * Remove all content streams which are not needed anymore from the projections.
     *
     * NOTE: This still **keeps** the event stream as is; so it would be possible to re-construct the content stream
     *       at a later point in time (though we currently do not provide any API for it).
     *
     *       To remove the deleted Content Streams,
     *       call {@see ContentStreamPruner::pruneRemovedFromEventStream()} afterwards.
     *
     * @return array<string,Workspace> the workspaces of the removed content streams
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \Neos\ContentRepository\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist
     * @throws \Neos\ContentRepository\Feature\Common\Exception\WorkspaceDoesNotExist
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function rebaseOutdatedWorkspaces(): array
    {
        $outdatedWorkspaces = $this->workspaceFinder->findOutdated();

        foreach ($outdatedWorkspaces as $workspace) {
            $this->lastCommandResult = $this->workspaceCommandHandler->handleRebaseWorkspace(RebaseWorkspace::create(
                $workspace->getWorkspaceName(),
                UserIdentifier::forSystemUser()
            ));
        }

        return $outdatedWorkspaces;
    }
}
