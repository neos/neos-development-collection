<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Introduce asset sources support
 */
class Version20180405104603 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Introduce asset sources support';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE neos_media_domain_model_importedasset (persistence_object_identifier VARCHAR(40) NOT NULL, assetsourceidentifier VARCHAR(255) NOT NULL, remoteassetidentifier VARCHAR(255) NOT NULL, localassetidentifier VARCHAR(255) NOT NULL, localoriginalassetidentifier VARCHAR(255) DEFAULT NULL, importedat DATETIME NOT NULL, UNIQUE INDEX flow_identity_neos_media_domain_model_importedasset (assetsourceidentifier, remoteassetidentifier, localassetidentifier), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset ADD assetsourceidentifier VARCHAR(255) DEFAULT \'neos\'');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE neos_media_domain_model_importedasset');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset DROP assetsourceidentifier');
    }
}
