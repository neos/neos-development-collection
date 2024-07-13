<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds scheme and port to domain model
 */
class Version20160411101639 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform), 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE typo3_neos_domain_model_domain ADD scheme VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_domain ADD port INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform), 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE typo3_neos_domain_model_domain DROP scheme');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_domain DROP port');
    }
}
