<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Service;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

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
     * @throws \Neos\ContentRepository\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist
     * @throws \Neos\ContentRepository\Feature\Common\Exception\WorkspaceDoesNotExist
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function rebaseOutdatedWorkspaces(): array
    {
        $outdatedWorkspaces = $this->contentRepository->getWorkspaceFinder()->findOutdated();

        foreach ($outdatedWorkspaces as $workspace) {
            $this->contentRepository->handle(RebaseWorkspace::create(
                $workspace->getWorkspaceName(),
                UserIdentifier::forSystemUser()
            ))->block();
        }

        return $outdatedWorkspaces;
    }
}
