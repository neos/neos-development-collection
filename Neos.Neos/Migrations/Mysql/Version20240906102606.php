<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
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
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\AbstractMySQLPlatform'."
        );

        $sql = <<<SQL
            CREATE TABLE `neos_asset_usage` (
                 `contentrepositoryid` char(16) DEFAULT NULL,
                 `assetid` varchar(40) NOT NULL DEFAULT '',
                 `originalassetid` varchar(40) DEFAULT NULL,
                 `workspacename` char(36) NOT NULL,
                 `nodeaggregateid` varchar(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
                 `origindimensionspacepoint` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT '{}',
                 `origindimensionspacepointhash` varbinary(32) NOT NULL DEFAULT '',
                 `propertyname` varchar(255) NOT NULL DEFAULT '',
                 UNIQUE KEY `IDX_14C94F11044B499EB28F27DAEAC5D4BB` (`contentrepositoryid`, `assetid`,`originalassetid`,`workspacename`,`nodeaggregateid`,`origindimensionspacepointhash`,`propertyname`),
                 KEY `IDX_55757035ADC144B7ED5AC6744F7D18CF` (`contentrepositoryid`, `workspacename`,`nodeaggregateid`,`origindimensionspacepointhash`),
                 KEY `IDX_0A70B9E69F347EB3D7CA716B10767577` (`contentrepositoryid`),
                 KEY `IDX_9FC89003DB4D99EB02993595B732415D` (`assetid`),
                 KEY `IDX_40479348B81805EA31D1A10B56B9455D` (`workspacename`),
                 KEY `IDX_1E6617E2E8A543E560401157FBBE2272` (`nodeaggregateid`),
                 KEY `IDX_D8E094F9CA47A07B4723A823179CFBEB` (`origindimensionspacepointhash`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;
        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\AbstractMySQLPlatform'."
        );

        $this->addSql('DROP TABLE IF EXISTS `neos_asset_usage`');
    }
}
