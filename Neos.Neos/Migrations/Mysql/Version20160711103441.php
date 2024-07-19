<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename hostpattern to hostname
 */
class Version20160711103441 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform), 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX flow_identity_typo3_neos_domain_model_domain ON typo3_neos_domain_model_domain');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_domain CHANGE hostpattern hostname VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX flow_identity_typo3_neos_domain_model_domain ON typo3_neos_domain_model_domain (hostname)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform), 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX flow_identity_typo3_neos_domain_model_domain ON typo3_neos_domain_model_domain');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_domain CHANGE hostname hostpattern VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('CREATE UNIQUE INDEX flow_identity_typo3_neos_domain_model_domain ON typo3_neos_domain_model_domain (hostpattern)');
    }
}
