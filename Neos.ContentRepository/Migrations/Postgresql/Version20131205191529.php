<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds a column "pathhash" to the NodeData table and a unique index over that column & the workspace column in order to prevent corrupt node trees.
 * pathhash must always be the MD5 hash of path.
 */
class Version20131205191529 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD pathhash VARCHAR(32) NOT NULL DEFAULT ''");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET pathhash = MD5(path)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_60A956B92DBEC7578D940019 ON typo3_typo3cr_domain_model_nodedata (pathhash, workspace)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("DROP INDEX UNIQ_60A956B92DBEC7578D940019");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP pathhash");
    }
}
