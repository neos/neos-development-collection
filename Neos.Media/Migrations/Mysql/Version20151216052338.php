<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Allow thumbnails without resources for asynchronous thumbnail generation.
 */
class Version20151216052338 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform));

        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail CHANGE resource resource VARCHAR(40) DEFAULT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform));

        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail CHANGE resource resource VARCHAR(40) NOT NULL COLLATE utf8mb4_unicode_ci");
    }
}
