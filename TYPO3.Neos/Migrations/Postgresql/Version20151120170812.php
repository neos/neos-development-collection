<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Change column type from json to jsonb on dimensionvalues and accessroles
 */
class Version20151120170812 extends AbstractMigration
{

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER dimensionvalues TYPE jsonb USING dimensionvalues::jsonb");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER dimensionvalues DROP DEFAULT");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER accessroles TYPE jsonb USING accessroles::jsonb");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER accessroles DROP DEFAULT");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER dimensionvalues TYPE JSON USING dimensionvalues::json");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER dimensionvalues DROP DEFAULT");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER accessroles TYPE JSON USING accessroles::json");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER accessroles DROP DEFAULT");
    }
}
