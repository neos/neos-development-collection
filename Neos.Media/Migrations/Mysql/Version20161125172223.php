<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename dtypes in neos_media_domain_model_asset and neos_media_domain_model_adjustment_abstractimageadjustment
 */
class Version20161125172223 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql("UPDATE neos_media_domain_model_asset SET dtype = REPLACE(dtype, 'typo3_media_', 'neos_media_')");
        $this->addSql("UPDATE neos_media_domain_model_adjustment_abstractimageadjustment SET dtype = REPLACE(dtype, 'typo3_media_', 'neos_media_')");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql("UPDATE neos_media_domain_model_asset SET dtype = REPLACE(dtype, 'neos_media_', 'typo3_media_')");
        $this->addSql("UPDATE neos_media_domain_model_adjustment_abstractimageadjustment SET dtype = REPLACE(dtype, 'neos_media_', 'typo3_media_')");
    }
}
