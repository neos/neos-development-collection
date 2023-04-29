<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Neos\Flow\Utility\Now;

/**
 * Add creation date, last modification date and last publication date to node data table
 */
class Version20150131174806 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $now = new Now();
        $default = $now->format($this->platform->getDateTimeFormatString());
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD creationdatetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT '" . $default . "'");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER COLUMN creationdatetime DROP DEFAULT");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD lastmodificationdatetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT '" . $default . "'");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER COLUMN lastmodificationdatetime DROP DEFAULT");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD lastpublicationdatetime TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET lastpublicationdatetime = '" . $default . "' WHERE workspace = 'live'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP creationdatetime");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP lastmodificationdatetime");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP lastpublicationdatetime");
    }
}
