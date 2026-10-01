<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds an auto increment primary key to the asset usage table (required for sql_require_primary_key)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\AbstractMySQLPlatform'."
        );
        $this->skipIf(
            $schema->getTable('neos_asset_usage')->hasColumn('id'),
            'Table "neos_asset_usage" already has an "id" column'
        );

        $this->addSql('ALTER TABLE `neos_asset_usage` ADD `id` bigint unsigned NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\AbstractMySQLPlatform'."
        );

        $this->addSql('ALTER TABLE `neos_asset_usage` DROP `id`');
    }
}
