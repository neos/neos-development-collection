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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
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
use Neos\Neos\Domain\Model\WorkspaceSubjectType;
use Neos\Neos\Domain\Model\WorkspaceTitle;

/**
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
     */
    public function getWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): WorkspaceMetadata
    {
        $this->requireWorkspace($contentRepositoryId, $workspaceName);
        $metadata = $this->loadWorkspaceMetadata($contentRepositoryId, $workspaceName);
        if ($metadata === null) {
            throw new \RuntimeException(sprintf('Failed to load metadata for workspace "%s" (Content Repository "%s"). Maybe workspace metadata and roles have to be synchronized', $workspaceName->value, $contentRepositoryId->value), 1726736384);
        }
        return $metadata;
    }

    /**
     * Change title and/or description metadata for the specified workspace
     */
    public function updateWorkspaceTitleAndDescription(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $newWorkspaceTitle, WorkspaceDescription $newWorkspaceDescription): void
    {
        $this->requireWorkspace($contentRepositoryId, $workspaceName);
        $this->updateWorkspaceMetadata($contentRepositoryId, $workspaceName, [
            'title' => $newWorkspaceTitle->value,
            'description' => $newWorkspaceDescription->value,
        ]);
    }

    /**
     * Add missing metadata and owner role assignments for the specified workspace
     *
     * Note: This is mainly needed in order to migrate existing installations or fix the state if it was corrupted
     */
    public function synchronizeWorkspaceMetadataAndRoles(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): void
    {
        $workspace = $this->requireWorkspace($contentRepositoryId, $workspaceName);
        if ($workspace->baseWorkspaceName === null) {
            $classification = WorkspaceClassification::ROOT;
        } elseif ($workspace->workspaceOwner !== null && str_starts_with($workspaceName->value, 'user-')) {
            $classification = WorkspaceClassification::PERSONAL;
        } else {
            $classification = WorkspaceClassification::SHARED;
        }

        $metadata = $this->loadWorkspaceMetadata($contentRepositoryId, $workspaceName);
        if ($metadata === null) {
            $this->addWorkspaceMetadata($contentRepositoryId, $workspaceName, WorkspaceTitle::fromString($workspace->workspaceTitle->value), WorkspaceDescription::fromString($workspace->workspaceDescription->value), $classification);
        }

        if ($workspace->workspaceOwner !== null) {
            $this->addWorkspaceRole($contentRepositoryId, $workspaceName, WorkspaceSubjectType::USER, $workspace->workspaceOwner, WorkspaceRole::OWNER);
        }
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
     */
    public function createRootWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceTitle $title, WorkspaceDescription $description): WorkspaceName
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspaceName = self::getUniqueWorkspaceName($contentRepository, $title->value);
        $contentRepository->handle(
            CreateRootWorkspace::create(
                $workspaceName,
                DeprecatedWorkspaceTitle::fromString($title->value),
                DeprecatedWorkspaceDescription::fromString($description->value),
                ContentStreamId::create()
            )
        );
        $this->addWorkspaceMetadata($contentRepositoryId, $workspaceName, $title, $description, WorkspaceClassification::ROOT);
        return $workspaceName;
    }

    /**
     * Create a new, personal, workspace for the specified user
     */
    public function createPersonalWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceTitle $title, WorkspaceDescription $description, WorkspaceName $baseWorkspaceName, UserId $ownerId): WorkspaceName
    {
        return $this->createWorkspace($contentRepositoryId, $title, $description, $baseWorkspaceName, $ownerId, WorkspaceClassification::PERSONAL);
    }

    /**
     * Create a new, potentially shared, workspace
     */
    public function createSharedWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceTitle $title, WorkspaceDescription $description, WorkspaceName $baseWorkspaceName, UserId|null $ownerId = null): WorkspaceName
    {
        return $this->createWorkspace($contentRepositoryId, $title, $description, $baseWorkspaceName, $ownerId, WorkspaceClassification::SHARED);
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
        $this->createPersonalWorkspace(
            $contentRepositoryId,
            WorkspaceTitle::fromString($user->getLabel()),
            WorkspaceDescription::empty(),
            WorkspaceName::forLive(),
            $user->getId(),
        );
    }

    /**
     *
     */
    public function getWorkspacePermissionsForUser(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, User $user): WorkspacePermissions
    {
        if ($this->userService->currentUserIsAdministrator()) {
            return WorkspacePermissions::create(
                read: true,
                write: true,
                manage: true,
            );
        }
        $userWorkspaceRole = $this->loadWorkspaceRoleOfUser($contentRepositoryId, $workspaceName, $user);
        return WorkspacePermissions::create(
            read: $userWorkspaceRole->isAtLeast(WorkspaceRole::COLLABORATOR),
            write: $userWorkspaceRole->isAtLeast(WorkspaceRole::COLLABORATOR),
            manage: $userWorkspaceRole->isAtLeast(WorkspaceRole::MANAGER),
        );
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
            throw new \RuntimeException(sprintf('Failed to load metadata for workspace "%s" (Content Repository "%s"): %s. Maybe workspace metadata and roles have to be synchronized', $workspaceName->value, $contentRepositoryId->value, $e->getMessage()), 1726736383, $e);
        }
        if (!is_array($metadataRow)) {
            return null;
        }
        assert(is_string($metadataRow['title']));
        assert(is_string($metadataRow['description']));
        assert(is_string($metadataRow['classification']));
        return new WorkspaceMetadata(
            $workspaceName,
            WorkspaceTitle::fromString($metadataRow['title']),
            WorkspaceDescription::fromString($metadataRow['description']),
            WorkspaceClassification::from($metadataRow['classification'])
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, array $data): void
    {
        try {
            $this->dbal->update(self::TABLE_NAME_WORKSPACE_METADATA, $data, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to update metadata for workspace "%s" (Content Repository "%s"): %s', $workspaceName->value, $contentRepositoryId->value, $e->getMessage()), 1726821159, $e);
        }
    }

    private function createWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceTitle $title, WorkspaceDescription $description, WorkspaceName $baseWorkspaceName, UserId|null $ownerId, WorkspaceClassification $classification): WorkspaceName
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspaceName = self::getUniqueWorkspaceName($contentRepository, $title->value);
        $contentRepository->handle(
            CreateWorkspace::create(
                $workspaceName,
                $baseWorkspaceName,
                DeprecatedWorkspaceTitle::fromString($title->value),
                DeprecatedWorkspaceDescription::fromString($description->value),
                ContentStreamId::create()
            )
        );
        if ($ownerId !== null) {
            $this->addWorkspaceRole($contentRepositoryId, $workspaceName, WorkspaceSubjectType::USER, $ownerId->value, WorkspaceRole::OWNER);
        }
        $this->addWorkspaceMetadata($contentRepositoryId, $workspaceName, $title, $description, $classification);
        return $workspaceName;
    }

    private function addWorkspaceRole(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceSubjectType $subjectType, string $subject, WorkspaceRole $role): void
    {
        try {
            $this->dbal->insert(self::TABLE_NAME_WORKSPACE_ROLE, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
                'subject_type' => $subjectType->name,
                'subject' => $subject,
                'role' => $role->name,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to add role for subject "%s" in workspace "%s" (Content Repository "%s"): %s', $workspaceName->value, $subject, $contentRepositoryId->value, $e->getMessage()), 1727083752, $e);
        }
    }

    private function addWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $title, WorkspaceDescription $description, WorkspaceClassification $classification): void
    {
        try {
            $this->dbal->insert(self::TABLE_NAME_WORKSPACE_METADATA, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
                'title' => $title->value,
                'description' => $description->value,
                'classification' => $classification->name,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to add metadata for workspace "%s" (Content Repository "%s"): %s', $workspaceName->value, $contentRepositoryId->value, $e->getMessage()), 1727084068, $e);
        }
    }

    private function findPrimaryWorkspaceNameForUser(ContentRepositoryId $contentRepositoryId, UserId $userId): ?WorkspaceName
    {
        $tableRole = self::TABLE_NAME_WORKSPACE_ROLE;
        $tableMetadata = self::TABLE_NAME_WORKSPACE_METADATA;
        $query = <<<SQL
            SELECT
                r.workspace_name
            FROM
                {$tableRole} r
                JOIN {$tableMetadata} m ON (
                    m.content_repository_id = r.content_repository_id
                    AND m.workspace_name = r.workspace_name
                    AND m.classification = :classification
                )
            WHERE
                r.content_repository_id = :contentRepositoryId
                AND subject = :subject
                AND subject_type = :subjectType
                AND role = :ownerRole
        SQL;
        $workspaceName = $this->dbal->fetchOne($query, [
            'contentRepositoryId' => $contentRepositoryId->value,
            'subject' => $userId->value,
            'subjectType' => WorkspaceSubjectType::USER->name,
            'ownerRole' => WorkspaceRole::OWNER->name,
            'classification' => WorkspaceClassification::PERSONAL->name,
        ]);
        return $workspaceName === false ? null : WorkspaceName::fromString($workspaceName);
    }

    private function loadWorkspaceRoleOfUser(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, User $user): WorkspaceRole
    {
        try {
            $userRoles = array_keys($this->userService->getAllRoles($user));
        } catch (NoSuchRoleException $e) {
            throw new \RuntimeException(sprintf('Failed to determine roles for user "%s", check your package dependencies: %s', $user->getId()->value, $e->getMessage()), 1727084881, $e);
        }
        $table = self::TABLE_NAME_WORKSPACE_ROLE;
        $query = <<<SQL
            SELECT
                role
            FROM
                {$table}
            WHERE
                content_repository_id = :contentRepositoryId
                AND workspace_name = :workspaceName
                AND (
                    (subject_type = :userSubjectType AND subject = :userId)
                    OR
                    (subject_type = :groupSubjectType AND subject IN (:groupSubjects))
                )
            ORDER BY
                CASE
                    WHEN role='OWNER' THEN 1
                    WHEN role='MANAGER' THEN 2
                    WHEN role='COLLABORATOR' THEN 3
                END
            LIMIT 1
        SQL;
        $role = $this->dbal->fetchOne($query, [
            'contentRepositoryId' => $contentRepositoryId->value,
            'workspaceName' => $workspaceName->value,
            'userSubjectType' => WorkspaceSubjectType::USER->name,
            'userId' => $user->getId()->value,
            'groupSubjectType' => WorkspaceSubjectType::GROUP->name,
            'groupSubjects' => $userRoles,
        ], [
            'groupSubjects' => Connection::PARAM_STR_ARRAY,
        ]);
        if ($role === false) {
            return WorkspaceRole::NONE;
        }
        return WorkspaceRole::from($role);
    }

    private static function getUniqueWorkspaceName(ContentRepository $contentRepository, string $candidate): WorkspaceName
    {
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


    private function requireWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): Workspace
    {
        $workspace = $this->contentRepositoryRegistry
            ->get($contentRepositoryId)
            ->getWorkspaceFinder()
            ->findOneByName($workspaceName);
        if ($workspace === null) {
            throw new \InvalidArgumentException(sprintf('Failed to find workspace with name "%s"', $workspaceName->value), 1718379722);
        }
        return $workspace;
    }
}
