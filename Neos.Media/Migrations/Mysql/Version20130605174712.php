<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add caption and lastModified to Asset
 */
class Version20130605174712 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_asset ADD caption LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_asset ADD lastmodified DATETIME NOT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_asset DROP lastmodified");
        $this->addSql("ALTER TABLE typo3_media_domain_model_asset DROP caption");
    }
}
