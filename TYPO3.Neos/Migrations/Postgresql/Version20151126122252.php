<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust column type and index names on Event schema to match code
 *
 * Note: the indexes are manually maintained, since the annotation adding them is not picked
 * up, seemingly because of the single table inheritance (Event > NodeEvent).
 */
class Version20151126122252 extends AbstractMigration
{

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("TRUNCATE TABLE typo3_neos_eventlog_domain_model_event");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER data TYPE jsonb USING data::jsonb");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER data DROP DEFAULT");

        $this->addSql("ALTER INDEX neos_eventlog_eventtype RENAME TO eventtype");
        $this->addSql("ALTER INDEX neos_eventlog_documentnodeidentifier RENAME TO documentnodeidentifier");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("TRUNCATE TABLE typo3_neos_eventlog_domain_model_event");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER data TYPE TEXT");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER data DROP DEFAULT");

        $this->addSql("ALTER INDEX eventtype RENAME TO neos_eventlog_eventtype");
        $this->addSql("ALTER INDEX documentnodeidentifier RENAME TO neos_eventlog_documentnodeidentifier");
    }
}
