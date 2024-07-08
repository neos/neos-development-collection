<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240425223900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the tables for neos workspace metadata and role assignments';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1027Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1027Platform'."
        );

        $tableWorkspaceMetadata = $schema->createTable('neos_neos_workspace_metadata');
        $tableWorkspaceMetadata->addColumn('content_repository_id', 'string', ['length' => 16]);
        $tableWorkspaceMetadata->addColumn('workspace_name', 'string', ['length' => 255]);
        $tableWorkspaceMetadata->addColumn('title', 'string', ['length' => 255]);
        $tableWorkspaceMetadata->addColumn('description', 'text');
        $tableWorkspaceMetadata->addColumn('classification', 'string', ['length' => 255]);
        $tableWorkspaceMetadata->setPrimaryKey(['content_repository_id', 'workspace_name']);

        $tableWorkspaceRole = $schema->createTable('neos_neos_workspace_role');
        $tableWorkspaceRole->addColumn('content_repository_id', 'string', ['length' => 16]);
        $tableWorkspaceRole->addColumn('workspace_name', 'string', ['length' => 255]);
        $tableWorkspaceRole->addColumn('subject_type', 'string', ['length' => 20]);
        $tableWorkspaceRole->addColumn('subject', 'string', ['length' => 255]);
        $tableWorkspaceRole->addColumn('role', 'string', ['length' => 20]);
        $tableWorkspaceRole->setPrimaryKey(['content_repository_id', 'workspace_name', 'subject_type', 'subject']);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1027Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1027Platform'."
        );

        $schema->dropTable('neos_neos_workspace_role');
        $schema->dropTable('neos_neos_workspace_metadata');
    }
}
