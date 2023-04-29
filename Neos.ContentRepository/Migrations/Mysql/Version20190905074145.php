<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create index on path for nodedata table
 */
class Version20190905074145 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Create index on path for nodedata table';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE INDEX path ON neos_contentrepository_domain_model_nodedata (path(255))');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP INDEX path ON neos_contentrepository_domain_model_nodedata');
    }
}
