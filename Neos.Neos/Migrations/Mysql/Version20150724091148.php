<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds indices to the event log to improve performance
 */
class Version20150724091148 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE INDEX documentnodeidentifier ON typo3_neos_eventlog_domain_model_event (documentnodeidentifier)");
        $this->addSql("CREATE INDEX eventtype ON typo3_neos_eventlog_domain_model_event (eventtype)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP INDEX documentnodeidentifier ON typo3_neos_eventlog_domain_model_event");
        $this->addSql("DROP INDEX eventtype ON typo3_neos_eventlog_domain_model_event");
    }
}
