<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename hostpattern to hostname
 */
class Version20160711103440 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("DROP INDEX flow_identity_typo3_neos_domain_model_domain");
        $this->addSql("ALTER TABLE typo3_neos_domain_model_domain RENAME COLUMN hostpattern TO hostname");
        $this->addSql("CREATE UNIQUE INDEX flow_identity_typo3_neos_domain_model_domain ON typo3_neos_domain_model_domain (hostname)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("DROP INDEX flow_identity_typo3_neos_domain_model_domain");
        $this->addSql("ALTER TABLE typo3_neos_domain_model_domain RENAME COLUMN hostname TO hostpattern");
        $this->addSql("CREATE UNIQUE INDEX flow_identity_typo3_neos_domain_model_domain ON typo3_neos_domain_model_domain (hostpattern)");
    }
}