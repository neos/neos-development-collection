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

use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Service\WorkspaceMaintenanceServiceFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\WorkspaceClassification;
use Neos\Neos\Domain\Model\WorkspaceDescription as NeosWorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceTitle as NeosWorkspaceTitle;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Domain\Service\WorkspacePublishingService;
use Neos\Neos\Domain\Service\WorkspaceService;

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

    #[Flow\Inject]
    protected WorkspacePublishingService $workspacePublishingService;

    #[Flow\Inject]
    protected WorkspaceService $workspaceService;

    /**
     * Publish changes of a workspace
     *
     * This command publishes all modified, created or deleted nodes in the specified workspace to its base workspace.
     *
     * @param string $workspace Name of the workspace containing the changes to publish, for example "user-john"
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     */
    public function publishCommand(string $workspace, string $contentRepository = 'default'): void
    {
        $this->workspacePublishingService->publishWorkspace(
            ContentRepositoryId::fromString($contentRepository),
            WorkspaceName::fromString($workspace)
        );

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
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @throws StopCommandException
     */
    public function discardCommand(string $workspace, string $contentRepository = 'default'): void
    {
        try {
            // @todo: bypass access control
            $this->workspacePublishingService->discardAllWorkspaceChanges(
                ContentRepositoryId::fromString($contentRepository),
                WorkspaceName::fromString($workspace)
            );
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
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @param bool $force Rebase all events that do not conflict
     * @throws StopCommandException
     */
    public function rebaseCommand(string $workspace, string $contentRepository = 'default', bool $force = false): void
    {
        try {
            // @todo: bypass access control
            $this->workspacePublishingService->rebaseWorkspace(
                ContentRepositoryId::fromString($contentRepository),
                WorkspaceName::fromString($workspace),
                $force ? RebaseErrorHandlingStrategy::STRATEGY_FORCE : RebaseErrorHandlingStrategy::STRATEGY_FAIL,
            );
        } catch (WorkspaceDoesNotExist $exception) {
            $this->outputLine('Workspace "%s" does not exist', [$workspace]);
            $this->quit(1);
        } catch (WorkspaceRebaseFailed $exception) {
            $this->outputLine('Rebasing of workspace %s is not possible due to conflicts. You can try the --force option.', [$workspace]);
            $this->quit(1);
        }

        $this->outputLine('Rebased workspace %s', [$workspace]);
    }

    /**
     * Create a new root workspace for a content repository.
     *
     * @param string $name Name of the new root
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @param string|null $description Optional description of the workspace
     */
    public function createRootCommand(string $name, string $contentRepository = 'default', string $description = null): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $workspaceName = $this->workspaceService->createRootWorkspace(
            $contentRepositoryId,
            NeosWorkspaceTitle::fromString($name),
            NeosWorkspaceDescription::fromString($description ?? $name)
        );
        $this->outputLine('Created root workspace "%s" in content repository "%s"', [$workspaceName->value, $contentRepositoryId->value]);
    }

    /**
     * Create a new workspace
     *
     * This command creates a new personal workspace.
     *
     * @param string $workspace Name of the workspace, for example "christmas-campaign"
     * @param string $baseWorkspace Name of the base workspace. If none is specified, "live" is assumed.
     * @param string|null $title Human friendly title of the workspace, for example "Christmas Campaign"
     * @param string|null $description A description explaining the purpose of the new workspace
     * @param string $owner The identifier of a User to own the workspace
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @throws StopCommandException
     */
    public function createCommand(
        string $workspace,
        string $baseWorkspace = 'live',
        string $title = null,
        string $description = null,
        string $owner = '',
        string $contentRepository = 'default'
    ): void {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);

        $this->workspaceService->createPersonalWorkspace(
            $contentRepositoryId,
            NeosWorkspaceTitle::fromString($title ?? $workspace),
            NeosWorkspaceDescription::fromString($description ?? ''),
            WorkspaceName::fromString($baseWorkspace),
            UserId::fromString($owner),
        );

        $contentRepositoryInstance = $this->contentRepositoryRegistry->get($contentRepositoryId);

        if ($owner === '') {
            $workspaceOwnerUserId = null;
        } else {
            $workspaceOwnerUserId = UserId::fromString($owner);
            $workspaceOwner = $this->userService->findUserById($workspaceOwnerUserId);
            if ($workspaceOwner === null) {
                $this->outputLine('The user "%s" specified as owner does not exist', [$owner]);
                $this->quit(3);
            }
        }

        try {
            $contentRepositoryInstance->handle(CreateWorkspace::create(
                WorkspaceName::fromString($workspace),
                WorkspaceName::fromString($baseWorkspace),
                WorkspaceTitle::fromString($title ?: $workspace),
                WorkspaceDescription::fromString($description ?: $workspace),
                ContentStreamId::create(),
                $workspaceOwnerUserId !== null ? \Neos\ContentRepository\Core\SharedModel\User\UserId::fromString($workspaceOwnerUserId->value) : null,
            ));
        } catch (WorkspaceAlreadyExists $workspaceAlreadyExists) {
            $this->outputLine('Workspace "%s" already exists', [$workspace]);
            $this->quit(1);
        } catch (BaseWorkspaceDoesNotExist $baseWorkspaceDoesNotExist) {
            $this->outputLine('The base workspace "%s" does not exist', [$baseWorkspace]);
            $this->quit(2);
        }

        if ($workspaceOwnerUserId !== null) {
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
     * @param string $contentRepository The name of the content repository. (Default: 'default')
     * @see neos.neos:workspace:discard
     */
    public function deleteCommand(string $workspace, bool $force = false, string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentRepositoryInstance = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspaceName = WorkspaceName::fromString($workspace);
        if ($workspaceName->isLive()) {
            $this->outputLine('Did not delete workspace "live" because it is required for Neos CMS to work properly.');
            $this->quit(2);
        }

        $crWorkspace = $contentRepositoryInstance->getWorkspaceFinder()->findOneByName($workspaceName);
        if ($crWorkspace === null) {
            $this->outputLine('Workspace "%s" does not exist', [$workspaceName->value]);
            $this->quit(1);
        }
        $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $workspaceName);

        if ($workspaceMetadata->classification === WorkspaceClassification::PERSONAL) {
            $this->outputLine(
                'Did not delete workspace "%s" because it is a personal workspace.'
                    . ' Personal workspaces cannot be deleted manually.',
                [$workspaceName->value]
            );
            $this->quit(2);
        }

        $dependentWorkspaces = $contentRepositoryInstance->getWorkspaceFinder()->findByBaseWorkspace($workspaceName);
        if (count($dependentWorkspaces) > 0) {
            $this->outputLine(
                'Workspace "%s" cannot be deleted because the following workspaces are based on it:',
                [$workspaceName->value]
            );

            $this->outputLine();
            $tableRows = [];
            $headerRow = ['Name', 'Title', 'Description'];

            foreach ($dependentWorkspaces as $dependentWorkspace) {
                $dependentWorkspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $dependentWorkspace->workspaceName);
                $tableRows[] = [
                    $dependentWorkspace->workspaceName->value,
                    $dependentWorkspaceMetadata->title->value,
                    $dependentWorkspaceMetadata->description->value
                ];
            }
            $this->output->outputTable($tableRows, $headerRow);
            $this->quit(3);
        }


        try {
            $nodesCount = $this->workspacePublishingService->countPendingWorkspaceChanges($contentRepositoryId, $workspaceName);
        } catch (\Exception $exception) {
            $this->outputLine('Could not fetch unpublished nodes for workspace %s, nothing was deleted. %s', [$workspaceName->value, $exception->getMessage()]);
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
            // @todo bypass access control?
            $this->workspacePublishingService->discardAllWorkspaceChanges($contentRepositoryId, $workspaceName);
        }

        $contentRepositoryInstance->handle(
            DeleteWorkspace::create(
                $workspaceName
            )
        );
        $this->outputLine('Deleted workspace "%s"', [$workspaceName->value]);
    }

    /**
     * Rebase all outdated content streams
     *
     * @param string $contentRepository The name of the content repository. (Default: 'default')
     * @param boolean $force
     */
    public function rebaseOutdatedCommand(string $contentRepository = 'default', bool $force = false): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $workspaceMaintenanceService = $this->contentRepositoryRegistry->buildService(
            $contentRepositoryId,
            new WorkspaceMaintenanceServiceFactory()
        );
        $outdatedWorkspaces = $workspaceMaintenanceService->rebaseOutdatedWorkspaces(
            $force ? RebaseErrorHandlingStrategy::STRATEGY_FORCE : RebaseErrorHandlingStrategy::STRATEGY_FAIL
        );

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
     * @param string $contentRepository The name of the content repository. (Default: 'default')
     * @throws StopCommandException
     */
    public function listCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentRepositoryInstance = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspaces = $contentRepositoryInstance->getWorkspaceFinder()->findAll();

        if (count($workspaces) === 0) {
            $this->outputLine('No workspaces found.');
            $this->quit(0);
        }

        $tableRows = [];
        $headerRow = ['Name', 'Classification', 'Base Workspace', 'Title', 'Description', 'Status', 'Content Stream'];

        foreach ($workspaces as $workspace) {
            $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $workspace->workspaceName);

            /* @var Workspace $workspace */
            $tableRows[] = [
                $workspace->workspaceName->value,
                $workspaceMetadata->classification->value,
                $workspace->baseWorkspaceName?->value ?: '-',
                $workspaceMetadata->title->value,
                $workspaceMetadata->description->value,
                $workspace->status->value,
                $workspace->currentContentStreamId->value,
            ];
        }
        $this->output->outputTable($tableRows, $headerRow);
    }

    /**
     * Display details for the specified workspace
     *
     * @param string $workspace Name of the workspace to show
     * @param string $contentRepository The name of the content repository. (Default: 'default')
     * @throws StopCommandException
     */
    public function showCommand(string $workspace, string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentRepositoryInstance = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspaceName = WorkspaceName::fromString($workspace);
        $workspacesInstance = $contentRepositoryInstance->getWorkspaceFinder()->findOneByName($workspaceName);

        if ($workspacesInstance === null) {
            $this->outputLine('Workspace "%s" not found.', [$workspaceName->value]);
            $this->quit();
        }
        $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $workspaceName);

        $this->outputFormatted('Name: <b>%s</b>', [$workspacesInstance->workspaceName->value]);
        $this->outputFormatted('Classification: <b>%s</b>', [$workspaceMetadata->classification->value]);
        $this->outputFormatted('Base Workspace: <b>%s</b>', [$workspacesInstance->baseWorkspaceName?->value ?: '-']);
        $this->outputFormatted('Title: <b>%s</b>', [$workspaceMetadata->title->value]);
        $this->outputFormatted('Description: <b>%s</b>', [$workspaceMetadata->description->value]);
        $this->outputFormatted('Status: <b>%s</b>', [$workspacesInstance->status->value]);
        $this->outputFormatted('Content Stream: <b>%s</b>', [$workspacesInstance->currentContentStreamId->value]);
    }


    /**
     * Synchronizes metadata and role assignments of all workspaces for the specified Content Repository
     *
     * @param string $contentRepository The name of the content repository. (Default: 'default')
     * @throws StopCommandException
     */
    public function syncAllCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentRepositoryInstance = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspaces = $contentRepositoryInstance->getWorkspaceFinder()->findAll();

        if (count($workspaces) === 0) {
            $this->outputLine('No workspaces found.');
            $this->quit();
        }
        foreach ($workspaces as $workspace) {
            try {
                $this->workspaceService->synchronizeWorkspaceMetadataAndRoles($contentRepositoryId, $workspace->workspaceName);
                $this->outputLine('<success>Synchronized workspace "%s"</success>', [$workspace->workspaceName->value]);
            } catch (\Exception $exception) {
                $this->outputLine('<error>Failed to synchronize workspace "%s": %s</error>', [$workspace->workspaceName->value, $exception->getMessage()]);
            }
        }
        $this->outputLine('Done.');
    }
}
