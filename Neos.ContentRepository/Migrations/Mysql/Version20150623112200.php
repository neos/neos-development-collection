<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds "title" and "description" to the Workspace model
 */
class Version20150623112200 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD title VARCHAR(255) NOT NULL, ADD description TEXT DEFAULT NULL");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_workspace SET title=name WHERE title=''");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP title, DROP description");
    }
}
