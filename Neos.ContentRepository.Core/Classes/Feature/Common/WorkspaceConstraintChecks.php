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
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceHasWorkspacesDependingOnIt;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceIsDeactivated;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;

trait WorkspaceConstraintChecks
{
    /**
     * @throws WorkspaceDoesNotExist
     * @phpstan-return Workspace
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
     * @throws WorkspaceDoesNotExist|WorkspaceIsDeactivated
     * @phpstan-return object{ currentContentStreamId: ContentStreamId, status: WorkspaceStatus::UP_TO_DATE|WorkspaceStatus::OUTDATED } & Workspace
     */
    private function requireActiveWorkspace(WorkspaceName $workspaceName, CommandHandlingDependencies $commandHandlingDependencies): Workspace
    {
        $workspace = $this->requireWorkspace($workspaceName, $commandHandlingDependencies);
        if (!$workspace->isActive()) {
            throw WorkspaceIsDeactivated::butWasSupposedToBeActivated($workspaceName);
        }

        // DANGEROUS error of type return.type ignored
        // This is done due to phpstan being unable to deduce that the returned workspace actually fulfills all
        // required conditions. This is guaranteed by `$workspace->isActive()` returning true:
        // isActive() is annotated with
        // @ phpstan-assert-if-true ContentStreamId $this->currentContentStreamId
        // @ phpstan-assert-if-true WorkspaceStatus::UP_TO_DATE|WorkspaceStatus::OUTDATED $this->status
        // which are the same guarantees this method makes, but in a syntactical different way.
        return $workspace; // @phpstan-ignore return.type
    }

    /**
     * @throws WorkspaceHasNoBaseWorkspaceName
     * @throws BaseWorkspaceDoesNotExist
     * @phpstan-return object{ currentContentStreamId: ContentStreamId, status: WorkspaceStatus::UP_TO_DATE|WorkspaceStatus::OUTDATED } & Workspace
     */
    private function requireBaseWorkspace(Workspace $workspace, CommandHandlingDependencies $commandHandlingDependencies): Workspace
    {
        if (is_null($workspace->baseWorkspaceName)) {
            throw WorkspaceHasNoBaseWorkspaceName::butWasSupposedTo($workspace->workspaceName);
        }
        $baseWorkspace = $commandHandlingDependencies->findWorkspaceByName($workspace->baseWorkspaceName);
        if (is_null($baseWorkspace)) {
            throw BaseWorkspaceDoesNotExist::butWasSupposedTo($workspace->workspaceName);
        } elseif (!$baseWorkspace->isActive()) {
            // should never happen!
            // TODO: if this happens, something is seriously wrong in the database, handle differently?
            throw WorkspaceIsDeactivated::butWasSupposedToBeActivated($workspace->baseWorkspaceName);
        }

        // @phpstan-ignore-next-line
        return $baseWorkspace; // @phpstan-ignore return.type
    }

    private function requireWorkspaceToBeRootOrRootBasedForDimensionAdjustment(WorkspaceName $workspaceName, CommandHandlingDependencies $commandHandlingDependencies): void
    {
        $workspace = $this->requireActiveWorkspace($workspaceName, $commandHandlingDependencies);
        if (!$workspace->isRootWorkspace()) {
            $baseWorkspace = $this->requireBaseWorkspace($workspace, $commandHandlingDependencies);
            if (!$baseWorkspace->isRootWorkspace()) {
                throw InvalidDimensionAdjustmentTargetWorkspace::becauseWorkspaceMustBeRootOrRootBased($workspace->workspaceName);
            }
        }
    }

    private function requireWorkspaceToNotBeBaseForAnyOtherWorkspace(WorkspaceName $workspaceName, CommandHandlingDependencies $commandHandlingDependencies): void
    {
        $workspaces = $commandHandlingDependencies->findAllWorkspaces();
        $conflictingWorkspaceNames = [];
        foreach ($workspaces as $workspace) {
            if ($workspace->baseWorkspaceName === $workspaceName) {
                $conflictingWorkspaceNames[] = $workspace->workspaceName;
            }
        }
        if ($conflictingWorkspaceNames !== []) {
            throw WorkspaceHasWorkspacesDependingOnIt::butWasNotSupposedTo(
                $workspaceName,
                ...$conflictingWorkspaceNames
            );
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
