<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add optimistic locking support (version field), parentpath, rename sorting_index
 * to sortingindex and remove the depth field.
 */
class Version20110928114048 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD version INT DEFAULT 1");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD parentpath VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node CHANGE sorting_index sortingindex INT DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP depth");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP version");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP parentpath");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node CHANGE sortingindex sorting_index VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD depth INT DEFAULT NULL");
    }
}
