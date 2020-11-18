<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add missing definitions for the json properties change
 */
class Version20150402234511 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER properties SET NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER dimensionvalues SET NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER accessroles SET NOT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER dimensionvalues DROP NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER properties DROP NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER accessroles DROP NOT NULL");
    }
}
