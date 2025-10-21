<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251021000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables for Neos.Neos';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQLPlatform'."
        );
        $this->addSql('CREATE TABLE neos_neos_domain_model_domain (persistence_object_identifier VARCHAR(40) NOT NULL, site VARCHAR(40) DEFAULT NULL, hostname VARCHAR(255) NOT NULL, scheme VARCHAR(255) DEFAULT NULL, port INT DEFAULT NULL, active TINYINT(1) NOT NULL, INDEX IDX_51265BE9694309E4 (site), UNIQUE INDEX flow_identity_neos_neos_domain_model_domain (hostname), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_neos_domain_model_site (persistence_object_identifier VARCHAR(40) NOT NULL, primarydomain VARCHAR(40) DEFAULT NULL, assetcollection VARCHAR(40) DEFAULT NULL, name VARCHAR(255) NOT NULL, nodename VARCHAR(255) NOT NULL, state INT NOT NULL, siteresourcespackagekey VARCHAR(255) NOT NULL, INDEX IDX_9B02A4EB8872B4A (primarydomain), INDEX IDX_9B02A4E5CEB2C15 (assetcollection), UNIQUE INDEX flow_identity_neos_neos_domain_model_site (nodename), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_neos_domain_model_user (persistence_object_identifier VARCHAR(40) NOT NULL, preferences VARCHAR(40) DEFAULT NULL, UNIQUE INDEX UNIQ_ED60F5E3E931A6F5 (preferences), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_neos_domain_model_userpreferences (persistence_object_identifier VARCHAR(40) NOT NULL, preferences LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE neos_neos_domain_model_domain ADD CONSTRAINT FK_51265BE9694309E4 FOREIGN KEY (site) REFERENCES neos_neos_domain_model_site (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_neos_domain_model_site ADD CONSTRAINT FK_9B02A4EB8872B4A FOREIGN KEY (primarydomain) REFERENCES neos_neos_domain_model_domain (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_neos_domain_model_site ADD CONSTRAINT FK_9B02A4E5CEB2C15 FOREIGN KEY (assetcollection) REFERENCES neos_media_domain_model_assetcollection (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_neos_domain_model_user ADD CONSTRAINT FK_ED60F5E3E931A6F5 FOREIGN KEY (preferences) REFERENCES neos_neos_domain_model_userpreferences (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_neos_domain_model_user ADD CONSTRAINT FK_ED60F5E347A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES neos_party_domain_model_abstractparty (persistence_object_identifier) ON DELETE CASCADE');

        $tableWorkspaceMetadata = $schema->createTable('neos_neos_workspace_metadata');
        $tableWorkspaceMetadata->addColumn('content_repository_id', 'string', ['length' => 16]);
        $tableWorkspaceMetadata->addColumn('workspace_name', 'string', ['length' => 255]);
        $tableWorkspaceMetadata->addColumn('title', 'string', ['length' => 255]);
        $tableWorkspaceMetadata->addColumn('description', 'text');
        $tableWorkspaceMetadata->addColumn('classification', 'string', ['length' => 255]);
        $tableWorkspaceMetadata->addColumn('owner_user_id', 'string', ['length' => 255, 'notnull' => false]);
        $tableWorkspaceMetadata->setPrimaryKey(['content_repository_id', 'workspace_name']);
        $tableWorkspaceMetadata->addIndex(['owner_user_id'], 'IDX_D6197E562B18554A');

        $tableWorkspaceRole = $schema->createTable('neos_neos_workspace_role');
        $tableWorkspaceRole->addColumn('content_repository_id', 'string', ['length' => 16]);
        $tableWorkspaceRole->addColumn('workspace_name', 'string', ['length' => 255]);
        $tableWorkspaceRole->addColumn('subject_type', 'string', ['length' => 20]);
        $tableWorkspaceRole->addColumn('subject', 'string', ['length' => 255]);
        $tableWorkspaceRole->addColumn('role', 'string', ['length' => 20]);
        $tableWorkspaceRole->setPrimaryKey(['content_repository_id', 'workspace_name', 'subject_type', 'subject']);

        $sql = <<<SQL
            CREATE TABLE `neos_asset_usage` (
                 `contentrepositoryid` char(16) DEFAULT NULL,
                 `assetid` varchar(40) NOT NULL DEFAULT '',
                 `originalassetid` varchar(40) DEFAULT NULL,
                 `workspacename` char(36) NOT NULL,
                 `nodeaggregateid` varchar(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
                 `origindimensionspacepoint` json DEFAULT (JSON_OBJECT()),
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

        $tableWorkspaceMetadata = $schema->getTable('neos_neos_workspace_metadata');
        $tableWorkspaceMetadata->addUniqueIndex(['content_repository_id', 'owner_user_id'], 'owner');
        $tableWorkspaceMetadata->dropIndex('IDX_D6197E562B18554A');

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
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQLPlatform'."
        );

        $schema->dropTable('neos_neos_impending_hard_removal_conflict');

        $tableWorkspaceMetadata = $schema->getTable('neos_neos_workspace_metadata');
        $tableWorkspaceMetadata->addIndex(['owner_user_id'], 'IDX_D6197E562B18554A');
        $tableWorkspaceMetadata->dropIndex('owner');

        $this->addSql('DROP TABLE IF EXISTS `neos_asset_usage`');

        $schema->dropTable('neos_neos_workspace_role');
        $schema->dropTable('neos_neos_workspace_metadata');

        $this->addSql('ALTER TABLE neos_neos_domain_model_domain DROP FOREIGN KEY FK_51265BE9694309E4');
        $this->addSql('ALTER TABLE neos_neos_domain_model_site DROP FOREIGN KEY FK_9B02A4EB8872B4A');
        $this->addSql('ALTER TABLE neos_neos_domain_model_site DROP FOREIGN KEY FK_9B02A4E5CEB2C15');
        $this->addSql('ALTER TABLE neos_neos_domain_model_user DROP FOREIGN KEY FK_ED60F5E3E931A6F5');
        $this->addSql('ALTER TABLE neos_neos_domain_model_user DROP FOREIGN KEY FK_ED60F5E347A46B0A');
        $this->addSql('DROP TABLE neos_neos_domain_model_domain');
        $this->addSql('DROP TABLE neos_neos_domain_model_site');
        $this->addSql('DROP TABLE neos_neos_domain_model_user');
        $this->addSql('DROP TABLE neos_neos_domain_model_userpreferences');
    }
}
