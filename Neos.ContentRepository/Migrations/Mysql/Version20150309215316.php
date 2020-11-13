<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust DB schema to a clean state (remove cruft that built up in the past)
 */
class Version20150309215316 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $indexes = $this->sm->listTableIndexes('typo3_typo3cr_domain_model_nodedata');
        if (array_key_exists('idx_820cadc88d940019', $indexes)) {
            $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP FOREIGN KEY FK_60A956B98D940019");
            $this->addSql("DROP INDEX idx_820cadc88d940019 ON typo3_typo3cr_domain_model_nodedata");
            $this->addSql("CREATE INDEX IDX_60A956B98D940019 ON typo3_typo3cr_domain_model_nodedata (workspace)");
            $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD CONSTRAINT FK_60A956B98D940019 FOREIGN KEY (workspace) REFERENCES typo3_typo3cr_domain_model_workspace (name) ON DELETE SET NULL");
        }
        if (array_key_exists('idx_820cadc84930c33c', $indexes)) {
            $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP FOREIGN KEY typo3_typo3cr_domain_model_nodedata_ibfk_2");
            $this->addSql("DROP INDEX idx_820cadc84930c33c ON typo3_typo3cr_domain_model_nodedata");
            $this->addSql("CREATE INDEX IDX_60A956B94930C33C ON typo3_typo3cr_domain_model_nodedata (contentobjectproxy)");
            $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD CONSTRAINT typo3_typo3cr_domain_model_nodedata_ibfk_2 FOREIGN KEY (contentobjectproxy) REFERENCES typo3_typo3cr_domain_model_contentobjectproxy (persistence_object_identifier)");
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $indexes = $this->sm->listTableIndexes('typo3_typo3cr_domain_model_nodedata');
        if (array_key_exists('idx_60a956b98d940019', $indexes)) {
            $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP FOREIGN KEY FK_60A956B98D940019");
            $this->addSql("DROP INDEX idx_60a956b98d940019 ON typo3_typo3cr_domain_model_nodedata");
            $this->addSql("CREATE INDEX IDX_820CADC88D940019 ON typo3_typo3cr_domain_model_nodedata (workspace)");
            $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD CONSTRAINT FK_60A956B98D940019 FOREIGN KEY (workspace) REFERENCES typo3_typo3cr_domain_model_workspace (name) ON DELETE SET NULL");
        }
        if (array_key_exists('idx_60a956b94930c33c', $indexes)) {
            $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP FOREIGN KEY typo3_typo3cr_domain_model_nodedata_ibfk_2");
            $this->addSql("DROP INDEX idx_60a956b94930c33c ON typo3_typo3cr_domain_model_nodedata");
            $this->addSql("CREATE INDEX IDX_820CADC84930C33C ON typo3_typo3cr_domain_model_nodedata (contentobjectproxy)");
            $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD CONSTRAINT typo3_typo3cr_domain_model_nodedata_ibfk_2 FOREIGN KEY (contentobjectproxy) REFERENCES typo3_typo3cr_domain_model_contentobjectproxy (persistence_object_identifier)");
        }
    }
}
