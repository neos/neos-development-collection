<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;

/**
 * @api
 */
class WorkspaceMaintenanceService implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly ContentRepository $contentRepository
    ) {
    }

    /**
     * @return Workspaces the rebased workspaces
     */
    public function rebaseOutdatedWorkspaces(?RebaseErrorHandlingStrategy $strategy = null): Workspaces
    {
        $rebasedWorkspaces = [];
        $workspaces = $this->contentRepository->findWorkspaces();
        foreach ($workspaces->getRootWorkspaces() as $rootWorkspace) {
            // root workspaces can by definition neither be outdated nor rebased
            $this->rebaseOutdatedDependentWorkspaces($rootWorkspace, $workspaces, $strategy, $rebasedWorkspaces);
        }

        return Workspaces::fromArray($rebasedWorkspaces);
    }

    /**
     * @param list<Workspace> $rebasedWorkspaces
     */
    private function rebaseOutdatedDependentWorkspaces(Workspace $workspace, Workspaces $workspaces, ?RebaseErrorHandlingStrategy $strategy, array &$rebasedWorkspaces): void
    {
        foreach ($workspaces->getDependantWorkspaces($workspace->workspaceName) as $dependentWorkspace) {
            if ($dependentWorkspace->status === WorkspaceStatus::OUTDATED) {
                $this->rebaseWorkspaceAndDependants($dependentWorkspace, $workspaces, $strategy, $rebasedWorkspaces);
            } else {
                $this->rebaseOutdatedDependentWorkspaces($dependentWorkspace, $workspaces, $strategy, $rebasedWorkspaces);
            }
        }
    }

    /**
     * @param list<Workspace> $rebasedWorkspaces
     */
    private function rebaseWorkspaceAndDependants(Workspace $workspace, Workspaces $workspaces, ?RebaseErrorHandlingStrategy $strategy, array &$rebasedWorkspaces): void
    {
        $rebaseCommand = RebaseWorkspace::create(
            $workspace->workspaceName,
        );
        if ($strategy) {
            $rebaseCommand = $rebaseCommand->withErrorHandlingStrategy($strategy);
        }
        $this->contentRepository->handle($rebaseCommand);
        $rebasedWorkspaces[] = $workspace;

        foreach ($workspaces->getDependantWorkspaces($workspace->workspaceName) as $dependentWorkspace) {
            $this->rebaseWorkspaceAndDependants($dependentWorkspace, $workspaces, $strategy, $rebasedWorkspaces);
        }
    }
}
