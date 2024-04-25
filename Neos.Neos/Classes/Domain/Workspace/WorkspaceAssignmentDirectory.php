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

namespace Neos\Neos\Domain\Workspace;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\ConnectionFactory;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Model\User;

/**
 * Neos' directory for workspace assignments
 *
 * @internal to be used in the {@see WorkspaceProvider}
 */
#[Flow\Scope('singleton')]
final readonly class WorkspaceAssignmentDirectory
{
    private const TABLE_NAME = 'neos_neos_workspaceassignment';

    private Connection $databaseConnection;

    public function __construct(
        ConnectionFactory $connectionFactory,
        private PersistenceManagerInterface $persistenceManager,
    ) {
        $this->databaseConnection = $connectionFactory->create();
    }

    public function assignWorkspaceToUser(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        User $user,
        WorkspaceClassification $classification
    ): void {
        $userId = $this->persistenceManager->getIdentifierByObject($user);
        $this->databaseConnection->transactional(function () use ($contentRepositoryId, $workspaceName, $userId, $classification) {
            if ($classification === WorkspaceClassification::CLASSIFICATION_PRIMARY_PERSONAL) {
                $this->databaseConnection->executeStatement(
                    'UPDATE ' . self::TABLE_NAME
                    . ' SET classification = :private WHERE classification = :primaryPrivate'
                    . ' WHERE content_repository_id = :contentRepositoryId AND user_id = :userId',
                    [
                        'private' => WorkspaceClassification::CLASSIFICATION_PERSONAL->value,
                        'primaryPrivate' => WorkspaceClassification::CLASSIFICATION_PRIMARY_PERSONAL->value,
                        'contentRepositoryId' => $contentRepositoryId->value,
                        'userId' => $userId
                    ]
                );
            }

            $this->databaseConnection->executeStatement(
                'INSERT INTO ' . self::TABLE_NAME . ' VALUES (:contentRepositoryId, :workspaceName, :workspaceClassification, :userId)',
                [
                    'contentRepositoryId' => $contentRepositoryId->value,
                    'workspaceName' => $workspaceName->value,
                    'workspaceClassification' => $classification->value,
                    'userId' => $userId
                ]
            );
        });
    }

    public function findPrimaryPersonalWorkspaceName(ContentRepositoryId $contentRepositoryId, User $user): ?WorkspaceName
    {
        $userId = $this->persistenceManager->getIdentifierByObject($user);
        $result = $this->databaseConnection->executeQuery(
            'SELECT workspace_name FROM ' . self::TABLE_NAME
                . ' WHERE content_repository_id = :contentRepositoryId
                    AND workspace_classification = :primaryPersonal
                    AND user_id = :userId',
            [
                'contentRepositoryId' => $contentRepositoryId->value,
                'primaryPersonal' => WorkspaceClassification::CLASSIFICATION_PRIMARY_PERSONAL->value,
                'userId' => $userId
            ]
        )->fetchOne();

        return $result ? WorkspaceName::fromString($result['workspace_name']) : null;
    }

    public function findAssignedUserId(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): ?UserId
    {
        $result = $this->databaseConnection->executeQuery(
            'SELECT user_id FROM ' . self::TABLE_NAME
            . ' WHERE content_repository_id = :contentRepositoryId
                    AND workspace_name = :workspaceName',
            [
                'contentRepositoryId' => $contentRepositoryId->value,
                'workspaceName' => $workspaceName->value
            ]
        )->fetchOne();

        return $result ? UserId::fromString($result['user_id']) : null;
    }
}
