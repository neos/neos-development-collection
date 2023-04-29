<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust table names to the renaming of TYPO3.Neos to Neos.Neos.
 */
class Version20161127010617 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE typo3_neos_eventlog_domain_model_event_uid_seq RENAME TO neos_neos_eventlog_domain_model_event_uid_seq');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE neos_neos_eventlog_domain_model_event_uid_seq RENAME TO typo3_neos_eventlog_domain_model_event_uid_seq');
    }
}
