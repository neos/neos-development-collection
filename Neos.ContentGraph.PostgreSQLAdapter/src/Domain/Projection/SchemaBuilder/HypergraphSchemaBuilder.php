<?php

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\SchemaBuilder;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HypergraphProjection;

/**
 * @internal
 */
class HypergraphSchemaBuilder
{
    public function __construct(
        private readonly string $tableNamePrefix,
    ) {
    }

    public function buildSchema(): Schema
    {
        self::registerTypes();
        $schema = new Schema();

        $this->createNodeTable($schema);
        $this->createHierarchyHyperrelationTable($schema);
        $this->createReferenceRelationTable($schema);
        $this->createRestrictionHyperrelationTable($schema);

        return $schema;
    }

    private static function registerTypes(): void
    {
        // do NOT RELY ON THESE TYPES BEING PRESENT - we only load them to build the schema.
        Type::addType('hypergraph_jsonb', JsonbType::class);
        Type::addType('hypergraph_uuid', UuidType::class);
        Type::addType('hypergraph_uuid_array', UuidArrayType::class);
        Type::addType('hypergraph_varchararray', VarcharArrayType::class);
    }

    private function createNodeTable(Schema $schema): void
    {

        $table = $schema->createTable($this->tableNamePrefix . '_node');
        $table->addColumn('relationanchorpoint', 'hypergraph_uuid')
            ->setNotnull(true);
        $table->addColumn('nodeaggregateidentifier', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('origindimensionspacepoint', Types::JSON)
            ->setNotnull(true);
        $table->addColumn('origindimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('nodetypename', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('properties', 'hypergraph_jsonb')
            ->setNotnull(true);
        $table->addColumn('classification', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('nodename', Types::STRING)
            ->setLength(255);

        $table
            ->setPrimaryKey(['relationanchorpoint'])
            ->addIndex(['origindimensionspacepointhash'], 'node_origin')
            ->addIndex(['nodeaggregateidentifier'], 'node_aggregate_identifier')
            /** NOTE: the GIN index on properties is added in {@see HypergraphProjection::setupTables()} */
            ->addIndex(['nodename'], 'node_name');
    }

    private function createHierarchyHyperrelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_hierarchyhyperrelation');
        $table->addColumn('contentstreamidentifier', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('parentnodeanchor', 'hypergraph_uuid')
            ->setNotnull(true);
        $table->addColumn('dimensionspacepoint', Types::JSON)
            ->setNotnull(true);
        $table->addColumn('dimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('childnodeanchors', 'hypergraph_uuids')
            ->setNotnull(true);
        $table
            ->setPrimaryKey(['contentstreamidentifier', 'parentnodeanchor', 'dimensionspacepointhash'])
            ->addIndex(['contentstreamidentifier'], 'hierarchy_content_stream_identifier')
            ->addIndex(['parentnodeanchor'], 'hierarchy_parent')
            /** NOTE: the GIN index on childnodeanchors is added in {@see HypergraphProjection::setupTables()} */
            ->addIndex(['dimensionspacepointhash'], 'hierarchy_dimension_space_point');
    }

    private function createReferenceRelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_referencerelation');
        $table->addColumn('sourcenodeanchor', 'hypergraph_uuid')
            ->setNotnull(true);
        $table->addColumn('name', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('position', Types::INTEGER)
            // TODO: SMALLINT?
            ->setNotnull(true);
        $table->addColumn('properties', 'hypergraph_jsonb')
            ->setNotnull(true);
        $table->addColumn('targetnodeaggregateidentifier', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table
            ->setPrimaryKey(['sourcenodeanchor', 'name', 'position'])
            ->addIndex(['sourcenodeanchor'], 'reference_source')
            ->addIndex(['targetnodeaggregateidentifier'], 'reference_target');
    }

    private function createRestrictionHyperrelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_restrictionhyperrelation');
        $table->addColumn('contentstreamidentifier', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('dimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('originnodeaggregateidentifier', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('affectednodeaggregateidentifiers', 'hypergraph_varchararray')
            ->setNotnull(true);

        $table
            ->setPrimaryKey([
                'contentstreamidentifier',
                'dimensionspacepointhash',
                'originnodeaggregateidentifier'
            ])
            ->addIndex(['contentstreamidentifier'], 'restriction_content_stream_identifier')
            ->addIndex(['dimensionspacepointhash'], 'restriction_dimension_space_point')
            ->addIndex(['originnodeaggregateidentifier'], 'restriction_origin');
            /** NOTE: the GIN index on affectednodeaggregateidentifiers is added in {@see HypergraphProjection::setupTables()} */
    }
}
