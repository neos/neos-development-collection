<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds primary domain to site model
 */
class Version20160411101640 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE typo3_neos_domain_model_site ADD primarydomain VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_site ADD CONSTRAINT FK_1854B207B8872B4A FOREIGN KEY (primarydomain) REFERENCES typo3_neos_domain_model_domain (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_1854B207B8872B4A ON typo3_neos_domain_model_site (primarydomain)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE typo3_neos_domain_model_site DROP CONSTRAINT FK_1854B207B8872B4A');
        $this->addSql('DROP INDEX IDX_1854B207B8872B4A');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_site DROP primarydomain');
    }
}