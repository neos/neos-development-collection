<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250224180749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the tables for Neos\' impending hard removal conflict repository';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\AbstractMySQLPlatform'."
        );

        $tableWorkspaceMetadata = $schema->createTable('neos_neos_impending_hard_removal_conflict');
        $tableWorkspaceMetadata->addColumn('content_repository_id', 'string', ['length' => 16]);
        $tableWorkspaceMetadata->addColumn('workspace_name', 'string', ['length' => 255]);
        $tableWorkspaceMetadata->addColumn('node_aggregate_id', 'string', ['length' => 64]);
        $tableWorkspaceMetadata->addColumn('dimension_space_points', 'json');
        $tableWorkspaceMetadata->setPrimaryKey(['content_repository_id', 'workspace_name', 'node_aggregate_id']);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\AbstractMySQLPlatform'."
        );

        $schema->dropTable('neos_neos_impending_hard_removal_conflict');
    }
}
