<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create Event Log Table
 */
class Version20141114115241 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE typo3_neos_eventlog_domain_model_event (persistence_object_identifier VARCHAR(40) NOT NULL, parentevent VARCHAR(40) DEFAULT NULL, timestamp DATETIME NOT NULL, uid INT(11) NOT NULL AUTO_INCREMENT UNIQUE, eventtype VARCHAR(255) NOT NULL, accountidentifier VARCHAR(255) DEFAULT NULL, data LONGTEXT NOT NULL COMMENT '(DC2Type:array)', dtype VARCHAR(255) NOT NULL, nodeidentifier VARCHAR(255) DEFAULT NULL, documentnodeidentifier VARCHAR(255) DEFAULT NULL, workspacename VARCHAR(255) DEFAULT NULL, dimension LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', INDEX IDX_30AB3A75B684C08 (parentevent), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD CONSTRAINT FK_30AB3A75B684C08 FOREIGN KEY (parentevent) REFERENCES typo3_neos_eventlog_domain_model_event (persistence_object_identifier)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP FOREIGN KEY FK_30AB3A75B684C08");
        $this->addSql("DROP TABLE typo3_neos_eventlog_domain_model_event");
    }
}
