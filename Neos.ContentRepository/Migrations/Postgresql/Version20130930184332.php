<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Raise length limit on path and parentpath
 */
class Version20130930184332 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER path TYPE VARCHAR(4000)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER parentpath TYPE VARCHAR(4000)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER path TYPE VARCHAR(255)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER parentpath TYPE VARCHAR(255)");
    }
}
