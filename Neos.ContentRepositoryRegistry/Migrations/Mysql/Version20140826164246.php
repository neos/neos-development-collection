<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add movedTo reference to NodeData
 */
class Version20140826164246 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD movedto VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD CONSTRAINT FK_60A956B92D45FE4D FOREIGN KEY (movedto) REFERENCES typo3_typo3cr_domain_model_nodedata (persistence_object_identifier)");
        $this->addSql("CREATE INDEX IDX_60A956B92D45FE4D ON typo3_typo3cr_domain_model_nodedata (movedto)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP FOREIGN KEY FK_60A956B92D45FE4D");
        $this->addSql("DROP INDEX IDX_60A956B92D45FE4D ON typo3_typo3cr_domain_model_nodedata");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP movedto");
    }
}
