<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Set default for ratio mode for image adjustments
 */
class Version20191125132700 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Set default for ratio mode for image adjustments';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform), 'Migration can only be executed safely on "mysql".');
        $this->addSql('UPDATE neos_media_domain_model_adjustment_abstractimageadjustment SET ratiomode=\'inset\' WHERE (ratiomode IS NULL OR ratiomode=\'\') AND dtype=\'neos_media_adjustment_resizeimageadjustment\'');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform), 'Migration can only be executed safely on "mysql".');
    }
}
