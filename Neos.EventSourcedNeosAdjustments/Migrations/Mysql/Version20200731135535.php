<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200731135535 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Read model tables for the DocumentUriPathProjector';
    }

    /**
     * @param Schema $schema
     * @throws AbortMigrationException | DBALException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE neos_neos_projection_document_uri (uripath varchar(4000) NOT NULL DEFAULT \'\', nodepath varchar(4000) NOT NULL DEFAULT \'\', disabled tinyint(4) unsigned NOT NULL DEFAULT 0, dimensionspacepointhash varchar(255) NOT NULL DEFAULT \'\', nodeaggregateidentifier varchar(255) NOT NULL DEFAULT \'\', originDimensionSpacePointHash varchar(255) NOT NULL DEFAULT \'\', UNIQUE KEY uripath (uripath,nodeaggregateidentifier,dimensionspacepointhash) USING HASH) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        $this->addSql('CREATE TABLE neos_neos_projection_document_uri_livecontentstreams (contentstreamidentifier varchar(255) NOT NULL DEFAULT \'\', workspacename varchar(255) NOT NULL DEFAULT \'\', PRIMARY KEY (contentstreamidentifier)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    }

    /**
     * @param Schema $schema
     * @throws AbortMigrationException | DBALException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE neos_neos_projection_document_uri');
        $this->addSql('DROP TABLE neos_neos_projection_document_uri_livecontentstreams');
    }
}
