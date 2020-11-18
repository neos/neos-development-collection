<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Drop content types table, as they are now stored inside the Settings.yaml
 */
class Version20110920104739 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP TABLE contentTypesDeclaredSuperTypes");
        $this->addSql("DROP TABLE typo3_typo3cr_domain_model_contenttype");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE contentTypesDeclaredSuperTypes (typo3cr_contenttype VARCHAR(40) NOT NULL, declaredSuperTypeId VARCHAR(40) NOT NULL, INDEX IDX_BEE1B2BEE2741BEE (declaredSuperTypeId), INDEX IDX_BEE1B2BEF2209F2 (typo3cr_contenttype), PRIMARY KEY(declaredSuperTypeId, typo3cr_contenttype)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3_typo3cr_domain_model_contenttype (flow3_persistence_identifier VARCHAR(40) NOT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE contentTypesDeclaredSuperTypes ADD CONSTRAINT contenttypesdeclaredsupertypes_ibfk_2 FOREIGN KEY (typo3cr_contenttype) REFERENCES typo3_typo3cr_domain_model_contenttype(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE contentTypesDeclaredSuperTypes ADD CONSTRAINT contenttypesdeclaredsupertypes_ibfk_1 FOREIGN KEY (declaredSuperTypeId) REFERENCES typo3_typo3cr_domain_model_contenttype(flow3_persistence_identifier)");
    }
}
