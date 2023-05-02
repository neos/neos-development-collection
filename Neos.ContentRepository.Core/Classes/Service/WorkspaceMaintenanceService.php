<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\WorkspaceEventStreamName;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\EventStore\EventStoreInterface;

/**
 * @api
 */
class WorkspaceMaintenanceService implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly EventStoreInterface $eventStore,
    ) {
    }

    /**
     * @return array<string,Workspace> the workspaces of the removed content streams
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist
     * @throws \Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist
     */
    public function rebaseOutdatedWorkspaces(): array
    {
        $outdatedWorkspaces = $this->contentRepository->getWorkspaceFinder()->findOutdated();

        foreach ($outdatedWorkspaces as $workspace) {
            /* @var Workspace $workspace */
            $this->contentRepository->handle(RebaseWorkspace::create(
                $workspace->workspaceName,
            ))->block();
        }

        return $outdatedWorkspaces;
    }

    public function pruneAll(): void
    {
        $workspaces = $this->contentRepository->getWorkspaceFinder()->findAll();

        foreach ($workspaces as $workspace) {
            $streamName = WorkspaceEventStreamName::fromWorkspaceName($workspace->workspaceName)->getEventStreamName();
            $this->eventStore->deleteStream($streamName);
        }
    }
}
