<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add ON DELETE CASCADE so that node dimension data is removed with node data.
 */
class Version20141105155717 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedimension DROP CONSTRAINT FK_6C144D3693BDC8E2");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedimension ADD CONSTRAINT FK_6C144D3693BDC8E2 FOREIGN KEY (nodedata) REFERENCES typo3_typo3cr_domain_model_nodedata (persistence_object_identifier) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedimension DROP CONSTRAINT fk_6c144d3693bdc8e2");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedimension ADD CONSTRAINT fk_6c144d3693bdc8e2 FOREIGN KEY (nodedata) REFERENCES typo3_typo3cr_domain_model_nodedata (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
    }
}
