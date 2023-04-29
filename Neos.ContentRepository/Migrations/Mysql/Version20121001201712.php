<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Neos\Flow\Persistence\Doctrine\Service;

/**
 * Adjust flow3 to flow
 */
class Version20121001201712 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

            // collect foreign keys pointing to "our" tables
        $tableNames = array(
            'typo3_typo3cr_migration_domain_model_migrationstatus',
            'typo3_typo3cr_domain_model_contentobjectproxy',
            'typo3_typo3cr_domain_model_node',
            'typo3_typo3cr_domain_model_workspace'
        );
        $foreignKeyHandlingSql = Service::getForeignKeyHandlingSql($schema, $this->platform, $tableNames, 'flow3_persistence_identifier', 'persistence_object_identifier');

            // drop FK constraints
        foreach ($foreignKeyHandlingSql['drop'] as $sql) {
            $this->addSql($sql);
        }

            // rename identifier fields
        $this->addSql("ALTER TABLE typo3_typo3cr_migration_domain_model_migrationstatus DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3cr_migration_domain_model_migrationstatus CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_migration_domain_model_migrationstatus ADD PRIMARY KEY (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_contentobjectproxy DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_contentobjectproxy CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_contentobjectproxy ADD PRIMARY KEY (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD PRIMARY KEY (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD PRIMARY KEY (persistence_object_identifier)");

            // add back FK constraints
        foreach ($foreignKeyHandlingSql['add'] as $sql) {
            $this->addSql($sql);
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

            // collect foreign keys pointing to "our" tables
        $tableNames = array(
            'typo3_typo3cr_migration_domain_model_migrationstatus',
            'typo3_typo3cr_domain_model_contentobjectproxy',
            'typo3_typo3cr_domain_model_node',
            'typo3_typo3cr_domain_model_workspace'
        );
        $foreignKeyHandlingSql = Service::getForeignKeyHandlingSql($schema, $this->platform, $tableNames, 'persistence_object_identifier', 'flow3_persistence_identifier');

            // drop FK constraints
        foreach ($foreignKeyHandlingSql['drop'] as $sql) {
            $this->addSql($sql);
        }

            // rename identifier fields
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_contentobjectproxy DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_contentobjectproxy CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_contentobjectproxy ADD PRIMARY KEY (flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD PRIMARY KEY (flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD PRIMARY KEY (flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_migration_domain_model_migrationstatus DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3cr_migration_domain_model_migrationstatus CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_migration_domain_model_migrationstatus ADD PRIMARY KEY (flow3_persistence_identifier)");

            // add back FK constraints
        foreach ($foreignKeyHandlingSql['add'] as $sql) {
            $this->addSql($sql);
        }
    }
}
