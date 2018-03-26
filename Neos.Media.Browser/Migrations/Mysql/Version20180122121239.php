<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 *
 */
class Version20180122121239 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'add localOriginalAssetIdentifier to ImportedAsset domain model';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX flow_identity_neos_media_browser_domain_model_impor_f2d03 ON neos_media_browser_domain_model_importedasset');
        $this->addSql('ALTER TABLE neos_media_browser_domain_model_importedasset ADD localoriginalassetidentifier VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX flow_identity_neos_media_browser_domain_model_impor_f2d03 ON neos_media_browser_domain_model_importedasset (assetsourceidentifier, remoteassetidentifier, localassetidentifier)');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX flow_identity_neos_media_browser_domain_model_impor_f2d03 ON neos_media_browser_domain_model_importedasset');
        $this->addSql('ALTER TABLE neos_media_browser_domain_model_importedasset DROP localoriginalassetidentifier');
        $this->addSql('CREATE UNIQUE INDEX flow_identity_neos_media_browser_domain_model_impor_f2d03 ON neos_media_browser_domain_model_importedasset (assetsourceidentifier, remoteassetidentifier)');
    }
}
