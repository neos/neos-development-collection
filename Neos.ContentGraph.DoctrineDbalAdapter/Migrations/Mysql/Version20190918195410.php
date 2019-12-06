<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20190918195410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add debug view';
    }

    /**
     * @param Schema $schema
     * @throws DBALException
     * @throws AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('
        CREATE OR REPLACE VIEW neos_contentgraph_debug_nodeswithparents AS

        SELECT
            ws.workspacename as _workspace, 
            h.contentstreamidentifier, 
            c.nodeaggregateidentifier, 
            p.nodeaggregateidentifier AS _parentnodeaggregateidentifier, 
            c.nodetypename,
            c.properties,
            c.origindimensionspacepoint, 
            c.classification,
            h.name FROM neos_contentgraph_node p
         INNER JOIN neos_contentgraph_hierarchyrelation h
            ON h.parentnodeanchor = p.relationanchorpoint
         INNER JOIN neos_contentgraph_node c
            ON h.childnodeanchor = c.relationanchorpoint
         LEFT JOIN neos_contentrepository_projection_workspace_v1 ws
            ON h.contentstreamidentifier=ws.currentcontentstreamidentifier
        ');
    }

    /**
     * @param Schema $schema
     * @throws AbortMigrationException
     * @throws DBALException
     */
    public function down(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('DROP VIEW neos_contentgraph_debug_nodeswithparents');
    }
}
