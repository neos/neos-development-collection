<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\WorkspaceEventStreamName;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Projection\Workspace\Workspaces;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceStatus;
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
     * @return Workspaces the workspaces of the removed content streams
     */
    public function rebaseOutdatedWorkspaces(?RebaseErrorHandlingStrategy $strategy = null): Workspaces
    {
        $outdatedWorkspaces = $this->contentRepository->getWorkspaces()->filter(
            fn (Workspace $workspace) => $workspace->status === WorkspaceStatus::OUTDATED
        );
        /** @var Workspace $workspace */
        foreach ($outdatedWorkspaces as $workspace) {
            if ($workspace->status !== WorkspaceStatus::OUTDATED) {
                continue;
            }
            $rebaseCommand = RebaseWorkspace::create(
                $workspace->workspaceName,
            );
            if ($strategy) {
                $rebaseCommand = $rebaseCommand->withErrorHandlingStrategy($strategy);
            }
            $this->contentRepository->handle($rebaseCommand);
        }

        return $outdatedWorkspaces;
    }

    public function pruneAll(): void
    {
        foreach ($this->contentRepository->getWorkspaces() as $workspace) {
            $streamName = WorkspaceEventStreamName::fromWorkspaceName($workspace->workspaceName)->getEventStreamName();
            $this->eventStore->deleteStream($streamName);
        }
    }
}
