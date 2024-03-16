<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Command;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceStatus;
use Neos\ContentRepository\Core\Service\WorkspaceMaintenanceServiceFactory;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\PendingChangesProjection\ChangeFinder;

/**
 * The Workspace Command Controller
 */
#[Flow\Scope('singleton')]
class WorkspaceCommandController extends CommandController
{
    #[Flow\Inject]
    protected UserService $userService;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * Publish changes of a workspace
     *
     * This command publishes all modified, created or deleted nodes in the specified workspace to its base workspace.
     *
     * @param string $workspace Name of the workspace containing the changes to publish, for example "user-john"
     * @param string $contentRepositoryIdentifier
     */
    public function publishCommand(string $workspace, string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $contentRepository->handle(PublishWorkspace::create(
            WorkspaceName::fromString($workspace),
        ))->block();

        $this->outputLine(
            'Published all nodes in workspace %s to its base workspace',
            [$workspace]
        );
    }

    /**
     * Discard changes in workspace
     *
     * This command discards all modified, created or deleted nodes in the specified workspace.
     *
     * @param string $workspace Name of the workspace, for example "user-john"
     * @param string $contentRepositoryIdentifier
     * @throws StopCommandException
     */
    public function discardCommand(string $workspace, string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        try {
            $contentRepository->handle(
                DiscardWorkspace::create(
                    WorkspaceName::fromString($workspace),
                )
            )->block();
        } catch (WorkspaceDoesNotExist $exception) {
            $this->outputLine('Workspace "%s" does not exist', [$workspace]);
            $this->quit(1);
        }
        $this->outputLine('Discarded all nodes in workspace %s', [$workspace]);
    }

    /**
     * Rebase workspace on base workspace
     *
     * This command rebases the given workspace on its base workspace, it may fail if the rebase is not possible.
     *
     * @param string $workspace Name of the workspace, for example "user-john"
     * @param string $contentRepositoryIdentifier
     * @param bool $force Rebase all events that do not conflict
     * @throws StopCommandException
     */
    public function rebaseCommand(string $workspace, string $contentRepositoryIdentifier = 'default', bool $force = false): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        try {
            $rebaseCommand = RebaseWorkspace::create(
                WorkspaceName::fromString($workspace),
            );
            if ($force) {
                $rebaseCommand = $rebaseCommand->withErrorHandlingStrategy(RebaseErrorHandlingStrategy::STRATEGY_FORCE);
            }
            $contentRepository->handle($rebaseCommand)->block();
        } catch (WorkspaceDoesNotExist $exception) {
            $this->outputLine('Workspace "%s" does not exist', [$workspace]);
            $this->quit(1);
        }

        $workspaceObject = $contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::fromString($workspace));
        if ($workspaceObject && $workspaceObject->status === WorkspaceStatus::OUTDATED_CONFLICT) {
            $this->outputLine('Rebasing of workspace %s is not possible due to conflicts. You can try the --force option.', [$workspace]);
            $this->quit(1);
        }

        $this->outputLine('Rebased workspace %s', [$workspace]);
    }

    /**
     * Create a new root workspace for a content repository.
     *
     * @param string $name
     * @param string $contentRepositoryIdentifier
     */
    public function createRootCommand(string $name, string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $contentRepository->handle(CreateRootWorkspace::create(
            WorkspaceName::fromString($name),
            WorkspaceTitle::fromString($name),
            WorkspaceDescription::fromString($name),
            ContentStreamId::create()
        ))->block();
    }

    /**
     * Create a new workspace
     *
     * This command creates a new workspace.
     *
     * @param string $workspace Name of the workspace, for example "christmas-campaign"
     * @param string $baseWorkspace Name of the base workspace. If none is specified, "live" is assumed.
     * @param string|null $title Human friendly title of the workspace, for example "Christmas Campaign"
     * @param string|null $description A description explaining the purpose of the new workspace
     * @param string $owner The identifier of a User to own the workspace
     * @param string $contentRepositoryIdentifier
     * @throws StopCommandException
     */
    public function createCommand(
        string $workspace,
        string $baseWorkspace = 'live',
        string $title = null,
        string $description = null,
        string $owner = '',
        string $contentRepositoryIdentifier = 'default'
    ): void {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        if ($owner === '') {
            $workspaceOwnerUserId = null;
        } else {
            $workspaceOwnerUserId = UserId::fromString($owner);
            $workspaceOwner = $this->userService->findByUserIdentifier($workspaceOwnerUserId);
            if ($workspaceOwner === null) {
                $this->outputLine('The user "%s" specified as owner does not exist', [$owner]);
                $this->quit(3);
            }
        }

        try {
            $contentRepository->handle(CreateWorkspace::create(
                WorkspaceName::fromString($workspace),
                WorkspaceName::fromString($baseWorkspace),
                WorkspaceTitle::fromString($title ?: $workspace),
                WorkspaceDescription::fromString($description ?: $workspace),
                ContentStreamId::create(),
                $workspaceOwnerUserId
            ))->block();
        } catch (WorkspaceAlreadyExists $workspaceAlreadyExists) {
            $this->outputLine('Workspace "%s" already exists', [$workspace]);
            $this->quit(1);
        } catch (BaseWorkspaceDoesNotExist $baseWorkspaceDoesNotExist) {
            $this->outputLine('The base workspace "%s" does not exist', [$baseWorkspace]);
            $this->quit(2);
        }

        if ($workspaceOwnerUserId instanceof UserId) {
            $this->outputLine(
                'Created a new workspace "%s", based on workspace "%s", owned by "%s".',
                [$workspace, $baseWorkspace, $owner]
            );
        } else {
            $this->outputLine(
                'Created a new workspace "%s", based on workspace "%s".',
                [$workspace, $baseWorkspace]
            );
        }
    }

    /**
     * Deletes a workspace
     *
     * This command deletes a workspace. If you only want to empty a workspace and not delete the
     * workspace itself, use <i>workspace:discard</i> instead.
     *
     * @param string $workspace Name of the workspace, for example "christmas-campaign"
     * @param boolean $force Delete the workspace and all of its contents
     * @see neos.neos:workspace:discard
     */
    public function deleteCommand(string $workspace, bool $force = false, string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspaceName = WorkspaceName::fromString($workspace);
        if ($workspaceName->isLive()) {
            $this->outputLine('Did not delete workspace "live" because it is required for Neos CMS to work properly.');
            $this->quit(2);
        }

        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if (!$workspace instanceof Workspace) {
            $this->outputLine('Workspace "%s" does not exist', [$workspaceName->value]);
            $this->quit(1);
        }

        if ($workspace->isPersonalWorkspace()) {
            $this->outputLine(
                'Did not delete workspace "%s" because it is a personal workspace.'
                    . ' Personal workspaces cannot be deleted manually.',
                [$workspaceName->value]
            );
            $this->quit(2);
        }

        $dependentWorkspaces = $contentRepository->getWorkspaceFinder()->findByBaseWorkspace($workspaceName);
        if (count($dependentWorkspaces) > 0) {
            $this->outputLine(
                'Workspace "%s" cannot be deleted because the following workspaces are based on it:',
                [$workspaceName->value]
            );
            $this->outputLine();
            $tableRows = [];
            $headerRow = ['Name', 'Title', 'Description'];

            foreach ($dependentWorkspaces as $dependentWorkspace) {
                $tableRows[] = [
                    $dependentWorkspace->workspaceName->value,
                    $dependentWorkspace->workspaceTitle->value,
                    $dependentWorkspace->workspaceDescription->value
                ];
            }
            $this->output->outputTable($tableRows, $headerRow);
            $this->quit(3);
        }

        try {
            $nodesCount = $contentRepository->projectionState(ChangeFinder::class)
                ->countByContentStreamId(
                    $workspace->currentContentStreamId
                );
        } catch (\Exception $exception) {
            $this->outputLine('Could not fetch unpublished nodes for workspace %s, nothing was deleted. %s', [$workspace->workspaceName->value, $exception->getMessage()]);
            $this->quit(4);
        }

        if ($nodesCount > 0) {
            if ($force === false) {
                $this->outputLine(
                    'Did not delete workspace "%s" because it contains %s unpublished node(s).'
                        . ' Use --force to delete it nevertheless.',
                    [$workspaceName->value, $nodesCount]
                );
                $this->quit(5);
            }
            $contentRepository->handle(
                DiscardWorkspace::create($workspaceName)
            )->block();
        }

        $contentRepository->handle(
            DeleteWorkspace::create(
                $workspaceName
            )
        )->block();
        $this->outputLine('Deleted workspace "%s"', [$workspaceName->value]);
    }

    /**
     * Rebase all outdated content streams
     */
    public function rebaseOutdatedCommand(string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $workspaceMaintenanceService = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new WorkspaceMaintenanceServiceFactory()
        );
        $outdatedWorkspaces = $workspaceMaintenanceService->rebaseOutdatedWorkspaces();

        if (!count($outdatedWorkspaces)) {
            $this->outputLine('There are no outdated workspaces.');
        } else {
            foreach ($outdatedWorkspaces as $outdatedWorkspace) {
                $this->outputFormatted('Rebased workspace %s', [$outdatedWorkspace->workspaceName->value]);
            }
        }
    }

    /**
     * Display a list of existing workspaces
     *
     * @throws StopCommandException
     */
    public function listCommand(string $contentRepositoryIdentifier = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdentifier);
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspaces = $contentRepository->getWorkspaceFinder()->findAll();

        if (count($workspaces) === 0) {
            $this->outputLine('No workspaces found.');
            $this->quit(0);
        }

        $tableRows = [];
        $headerRow = ['Name', 'Base Workspace', 'Title', 'Owner', 'Description', 'Status', 'Content Stream'];

        foreach ($workspaces as $workspace) {
            /* @var Workspace $workspace */
            $tableRows[] = [
                $workspace->workspaceName->value,
                $workspace->baseWorkspaceName?->value ?: '',
                $workspace->workspaceTitle->value,
                $workspace->workspaceOwner ?: '',
                $workspace->workspaceDescription->value,
                $workspace->status->value,
                $workspace->currentContentStreamId->value,
            ];
        }
        $this->output->outputTable($tableRows, $headerRow);
    }
}
