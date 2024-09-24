<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add static resource property to thumbnail table.
 */
class Version20151216143756 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform));

        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ADD staticresource VARCHAR(255) DEFAULT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform));

        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail DROP staticresource");
    }
}
