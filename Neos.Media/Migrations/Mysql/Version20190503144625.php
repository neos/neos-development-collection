<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Make some asset-related fields non-nullable, add DC2 type comment
 */
class Version20190503144625 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Make some asset-related fields non-nullable, add DC2 type comment';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        // Make non-nullable and remove default (was used for transition only)
        $this->addSql('ALTER TABLE neos_media_domain_model_asset CHANGE assetsourceidentifier assetsourceidentifier VARCHAR(255) NOT NULL');
        // Add DC2 comment
        $this->addSql('ALTER TABLE neos_media_domain_model_importedasset CHANGE importedat importedat DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_media_domain_model_asset CHANGE assetsourceidentifier assetsourceidentifier VARCHAR(255) DEFAULT \'neos\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE neos_media_domain_model_importedasset CHANGE importedat importedat DATETIME NOT NULL');
    }
}
