<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Schema adjustments for dimension support in TYPO3CR.
 */
class Version20140206122814 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("CREATE TABLE typo3_typo3cr_domain_model_nodedimension (persistence_object_identifier VARCHAR(40) NOT NULL, nodedata VARCHAR(40) DEFAULT NULL, name VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY(persistence_object_identifier))");
        $this->addSql("CREATE INDEX IDX_6C144D3693BDC8E2 ON typo3_typo3cr_domain_model_nodedimension (nodedata)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_6C144D3693BDC8E25E237E061D775834 ON typo3_typo3cr_domain_model_nodedimension (nodedata, name, value)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedimension ADD CONSTRAINT FK_6C144D3693BDC8E2 FOREIGN KEY (nodedata) REFERENCES typo3_typo3cr_domain_model_nodedata (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD dimensionshash VARCHAR(32) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD dimensionvalues BYTEA DEFAULT NULL");
        $this->addSql("COMMENT ON COLUMN typo3_typo3cr_domain_model_nodedata.dimensionvalues IS '(DC2Type:objectarray)'");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET dimensionvalues = 'a:0:{}'");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER dimensionvalues SET NOT NULL");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET dimensionshash = 'd751713988987e9331980363e24189ce'");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER dimensionshash SET NOT NULL");
        $this->addSql("DROP INDEX UNIQ_60a956b92dbec7578d940019");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_60A956B92DBEC7578D94001992F8FB01 ON typo3_typo3cr_domain_model_nodedata (pathhash, workspace, dimensionshash)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("DROP TABLE typo3_typo3cr_domain_model_nodedimension");
        $this->addSql("DROP INDEX UNIQ_60A956B92DBEC7578D94001992F8FB01");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP dimensionshash, DROP dimensionvalues");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_60a956b92dbec7578d940019 ON typo3_typo3cr_domain_model_nodedata (pathhash, workspace)");
    }
}
