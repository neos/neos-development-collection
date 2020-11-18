<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add constraint to thumbnail table to prevent duplicates.
 */
class Version20151216144435 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("TRUNCATE TABLE typo3_media_domain_model_thumbnail");
        $this->addSql("DROP INDEX originalasset_configurationhash");
        $this->addSql("CREATE UNIQUE INDEX originalasset_configurationhash ON typo3_media_domain_model_thumbnail (originalasset, configurationhash)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("DROP INDEX originalasset_configurationhash");
        $this->addSql("CREATE INDEX originalasset_configurationhash ON typo3_media_domain_model_thumbnail (originalasset, configurationhash)");
    }
}
