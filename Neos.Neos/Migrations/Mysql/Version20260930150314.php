<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930150314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'This migration was introduced to fix an old state of the migration Version20240906102606 in case it was executed before the Primary Key was added. The migration will only apply missing changes by checking current state before.';
    }

    /**
     * @see https://github.com/neos/neos-development-collection/issues/5988
     * @see Version20240906102606.php
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\AbstractMySQLPlatform'."
        );

        if ($schema->getTable('neos_asset_usage')->hasIndex('IDX_14C94F11044B499EB28F27DAEAC5D4BB')) {
            $this->addSql('ALTER TABLE `neos_asset_usage` DROP INDEX IDX_14C94F11044B499EB28F27DAEAC5D4BB');
        }

        if ($schema->getTable('neos_asset_usage')->getPrimaryKey() === null) {
            $this->addSql('ALTER TABLE `neos_asset_usage` MODIFY `contentrepositoryid` char(16) NOT NULL, ADD PRIMARY KEY (`contentrepositoryid`, `assetid`,`workspacename`,`nodeaggregateid`,`origindimensionspacepointhash`,`propertyname`)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            true,
            "Migration can't get reverted. See Version20240906102606 for removal of the table."
        );
    }
}
