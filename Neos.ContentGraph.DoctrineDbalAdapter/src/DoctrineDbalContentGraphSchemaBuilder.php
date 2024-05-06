<?php

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaFactory;

/**
 * @internal
 */
class DoctrineDbalContentGraphSchemaBuilder
{
    private const DEFAULT_TEXT_COLLATION = 'utf8mb4_unicode_520_ci';

    private readonly ContentGraphTableNames $contentGraphTableNames;

    public function __construct(
        string $tableNamePrefix
    ) {
        $this->contentGraphTableNames = ContentGraphTableNames::withPrefix($tableNamePrefix);
    }

    public function buildSchema(AbstractSchemaManager $schemaManager): Schema
    {
        return DbalSchemaFactory::createSchemaWithTables($schemaManager, [
            $this->createNodeTable(),
            $this->createHierarchyRelationTable(),
            $this->createReferenceRelationTable(),
            $this->createDimensionSpacePointsTable()
        ]);
    }

    private function createNodeTable(): Table
    {
        $table = new Table($this->contentGraphTableNames->node(), [
            DbalSchemaFactory::columnForNodeAnchorPoint('relationanchorpoint')->setAutoincrement(true),
            DbalSchemaFactory::columnForNodeAggregateId('nodeaggregateid')->setNotnull(false),
            DbalSchemaFactory::columnForDimensionSpacePointHash('origindimensionspacepointhash')->setNotnull(false),
            DbalSchemaFactory::columnForNodeTypeName('nodetypename'),
            (new Column('properties', Type::getType(Types::TEXT)))->setNotnull(true)->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION),
            (new Column('classification', Type::getType(Types::BINARY)))->setLength(20)->setNotnull(true),
            (new Column('created', Type::getType(Types::DATETIME_IMMUTABLE)))->setDefault('CURRENT_TIMESTAMP')->setNotnull(true),
            (new Column('originalcreated', Type::getType(Types::DATETIME_IMMUTABLE)))->setDefault('CURRENT_TIMESTAMP')->setNotnull(true),
            (new Column('lastmodified', Type::getType(Types::DATETIME_IMMUTABLE)))->setNotnull(false)->setDefault(null),
            (new Column('originallastmodified', Type::getType(Types::DATETIME_IMMUTABLE)))->setNotnull(false)->setDefault(null)
        ]);

        return $table
            ->setPrimaryKey(['relationanchorpoint'])
            ->addIndex(['nodeaggregateid'])
            ->addIndex(['nodetypename']);
    }

    private function createHierarchyRelationTable(): Table
    {
        $table = new Table($this->contentGraphTableNames->hierachyRelation(), [
            (new Column('name', Type::getType(Types::STRING)))->setLength(255)->setNotnull(false)->setCustomSchemaOption('charset', 'ascii')->setCustomSchemaOption('collation', 'ascii_general_ci'),
            (new Column('position', Type::getType(Types::INTEGER)))->setNotnull(true),
            DbalSchemaFactory::columnForContentStreamId('contentstreamid')->setNotnull(true),
            DbalSchemaFactory::columnForDimensionSpacePointHash('dimensionspacepointhash')->setNotnull(true),
            DbalSchemaFactory::columnForNodeAnchorPoint('parentnodeanchor'),
            DbalSchemaFactory::columnForNodeAnchorPoint('childnodeanchor'),
            (new Column('subtreetags', Type::getType(Types::JSON)))->setDefault('{}'),
        ]);

        return $table
            ->addIndex(['childnodeanchor'])
            ->addIndex(['contentstreamid'])
            ->addIndex(['parentnodeanchor'])
            ->addIndex(['contentstreamid', 'childnodeanchor', 'dimensionspacepointhash'])
            ->addIndex(['contentstreamid', 'dimensionspacepointhash']);
    }

    private function createDimensionSpacePointsTable(): Table
    {
        $table = new Table($this->contentGraphTableNames->dimensionSpacePoints(), [
            DbalSchemaFactory::columnForDimensionSpacePointHash('hash')->setNotnull(true),
            DbalSchemaFactory::columnForDimensionSpacePoint('dimensionspacepoint')->setNotnull(true)
        ]);

        return $table
            ->setPrimaryKey(['hash']);
    }

    private function createReferenceRelationTable(): Table
    {
        $table = new Table($this->contentGraphTableNames->referenceRelation(), [
            (new Column('name', Type::getType(Types::STRING)))->setLength(255)->setNotnull(true)->setCustomSchemaOption('charset', 'ascii')->setCustomSchemaOption('collation', 'ascii_general_ci'),
            (new Column('position', Type::getType(Types::INTEGER)))->setNotnull(true),
            DbalSchemaFactory::columnForNodeAnchorPoint('nodeanchorpoint'),
            (new Column('properties', Type::getType(Types::TEXT)))->setNotnull(false)->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION),
            DbalSchemaFactory::columnForNodeAggregateId('destinationnodeaggregateid')->setNotnull(true)
        ]);

        return $table
            ->setPrimaryKey(['name', 'position', 'nodeanchorpoint']);
    }
}
