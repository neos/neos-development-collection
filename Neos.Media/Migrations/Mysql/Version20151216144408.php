<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add constraint to thumbnail table to prevent duplicates.
 */
class Version20151216144408 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform));

        $this->addSql("TRUNCATE TABLE typo3_media_domain_model_thumbnail");
        $this->addSql("DROP INDEX originalasset_configurationhash ON typo3_media_domain_model_thumbnail");
        $this->addSql("CREATE UNIQUE INDEX originalasset_configurationhash ON typo3_media_domain_model_thumbnail (originalasset, configurationhash)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform));

        $this->addSql("DROP INDEX originalasset_configurationhash ON typo3_media_domain_model_thumbnail");
        $this->addSql("CREATE INDEX originalasset_configurationhash ON typo3_media_domain_model_thumbnail (originalasset, configurationhash)");
    }
}
