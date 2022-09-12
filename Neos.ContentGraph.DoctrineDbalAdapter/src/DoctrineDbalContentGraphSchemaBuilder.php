<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * @internal
 */
class DoctrineDbalContentGraphSchemaBuilder
{
    public function __construct(
        private readonly string $tableNamePrefix,
    ) {
    }

    public function buildSchema(): Schema
    {
        $schema = new Schema();

        $this->createNodeTable($schema);
        $this->createHierarchyRelationTable($schema);
        $this->createReferenceRelationTable($schema);
        $this->createRestrictionRelationTable($schema);

        return $schema;
    }

    private function createNodeTable(Schema $schema): void
    {

        $table = $schema->createTable($this->tableNamePrefix . '_node');
        $table->addColumn('relationanchorpoint', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('nodeaggregateid', Types::STRING)
            ->setLength(255)
            ->setNotnull(false);
        $table->addColumn('origindimensionspacepoint', Types::TEXT)
            ->setNotnull(false);
        $table->addColumn('origindimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(false);
        $table->addColumn('nodetypename', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('properties', Types::TEXT) // TODO longtext?
        ->setNotnull(true);
        $table->addColumn('classification', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table
            ->setPrimaryKey(['relationanchorpoint'])
            ->addIndex(['nodeaggregateid'], 'NODE_AGGREGATE_ID')
            ->addIndex(['nodetypename'], 'NODE_TYPE_NAME');
    }

    private function createHierarchyRelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_hierarchyrelation');
        $table->addColumn('name', Types::STRING)
            ->setLength(255)
            ->setNotnull(false);
        $table->addColumn('position', Types::INTEGER)
            ->setNotnull(true);
        $table->addColumn('contentstreamid', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('dimensionspacepoint', Types::TEXT)
            ->setNotnull(true);
        $table->addColumn('dimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('parentnodeanchor', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('childnodeanchor', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table
            ->addIndex(['childnodeanchor'], 'CHILDNODEANCHOR')
            ->addIndex(['contentstreamid'], 'CONTENTSTREAMID')
            ->addIndex(['parentnodeanchor'], 'PARENTNODEANCHOR')
            ->addIndex(['contentstreamid', 'dimensionspacepointhash'], 'SUBGRAPH_ID');
    }

    private function createReferenceRelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_referencerelation');
        $table->addColumn('name', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('position', Types::INTEGER)
            ->setNotnull(true);
        $table->addColumn('nodeanchorpoint', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('properties', Types::TEXT)
            ->setNotnull(false);
        $table->addColumn('destinationnodeaggregateid', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table
            ->setPrimaryKey(['name', 'position', 'nodeanchorpoint']);
    }

    private function createRestrictionRelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_restrictionrelation');
        $table->addColumn('contentstreamid', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('dimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('originnodeaggregateid', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('affectednodeaggregateid', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table
            ->setPrimaryKey([
                'contentstreamid',
                'dimensionspacepointhash',
                'originnodeaggregateid',
                'affectednodeaggregateid'
            ]);
    }
}
