<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add property allowupscaling in typo3_media_domain_model_adjustment_abstractimageadjustment
 */
class Version20150324185008 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment ADD allowupscaling BOOLEAN DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ADD allowupscaling BOOLEAN DEFAULT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment DROP allowupscaling");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail DROP allowupscaling");
    }
}
