<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Service;

use Doctrine\DBAL\Connection;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\RebaseWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\WorkspaceCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class WorkspaceMaintenanceService
{

    /**
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @var WorkspaceCommandHandler
     */
    protected $workspaceCommandHandler;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ?CommandResult
     */
    protected $lastCommandResult;

    /**
     * ContentStreamPruner constructor.
     * @param WorkspaceFinder $workspaceFinder
     * @param WorkspaceCommandHandler $workspaceCommandHandler
     * @param DbalClient $dbalClient
     */
    public function __construct(WorkspaceFinder $workspaceFinder, WorkspaceCommandHandler $workspaceCommandHandler, DbalClient $dbalClient)
    {
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
     *       To remove the deleted Content Streams, call {@see ContentStreamPruner::pruneRemovedFromEventStream()} afterwards.
     *
     * @return WorkspaceName[] the removed content streams
     */
    public function rebaseOutdatedWorkspaces(): array
    {
        $outdatedWorkspaces = $this->workspaceFinder->findOutdated();

        foreach ($outdatedWorkspaces as $workspace) {
            $this->lastCommandResult = $this->workspaceCommandHandler->handleRebaseWorkspace(new RebaseWorkspace($workspace));
        }

        return $outdatedWorkspaces;
    }
}
