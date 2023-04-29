<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Neos\Flow\Persistence\Doctrine\Service;

/**
 * Adjust flow3 to flow
 */
class Version20121001181137 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

            // collect foreign keys pointing to "our" tables
        $foreignKeyHandlingSql = Service::getForeignKeyHandlingSql($schema, $this->platform, array('typo3_media_domain_model_image'), 'flow3_persistence_identifier', 'persistence_object_identifier');

            // drop FK constraints
        foreach ($foreignKeyHandlingSql['drop'] as $sql) {
            $this->addSql($sql);
        }

            // rename identifier fields
        $this->addSql("ALTER TABLE typo3_media_domain_model_image DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image CHANGE flow3_persistence_identifier persistence_object_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image ADD PRIMARY KEY (persistence_object_identifier)");

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
        $foreignKeyHandlingSql = Service::getForeignKeyHandlingSql($schema, $this->platform, array('typo3_media_domain_model_image'), 'persistence_object_identifier', 'flow3_persistence_identifier');

            // drop FK constraints
        foreach ($foreignKeyHandlingSql['drop'] as $sql) {
            $this->addSql($sql);
        }

            // rename identifier fields
        $this->addSql("ALTER TABLE typo3_media_domain_model_image DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image CHANGE persistence_object_identifier flow3_persistence_identifier VARCHAR(40) NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image ADD PRIMARY KEY (flow3_persistence_identifier)");

            // add back FK constraints
        foreach ($foreignKeyHandlingSql['add'] as $sql) {
            $this->addSql($sql);
        }
    }
}
