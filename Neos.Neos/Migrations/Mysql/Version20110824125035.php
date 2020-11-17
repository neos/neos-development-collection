<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename TYPO3 tables to follow FQCN
 */
class Version20110824125035 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("RENAME TABLE typo3_domain TO typo3_typo3_domain_model_domain");
        $this->addSql("RENAME TABLE typo3_site TO typo3_typo3_domain_model_site");
        $this->addSql("RENAME TABLE typo3_user TO typo3_typo3_domain_model_user");
        $this->addSql("RENAME TABLE typo3_userpreferences TO typo3_typo3_domain_model_userpreferences");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("RENAME TABLE typo3_typo3_domain_model_domain TO typo3_domain");
        $this->addSql("RENAME TABLE typo3_typo3_domain_model_site TO typo3_site");
        $this->addSql("RENAME TABLE typo3_typo3_domain_model_user TO typo3_user");
        $this->addSql("RENAME TABLE typo3_typo3_domain_model_userpreferences TO typo3_userpreferences");
    }
}
