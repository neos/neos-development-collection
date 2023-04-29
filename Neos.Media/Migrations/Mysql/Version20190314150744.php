<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Introduce variant presets
 */
class Version20190314150744 extends AbstractMigration
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
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        // It is now safe the remove the default value from the database schema, as we only needed it during migration of earlier Neos versions:
        $this->addSql('ALTER TABLE neos_media_domain_model_asset CHANGE assetsourceidentifier assetsourceidentifier VARCHAR(255) NOT NULL');

        // Add Doctrine hint for DateTimeImmutable:
        $this->addSql('ALTER TABLE neos_media_domain_model_importedasset CHANGE importedat importedat DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // Introduce variant preset fields:
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant ADD presetidentifier VARCHAR(255) DEFAULT NULL, ADD presetvariantname VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_media_domain_model_asset CHANGE assetsourceidentifier assetsourceidentifier VARCHAR(255) DEFAULT \'neos\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant DROP presetidentifier, DROP presetvariantname');
        $this->addSql('ALTER TABLE neos_media_domain_model_importedasset CHANGE importedat importedat DATETIME NOT NULL');
    }
}
