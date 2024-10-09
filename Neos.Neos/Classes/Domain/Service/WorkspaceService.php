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

namespace Neos\Neos\Domain\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription as DeprecatedWorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle as DeprecatedWorkspaceTitle;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\WorkspaceClassification;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceMetadata;
use Neos\Neos\Domain\Model\WorkspacePermissions;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleSubject;
use Neos\Neos\Domain\Model\WorkspaceRoleSubjectType;
use Neos\Neos\Domain\Model\WorkspaceTitle;

/**
 * Central authority to interact with Content Repository Workspaces within Neos
 *
 * @api
 */
#[Flow\Scope('singleton')]
final class WorkspaceService
{
    private const TABLE_NAME_WORKSPACE_METADATA = 'neos_neos_workspace_metadata';
    private const TABLE_NAME_WORKSPACE_ROLE = 'neos_neos_workspace_role';

    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly UserService $userService,
        private readonly Connection $dbal,
    ) {
    }

    /**
     * Load metadata for the specified workspace
     *
     * Note: If no metadata exists for the specified workspace, an instance with classification {@see WorkspaceClassification::UNKNOWN} is returned!
     */
    public function getWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): WorkspaceMetadata
    {
        $workspace = $this->requireWorkspace($contentRepositoryId, $workspaceName);
        $metadata = $this->loadWorkspaceMetadata($contentRepositoryId, $workspaceName);
        return $metadata ?? new WorkspaceMetadata(
            $workspaceName,
            WorkspaceTitle::fromString($workspaceName->value),
            WorkspaceDescription::fromString(''),
            $workspace->baseWorkspaceName === null ? WorkspaceClassification::ROOT : WorkspaceClassification::UNKNOWN,
            null,
        );
    }

    /**
     * Update/set title metadata for the specified workspace
     */
    public function setWorkspaceTitle(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $newWorkspaceTitle): void
    {
        $this->updateWorkspaceMetadata($contentRepositoryId, $workspaceName, [
            'title' => $newWorkspaceTitle->value,
        ]);
    }

    /**
     * Update/set description metadata for the specified workspace
     */
    public function setWorkspaceDescription(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceDescription $newWorkspaceDescription): void
    {
        $this->updateWorkspaceMetadata($contentRepositoryId, $workspaceName, [
            'description' => $newWorkspaceDescription->value,
        ]);
    }

    /**
     * Retrieve the currently active personal workspace for the specified $userId
     *
     * NOTE: Currently there can only ever be a single personal workspace per user. But this API already prepares support for multiple personal workspaces per user
     */
    public function getPersonalWorkspaceForUser(ContentRepositoryId $contentRepositoryId, UserId $userId): Workspace
    {
        $workspaceName = $this->findPrimaryWorkspaceNameForUser($contentRepositoryId, $userId);
        if ($workspaceName === null) {
            throw new \RuntimeException(sprintf('No workspace is assigned to the user with id "%s")', $userId->value), 1718293801);
        }
        return $this->requireWorkspace($contentRepositoryId, $workspaceName);
    }

    /**
     * Create a new root (aka base) workspace with the specified metadata
     *
     * @throws WorkspaceAlreadyExists
     */
    public function createRootWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $title, WorkspaceDescription $description): void
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepository->handle(
            CreateRootWorkspace::create(
                $workspaceName,
                DeprecatedWorkspaceTitle::fromString($title->value),
                DeprecatedWorkspaceDescription::fromString($description->value),
                ContentStreamId::create()
            )
        );
        $this->addWorkspaceMetadata($contentRepositoryId, $workspaceName, $title, $description, WorkspaceClassification::ROOT, null);
    }

    /**
     * Create a new, personal, workspace for the specified user
     */
    public function createPersonalWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $title, WorkspaceDescription $description, WorkspaceName $baseWorkspaceName, UserId $ownerId): void
    {
        $this->createWorkspace($contentRepositoryId, $workspaceName, $title, $description, $baseWorkspaceName, $ownerId, WorkspaceClassification::PERSONAL);
    }

    /**
     * Create a new, potentially shared, workspace
     */
    public function createSharedWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $title, WorkspaceDescription $description, WorkspaceName $baseWorkspaceName): void
    {
        $this->createWorkspace($contentRepositoryId, $workspaceName, $title, $description, $baseWorkspaceName, null, WorkspaceClassification::SHARED);
    }

    /**
     * Create a new, personal, workspace for the specified user if none exists yet
     */
    public function createPersonalWorkspaceForUserIfMissing(ContentRepositoryId $contentRepositoryId, User $user): void
    {
        $existingWorkspaceName = $this->findPrimaryWorkspaceNameForUser($contentRepositoryId, $user->getId());
        if ($existingWorkspaceName !== null) {
            $this->requireWorkspace($contentRepositoryId, $existingWorkspaceName);
            return;
        }
        $workspaceName = $this->getUniqueWorkspaceName($contentRepositoryId, $user->getLabel());
        $this->createPersonalWorkspace(
            $contentRepositoryId,
            $workspaceName,
            WorkspaceTitle::fromString($user->getLabel()),
            WorkspaceDescription::empty(),
            WorkspaceName::forLive(),
            $user->getId(),
        );
    }

    /**
     * Assign a workspace role to the given user/user group
     *
     * Without explicit workspace roles, only administrators can change the corresponding workspace.
     * With this method, the subject (i.e. a Neos user or group represented by a Flow role identifier) can be granted a {@see WorkspaceRole} for the specified workspace
     */
    public function assignWorkspaceRole(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceRoleSubjectType $subjectType, WorkspaceRoleSubject $subject, WorkspaceRole $role): void
    {
        $this->requireWorkspace($contentRepositoryId, $workspaceName);
        try {
            $this->dbal->insert(self::TABLE_NAME_WORKSPACE_ROLE, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
                'subject_type' => $subjectType->value,
                'subject' => $subject->value,
                'role' => $role->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to assign role for workspace "%s" to subject "%s" (Content Repository "%s"): %s', $workspaceName->value, $subject->value, $contentRepositoryId->value, $e->getMessage()), 1728396138, $e);
        }
    }

    /**
     * Remove a workspace role assignment for the given subject
     *
     * @see self::assignWorkspaceRole()
     */
    public function unassignWorkspaceRole(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceRoleSubjectType $subjectType, WorkspaceRoleSubject $subject): void
    {
        $this->requireWorkspace($contentRepositoryId, $workspaceName);
        try {
            $this->dbal->delete(self::TABLE_NAME_WORKSPACE_ROLE, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
                'subject_type' => $subjectType->value,
                'subject' => $subject->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to unassign role for subject "%s" from workspace "%s" (Content Repository "%s"): %s', $subject->value, $workspaceName->value, $contentRepositoryId->value, $e->getMessage()), 1728396169, $e);
        }
    }

    /**
     * Determines the permission the given user has for the specified workspace {@see WorkspacePermissions}
     */
    public function getWorkspacePermissionsForUser(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, User $user): WorkspacePermissions
    {
        $userIsAdministrator = $this->userService->currentUserIsAdministrator();
        $workspaceMetadata = $this->loadWorkspaceMetadata($contentRepositoryId, $workspaceName);
        if ($workspaceMetadata === null) {
            return WorkspacePermissions::create(false, false, $userIsAdministrator);
        }
        if ($workspaceMetadata->ownerUserId !== null && $workspaceMetadata->ownerUserId->equals($user->getId())) {
            return WorkspacePermissions::all();
        }

        $userWorkspaceRole = $this->loadWorkspaceRoleOfUser($contentRepositoryId, $workspaceName, $user);
        if ($userWorkspaceRole === null) {
            return WorkspacePermissions::create(false, false, $userIsAdministrator);
        }
        return WorkspacePermissions::create(
            read: $userWorkspaceRole->isAtLeast(WorkspaceRole::COLLABORATOR),
            write: $userWorkspaceRole->isAtLeast(WorkspaceRole::COLLABORATOR),
            manage: $userIsAdministrator || $userWorkspaceRole->isAtLeast(WorkspaceRole::MANAGER),
        );
    }

    public function getUniqueWorkspaceName(ContentRepositoryId $contentRepositoryId, string $candidate): WorkspaceName
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspaceNameCandidate = WorkspaceName::transliterateFromString($candidate);
        $workspaceName = $workspaceNameCandidate;
        $attempt = 1;
        do {
            if ($contentRepository->getWorkspaceFinder()->findOneByName($workspaceName) === null) {
                return $workspaceName;
            }
            if ($attempt === 1) {
                $suffix = '';
            } else {
                $suffix = '-' . ($attempt - 1);
            }
            $workspaceName = WorkspaceName::fromString(
                substr($workspaceNameCandidate->value, 0, WorkspaceName::MAX_LENGTH - strlen($suffix)) . $suffix
            );
            $attempt++;
        } while ($attempt <= 10);
        throw new \RuntimeException(sprintf('Failed to find unique workspace name for "%s" after %d attempts.', $candidate, $attempt - 1), 1725975479);
    }

    // ------------------

    private function loadWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): ?WorkspaceMetadata
    {
        $table = self::TABLE_NAME_WORKSPACE_METADATA;
        $query = <<<SQL
            SELECT
                *
            FROM
                {$table}
            WHERE
                content_repository_id = :contentRepositoryId
                AND workspace_name = :workspaceName
        SQL;
        try {
            $metadataRow = $this->dbal->fetchAssociative($query, [
                'contentRepositoryId' => $contentRepositoryId->value,
                'workspaceName' => $workspaceName->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to fetch metadata for workspace "%s" (Content Repository "%s), please ensure the database schema is up to date. %s',
                $workspaceName->value,
                $contentRepositoryId->value,
                $e->getMessage()
            ), 1727782164, $e);
        }
        if (!is_array($metadataRow)) {
            return null;
        }
        return new WorkspaceMetadata(
            $workspaceName,
            WorkspaceTitle::fromString($metadataRow['title']),
            WorkspaceDescription::fromString($metadataRow['description']),
            WorkspaceClassification::from($metadataRow['classification']),
            $metadataRow['owner_user_id'] !== null ? UserId::fromString($metadataRow['owner_user_id']) : null,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, array $data): void
    {
        $workspace = $this->requireWorkspace($contentRepositoryId, $workspaceName);
        try {
            $affectedRows = $this->dbal->update(self::TABLE_NAME_WORKSPACE_METADATA, $data, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
            ]);
            if ($affectedRows === 0) {
                $this->dbal->insert(self::TABLE_NAME_WORKSPACE_METADATA, [
                    'content_repository_id' => $contentRepositoryId->value,
                    'workspace_name' => $workspaceName->value,
                    'description' => '',
                    'title' => $workspaceName->value,
                    'classification' => $workspace->baseWorkspaceName === null ? WorkspaceClassification::ROOT->value : WorkspaceClassification::UNKNOWN->value,
                    ...$data,
                ]);
            }
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to update metadata for workspace "%s" (Content Repository "%s"): %s', $workspaceName->value, $contentRepositoryId->value, $e->getMessage()), 1726821159, $e);
        }
    }

    private function createWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $title, WorkspaceDescription $description, WorkspaceName $baseWorkspaceName, UserId|null $ownerId, WorkspaceClassification $classification): void
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepository->handle(
            CreateWorkspace::create(
                $workspaceName,
                $baseWorkspaceName,
                DeprecatedWorkspaceTitle::fromString($title->value),
                DeprecatedWorkspaceDescription::fromString($description->value),
                ContentStreamId::create()
            )
        );
        $this->addWorkspaceMetadata($contentRepositoryId, $workspaceName, $title, $description, $classification, $ownerId);
    }

    private function addWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $title, WorkspaceDescription $description, WorkspaceClassification $classification, UserId|null $ownerUserId): void
    {
        try {
            $this->dbal->insert(self::TABLE_NAME_WORKSPACE_METADATA, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
                'title' => $title->value,
                'description' => $description->value,
                'classification' => $classification->value,
                'owner_user_id' => $ownerUserId?->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to add metadata for workspace "%s" (Content Repository "%s"): %s', $workspaceName->value, $contentRepositoryId->value, $e->getMessage()), 1727084068, $e);
        }
    }

    private function findPrimaryWorkspaceNameForUser(ContentRepositoryId $contentRepositoryId, UserId $userId): ?WorkspaceName
    {
        $tableMetadata = self::TABLE_NAME_WORKSPACE_METADATA;
        $query = <<<SQL
            SELECT
                workspace_name
            FROM
                {$tableMetadata}
            WHERE
                content_repository_id = :contentRepositoryId
                AND classification = :personalWorkspaceClassification
                AND owner_user_id = :userId
        SQL;
        $workspaceName = $this->dbal->fetchOne($query, [
            'contentRepositoryId' => $contentRepositoryId->value,
            'personalWorkspaceClassification' => WorkspaceClassification::PERSONAL->value,
            'userId' => $userId->value,
        ]);
        return $workspaceName === false ? null : WorkspaceName::fromString($workspaceName);
    }

    private function loadWorkspaceRoleOfUser(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, User $user): ?WorkspaceRole
    {
        try {
            $userRoles = array_keys($this->userService->getAllRoles($user));
        } catch (NoSuchRoleException $e) {
            throw new \RuntimeException(sprintf('Failed to determine roles for user "%s", check your package dependencies: %s', $user->getId()->value, $e->getMessage()), 1727084881, $e);
        }
        $tableRole = self::TABLE_NAME_WORKSPACE_ROLE;
        $query = <<<SQL
            SELECT
                role
            FROM
                {$tableRole}
            WHERE
                content_repository_id = :contentRepositoryId
                AND workspace_name = :workspaceName
                AND (
                    (subject_type = :userSubjectType AND subject = :userId)
                    OR
                    (subject_type = :groupSubjectType AND subject IN (:groupSubjects))
                )
            ORDER BY
                /* We only want to return the most specific role so we order them and return the first row */
                CASE
                    WHEN role='MANAGER' THEN 1
                    WHEN role='COLLABORATOR' THEN 2
                END
            LIMIT 1
        SQL;
        $role = $this->dbal->fetchOne($query, [
            'contentRepositoryId' => $contentRepositoryId->value,
            'workspaceName' => $workspaceName->value,
            'userSubjectType' => WorkspaceRoleSubjectType::USER->value,
            'userId' => $user->getId()->value,
            'groupSubjectType' => WorkspaceRoleSubjectType::GROUP->value,
            'groupSubjects' => $userRoles,
        ], [
            'groupSubjects' => ArrayParameterType::STRING,
        ]);
        if ($role === false) {
            return null;
        }
        return WorkspaceRole::from($role);
    }

    private function requireWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): Workspace
    {
        $workspace = $this->contentRepositoryRegistry
            ->get($contentRepositoryId)
            ->getWorkspaceFinder()
            ->findOneByName($workspaceName);
        if ($workspace === null) {
            throw new \RuntimeException(sprintf('Failed to find workspace with name "%s" for content repository "%s"', $workspaceName->value, $contentRepositoryId->value), 1718379722);
        }
        return $workspace;
    }
}
