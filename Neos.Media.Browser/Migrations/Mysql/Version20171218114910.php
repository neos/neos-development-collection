<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Introduce ImportedAsset
 */
class Version20171218114910 extends AbstractMigration
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
        $this->addSql('CREATE TABLE neos_media_browser_domain_model_importedasset (persistence_object_identifier VARCHAR(40) NOT NULL, localasset VARCHAR(40) DEFAULT NULL, assetsourceidentifier VARCHAR(255) NOT NULL, remoteassetidentifier VARCHAR(255) NOT NULL, importedat DATETIME NOT NULL, UNIQUE INDEX UNIQ_368F66CC63954094 (localasset), UNIQUE INDEX flow_identity_neos_media_browser_domain_model_impor_f2d03 (assetsourceidentifier, remoteassetidentifier), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE neos_media_browser_domain_model_importedasset ADD CONSTRAINT FK_368F66CC63954094 FOREIGN KEY (localasset) REFERENCES neos_media_domain_model_asset (persistence_object_identifier)');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql('DROP TABLE neos_media_browser_domain_model_importedasset');
    }
}
