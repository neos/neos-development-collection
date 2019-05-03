<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Change index on "movedto" to unique (was forgotten)
 */
class Version20190503102825 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Change index on "movedto" to unique (was forgotten)';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('DROP INDEX idx_ce6515692d45fe4d');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CE6515692D45FE4D ON neos_contentrepository_domain_model_nodedata (movedto)');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('DROP INDEX UNIQ_CE6515692D45FE4D');
        $this->addSql('CREATE INDEX idx_ce6515692d45fe4d ON neos_contentrepository_domain_model_nodedata (movedto)');
    }
}
