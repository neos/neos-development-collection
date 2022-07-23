<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\Migrations\Exception\AbortMigration;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20220721154646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add properties to reference relations';
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     * @throws AbortMigration
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('ALTER TABLE neos_contentgraph_referencerelation ADD properties LONGTEXT NULL');

    }

    /**
     * @throws AbortMigration
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('ALTER TABLE neos_contentgraph_referencerelation DROP properties');
    }
}
