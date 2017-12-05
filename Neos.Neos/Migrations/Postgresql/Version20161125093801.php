<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust table names to the renaming of TYPO3.Neos to Neos.Neos.
 */
class Version20161125093801 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE typo3_neos_domain_model_domain RENAME TO neos_neos_domain_model_domain');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_site RENAME TO neos_neos_domain_model_site');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_user RENAME TO neos_neos_domain_model_user');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_userpreferences RENAME TO neos_neos_domain_model_userpreferences');
        $this->addSql('ALTER TABLE typo3_neos_eventlog_domain_model_event RENAME TO neos_neos_eventlog_domain_model_event');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE neos_neos_domain_model_domain RENAME TO typo3_neos_domain_model_domain');
        $this->addSql('ALTER TABLE neos_neos_domain_model_site RENAME TO typo3_neos_domain_model_site');
        $this->addSql('ALTER TABLE neos_neos_domain_model_user RENAME TO typo3_neos_domain_model_user');
        $this->addSql('ALTER TABLE neos_neos_domain_model_userpreferences RENAME TO typo3_neos_domain_model_userpreferences');
        $this->addSql('ALTER TABLE neos_neos_eventlog_domain_model_event RENAME TO typo3_neos_eventlog_domain_model_event');
    }
}
