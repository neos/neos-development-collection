<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust table names to the renaming of TYPO3.TYPO3CR to Neos.ContentRepository.
 */
class Version20161125093820 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('RENAME TABLE typo3_typo3cr_domain_model_contentobjectproxy TO neos_contentrepository_domain_model_contentobjectproxy');
        $this->addSql('RENAME TABLE typo3_typo3cr_domain_model_nodedata TO neos_contentrepository_domain_model_nodedata');
        $this->addSql('RENAME TABLE typo3_typo3cr_domain_model_nodedimension TO neos_contentrepository_domain_model_nodedimension');
        $this->addSql('RENAME TABLE typo3_typo3cr_domain_model_workspace TO neos_contentrepository_domain_model_workspace');
        $this->addSql('RENAME TABLE typo3_typo3cr_migration_domain_model_migrationstatus TO neos_contentrepository_migration_domain_model_migrationstatus');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('RENAME TABLE neos_contentrepository_domain_model_contentobjectproxy TO typo3_typo3cr_domain_model_contentobjectproxy');
        $this->addSql('RENAME TABLE neos_contentrepository_domain_model_nodedata TO typo3_typo3cr_domain_model_nodedata');
        $this->addSql('RENAME TABLE neos_contentrepository_domain_model_nodedimension TO typo3_typo3cr_domain_model_nodedimension');
        $this->addSql('RENAME TABLE neos_contentrepository_domain_model_workspace TO typo3_typo3cr_domain_model_workspace');
        $this->addSql('RENAME TABLE neos_contentrepository_migration_domain_model_migrationstatus TO typo3_typo3cr_migration_domain_model_migrationstatus');
    }
}
