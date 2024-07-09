<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename node names in neos_contentrepository_domain_model_nodedata
 */
class Version20161125123504 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform), 'Migration can only be executed safely on "mysql".');

        $schemaManager = $this->connection->createSchemaManager();
        $hasTables = $schemaManager->tablesExist(['neos_contentrepository_domain_model_nodedata']);
        if ($hasTables) {
            $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET nodetype=REPLACE(nodetype, 'TYPO3.Neos.NodeTypes:', 'Neos.NodeTypes:')");
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform), 'Migration can only be executed safely on "mysql".');

        $schemaManager = $this->connection->createSchemaManager();
        $hasTables = $schemaManager->tablesExist(['neos_contentrepository_domain_model_nodedata']);
        if ($hasTables) {
            $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET nodetype=REPLACE(nodetype, 'Neos.NodeTypes:', 'TYPO3.Neos.NodeTypes:')");
        }
    }
}
