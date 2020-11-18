<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Set default for event uid column
 */
class Version20170629102140 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Set default for event uid column';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql("SELECT setval('neos_neos_eventlog_domain_model_event_uid_seq', (SELECT MAX(uid) FROM neos_neos_eventlog_domain_model_event))");
        $this->addSql("ALTER TABLE neos_neos_eventlog_domain_model_event ALTER uid SET DEFAULT nextval('neos_neos_eventlog_domain_model_event_uid_seq')");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        // No down migration available
    }
}
