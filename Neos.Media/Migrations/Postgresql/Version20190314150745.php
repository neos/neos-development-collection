<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Introduce variant presets
 */
class Version20190314150745 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Introduce variant presets';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        // It is now safe the remove the default value from the database schema, as we only needed it during migration of earlier Neos versions:
        $this->addSql('ALTER TABLE neos_media_domain_model_asset ALTER assetsourceidentifier DROP DEFAULT');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset ALTER assetsourceidentifier SET NOT NULL');

        // Add Doctrine hint for DateTimeImmutable:
        $this->addSql('COMMENT ON COLUMN neos_media_domain_model_importedasset.importedat IS \'(DC2Type:datetime_immutable)\'');

        // Introduce variant preset fields:
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant ADD presetidentifier VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant ADD presetvariantname VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');
        
        $this->addSql('ALTER TABLE neos_media_domain_model_asset ALTER assetsourceidentifier SET DEFAULT \'neos\'');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset ALTER assetsourceidentifier DROP NOT NULL');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant DROP presetidentifier');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant DROP presetvariantname');
        $this->addSql('COMMENT ON COLUMN neos_media_domain_model_importedasset.importedat IS NULL');
    }
}