<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240906102606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates table for asset usage';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQLPlatform'."
        );

        $table = $schema->createTable('neos_asset_usage');
        $table->addColumn('contentrepositoryid', 'string', ['length' => 16, 'notnull' => false]);
        $table->addColumn('assetid', 'string', ['length' => 40, 'notnull' => true, 'default' => '']);
        $table->addColumn('originalassetid', 'string', ['length' => 40, 'notnull' => false]);
        $table->addColumn('workspacename', 'string', ['length' => 36, 'notnull' => true]);
        $table->addColumn('nodeaggregateid', 'string', ['length' => 64, 'notnull' => true]);
        $table->addColumn('origindimensionspacepoint', 'json', ['notnull' => false]);
        $table->addColumn('origindimensionspacepointhash', 'string', ['length' => 32, 'notnull' => true, 'default' => '']);
        $table->addColumn('propertyname', 'string', ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addUniqueIndex(
            ['contentrepositoryid', 'assetid', 'originalassetid', 'workspacename', 'nodeaggregateid', 'origindimensionspacepointhash', 'propertyname'],
            'IDX_14C94F11044B499EB28F27DAEAC5D4BB'
        );
        $table->addIndex(['contentrepositoryid', 'workspacename', 'nodeaggregateid', 'origindimensionspacepointhash'], 'IDX_55757035ADC144B7ED5AC6744F7D18CF');
        $table->addIndex(['contentrepositoryid'], 'IDX_0A70B9E69F347EB3D7CA716B10767577');
        $table->addIndex(['assetid'], 'IDX_9FC89003DB4D99EB02993595B732415D');
        $table->addIndex(['workspacename'], 'IDX_40479348B81805EA31D1A10B56B9455D');
        $table->addIndex(['nodeaggregateid'], 'IDX_1E6617E2E8A543E560401157FBBE2272');
        $table->addIndex(['origindimensionspacepointhash'], 'IDX_D8E094F9CA47A07B4723A823179CFBEB');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQLPlatform'."
        );

        $schema->dropTable('neos_asset_usage');
    }
}
