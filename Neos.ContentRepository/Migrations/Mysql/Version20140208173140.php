<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * This migration adds "parentpathhash" and additional indexes resulting in drastic speed improvements
 */
class Version20140208173140 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD parentpathhash VARCHAR(32) NOT NULL");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET parentpathhash = MD5(parentpath)");
        $this->addSql("CREATE INDEX parentpath_sortingindex ON typo3_typo3cr_domain_model_nodedata (parentpathhash, sortingindex)");
        $this->addSql("CREATE INDEX identifierindex ON typo3_typo3cr_domain_model_nodedata (identifier)");
        $this->addSql("CREATE INDEX nodetypeindex ON typo3_typo3cr_domain_model_nodedata (nodetype)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP INDEX identifierindex ON typo3_typo3cr_domain_model_nodedata");
        $this->addSql("DROP INDEX nodetypeindex ON typo3_typo3cr_domain_model_nodedata");
        $this->addSql("DROP INDEX parentpath_sortingindex ON typo3_typo3cr_domain_model_nodedata");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP parentpathhash");
    }
}
