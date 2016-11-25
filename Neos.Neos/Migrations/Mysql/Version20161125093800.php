<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust table names to the renaming of TYPO3.Neos to Neos.Neos.
 */
class Version20161125093800 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('RENAME TABLE typo3_neos_domain_model_domain TO neos_neos_domain_model_domain');
        $this->addSql('RENAME TABLE typo3_neos_domain_model_site TO neos_neos_domain_model_site');
        $this->addSql('RENAME TABLE typo3_neos_domain_model_user TO neos_neos_domain_model_user');
        $this->addSql('RENAME TABLE typo3_neos_domain_model_userpreferences TO neos_neos_domain_model_userpreferences');
        $this->addSql('RENAME TABLE typo3_neos_eventlog_domain_model_event TO neos_neos_eventlog_domain_model_event');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('RENAME TABLE neos_neos_domain_model_domain TO typo3_neos_domain_model_domain');
        $this->addSql('RENAME TABLE neos_neos_domain_model_site TO typo3_neos_domain_model_site');
        $this->addSql('RENAME TABLE neos_neos_domain_model_user TO typo3_neos_domain_model_user');
        $this->addSql('RENAME TABLE neos_neos_domain_model_userpreferences TO typo3_neos_domain_model_userpreferences');
        $this->addSql('RENAME TABLE neos_neos_eventlog_domain_model_event TO typo3_neos_eventlog_domain_model_event');
    }
}
