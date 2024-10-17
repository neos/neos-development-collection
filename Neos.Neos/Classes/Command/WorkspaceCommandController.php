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

use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Service\WorkspaceMaintenanceServiceFactory;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Neos\Domain\Model\WorkspaceClassification;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignment;
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
     * Create a new root workspace for a content repository
     *
     * NOTE: By default, only administrators can access workspaces without role assignments. Use <i>workspace:assignrole</i> to add workspace permissions
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
    public function createPersonalCommand(string $workspace, string $owner, string $baseWorkspace = 'live', string $title = null, string $description = null, string $contentRepository = 'default'): void
    {
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
     * NOTE: By default, only administrators can access workspaces without role assignments. Use <i>workspace:assignrole</i> to add workspace permissions
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
     * Set/change the title of a workspace
     *
     * @param string $workspace Name of the workspace, for example "some-workspace"
     * @param string $newTitle Human friendly title of the workspace, for example "Some workspace"
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @throws StopCommandException
     */
    public function setTitleCommand(string $workspace, string $newTitle, string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $workspaceName = WorkspaceName::fromString($workspace);
        $this->workspaceService->setWorkspaceTitle(
            $contentRepositoryId,
            $workspaceName,
            WorkspaceTitle::fromString($newTitle),
        );
        $this->outputLine('<success>Set title of workspace "%s" to "%s"</success>', [$workspaceName->value, $newTitle]);
    }

    /**
     * Set/change the description of a workspace
     *
     * @param string $workspace Name of the workspace, for example "some-workspace"
     * @param string $newDescription Human friendly description of the workspace
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @throws StopCommandException
     */
    public function setDescriptionCommand(string $workspace, string $newDescription, string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $workspaceName = WorkspaceName::fromString($workspace);
        $this->workspaceService->setWorkspaceDescription(
            $contentRepositoryId,
            $workspaceName,
            WorkspaceDescription::fromString($newDescription),
        );
        $this->outputLine('<success>Set description of workspace "%s"</success>', [$workspaceName->value]);
    }

    /**
     * Assign a workspace role to the given user/user group
     *
     * Without explicit workspace roles, only administrators can change the corresponding workspace.
     * With this command, a user or group (represented by a Flow role identifier) can be granted one of the two roles:
     * - collaborator: Can read from and write to the workspace
     * - manager: Can read from and write to the workspace and manage it (i.e. change metadata & role assignments)
     *
     * Examples:
     *
     * To grant editors read and write access to a (shared) workspace: <i>./flow workspace:assignrole some-workspace "Neos.Neos:AbstractEditor" collaborator</i>
     *
     * To grant a specific user read, write and manage access to a workspace: <i>./flow workspace:assignrole some-workspace admin manager --type user</i>
     *
     * {@see WorkspaceRole}
     *
     * @param string $workspace Name of the workspace, for example "some-workspace"
     * @param string $subject The user/group that should be assigned. By default, this is expected to be a Flow role identifier (e.g. 'Neos.Neos:AbstractEditor') – if $type is 'user', this is the username (aka account identifier) of a Neos user
     * @param string $role Role to assign, either 'collaborator' or 'manager' – a collaborator can read and write from/to the workspace. A manager can _on top_ change the workspace metadata & roles itself
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @param string $type Type of role, either 'group' (default) or 'user' – if 'group', $subject is expected to be a Flow role identifier, otherwise the username (aka account identifier) of a Neos user
     * @throws StopCommandException
     */
    public function assignRoleCommand(string $workspace, string $subject, string $role, string $contentRepository = 'default', string $type = 'group'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $workspaceName = WorkspaceName::fromString($workspace);

        $subjectType = match ($type) {
            'group' => WorkspaceRoleSubjectType::GROUP,
            'user' => WorkspaceRoleSubjectType::USER,
            default => throw new \InvalidArgumentException(sprintf('type must be "group" or "user", given "%s"', $type), 1728398802),
        };
        $workspaceRole = match ($role) {
            'collaborator' => WorkspaceRole::COLLABORATOR,
            'manager' => WorkspaceRole::MANAGER,
            default => throw new \InvalidArgumentException(sprintf('role must be "collaborator" or "manager", given "%s"', $role), 1728398880),
        };
        if ($subjectType === WorkspaceRoleSubjectType::USER) {
            $neosUser = $this->userService->getUser($subject);
            if ($neosUser === null) {
                $this->outputLine('<error>The user "%s" specified as subject does not exist</error>', [$subject]);
                $this->quit(1);
            }
            $roleSubject = WorkspaceRoleSubject::fromString($neosUser->getId()->value);
        } else {
            $roleSubject = WorkspaceRoleSubject::fromString($subject);
        }
        $this->workspaceService->assignWorkspaceRole(
            $contentRepositoryId,
            $workspaceName,
            WorkspaceRoleAssignment::create(
                $subjectType,
                $roleSubject,
                $workspaceRole
            )
        );
        $this->outputLine('<success>Assigned role "%s" to subject "%s" for workspace "%s"</success>', [$workspaceRole->value, $roleSubject->value, $workspaceName->value]);
    }

    /**
     * Unassign a workspace role from the given user/user group
     *
     * @see assignRoleCommand()
     *
     * @param string $workspace Name of the workspace, for example "some-workspace"
     * @param string $subject The user/group that should be unassigned. By default, this is expected to be a Flow role identifier (e.g. 'Neos.Neos:AbstractEditor') – if $type is 'user', this is the username (aka account identifier) of a Neos user
     * @param string $contentRepository Identifier of the content repository. (Default: 'default')
     * @param string $type Type of role, either 'group' (default) or 'user' – if 'group', $subject is expected to be a Flow role identifier, otherwise the username (aka account identifier) of a Neos user
     * @throws StopCommandException
     */
    public function unassignRoleCommand(string $workspace, string $subject, string $contentRepository = 'default', string $type = 'group'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $workspaceName = WorkspaceName::fromString($workspace);

        $subjectType = match ($type) {
            'group' => WorkspaceRoleSubjectType::GROUP,
            'user' => WorkspaceRoleSubjectType::USER,
            default => throw new \InvalidArgumentException(sprintf('type must be "group" or "user", given "%s"', $type), 1728398802),
        };
        $roleSubject = WorkspaceRoleSubject::fromString($subject);
        $this->workspaceService->unassignWorkspaceRole(
            $contentRepositoryId,
            $workspaceName,
            $subjectType,
            $roleSubject,
        );
        $this->outputLine('<success>Removed role assignment from subject "%s" for workspace "%s"</success>', [$roleSubject->value, $workspaceName->value]);
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

        $crWorkspace = $contentRepositoryInstance->findWorkspaceByName($workspaceName);
        if ($crWorkspace === null) {
            $this->outputLine('Workspace "%s" does not exist', [$workspaceName->value]);
            $this->quit(1);
        }
        $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $workspaceName);

        if ($workspaceMetadata->classification === WorkspaceClassification::PERSONAL) {
            $this->outputLine('<error>Did not delete workspace "%s" because it is a personal workspace. Personal workspaces cannot be deleted manually.</error>', [$workspaceName->value]);
            $this->quit(2);
        }

        $dependentWorkspaces = $contentRepositoryInstance->findWorkspaces()->getDependantWorkspaces($workspaceName);
        if (!$dependentWorkspaces->isEmpty()) {
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

        if ($outdatedWorkspaces->isEmpty()) {
            $this->outputLine('There are no outdated workspaces.');
            return;
        }
        foreach ($outdatedWorkspaces as $outdatedWorkspace) {
            $this->outputFormatted('Rebased workspace <b>%s</b>', [$outdatedWorkspace->workspaceName->value]);
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

        $workspaces = $this->contentRepositoryRegistry->get($contentRepositoryId)->findWorkspaces();

        if ($workspaces->isEmpty()) {
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
        $workspacesInstance = $contentRepositoryInstance->findWorkspaceByName($workspaceName);

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

        $workspaceRoleAssignments = $this->workspaceService->getWorkspaceRoleAssignments($contentRepositoryId, $workspaceName);
        $this->outputLine();
        $this->outputLine('<b>Role assignments:</b>');
        if ($workspaceRoleAssignments->isEmpty()) {
            $this->outputLine('There are no role assignments for workspace "%s". Use the <i>workspace:assignrole</i> command to assign roles', [$workspaceName->value]);
            return;
        }
        $this->output->outputTable(array_map(static fn (WorkspaceRoleAssignment $assignment) => [
            $assignment->subjectType->value,
            $assignment->subject->value,
            $assignment->role->value,
        ], iterator_to_array($workspaceRoleAssignments)), [
            'Subject type',
            'Subject',
            'Role',
        ]);
    }
}
