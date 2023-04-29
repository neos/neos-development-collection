<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration to remove persistence_object_identifier from workspace model. The workspace name is used as identifier.
 */
class Version20131203110242 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP CONSTRAINT fk_820cadc88d940019");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP CONSTRAINT FK_71DE9CFBE9BFE681");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP CONSTRAINT typo3_typo3cr_domain_model_workspace_pkey");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER workspace TYPE VARCHAR(255)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ALTER baseworkspace TYPE VARCHAR(255)");

        $this->addSql("UPDATE typo3_typo3cr_domain_model_workspace w SET baseworkspace = b.name FROM typo3_typo3cr_domain_model_workspace b WHERE b.persistence_object_identifier = w.baseworkspace");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata n SET workspace = w.name FROM typo3_typo3cr_domain_model_workspace w WHERE w.persistence_object_identifier = n.workspace");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP persistence_object_identifier");

        $this->addSql("DROP INDEX flow3_identity_typo3_typo3cr_domain_model_workspace");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD PRIMARY KEY (name)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD CONSTRAINT FK_60A956B98D940019 FOREIGN KEY (workspace) REFERENCES typo3_typo3cr_domain_model_workspace (name) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBE9BFE681 FOREIGN KEY (baseworkspace) REFERENCES typo3_typo3cr_domain_model_workspace (name) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

    /**
     * Generate UUIDs in PHP if the uuid-ossp extension is not installed.
     *
     * @param Schema $schema
     * @return void
     */
    public function preDown(Schema $schema): void 
    {
        $result = $this->connection->executeQuery("SELECT installed_version FROM pg_available_extensions WHERE name = 'uuid-ossp'");
        if ($result->fetchColumn() === null) {
            $this->connection->executeUpdate("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD persistence_object_identifier VARCHAR(40)");
            $result = $this->connection->executeQuery('SELECT name FROM typo3_typo3cr_domain_model_workspace');
            foreach ($result->fetchAll() as $workspace) {
                $this->connection->update('typo3_typo3cr_domain_model_workspace', array('persistence_object_identifier' => \Neos\Flow\Utility\Algorithms::generateUUID()), array('name' => $workspace['name']));
            }
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP CONSTRAINT FK_60A956B98D940019");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP CONSTRAINT FK_71DE9CFBE9BFE681");

        $result = $this->connection->executeQuery("SELECT installed_version FROM pg_available_extensions WHERE name = 'uuid-ossp'");
        if ($result->fetchColumn() !== null) {
            $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD persistence_object_identifier VARCHAR(40)");
            $this->addSql("UPDATE typo3_typo3cr_domain_model_workspace SET persistence_object_identifier = uuid_generate_v4()");
        }
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ALTER persistence_object_identifier SET NOT NULL");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_workspace w SET baseworkspace = b.persistence_object_identifier FROM typo3_typo3cr_domain_model_workspace b WHERE b.name = w.baseworkspace");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata n SET workspace = w.persistence_object_identifier FROM typo3_typo3cr_domain_model_workspace w WHERE w.name = n.workspace");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER workspace TYPE VARCHAR(40)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ALTER baseworkspace TYPE VARCHAR(40)");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP CONSTRAINT typo3_typo3cr_domain_model_workspace_pkey");
        $this->addSql("CREATE UNIQUE INDEX flow3_identity_typo3_typo3cr_domain_model_workspace ON typo3_typo3cr_domain_model_workspace (name)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD PRIMARY KEY (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD CONSTRAINT fk_820cadc88d940019 FOREIGN KEY (workspace) REFERENCES typo3_typo3cr_domain_model_workspace (persistence_object_identifier) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT fk_71de9cfbe9bfe681 FOREIGN KEY (baseworkspace) REFERENCES typo3_typo3cr_domain_model_workspace (persistence_object_identifier) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
    }
}
