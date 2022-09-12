<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add unique constraint on node data across identifier, workspace and dimensionshash.
 */
class Version20150211181736 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE UNIQUE INDEX UNIQ_60A956B9772E836A8D94001992F8FB012D45FE4D ON typo3_typo3cr_domain_model_nodedata (identifier, workspace, dimensionshash, movedto)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP INDEX UNIQ_60A956B9772E836A8D94001992F8FB012D45FE4D ON typo3_typo3cr_domain_model_nodedata");
    }
}
