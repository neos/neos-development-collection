<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename Typo3BackendProvider to Neos.Neos:Backend
 */
class Version20170115115240 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Rename Typo3BackendProvider to Neos.Neos:Backend';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform), 'Migration can only be executed safely on "postgresql".');

        $this->addSql("UPDATE neos_flow_security_account SET authenticationprovidername = 'Neos.Neos:Backend' WHERE authenticationprovidername = 'Typo3BackendProvider'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform), 'Migration can only be executed safely on "postgresql".');

        $this->addSql("UPDATE neos_flow_security_account SET authenticationprovidername = 'Typo3BackendProvider' WHERE authenticationprovidername = 'Neos.Neos:Backend'");
    }
}
