<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\AbortMigration as AbortMigrationException;

/**
 * Add workspace/parent index to NodeEvent table
 */
class Version20211220145602 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Add workspace/parent index to NodeEvent table';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform), 'Migration can only be executed safely on "postgresql".');

        $this->addSql('CREATE INDEX workspacename_parentevent ON neos_neos_eventlog_domain_model_event (workspacename, parentevent)');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform), 'Migration can only be executed safely on "postgresql".');

        $this->addSql('DROP INDEX workspacename_parentevent');
    }
}
