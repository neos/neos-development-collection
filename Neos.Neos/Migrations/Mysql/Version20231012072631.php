<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231012072631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes the table for the EventLog (neos_neos_eventlog_domain_model_event)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1027Platform'."
        );

        $this->addSql('DROP TABLE IF EXISTS neos_neos_eventlog_domain_model_event');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1027Platform'."
        );

        $this->addSql('CREATE TABLE neos_neos_eventlog_domain_model_event (parentevent int(10) unsigned DEFAULT NULL, timestamp datetime NOT NULL, uid int(10) unsigned NOT NULL AUTO_INCREMENT, eventtype varchar(255) NOT NULL, accountidentifier varchar(255) DEFAULT NULL, data longtext NOT NULL COMMENT \'(DC2Type:flow_json_array)\', dtype varchar(255) NOT NULL, nodeidentifier varchar(255) DEFAULT NULL, documentnodeidentifier varchar(255) DEFAULT NULL, workspacename varchar(255) DEFAULT NULL, dimension longtext DEFAULT NULL COMMENT \'(DC2Type:array)\', dimensionshash varchar(32) DEFAULT NULL, PRIMARY KEY (uid), KEY eventtype (eventtype), KEY IDX_D6DBC30A5B684C08 (parentevent), KEY documentnodeidentifier (documentnodeidentifier), KEY dimensionshash (dimensionshash), KEY workspacename_parentevent (`workspacename`,`parentevent`), CONSTRAINT `FK_30AB3A75B684C08` FOREIGN KEY (`parentevent`) REFERENCES `neos_neos_eventlog_domain_model_event` (`uid`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }
}
