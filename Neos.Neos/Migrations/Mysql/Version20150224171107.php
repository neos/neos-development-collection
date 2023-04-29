<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust event log table to schema valid table structure
 */
class Version20150224171107 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP FOREIGN KEY FK_30AB3A75B684C08");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP PRIMARY KEY");
        $this->addSql("UPDATE typo3_neos_eventlog_domain_model_event e, (SELECT DISTINCT uid, persistence_object_identifier FROM typo3_neos_eventlog_domain_model_event) p SET e.parentevent = p.uid WHERE parentevent IS NOT NULL AND p.persistence_object_identifier = e.parentevent");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP persistence_object_identifier, CHANGE parentevent parentevent INT UNSIGNED DEFAULT NULL, CHANGE uid uid INT UNSIGNED AUTO_INCREMENT NOT NULL");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD CONSTRAINT FK_30AB3A75B684C08 FOREIGN KEY (parentevent) REFERENCES typo3_neos_eventlog_domain_model_event (uid)");
        $indexes = $this->sm->listTableIndexes('typo3_neos_eventlog_domain_model_event');
        if (array_key_exists('uid', $indexes)) {
            $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP INDEX uid, ADD INDEX olduid (uid)");
        }
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD PRIMARY KEY (uid)");
        if (array_key_exists('uid', $indexes)) {
            $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP INDEX olduid");
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP FOREIGN KEY FK_30AB3A75B684C08");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD persistence_object_identifier VARCHAR(40) NOT NULL, CHANGE uid uid INT AUTO_INCREMENT NOT NULL, CHANGE parentevent parentevent VARCHAR(40) DEFAULT NULL");
        $this->addSql("UPDATE typo3_neos_eventlog_domain_model_event SET persistence_object_identifier = UUID()");
        $this->addSql("UPDATE typo3_neos_eventlog_domain_model_event e, (SELECT DISTINCT uid, persistence_object_identifier FROM typo3_neos_eventlog_domain_model_event) p SET e.parentevent = p.persistence_object_identifier WHERE parentevent IS NOT NULL AND p.uid = e.parentevent");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD PRIMARY KEY (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD CONSTRAINT FK_30AB3A75B684C08 FOREIGN KEY (parentevent) REFERENCES typo3_neos_eventlog_domain_model_event (persistence_object_identifier)");
    }
}
