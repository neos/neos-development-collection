<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version202404252239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the table for workspace assignments';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1027Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1027Platform'."
        );

        $table = $schema->createTable('neos_neos_workspaceassignment');
        $table->addColumn('content_repository_id', 'string', ['length' => 255]);
        $table->addColumn('workspace_name', 'string', ['length' => 255]);
        $table->addColumn('workspace_classification', 'string', ['length' => 255]);
        $table->addColumn('user_id', 'string', ['length' => 255]);
        $table->setPrimaryKey(['content_repository_id', 'workspace_name']);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1027Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1027Platform'."
        );

        $schema->dropTable('neos_neos_workspaceassignment');
    }
}
