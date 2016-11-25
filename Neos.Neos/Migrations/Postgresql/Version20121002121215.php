<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Neos\Flow\Persistence\Doctrine\Service;

/**
 * Adjust flow3 to flow
 */
class Version20121002121215 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

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
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain RENAME COLUMN flow3_persistence_identifier TO persistence_object_identifier");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_site RENAME COLUMN flow3_persistence_identifier TO persistence_object_identifier");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user RENAME COLUMN flow3_persistence_identifier TO persistence_object_identifier");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_userpreferences RENAME COLUMN flow3_persistence_identifier TO persistence_object_identifier");

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
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

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
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_site RENAME COLUMN persistence_object_identifier TO flow3_persistence_identifier");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain RENAME COLUMN persistence_object_identifier TO flow3_persistence_identifier");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_userpreferences RENAME COLUMN persistence_object_identifier TO flow3_persistence_identifier");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user RENAME COLUMN persistence_object_identifier TO flow3_persistence_identifier");

            // add back FK constraints
        foreach ($foreignKeyHandlingSql['add'] as $sql) {
            $this->addSql($sql);
        }
    }
}
