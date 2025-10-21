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

namespace Neos\Neos\Domain\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
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
use Neos\Neos\Domain\Service\WorkspaceService;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;

/**
 * Implementation detail of {@see WorkspaceService} and {@see ContentRepositoryAuthorizationService}
 *
 * @internal Neos users should not need to deal with this low level repository. No security is imposed here. Please use the {@see WorkspaceService}!
 */
#[Flow\Scope('singleton')]
final readonly class WorkspaceMetadataAndRoleRepository
{
    private const TABLE_NAME_WORKSPACE_METADATA = 'neos_neos_workspace_metadata';
    private const TABLE_NAME_WORKSPACE_ROLE = 'neos_neos_workspace_role';

    public function __construct(
        private Connection $dbal
    ) {
    }

    /**
     * The public and documented API is {@see WorkspaceService::assignWorkspaceRole}
     */
    public function assignWorkspaceRole(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceRoleAssignment $assignment): void
    {
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
     * The public and documented API is {@see WorkspaceService::unassignWorkspaceRole}
     */
    public function unassignWorkspaceRole(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceRoleSubject $subject): void
    {
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

    public function getMostPrivilegedWorkspaceRoleForSubjects(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceRoleSubjects $subjects): ?WorkspaceRole
    {
        $tableRole = self::TABLE_NAME_WORKSPACE_ROLE;
        $roleCasesBySpecificity = implode("\n", array_map(static fn (WorkspaceRole $role) => "WHEN role='{$role->value}' THEN {$role->specificity()}\n", WorkspaceRole::cases()));
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
                    {$roleCasesBySpecificity}
                END
                DESC
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

    public function deleteWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): void
    {
        try {
            $this->dbal->delete(self::TABLE_NAME_WORKSPACE_METADATA, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to delete metadata for workspace "%s" (Content Repository "%s"): %s',
                $workspaceName->value,
                $contentRepositoryId->value,
                $e->getMessage()
            ), 1726821159, $e);
        }
    }

    public function deleteWorkspaceRoleAssignments(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): void
    {
        try {
            $this->dbal->delete(self::TABLE_NAME_WORKSPACE_ROLE, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
            ]);
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to delete role assignments for workspace "%s" (Content Repository "%s"): %s',
                $workspaceName->value,
                $contentRepositoryId->value,
                $e->getMessage()
            ), 1726821159, $e);
        }
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

    /**
     * The public and documented API is {@see WorkspaceService::getWorkspaceMetadata()}
     */
    public function loadWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): ?WorkspaceMetadata
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
     * The public and documented API is {@see WorkspaceService::setWorkspaceTitle()} and {@see WorkspaceService::setWorkspaceDescription()}
     */
    public function updateWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, Workspace $workspace, string|null $title, string|null $description): void
    {
        $data = array_filter([
            'title' => $title,
            'description' => $description,
        ], static fn ($value) => $value !== null);

        $table = self::TABLE_NAME_WORKSPACE_METADATA;
        $query = <<<SQL
            SELECT
                content_repository_id
            FROM
                {$table}
            WHERE
                content_repository_id = :contentRepositoryId
                AND workspace_name = :workspaceName
        SQL;
        try {
            $rowExists = $this->dbal->fetchOne($query, [
                'contentRepositoryId' => $contentRepositoryId->value,
                'workspaceName' => $workspace->workspaceName->value,
            ]) !== false;
            if ($rowExists) {
                $this->dbal->update($table, $data, [
                    'content_repository_id' => $contentRepositoryId->value,
                    'workspace_name' => $workspace->workspaceName->value,
                ]);
            } else {
                $this->dbal->insert($table, [
                    'content_repository_id' => $contentRepositoryId->value,
                    'workspace_name' => $workspace->workspaceName->value,
                    'description' => '',
                    'title' => $workspace->workspaceName->value,
                    'classification' => $workspace->isRootWorkspace() ? WorkspaceClassification::ROOT->value : WorkspaceClassification::UNKNOWN->value,
                    ...$data,
                ]);
            }
        } catch (DbalException $e) {
            throw new \RuntimeException(sprintf('Failed to update metadata for workspace "%s" (Content Repository "%s"): %s', $workspace->workspaceName->value, $contentRepositoryId->value, $e->getMessage()), 1726821159, $e);
        }
    }

    public function addWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $title, WorkspaceDescription $description, WorkspaceClassification $classification, UserId|null $ownerUserId): void
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

    public function findWorkspaceNameByUser(ContentRepositoryId $contentRepositoryId, UserId $userId): ?WorkspaceName
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

    /**
     * @return \Traversable<ContentRepositoryId,WorkspaceName>
     */
    public function findAllPersonalWorkspaceNamesByUser(UserId $userId): \Traversable
    {
        $tableMetadata = self::TABLE_NAME_WORKSPACE_METADATA;
        $query = <<<SQL
            SELECT
                content_repository_id, workspace_name
            FROM
                {$tableMetadata}
            WHERE
                classification = :personalWorkspaceClassification
                AND owner_user_id = :userId
        SQL;
        $rows = $this->dbal->fetchAllAssociative($query, [
            'personalWorkspaceClassification' => WorkspaceClassification::PERSONAL->value,
            'userId' => $userId->value,
        ]);
        foreach ($rows as $row) {
            yield ContentRepositoryId::fromString($row['content_repository_id']) => WorkspaceName::fromString($row['workspace_name']);
        }
    }

    /**
     * @param \Closure(): void $fn
     * @return void
     */
    public function transactional(\Closure $fn): void
    {
        $this->dbal->transactional($fn);
    }
}
