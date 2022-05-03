<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220225092238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial migration creating the "neos_neos_projection_asset_usage" table';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof MySqlPlatform, sprintf('Migration can only be executed safely on "%s"', MySqlPlatform::class));

        $this->addSql('CREATE TABLE neos_neos_projection_asset_usage (
          assetidentifier VARCHAR(40) NOT NULL DEFAULT "",
          contentstreamidentifier VARCHAR(255) NOT NULL DEFAULT "",
          nodeaggregateidentifier VARCHAR(255) NOT NULL DEFAULT "",
          origindimensionspacepointhash VARCHAR(255) NOT NULL DEFAULT "",
          propertyname VARCHAR(255) NOT NULL DEFAULT "",

          UNIQUE KEY assetperproperty (assetidentifier, contentstreamidentifier, nodeaggregateidentifier, origindimensionspacepointhash, propertyname),
          KEY assetidentifier (assetidentifier),
          KEY contentstreamidentifier (contentstreamidentifier),
          KEY nodeaggregateidentifier (nodeaggregateidentifier),
          KEY origindimensionspacepointhash (origindimensionspacepointhash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof MySqlPlatform, sprintf('Migration can only be executed safely on "%s"', MySqlPlatform::class));

        $this->addSql('DROP TABLE neos_neos_projection_asset_usage');
    }
}
