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

use Doctrine\DBAL\Exception as DbalException;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Service\WorkspaceMaintenanceServiceFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Neos\Domain\Model\WorkspaceClassification;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleSubject;
use Neos\Neos\Domain\Model\WorkspaceRoleSubjectType;
use Neos\Neos\Domain\Model\WorkspaceTitle;
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
            '<success>Published all nodes in workspace "%s" to its base workspace</success>',
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
            $this->workspacePublishingService->discardAllWorkspaceChanges(
                ContentRepositoryId::fromString($contentRepository),
                WorkspaceName::fromString($workspace)
            );
        } catch (WorkspaceDoesNotExist) {
            $this->outputLine('<error>Workspace "%s" does not exist</error>', [$workspace]);
            $this->quit(1);
        }
        $this->outputLine('<success>Discarded all nodes in workspace "%s"</success>', [$workspace]);
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
            $this->workspacePublishingService->rebaseWorkspace(
                ContentRepositoryId::fromString($contentRepository),
                WorkspaceName::fromString($workspace),
                $force ? RebaseErrorHandlingStrategy::STRATEGY_FORCE : RebaseErrorHandlingStrategy::STRATEGY_FAIL,
            );
        } catch (WorkspaceDoesNotExist $exception) {
            $this->outputLine('<error>Workspace "%s" does not exist</error>', [$workspace]);
            $this->quit(1);
        } catch (WorkspaceRebaseFailed $exception) {
            $this->outputLine('<error>Rebasing of workspace "%s" is not possible due to conflicts. You can try the <em>--force</em> option.</error>', [$workspace]);
            $this->quit(1);
        }

        $this->outputLine('<success>Rebased workspace "%s"</success>', [$workspace]);
    }

    /**
     * Create a new root workspace for a content repository.
     *
     * @param string $name Name of the new root
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @param string|null $title Optional title of the workspace
     * @param string|null $description Optional description of the workspace
     * @throws WorkspaceAlreadyExists
     */
    public function createRootCommand(string $name, string $contentRepository = 'default', string $title = null, string $description = null): void
    {
        $workspaceName = WorkspaceName::fromString($name);
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $this->workspaceService->createRootWorkspace(
            $contentRepositoryId,
            $workspaceName,
            WorkspaceTitle::fromString($title ?? $name),
            WorkspaceDescription::fromString($description ?? '')
        );
        $this->outputLine('<success>Created root workspace "%s" in content repository "%s"</success>', [$workspaceName->value, $contentRepositoryId->value]);
    }

    /**
     * Create a new personal workspace for the specified user
     *
     * @param string $workspace Name of the workspace, for example "christmas-campaign"
     * @param string $owner The username (aka account identifier) of a User to own the workspace
     * @param string $baseWorkspace Name of the base workspace. If none is specified, "live" is assumed.
     * @param string|null $title Human friendly title of the workspace, for example "Christmas Campaign"
     * @param string|null $description A description explaining the purpose of the new workspace
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @throws StopCommandException
     */
    public function createPersonalCommand(string $workspace, string $owner, string $baseWorkspace = 'live', string $title = null, string $description = null, string $contentRepository = 'default'): void {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $workspaceOwner = $this->userService->getUser($owner);
        if ($workspaceOwner === null) {
            $this->outputLine('<error>The user "%s" specified as owner does not exist</error>', [$owner]);
            $this->quit(1);
        }
        $workspaceName = WorkspaceName::fromString($workspace);
        $this->workspaceService->createPersonalWorkspace(
            $contentRepositoryId,
            $workspaceName,
            WorkspaceTitle::fromString($title ?? $workspaceName->value),
            WorkspaceDescription::fromString($description ?? ''),
            WorkspaceName::fromString($baseWorkspace),
            $workspaceOwner->getId(),
        );
        $this->outputLine('<success>Created personal workspace "%s" for user "%s"</success>', [$workspaceName->value, (string)$workspaceOwner->getName()]);
    }

    /**
     * Create a new shared workspace
     *
     * @param string $workspace Name of the workspace, for example "christmas-campaign"
     * @param string $baseWorkspace Name of the base workspace. If none is specified, "live" is assumed.
     * @param string|null $title Human friendly title of the workspace, for example "Christmas Campaign"
     * @param string|null $description A description explaining the purpose of the new workspace
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @throws StopCommandException
     */
    public function createSharedCommand(string $workspace, string $baseWorkspace = 'live', string $title = null, string $description = null, string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $workspaceName = WorkspaceName::fromString($workspace);
        $this->workspaceService->createSharedWorkspace(
            $contentRepositoryId,
            $workspaceName,
            WorkspaceTitle::fromString($title ?? $workspaceName->value),
            WorkspaceDescription::fromString($description ?? ''),
            WorkspaceName::fromString($baseWorkspace),
        );
        $this->outputLine('<success>Created shared workspace "%s"</success>', [$workspaceName->value]);
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
     * @throws StopCommandException
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
            $this->outputLine('<error>Did not delete workspace "%s" because it is a personal workspace. Personal workspaces cannot be deleted manually.</error>', [$workspaceName->value]);
            $this->quit(2);
        }

        try {
            $dependentWorkspaces = $contentRepositoryInstance->getWorkspaceFinder()->findByBaseWorkspace($workspaceName);
        } catch (DbalException $e) {
            $this->outputLine('<error>Failed to determine dependant workspaces: %s</error>', [$e->getMessage()]);
            $this->quit(1);
        }
        if (count($dependentWorkspaces) > 0) {
            $this->outputLine('<error>Workspace "%s" cannot be deleted because the following workspaces are based on it:</error>', [$workspaceName->value]);

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
}
