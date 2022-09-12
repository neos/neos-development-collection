<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Set the movedto column to NULL if a node was removed, we need this to have consistent updates when moving nodes
 */
class Version20141022111921 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP CONSTRAINT FK_60A956B92D45FE4D");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD CONSTRAINT FK_60A956B92D45FE4D FOREIGN KEY (movedto) REFERENCES typo3_typo3cr_domain_model_nodedata (persistence_object_identifier) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP CONSTRAINT fk_60a956b92d45fe4d");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD CONSTRAINT fk_60a956b92d45fe4d FOREIGN KEY (movedto) REFERENCES typo3_typo3cr_domain_model_nodedata (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
    }
}
