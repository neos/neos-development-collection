<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Drop media image tables of TYPO3 package, no longer used
 * as the Media package is now used.
 */
class Version20110925123119 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP TABLE typo3_typo3_domain_model_media_image");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE typo3_typo3_domain_model_media_image (flow3_persistence_identifier VARCHAR(40) NOT NULL, resource VARCHAR(40) DEFAULT NULL, UNIQUE INDEX UNIQ_E5EA82E2BC91F416 (resource), PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image ADD CONSTRAINT typo3_typo3_domain_model_media_image_ibfk_1 FOREIGN KEY (resource) REFERENCES typo3_flow3_resource_resource(flow3_persistence_identifier)");
    }
}
