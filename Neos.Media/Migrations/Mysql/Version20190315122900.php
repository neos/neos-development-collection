<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Introduce aspect ratio field for image adjustments
 */
class Version20190315122900 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Introduce aspect ratio field for image adjustments';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform), 'Migration can only be executed safely on "mysql".');
        $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment ADD aspectratioasstring VARCHAR(255) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws Exception
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform), 'Migration can only be executed safely on "mysql".');
        $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment DROP aspectratioasstring');
    }
}
