<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add movedTo reference to NodeData
 */
class Version20140826164247 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD movedto VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD CONSTRAINT FK_60A956B92D45FE4D FOREIGN KEY (movedto) REFERENCES typo3_typo3cr_domain_model_nodedata (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("CREATE INDEX IDX_60A956B92D45FE4D ON typo3_typo3cr_domain_model_nodedata (movedto)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP movedto");
    }
}
