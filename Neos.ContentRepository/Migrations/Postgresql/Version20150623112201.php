<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Introduce new properties to Workspace model
 */
class Version20150623112201 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD title VARCHAR(255) DEFAULT NULL");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_workspace SET title=name WHERE title IS NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ALTER title SET NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD description TEXT DEFAULT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP title");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP description");
    }
}
