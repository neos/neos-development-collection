<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Introduce asset sources support
 */
class Version20180406163142 extends AbstractMigration
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
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('CREATE TABLE neos_media_domain_model_importedasset (persistence_object_identifier VARCHAR(40) NOT NULL, assetsourceidentifier VARCHAR(255) NOT NULL, remoteassetidentifier VARCHAR(255) NOT NULL, localassetidentifier VARCHAR(255) NOT NULL, localoriginalassetidentifier VARCHAR(255) DEFAULT NULL, importedat TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(persistence_object_identifier))');
        $this->addSql('CREATE UNIQUE INDEX flow_identity_neos_media_domain_model_importedasset ON neos_media_domain_model_importedasset (assetsourceidentifier, remoteassetidentifier, localassetidentifier)');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset ADD assetsourceidentifier VARCHAR(255) DEFAULT \'neos\'');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('DROP TABLE neos_media_domain_model_importedasset');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset DROP assetsourceidentifier');
    }
}
