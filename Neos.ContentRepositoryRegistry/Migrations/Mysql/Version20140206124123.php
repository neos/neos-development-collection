<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Schema adjustments for dimension support in TYPO3CR.
 */
class Version20140206124123 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE typo3_typo3cr_domain_model_nodedimension (persistence_object_identifier VARCHAR(40) NOT NULL, nodedata VARCHAR(40) DEFAULT NULL, name VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, INDEX IDX_6C144D3693BDC8E2 (nodedata), UNIQUE INDEX UNIQ_6C144D3693BDC8E25E237E061D775834 (nodedata, name, value), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedimension ADD CONSTRAINT FK_6C144D3693BDC8E2 FOREIGN KEY (nodedata) REFERENCES typo3_typo3cr_domain_model_nodedata (persistence_object_identifier)");
        $this->addSql("DROP INDEX UNIQ_60A956B92DBEC7578D940019 ON typo3_typo3cr_domain_model_nodedata");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD dimensionshash VARCHAR(32) NOT NULL, ADD dimensionvalues LONGBLOB NOT NULL COMMENT '(DC2Type:objectarray)'");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET dimensionvalues = 'a:0:{}'");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET dimensionshash = 'd751713988987e9331980363e24189ce'");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_60A956B92DBEC7578D94001992F8FB01 ON typo3_typo3cr_domain_model_nodedata (pathhash, workspace, dimensionshash)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP TABLE typo3_typo3cr_domain_model_nodedimension");
        $this->addSql("DROP INDEX UNIQ_60A956B92DBEC7578D94001992F8FB01 ON typo3_typo3cr_domain_model_nodedata");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP dimensionshash, DROP dimensionvalues");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_60A956B92DBEC7578D940019 ON typo3_typo3cr_domain_model_nodedata (pathhash, workspace)");
    }
}
