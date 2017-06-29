<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create unique indexes for identity properties
 */
class Version20120429213448 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE UNIQUE INDEX flow3_identity_typo3_typo3_domain_model_site ON typo3_typo3_domain_model_site (nodename)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP INDEX flow3_identity_typo3_typo3_domain_model_site ON typo3_typo3_domain_model_site");
    }
}
