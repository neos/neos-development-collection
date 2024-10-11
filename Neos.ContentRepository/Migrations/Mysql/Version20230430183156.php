<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Make index on movedto unique
 */
final class Version20230430183156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make index on movedto unique';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MysqlPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MysqlPlatform'."
        );

        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata DROP INDEX IDX_CE6515692D45FE4D, ADD UNIQUE INDEX UNIQ_CE6515692D45FE4D (movedto)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MysqlPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MysqlPlatform'."
        );

        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata DROP INDEX UNIQ_CE6515692D45FE4D, ADD INDEX IDX_CE6515692D45FE4D (movedto)');
    }
}
