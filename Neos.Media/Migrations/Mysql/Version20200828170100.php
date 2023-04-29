<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix paths to static icons still pointing to TYPO3.Media PNG icons
 */
class Version20200828170100 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Fix paths to static icons still pointing to TYPO3.Media PNG icons';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws DBALException
     * @throws AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql("UPDATE neos_media_domain_model_thumbnail SET staticresource = REPLACE(staticresource, 'resource://TYPO3.Media/Public/Icons/512px/', 'resource://Neos.Media/Public/IconSets/vivid/')");
        $this->addSql("UPDATE neos_media_domain_model_thumbnail SET staticresource = REPLACE(staticresource, '.png', '.svg')");
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws DBALException
     * @throws AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql("UPDATE neos_media_domain_model_thumbnail SET staticresource = REPLACE(staticresource, 'resource://Neos.Media/Public/IconSets/vivid/', 'resource://TYPO3.Media/Public/Icons/512px/')");
        $this->addSql("UPDATE neos_media_domain_model_thumbnail SET staticresource = REPLACE(staticresource, '.svg', '.png')");
    }
}
