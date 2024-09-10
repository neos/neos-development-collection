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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Algorithms;
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

    public function getWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): WorkspaceMetadata
    {
        $this->requireWorkspace($contentRepositoryId, $workspaceName);

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
        $metadataRow = $this->dbal->fetchAssociative($query, [
            'contentRepositoryId' => $contentRepositoryId->value,
            'workspaceName' => $workspaceName->value,
        ]);
        if ($metadataRow === false) {
            return new WorkspaceMetadata(
                $workspaceName,
                WorkspaceTitle::fromString(ucfirst($workspaceName->value)),
                WorkspaceDescription::fromString(''),
                $workspaceName->isLive() ? WorkspaceClassification::ROOT : WorkspaceClassification::UNKNOWN,
            );
        }
        return new WorkspaceMetadata(
            $workspaceName,
            WorkspaceTitle::fromString($metadataRow['title']),
            WorkspaceDescription::fromString($metadataRow['description']),
            WorkspaceClassification::from($metadataRow['classification'])
        );
    }

    public function updateWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $newWorkspaceTitle, WorkspaceDescription $newWorkspaceDescription): void
    {
        $this->requireWorkspace($contentRepositoryId, $workspaceName);
        try {
            $this->dbal->insert(self::TABLE_NAME_WORKSPACE_METADATA, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
                'title' => $newWorkspaceTitle->value,
                'description' => $newWorkspaceDescription->value,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            $this->dbal->update(self::TABLE_NAME_WORKSPACE_METADATA, [
                'title' => $newWorkspaceTitle->value,
                'description' => $newWorkspaceDescription->value,
            ], [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
            ]);
        }
    }

    public function getPersonalWorkspaceForUser(ContentRepositoryId $contentRepositoryId, UserId $userId): Workspace
    {
        $workspaceName = $this->findPrimaryWorkspaceNameForUser($contentRepositoryId, $userId);
        if ($workspaceName === null) {
            throw new \RuntimeException(sprintf('No workspace is assigned to the user with id "%s")', $userId->value), 1718293801);
        }
        return $this->requireWorkspace($contentRepositoryId, $workspaceName);
    }

    public function createRootWorkspace(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceTitle $title,
        WorkspaceDescription $description,
    ): WorkspaceName {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspaceName = self::getUniqueWorkspaceName($contentRepository, $title->value);
        $contentRepository->handle(
            CreateRootWorkspace::create(
                $workspaceName,
                \Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle::fromString($title->value),
                \Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription::fromString($description->value),
                ContentStreamId::create()
            )
        );

        // TODO catch exceptions

//        $this->dbal->insert(self::TABLE_NAME_WORKSPACE_ROLE, [
//            'content_repository_id' => $contentRepositoryId->value,
//            'workspace_name' => $workspaceName->value,
//            'subject_type' => WorkspaceSubjectType::GROUP->name,
//            'subject' => 'Neos.Flow:Everybody',
//            'role' => WorkspaceRole::COLLABORATOR,
//        ]);

        // TODO catch exceptions

        $this->dbal->insert(self::TABLE_NAME_WORKSPACE_METADATA, [
            'content_repository_id' => $contentRepositoryId->value,
            'workspace_name' => $workspaceName->value,
            'title' => $title->value,
            'description' => $description->value,
            'classification' => WorkspaceClassification::ROOT->name,
        ]);

        return $workspaceName;
    }

    public function createWorkspace(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceTitle $title,
        WorkspaceDescription $description,
        WorkspaceName $baseWorkspaceName,
        UserId|null $ownerId,
        WorkspaceClassification $classification,
    ): WorkspaceName {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspaceName = self::getUniqueWorkspaceName($contentRepository, $title->value);
        $contentRepository->handle(
            CreateWorkspace::create(
                $workspaceName,
                $baseWorkspaceName,
                \Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle::fromString($title->value),
                \Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription::fromString($description->value),
                ContentStreamId::create()
            )
        );

        // TODO catch exceptions

        if ($ownerId !== null) {
            $this->dbal->insert(self::TABLE_NAME_WORKSPACE_ROLE, [
                'content_repository_id' => $contentRepositoryId->value,
                'workspace_name' => $workspaceName->value,
                'subject_type' => WorkspaceSubjectType::USER->name,
                'subject' => $ownerId->value,
                'role' => WorkspaceRole::OWNER->name,
            ]);
        }

        // TODO catch exceptions

        $this->dbal->insert(self::TABLE_NAME_WORKSPACE_METADATA, [
            'content_repository_id' => $contentRepositoryId->value,
            'workspace_name' => $workspaceName->value,
            'title' => $title->value,
            'description' => $description->value,
            'classification' => $classification->name,
        ]);

        return $workspaceName;
    }

    public function createPersonalWorkspaceForUserIfMissing(ContentRepositoryId $contentRepositoryId, User $user): void
    {
        $existingWorkspaceName = $this->findPrimaryWorkspaceNameForUser($contentRepositoryId, $user->getId());
        if ($existingWorkspaceName !== null) {
            $this->requireWorkspace($contentRepositoryId, $existingWorkspaceName);
            return;
        }
        $this->createWorkspace(
            $contentRepositoryId,
            WorkspaceTitle::fromString($user->getLabel()),
            WorkspaceDescription::empty(),
            WorkspaceName::forLive(),
            $user->getId(),
            WorkspaceClassification::PERSONAL,
        );
    }

    public function getWorkspacePermissionsForUser(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, User $user): WorkspacePermissions
    {
        $userRoles = $this->userService->getAllRoles($user);
        $userIsAdmin = array_key_exists('Neos.Neos:Administrator', $userRoles);

        $userWorkspaceRole = $this->getWorkspaceRoleOfUser($contentRepositoryId, $workspaceName, $user->getId(), array_keys($userRoles));

        return WorkspacePermissions::create(
            read: $userIsAdmin || $userWorkspaceRole->isAtLeast(WorkspaceRole::COLLABORATOR),
            write: $userIsAdmin || $userWorkspaceRole->isAtLeast(WorkspaceRole::COLLABORATOR),
            manage: $userIsAdmin || $userWorkspaceRole->isAtLeast(WorkspaceRole::MANAGER),
        );
    }

    private function findPrimaryWorkspaceNameForUser(ContentRepositoryId $contentRepositoryId, UserId $userId): ?WorkspaceName
    {
        $table = self::TABLE_NAME_WORKSPACE_ROLE;
        $query = <<<SQL
            SELECT
                workspace_name
            FROM
                {$table}
            WHERE
                content_repository_id = :contentRepositoryId
                AND subject = :subject
                AND subject_type = :subjectType
                AND role = :ownerRole
        SQL;
        $workspaceName = $this->dbal->fetchOne($query, [
            'contentRepositoryId' => $contentRepositoryId->value,
            'subject' => $userId->value,
            'subjectType' => WorkspaceSubjectType::USER->name,
            'ownerRole' => WorkspaceRole::OWNER->name,
        ]);
        return $workspaceName === false ? null : WorkspaceName::fromString($workspaceName);
    }

    /**
     * @param array<string> $userRoles
     */
    private function getWorkspaceRoleOfUser(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, UserId $userId, array $userRoles): WorkspaceRole
    {
        $table = self::TABLE_NAME_WORKSPACE_ROLE;
        $query = <<<SQL
            SELECT
                role
            FROM
                {$table}
            WHERE
                content_repository_id = :contentRepositoryId
                AND workspace_name = :workspaceName
                AND subject IN (:subjects)
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
            'subjects' => ['user:' . $userId->value, ...array_map(static fn (string $role) => 'group:' . $role, $userRoles)],
        ], [
            'subjects' => Connection::PARAM_STR_ARRAY,
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
        while ($contentRepository->getWorkspaceFinder()->findOneByName($workspaceName) instanceof Workspace) {
            $workspaceName = WorkspaceName::fromString(
                $workspaceNameCandidate->value . '-' . strtolower(Algorithms::generateRandomString(5))
            );
        }
        return $workspaceName;
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
