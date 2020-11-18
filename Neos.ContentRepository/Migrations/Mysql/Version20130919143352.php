<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename rootnode to rootnodedata on workspace
 */
class Version20130919143352 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY FK_71DE9CFBA762B951");
        $this->addSql("DROP INDEX IDX_71DE9CFB750166F ON typo3_typo3cr_domain_model_workspace");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace CHANGE rootnode rootnodedata VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBBB46155 FOREIGN KEY (rootnodedata) REFERENCES typo3_typo3cr_domain_model_nodedata (persistence_object_identifier)");
        $this->addSql("CREATE INDEX IDX_71DE9CFBBB46155 ON typo3_typo3cr_domain_model_workspace (rootnodedata)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY FK_71DE9CFBBB46155");
        $this->addSql("DROP INDEX IDX_71DE9CFBBB46155 ON typo3_typo3cr_domain_model_workspace");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace CHANGE rootnodedata rootnode VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBA762B951 FOREIGN KEY (rootnode) REFERENCES typo3_typo3cr_domain_model_nodedata (persistence_object_identifier)");
        $this->addSql("CREATE INDEX IDX_71DE9CFB750166F ON typo3_typo3cr_domain_model_workspace (rootnode)");
    }
}
