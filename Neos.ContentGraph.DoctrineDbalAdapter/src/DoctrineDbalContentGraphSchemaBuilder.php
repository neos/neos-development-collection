<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaFactory;

/**
 * @internal
 */
class DoctrineDbalContentGraphSchemaBuilder
{
    private const DEFAULT_TEXT_COLLATION = 'utf8mb4_unicode_520_ci';

    public function __construct(
        private readonly string $tableNamePrefix
    ) {
    }

    public function buildSchema(AbstractSchemaManager $schemaManager): Schema
    {
        $schema = DbalSchemaFactory::createEmptySchema($schemaManager);

        $this->createNodeTable($schema);
        $this->createHierarchyRelationTable($schema);
        $this->createReferenceRelationTable($schema);
        $this->createRestrictionRelationTable($schema);

        return $schema;
    }

    private function createNodeTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_node');
        $table = DbalSchemaFactory::addColumnForNodeAnchorPoint($table, 'relationanchorpoint');
        $table = DbalSchemaFactory::addColumnForNodeAggregateId($table, 'nodeaggregateid', false);
        $table = DbalSchemaFactory::addColumnForDimensionSpacePoint($table, 'origindimensionspacepoint', false);
        $table = DbalSchemaFactory::addColumnForDimensionSpacePointHash($table, 'origindimensionspacepointhash', false);
        $table = DbalSchemaFactory::addColumnForNodeTypeName($table, 'nodetypename');
        $table->addColumn('properties', Types::TEXT)
            ->setNotnull(true)
            ->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION);
        $table->addColumn('classification', Types::STRING)
            ->setLength(20)
            ->setNotnull(true)
            ->setCustomSchemaOption('charset', 'binary');
        $table->addColumn('created', Types::DATETIME_IMMUTABLE)
            ->setDefault('CURRENT_TIMESTAMP')
            ->setNotnull(true);
        $table->addColumn('originalcreated', Types::DATETIME_IMMUTABLE)
            ->setDefault('CURRENT_TIMESTAMP')
            ->setNotnull(true);
        $table->addColumn('lastmodified', Types::DATETIME_IMMUTABLE)
            ->setNotnull(false)
            ->setDefault(null);
        $table->addColumn('originallastmodified', Types::DATETIME_IMMUTABLE)
            ->setNotnull(false)
            ->setDefault(null);
        $table
            ->setPrimaryKey(['relationanchorpoint'])
            ->addIndex(['nodeaggregateid'])
            ->addIndex(['nodetypename']);
    }

    private function createHierarchyRelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_hierarchyrelation');
        $table->addColumn('name', Types::STRING)
            ->setLength(255)
            ->setNotnull(false)
            ->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION);
        $table->addColumn('position', Types::INTEGER)
            ->setNotnull(true);
        $table = DbalSchemaFactory::addColumnForContentStreamId($table, 'contentstreamid', true);
        $table = DbalSchemaFactory::addColumnForDimensionSpacePoint($table, 'dimensionspacepoint', true);
        $table = DbalSchemaFactory::addColumnForDimensionSpacePointHash($table, 'dimensionspacepointhash', true);
        $table = DbalSchemaFactory::addColumnForNodeAnchorPoint($table, 'parentnodeanchor');
        $table = DbalSchemaFactory::addColumnForNodeAnchorPoint($table, 'childnodeanchor');
        $table
            ->addIndex(['childnodeanchor'])
            ->addIndex(['contentstreamid'])
            ->addIndex(['parentnodeanchor'])
            ->addIndex(['contentstreamid', 'childnodeanchor', 'dimensionspacepointhash'])
            ->addIndex(['contentstreamid', 'dimensionspacepointhash']);
    }

    private function createReferenceRelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_referencerelation');
        $table->addColumn('name', Types::STRING)
            ->setLength(255)
            ->setNotnull(true)
            ->setCustomSchemaOption('charset', 'ascii')
            ->setCustomSchemaOption('collation', 'ascii_general_ci');
        $table->addColumn('position', Types::INTEGER)
            ->setNotnull(true);
        $table = DbalSchemaFactory::addColumnForNodeAnchorPoint($table, 'nodeanchorpoint');
        $table->addColumn('properties', Types::TEXT)
            ->setNotnull(false)
            ->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION);
        $table = DbalSchemaFactory::addColumnForNodeAggregateId($table, 'destinationnodeaggregateid', true);

        $table
            ->setPrimaryKey(['name', 'position', 'nodeanchorpoint']);
    }

    private function createRestrictionRelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_restrictionrelation');
        $table = DbalSchemaFactory::addColumnForContentStreamId($table, 'contentstreamid', true);
        $table = DbalSchemaFactory::addColumnForDimensionSpacePointHash($table, 'dimensionspacepointhash', true);
        $table = DbalSchemaFactory::addColumnForNodeAggregateId($table, 'originnodeaggregateid', true);
        $table = DbalSchemaFactory::addColumnForNodeAggregateId($table, 'affectednodeaggregateid', true);

        $table
            ->setPrimaryKey([
                'contentstreamid',
                'dimensionspacepointhash',
                'originnodeaggregateid',
                'affectednodeaggregateid'
            ]);
    }
}
