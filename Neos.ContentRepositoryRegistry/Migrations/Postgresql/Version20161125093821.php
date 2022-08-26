<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust table names to the renaming of TYPO3.TYPO3CR to Neos.ContentRepository.
 */
class Version20161125093821 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE typo3_typo3cr_domain_model_contentobjectproxy RENAME TO neos_contentrepository_domain_model_contentobjectproxy');
        $this->addSql('ALTER TABLE typo3_typo3cr_domain_model_nodedata RENAME TO neos_contentrepository_domain_model_nodedata');
        $this->addSql('ALTER TABLE typo3_typo3cr_domain_model_nodedimension RENAME TO neos_contentrepository_domain_model_nodedimension');
        $this->addSql('ALTER TABLE typo3_typo3cr_domain_model_workspace RENAME TO neos_contentrepository_domain_model_workspace');
        $this->addSql('ALTER TABLE typo3_typo3cr_migration_domain_model_migrationstatus RENAME TO neos_contentrepository_migration_domain_model_migrationstatus');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_contentobjectproxy RENAME TO typo3_typo3cr_domain_model_contentobjectproxy');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata RENAME TO typo3_typo3cr_domain_model_nodedata');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedimension RENAME TO typo3_typo3cr_domain_model_nodedimension');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace RENAME TO typo3_typo3cr_domain_model_workspace');
        $this->addSql('ALTER TABLE neos_contentrepository_migration_domain_model_migrationstatus RENAME TO typo3_typo3cr_migration_domain_model_migrationstatus');
    }
}
