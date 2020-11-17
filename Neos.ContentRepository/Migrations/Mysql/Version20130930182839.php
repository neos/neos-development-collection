<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Raise length limit on path and parentpath
 */
class Version20130930182839 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE path path VARCHAR(4000) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE parentpath parentpath VARCHAR(4000) NOT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE path path VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE parentpath parentpath VARCHAR(255) NOT NULL");
    }
}
