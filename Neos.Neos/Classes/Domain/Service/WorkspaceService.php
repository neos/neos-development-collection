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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\WorkspaceClassification;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceMetadata;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignment;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignments;
use Neos\Neos\Domain\Model\WorkspaceRoleSubject;
use Neos\Neos\Domain\Model\WorkspaceRoleSubjects;
use Neos\Neos\Domain\Model\WorkspaceRoleSubjectType;
use Neos\Neos\Domain\Model\WorkspaceTitle;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;

/**
 * Central authority to interact with Content Repository Workspaces within Neos
 *
 * @api
 */
#[Flow\Scope('singleton')]
final readonly class WorkspaceService
{
    private const TABLE_NAME_WORKSPACE_METADATA = 'neos_neos_workspace_metadata';
    private const TABLE_NAME_WORKSPACE_ROLE = 'neos_neos_workspace_role';

    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry,
        private Connection $dbal,
    ) {
    }

    /**
     * Load metadata for the specified workspace
     *
     * Note: If no metadata exists for the specified workspace, metadata with title based on the name and classification
     * according to the content repository workspace is returned. Root workspaces are of classification ROOT whereas simple ones will yield UNKNOWN.
     * {@see WorkspaceClassification::UNKNOWN}
     */
    public function getWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): WorkspaceMetadata
    {
        $workspace = $this->requireWorkspace($contentRepositoryId, $workspaceName);
        $metadata = $this->loadWorkspaceMetadata($contentRepositoryId, $workspaceName);
        return $metadata ?? new WorkspaceMetadata(
            WorkspaceTitle::fromString($workspaceName->value),
            WorkspaceDescription::fromString(''),
            $workspace->isRootWorkspace() ? WorkspaceClassification::ROOT : WorkspaceClassification::UNKNOWN,
            null,
        );
    }

    /**
     * Update/set title metadata for the specified workspace
     *
     * NOTE: The workspace privileges are not evaluated for this interaction, this should be done in the calling side if needed
     */
    public function setWorkspaceTitle(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $newWorkspaceTitle): void
    {
        $this->updateWorkspaceMetadata($contentRepositoryId, $workspaceName, [
            'title' => $newWorkspaceTitle->value,
        ]);
    }

    /**
     * Update/set description metadata for the specified workspace
     *
     * NOTE: The workspace privileges are not evaluated for this interaction, this should be done in the calling side if needed
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
                ContentStreamId::create()
            )
        );
        $this->addWorkspaceMetadata($contentRepositoryId, $workspaceName, $title, $description, WorkspaceClassification::ROOT, null);
    }

    /**
     * Create the "live" root workspace with the default role assignment (users with the role "Neos.Neos:LivePublisher" are collaborators)
     */
    public function createLiveWorkspaceIfMissing(ContentRepositoryId $contentRepositoryId): void
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspaceName = WorkspaceName::forLive();
        $liveWorkspace = $contentRepository->findWorkspaceByName($workspaceName);
        if ($liveWorkspace !== null) {
            // live workspace already exists
            return;
        }
        $this->createRootWorkspace($contentRepositoryId, $workspaceName, WorkspaceTitle::fromString('Public live workspace'), WorkspaceDescription::empty());
        $this->assignWorkspaceRole($contentRepositoryId, $workspaceName, WorkspaceRoleAssignment::createForGroup('Neos.Neos:LivePublisher', WorkspaceRole::COLLABORATOR));
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
     * @internal experimental api, until actually used by the Neos.Ui
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
    public function assignWorkspaceRole(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceRoleAssignment $assignment): void
    {
        $this->requireWorkspace($contentRepositoryId, $workspaceName);
        try {
            $this->dbal->insert(self::TABLE_NAME_WORKSPACE_ROLE, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
                'subject_type' => $assignment->subject->type->value,
                'subject' => $assignment->subject->value,
                'role' => $assignment->role->value,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            throw new \RuntimeException(sprintf('Failed to assign role for workspace "%s" to subject "%s" (Content Repository "%s"): There is already a role assigned for that user/group, please unassign that first', $workspaceName->value, $assignment->subject->value, $contentRepositoryId->value), 1728476154, $e);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to assign role for workspace "%s" to subject "%s" (Content Repository "%s"): %s', $workspaceName->value, $assignment->subject->value, $contentRepositoryId->value, $e->getMessage()), 1728396138, $e);
        }
    }

    /**
     * Remove a workspace role assignment for the given subject
     *
     * @see self::assignWorkspaceRole()
     */
    public function unassignWorkspaceRole(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceRoleSubject $subject): void
    {
        $this->requireWorkspace($contentRepositoryId, $workspaceName);
        try {
            $affectedRows = $this->dbal->delete(self::TABLE_NAME_WORKSPACE_ROLE, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
                'subject_type' => $subject->type->value,
                'subject' => $subject->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to unassign role for subject "%s" from workspace "%s" (Content Repository "%s"): %s', $subject->value, $workspaceName->value, $contentRepositoryId->value, $e->getMessage()), 1728396169, $e);
        }
        if ($affectedRows === 0) {
            throw new \RuntimeException(sprintf('Failed to unassign role for subject "%s" from workspace "%s" (Content Repository "%s"): No role assignment exists for this user/group', $subject->value, $workspaceName->value, $contentRepositoryId->value), 1728477071);
        }
    }

    /**
     * Get all role assignments for the specified workspace
     *
     * NOTE: This should never be used to evaluate permissions, instead {@see ContentRepositoryAuthorizationService::getWorkspacePermissionsForAccount()} and {@see ContentRepositoryAuthorizationService::getWorkspacePermissionsForAnonymousUser()} should be used!
     */
    public function getWorkspaceRoleAssignments(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): WorkspaceRoleAssignments
    {
        $table = self::TABLE_NAME_WORKSPACE_ROLE;
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
            $rows = $this->dbal->fetchAllAssociative($query, [
                'contentRepositoryId' => $contentRepositoryId->value,
                'workspaceName' => $workspaceName->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch workspace role assignments for workspace "%s" (Content Repository "%s"): %s', $workspaceName->value, $contentRepositoryId->value, $e->getMessage()), 1728474440, $e);
        }
        return WorkspaceRoleAssignments::fromArray(
            array_map(static fn (array $row) => WorkspaceRoleAssignment::create(
                WorkspaceRoleSubject::create(
                    WorkspaceRoleSubjectType::from($row['subject_type']),
                    $row['subject'],
                ),
                WorkspaceRole::from($row['role']),
            ), $rows)
        );
    }

    /**
     * Get the role with the most privileges for the specified {@see WorkspaceRoleSubjects} on workspace $workspaceName
     *
     * NOTE: This should never be used to evaluate permissions, instead {@see ContentRepositoryAuthorizationService::getWorkspacePermissionsForAccount()} and {@see ContentRepositoryAuthorizationService::getWorkspacePermissionsForAnonymousUser()} should be used!
     */
    public function getMostPrivilegedWorkspaceRoleForSubjects(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceRoleSubjects $subjects): ?WorkspaceRole
    {
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
                    (subject_type = :userSubjectType AND subject IN (:userSubjectValues))
                    OR
                    (subject_type = :groupSubjectType AND subject IN (:groupSubjectValues))
                )
            ORDER BY
                /* We only want to return the most specific role so we order them and return the first row */
                CASE
                    WHEN role='MANAGER' THEN 1
                    WHEN role='COLLABORATOR' THEN 2
                    WHEN role='VIEWER' THEN 3
                END
            LIMIT 1
        SQL;
        $userSubjectValues = [];
        $groupSubjectValues = [];
        foreach ($subjects as $subject) {
            if ($subject->type ===  WorkspaceRoleSubjectType::GROUP) {
                $groupSubjectValues[] = $subject->value;
            } else {
                $userSubjectValues[] = $subject->value;
            }
        }
        try {
            $role = $this->dbal->fetchOne($query, [
                'contentRepositoryId' => $contentRepositoryId->value,
                'workspaceName' => $workspaceName->value,
                'userSubjectType' => WorkspaceRoleSubjectType::USER->value,
                'userSubjectValues' => $userSubjectValues,
                'groupSubjectType' => WorkspaceRoleSubjectType::GROUP->value,
                'groupSubjectValues' => $groupSubjectValues,
            ], [
                'userSubjectValues' => ArrayParameterType::STRING,
                'groupSubjectValues' => ArrayParameterType::STRING,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to load role for workspace "%s" (content repository "%s"): %e', $workspaceName->value, $contentRepositoryId->value, $e->getMessage()), 1729325871, $e);
        }
        if ($role === false) {
            return null;
        }
        return WorkspaceRole::from($role);
    }

    /**
     * Builds a workspace name that is unique within the specified content repository.
     * If $candidate already refers to a workspace name that is not used yet, it will be used (with transliteration to enforce a valid format)
     * Otherwise a counter "-n" suffix is appended and increased until a unique name is found, or the maximum number of attempts has been reached (in which case an exception is thrown)
     */
    public function getUniqueWorkspaceName(ContentRepositoryId $contentRepositoryId, string $candidate): WorkspaceName
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspaceNameCandidate = WorkspaceName::transliterateFromString($candidate);
        $workspaceName = $workspaceNameCandidate;
        $attempt = 1;
        do {
            if ($contentRepository->findWorkspaceByName($workspaceName) === null) {
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

    /**
     * Removes all workspace metadata records for the specified content repository id
     */
    public function pruneWorkspaceMetadata(ContentRepositoryId $contentRepositoryId): void
    {
        try {
            $this->dbal->delete(self::TABLE_NAME_WORKSPACE_METADATA, [
                'content_repository_id' => $contentRepositoryId->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to prune workspace metadata Content Repository "%s": %s', $contentRepositoryId->value, $e->getMessage()), 1729512100, $e);
        }
    }

    /**
     * Removes all workspace role assignments for the specified content repository id
     */
    public function pruneRoleAssignments(ContentRepositoryId $contentRepositoryId): void
    {
        try {
            $this->dbal->delete(self::TABLE_NAME_WORKSPACE_ROLE, [
                'content_repository_id' => $contentRepositoryId->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to prune workspace roles for Content Repository "%s": %s', $contentRepositoryId->value, $e->getMessage()), 1729512142, $e);
        }
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
                    'classification' => $workspace->isRootWorkspace() ? WorkspaceClassification::ROOT->value : WorkspaceClassification::UNKNOWN->value,
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

    private function requireWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): Workspace
    {
        $workspace = $this->contentRepositoryRegistry
            ->get($contentRepositoryId)
            ->findWorkspaceByName($workspaceName);
        if ($workspace === null) {
            throw new \RuntimeException(sprintf('Failed to find workspace with name "%s" for content repository "%s"', $workspaceName->value, $contentRepositoryId->value), 1718379722);
        }
        return $workspace;
    }
}
