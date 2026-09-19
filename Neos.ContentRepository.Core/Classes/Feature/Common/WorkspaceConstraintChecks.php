<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\CommandHandler\CommandHandlingDependencies;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Exception\DimensionSpacePointAlreadyExists;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Exception\InvalidDimensionAdjustmentTargetWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceContainsPublishableChanges;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceHasNoBaseWorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;

trait WorkspaceConstraintChecks
{
    /**
     * @throws WorkspaceDoesNotExist
     */
    private function requireWorkspace(WorkspaceName $workspaceName, CommandHandlingDependencies $commandHandlingDependencies): Workspace
    {
        $workspace = $commandHandlingDependencies->findWorkspaceByName($workspaceName);
        if (is_null($workspace)) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }

        return $workspace;
    }

    /**
     * @throws WorkspaceHasNoBaseWorkspaceName
     * @throws BaseWorkspaceDoesNotExist
     */
    private function requireBaseWorkspace(Workspace $workspace, CommandHandlingDependencies $commandHandlingDependencies): Workspace
    {
        if (is_null($workspace->baseWorkspaceName)) {
            throw WorkspaceHasNoBaseWorkspaceName::butWasSupposedTo($workspace->workspaceName);
        }
        $baseWorkspace = $commandHandlingDependencies->findWorkspaceByName($workspace->baseWorkspaceName);
        if (is_null($baseWorkspace)) {
            throw BaseWorkspaceDoesNotExist::butWasSupposedTo($workspace->workspaceName);
        }
        return $baseWorkspace;
    }

    private function requireWorkspaceToBeRootOrRootBasedForDimensionAdjustment(WorkspaceName $workspaceName, CommandHandlingDependencies $commandHandlingDependencies): void
    {
        $workspace = $this->requireWorkspace($workspaceName, $commandHandlingDependencies);
        if (!$workspace->isRootWorkspace()) {
            $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);
            if (!$baseWorkspace->isRootWorkspace()) {
                throw InvalidDimensionAdjustmentTargetWorkspace::becauseWorkspaceMustBeRootOrRootBased($workspace->workspaceName);
            }
        }
    }

    private static function requireNoWorkspaceToHaveChanges(Workspaces $workspaces): void
    {
        $conflictingWorkspaceNames = [];
        foreach ($workspaces as $workspace) {
            if ($workspace->hasPublishableChanges()) {
                $conflictingWorkspaceNames[] = $workspace->workspaceName;
            }
        }

        if ($conflictingWorkspaceNames !== []) {
            throw WorkspaceContainsPublishableChanges::butWasNotSupposedTo(...$conflictingWorkspaceNames);
        }
    }

    private static function requireDimensionSpacePointToBeEmptyInContentStream(
        ContentGraphInterface $contentGraph,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        $hasNodes = $contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::createEmpty())->countNodes();
        if ($hasNodes > 0) {
            throw new DimensionSpacePointAlreadyExists(sprintf(
                'the content stream %s already contained nodes in dimension space point %s - this is not allowed.',
                $contentGraph->getContentStreamId()->value,
                $dimensionSpacePoint->toJson(),
            ), 1612898126);
        }
    }
}
