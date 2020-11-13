<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust default values to NOT NULL unless allowed in model.
 */
class Version20120329220344 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_image CHANGE title title VARCHAR(255) NOT NULL, CHANGE width width INT NOT NULL, CHANGE height height INT NOT NULL, CHANGE type type INT NOT NULL, CHANGE imagevariants imagevariants LONGTEXT NOT NULL COMMENT '(DC2Type:array)'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_image CHANGE title title VARCHAR(255) DEFAULT NULL, CHANGE width width INT DEFAULT NULL, CHANGE height height INT DEFAULT NULL, CHANGE type type INT DEFAULT NULL, CHANGE imagevariants imagevariants LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'");
    }
}
