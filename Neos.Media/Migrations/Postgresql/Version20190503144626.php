<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Make assetsourceidentifier field non-nullable
 */
class Version20190503144626 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Make assetsourceidentifier field non-nullable';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        // Make non-nullable and remove default (was used for transition only)
        $this->addSql('ALTER TABLE neos_media_domain_model_asset ALTER assetsourceidentifier DROP DEFAULT');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset ALTER assetsourceidentifier SET NOT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE neos_media_domain_model_asset ALTER assetsourceidentifier SET DEFAULT \'neos\'');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset ALTER assetsourceidentifier DROP NOT NULL');
    }
}
