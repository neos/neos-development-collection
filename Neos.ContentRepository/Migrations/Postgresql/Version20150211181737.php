<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add unique constraint on node data across identifier, workspace and dimensionshash.
 */
class Version20150211181737 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("CREATE UNIQUE INDEX UNIQ_60A956B9772E836A8D94001992F8FB012D45FE4D ON typo3_typo3cr_domain_model_nodedata (identifier, workspace, dimensionshash, movedto)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("DROP INDEX UNIQ_60A956B9772E836A8D94001992F8FB012D45FE4D");
    }
}
