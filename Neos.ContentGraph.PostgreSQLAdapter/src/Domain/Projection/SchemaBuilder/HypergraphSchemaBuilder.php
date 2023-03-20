<?php

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\SchemaBuilder;

use Doctrine\DBAL\Platforms\AbstractPlatform;
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
        $schema = new Schema();

        $this->createNodeTable($schema);
        $this->createHierarchyHyperrelationTable($schema);
        $this->createReferenceRelationTable($schema);
        $this->createRestrictionHyperrelationTable($schema);

        return $schema;
    }

    public static function registerTypes(AbstractPlatform $platform): void
    {
        // do NOT RELY ON THESE TYPES BEING PRESENT - we only load them to build the schema.
        if (!Type::hasType('hypergraphjsonb')) {
            Type::addType('hypergraphjsonb', JsonbType::class);
            Type::addType('hypergraphuuid', UuidType::class);
            $platform->registerDoctrineTypeMapping('_uuid', 'hypergraphuuid');
            Type::addType('hypergraphuuids', UuidArrayType::class);
            Type::addType('hypergraphvarchars', VarcharArrayType::class);
        }
    }

    private function createNodeTable(Schema $schema): void
    {

        $table = $schema->createTable($this->tableNamePrefix . '_node');
        $table->addColumn('relationanchorpoint', 'hypergraphuuid')
            ->setNotnull(true);
        $table->addColumn('nodeaggregateid', Types::STRING)
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
        $table->addColumn('properties', 'hypergraphjsonb')
            ->setNotnull(true);
        $table->addColumn('classification', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('nodename', Types::STRING)
            ->setLength(255);

        $table
            ->setPrimaryKey(['relationanchorpoint'])
            ->addIndex(['origindimensionspacepointhash'], 'node_origin')
            ->addIndex(['nodeaggregateid'], 'node_aggregate_identifier')
            /** NOTE: the GIN index on properties is added in {@see HypergraphProjection::setupTables()} */
            ->addIndex(['nodename'], 'node_name');
    }

    private function createHierarchyHyperrelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_hierarchyhyperrelation');
        $table->addColumn('contentstreamid', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('parentnodeanchor', 'hypergraphuuid')
            ->setNotnull(true);
        $table->addColumn('dimensionspacepoint', Types::JSON)
            ->setNotnull(true);
        $table->addColumn('dimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('childnodeanchors', 'hypergraphuuids')
            ->setNotnull(true);
        $table
            ->setPrimaryKey(['contentstreamid', 'parentnodeanchor', 'dimensionspacepointhash'])
            ->addIndex(['contentstreamid'], 'hierarchy_content_stream_identifier')
            ->addIndex(['parentnodeanchor'], 'hierarchy_parent')
            /** NOTE: the GIN index on childnodeanchors is added in {@see HypergraphProjection::setupTables()} */
            ->addIndex(['dimensionspacepointhash'], 'hierarchy_dimension_space_point');
    }

    private function createReferenceRelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_referencerelation');
        $table->addColumn('sourcenodeanchor', 'hypergraphuuid')
            ->setNotnull(true);
        $table->addColumn('name', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('position', Types::INTEGER)
            // TODO: SMALLINT?
            ->setNotnull(true);
        $table->addColumn('properties', 'hypergraphjsonb')
            ->setNotnull(false);
        $table->addColumn('targetnodeaggregateid', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table
            ->setPrimaryKey(['sourcenodeanchor', 'name', 'position'])
            ->addIndex(['sourcenodeanchor'], 'reference_source')
            ->addIndex(['targetnodeaggregateid'], 'reference_target');
    }

    private function createRestrictionHyperrelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_restrictionhyperrelation');
        $table->addColumn('contentstreamid', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('dimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('originnodeaggregateid', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('affectednodeaggregateids', 'hypergraphvarchars')
            ->setNotnull(true);

        $table
            ->setPrimaryKey([
                'contentstreamid',
                'dimensionspacepointhash',
                'originnodeaggregateid'
            ])
            ->addIndex(['contentstreamid'], 'restriction_content_stream_identifier')
            ->addIndex(['dimensionspacepointhash'], 'restriction_dimension_space_point')
            ->addIndex(['originnodeaggregateid'], 'restriction_origin');
            /** NOTE: the GIN index on affectednodeaggregateids is added in {@see HypergraphProjection::setupTables()} */
    }
}
