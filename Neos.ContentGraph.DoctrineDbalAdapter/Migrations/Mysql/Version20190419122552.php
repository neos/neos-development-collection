<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20190419122552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename restriction edge to restriction relation';
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     * @throws AbortMigrationException
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('RENAME TABLE neos_contentgraph_restrictionedge TO neos_contentgraph_restrictionrelation');
    }

    /**
     * @param Schema $schema
     * @throws AbortMigrationException
     * @throws DBALException
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('RENAME TABLE neos_contentgraph_restrictionrelation TO neos_contentgraph_restrictionedge');
    }
}
