<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create Event Log Table
 */
class Version20141125133037 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("CREATE SEQUENCE typo3_neos_eventlog_domain_model_event_uid_seq INCREMENT BY 1 MINVALUE 1 START 1");
        $this->addSql("CREATE TABLE typo3_neos_eventlog_domain_model_event (persistence_object_identifier VARCHAR(40) NOT NULL, parentevent VARCHAR(40) DEFAULT NULL, timestamp TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, uid INT DEFAULT nextval('typo3_neos_eventlog_domain_model_event_uid_seq'), eventtype VARCHAR(255) NOT NULL, accountidentifier VARCHAR(255) DEFAULT NULL, data TEXT NOT NULL, dtype VARCHAR(255) NOT NULL, nodeidentifier VARCHAR(255) DEFAULT NULL, documentnodeidentifier VARCHAR(255) DEFAULT NULL, workspacename VARCHAR(255) DEFAULT NULL, dimension TEXT DEFAULT NULL, PRIMARY KEY(persistence_object_identifier))");
        $this->addSql("CREATE INDEX IDX_30AB3A75B684C08 ON typo3_neos_eventlog_domain_model_event (parentevent)");
        $this->addSql("COMMENT ON COLUMN typo3_neos_eventlog_domain_model_event.data IS '(DC2Type:array)'");
        $this->addSql("COMMENT ON COLUMN typo3_neos_eventlog_domain_model_event.dimension IS '(DC2Type:array)'");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD CONSTRAINT FK_30AB3A75B684C08 FOREIGN KEY (parentevent) REFERENCES typo3_neos_eventlog_domain_model_event (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP CONSTRAINT FK_30AB3A75B684C08");
        $this->addSql("DROP TABLE typo3_neos_eventlog_domain_model_event");
        $this->addSql("DROP SEQUENCE typo3_neos_eventlog_domain_model_event_uid_seq CASCADE");
    }
}
