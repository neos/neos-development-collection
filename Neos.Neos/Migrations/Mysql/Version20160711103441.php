<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename hostpattern to hostname
 */
class Version20160711103441 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX flow_identity_typo3_neos_domain_model_domain ON typo3_neos_domain_model_domain');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_domain CHANGE hostpattern hostname VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX flow_identity_typo3_neos_domain_model_domain ON typo3_neos_domain_model_domain (hostname)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX flow_identity_typo3_neos_domain_model_domain ON typo3_neos_domain_model_domain');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_domain CHANGE hostname hostpattern VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('CREATE UNIQUE INDEX flow_identity_typo3_neos_domain_model_domain ON typo3_neos_domain_model_domain (hostpattern)');
    }
}
