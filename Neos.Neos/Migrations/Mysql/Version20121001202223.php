<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Neos\Flow\Persistence\Doctrine\Service;

/**
 * Adjust flow3 to flow
 */
class Version20121001202223 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

            // collect foreign keys pointing to "our" tables
        $tableNames = array(
            'typo3_typo3_domain_model_domain',
            'typo3_typo3_domain_model_site',
            'typo3_typo3_domain_model_user',
            'typo3_typo3_domain_model_userpreferences'
        );
        $foreignKeyHandlingSql = Service::getForeignKeyHandlingSql($schema, $this->platform, $tableNames, 'flow3_persistence_identifier', 'persistence_object_identifier');

            // drop FK constraints
        foreach ($foreignKeyHandlingSql['drop'] as $sql) {
            $this->addSql($sql);
        }

            // rename identifier fields
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain ADD PRIMARY KEY (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_site DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_site CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_site ADD PRIMARY KEY (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user ADD PRIMARY KEY (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_userpreferences DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_userpreferences CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_userpreferences ADD PRIMARY KEY (persistence_object_identifier)");

            // add back FK constraints
        foreach ($foreignKeyHandlingSql['add'] as $sql) {
            $this->addSql($sql);
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

            // collect foreign keys pointing to "our" tables
        $tableNames = array(
            'typo3_typo3_domain_model_domain',
            'typo3_typo3_domain_model_site',
            'typo3_typo3_domain_model_user',
            'typo3_typo3_domain_model_userpreferences'
        );
        $foreignKeyHandlingSql = Service::getForeignKeyHandlingSql($schema, $this->platform, $tableNames, 'persistence_object_identifier', 'flow3_persistence_identifier');

            // drop FK constraints
        foreach ($foreignKeyHandlingSql['drop'] as $sql) {
            $this->addSql($sql);
        }

            // rename identifier fields
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain ADD PRIMARY KEY (flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_site DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_site CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_site ADD PRIMARY KEY (flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user ADD PRIMARY KEY (flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_userpreferences DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_userpreferences CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_userpreferences ADD PRIMARY KEY (flow3_persistence_identifier)");

            // add back FK constraints
        foreach ($foreignKeyHandlingSql['add'] as $sql) {
            $this->addSql($sql);
        }
    }
}
