<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 *
 */
class Version20171219121748 extends AbstractMigration
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
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_media_browser_domain_model_importedasset DROP FOREIGN KEY FK_368F66CC63954094');
        $this->addSql('DROP INDEX UNIQ_368F66CC63954094 ON neos_media_browser_domain_model_importedasset');
        $this->addSql('ALTER TABLE neos_media_browser_domain_model_importedasset ADD localassetidentifier VARCHAR(255) NOT NULL, DROP localasset');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_media_browser_domain_model_importedasset ADD localasset VARCHAR(40) DEFAULT NULL COLLATE utf8_unicode_ci, DROP localassetidentifier');
        $this->addSql('ALTER TABLE neos_media_browser_domain_model_importedasset ADD CONSTRAINT FK_368F66CC63954094 FOREIGN KEY (localasset) REFERENCES neos_media_domain_model_asset (persistence_object_identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_368F66CC63954094 ON neos_media_browser_domain_model_importedasset (localasset)');
    }
}
