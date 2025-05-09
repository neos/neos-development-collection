<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241212000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restricts that the owner_user_id of the neos workspace metadata is unique';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\AbstractMySQLPlatform'."
        );

        $tableWorkspaceMetadata = $schema->getTable('neos_neos_workspace_metadata');
        $tableWorkspaceMetadata->addUniqueIndex(['content_repository_id', 'owner_user_id'], 'owner');
        $tableWorkspaceMetadata->dropIndex('IDX_D6197E562B18554A');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\AbstractMySQLPlatform'."
        );

        $tableWorkspaceMetadata = $schema->getTable('neos_neos_workspace_metadata');
        $tableWorkspaceMetadata->addIndex(['owner_user_id'], 'IDX_D6197E562B18554A');
        $tableWorkspaceMetadata->dropIndex('owner');
    }
}
