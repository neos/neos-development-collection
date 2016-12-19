<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds primary domain to site model
 */
class Version20160411101458 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE typo3_neos_domain_model_site ADD primarydomain VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_site ADD CONSTRAINT FK_1854B207B8872B4A FOREIGN KEY (primarydomain) REFERENCES typo3_neos_domain_model_domain (persistence_object_identifier)');
        $this->addSql('CREATE INDEX IDX_1854B207B8872B4A ON typo3_neos_domain_model_site (primarydomain)');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE typo3_neos_domain_model_site DROP FOREIGN KEY FK_1854B207B8872B4A');
        $this->addSql('DROP INDEX IDX_1854B207B8872B4A ON typo3_neos_domain_model_site');
        $this->addSql('ALTER TABLE typo3_neos_domain_model_site DROP primarydomain');
    }
}