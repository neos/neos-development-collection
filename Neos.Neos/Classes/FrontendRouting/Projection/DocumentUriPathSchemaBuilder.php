<?php

namespace Neos\Neos\FrontendRouting\Projection;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class DocumentUriPathSchemaBuilder
{
    public function __construct(
        private readonly string $tableNamePrefix,
    ) {
    }

    public function buildSchema(): Schema
    {
        $schema = new Schema();

        $this->createUriTable($schema);
        $this->createLiveContentStreamsTable($schema);

        return $schema;
    }

    private function createUriTable(Schema $schema): void
    {

        $table = $schema->createTable($this->tableNamePrefix . '_uri');
        $table->addColumn('nodeaggregateid', Types::STRING)
            ->setLength(255)
            ->setDefault('')
            ->setNotnull(true);
        $table->addColumn('uripath', Types::STRING)
            ->setLength(4000)
            ->setDefault('')
            ->setNotnull(true);
        $table->addColumn('dimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setDefault('')
            ->setNotnull(true);
        $table->addColumn('disabled', Types::INTEGER)
            ->setLength(4)
            ->setUnsigned(true)
            ->setDefault(0)
            ->setNotnull(true);
        $table->addColumn('nodeaggregateidpath', Types::STRING)
            ->setLength(4000)
            ->setDefault('')
            ->setNotnull(true);
        $table->addColumn('sitenodename', Types::STRING)
            ->setLength(255)
            ->setDefault('')
            ->setNotnull(true);
        $table->addColumn('origindimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setDefault('')
            ->setNotnull(true);
        $table->addColumn('parentnodeaggregateid', Types::STRING)
            ->setLength(255)
            ->setNotnull(false);
        $table->addColumn('precedingnodeaggregateid', Types::STRING)
            ->setLength(255)
            ->setNotnull(false);
        $table->addColumn('succeedingnodeaggregateid', Types::STRING)
            ->setLength(255)
            ->setNotnull(false);
        $table->addColumn('shortcuttarget', Types::STRING)
            ->setLength(1000)
            ->setNotnull(false);

        $table
            ->addUniqueIndex(['nodeaggregateid', 'dimensionspacepointhash'], 'variant')
            ->addIndex([
                'parentnodeaggregateid',
                'precedingnodeaggregateid',
                'succeedingnodeaggregateid'
            ], 'preceding_succeeding')
            ->addIndex(['sitenodename', 'uripath'], 'sitenode_uripath', [], ['lengths' => [null,100]]);
    }

    private function createLiveContentStreamsTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_livecontentstreams');
        $table->addColumn('contentstreamid', Types::STRING)
            ->setLength(255)
            ->setDefault('')
            ->setNotnull(true);
        $table->addColumn('workspacename', Types::STRING)
            ->setLength(255)
            ->setDefault('')
            ->setNotnull(true);
        $table->setPrimaryKey(['contentstreamid']);
    }
}
