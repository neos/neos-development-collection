<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust event log table to schema valid table structure
 */
class Version20150224171108 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP CONSTRAINT FK_30AB3A75B684C08");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP CONSTRAINT typo3_neos_eventlog_domain_model_event_pkey");
        $this->addSql("WITH p AS (SELECT DISTINCT uid, persistence_object_identifier FROM typo3_neos_eventlog_domain_model_event) UPDATE typo3_neos_eventlog_domain_model_event SET parentevent = p.uid FROM p WHERE parentevent IS NOT NULL AND p.persistence_object_identifier = parentevent");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER parentevent DROP DEFAULT");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER parentevent TYPE INTEGER USING (parentevent::integer)");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER uid SET NOT NULL");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP persistence_object_identifier");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD PRIMARY KEY (uid)");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD CONSTRAINT FK_30AB3A75B684C08 FOREIGN KEY (parentevent) REFERENCES typo3_neos_eventlog_domain_model_event (uid) NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP CONSTRAINT fk_30ab3a75b684c08");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event DROP CONSTRAINT typo3_neos_eventlog_domain_model_event_pkey");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD persistence_object_identifier VARCHAR(40) NULL");
        $result = $this->connection->executeQuery("SELECT installed_version FROM pg_available_extensions WHERE name = 'uuid-ossp'");
        if ($result->fetchColumn() !== null) {
            $this->addSql("UPDATE typo3_neos_eventlog_domain_model_event SET persistence_object_identifier = uuid_generate_v4()");
        } else {
            $result = $this->connection->executeQuery("SELECT uid FROM typo3_neos_eventlog_domain_model_event");
            while ($uid = $result->fetchColumn()) {
                $this->addSql("UPDATE typo3_neos_eventlog_domain_model_event SET persistence_object_identifier = '" . \Neos\Flow\Utility\Algorithms::generateUUID() . "' WHERE uid = " . $uid);
            }
        }
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER COLUMN persistence_object_identifier SET NOT NULL");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER uid DROP NOT NULL");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER parentevent TYPE VARCHAR(40) USING (parentevent::varchar)");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER parentevent DROP DEFAULT");

        $this->addSql("WITH p AS (SELECT DISTINCT uid, persistence_object_identifier FROM typo3_neos_eventlog_domain_model_event) UPDATE typo3_neos_eventlog_domain_model_event SET parentevent = p.persistence_object_identifier FROM p WHERE parentevent IS NOT NULL AND p.uid = parentevent::integer");

        $this->addSql("SELECT setval('typo3_neos_eventlog_domain_model_event_uid_seq', (SELECT MAX(uid) FROM typo3_neos_eventlog_domain_model_event)+1);");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER uid SET DEFAULT nextval('typo3_neos_eventlog_domain_model_event_uid_seq')");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD PRIMARY KEY (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ADD CONSTRAINT fk_30ab3a75b684c08 FOREIGN KEY (parentevent) REFERENCES typo3_neos_eventlog_domain_model_event (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
    }
}
