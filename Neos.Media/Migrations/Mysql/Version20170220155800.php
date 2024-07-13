<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add quality column to tables abstractimageadjustment and thumbnail.
 */
class Version20170220155800 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Add quality column to tables abstractimageadjustment and thumbnail.';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform), 'Migration can only be executed safely on "mysql".');
        $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment ADD quality INT DEFAULT NULL');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail ADD quality INT DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform), 'Migration can only be executed safely on "mysql".');
        $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment DROP quality');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail DROP quality');
    }
}
