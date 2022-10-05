<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\User\UserId;

/**
 * @api
 */
class WorkspaceMaintenanceService implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
    ) {
    }

    /**
     * @return array<string,Workspace> the workspaces of the removed content streams
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws \Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist
     * @throws \Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
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
}
