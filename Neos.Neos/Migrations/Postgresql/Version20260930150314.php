<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930150314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Changes unique key into primary key for asset usage';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQLPlatform'."
        );

        $table = $schema->getTable('neos_asset_usage');
        $table->setPrimaryKey([
            'contentrepositoryid', 'assetid', 'workspacename', 'nodeaggregateid', 'origindimensionspacepointhash', 'propertyname'
        ]);
        $table->dropIndex('IDX_14C94F11044B499EB28F27DAEAC5D4BB');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQLPlatform'."
        );

        $table = $schema->getTable('neos_asset_usage');
        $table->addUniqueIndex(
            ['contentrepositoryid', 'assetid', 'originalassetid', 'workspacename', 'nodeaggregateid', 'origindimensionspacepointhash', 'propertyname'],
            'IDX_14C94F11044B499EB28F27DAEAC5D4BB'
        );
        $table->dropPrimaryKey();
    }
}
