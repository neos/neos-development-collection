<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Dbal\DbalSchemaFactory;

/**
 * @internal
 */
class DoctrineDbalContentGraphSchemaBuilder
{
    public function __construct(
        private readonly ContentGraphTableNames $tableNames
    ) {
    }

    public function buildSchema(Connection $connection): Schema
    {
        return DbalSchemaFactory::createSchemaWithTables($connection, [
            $this->createNodeTable($connection->getDatabasePlatform()),
            $this->createHierarchyRelationTable($connection->getDatabasePlatform()),
            $this->createReferenceRelationTable($connection->getDatabasePlatform()),
            $this->createDimensionSpacePointsTable($connection->getDatabasePlatform()),
            $this->createWorkspaceTable($connection->getDatabasePlatform()),
            $this->createContentStreamTable($connection->getDatabasePlatform()),
            $this->createContentStreamLayerTable($connection->getDatabasePlatform()),
        ]);
    }

    private function createNodeTable(AbstractPlatform $platform): Table
    {
        $table = self::createTable($this->tableNames->node(), [
            DbalSchemaFactory::columnForNodeAnchorPoint('relationanchorpoint', $platform)->setAutoincrement(true),
            DbalSchemaFactory::columnForNodeAggregateId('nodeaggregateid', $platform)->setNotnull(false),
            DbalSchemaFactory::columnForDimensionSpacePointHash('origindimensionspacepointhash', $platform)->setNotnull(false),
            DbalSchemaFactory::columnForNodeTypeName('nodetypename', $platform),
            (new Column('name', self::type(Types::STRING)))->setLength(255)->setNotnull(false),
            DbalSchemaFactory::columnForProperties('properties', $platform)->setNotnull(true),
            (new Column('classification', self::type(Types::BINARY)))->setLength(20)->setNotnull(true),
            (new Column('created', self::type(Types::DATETIME_IMMUTABLE)))->setDefault('CURRENT_TIMESTAMP')->setNotnull(true),
            (new Column('originalcreated', self::type(Types::DATETIME_IMMUTABLE)))->setDefault('CURRENT_TIMESTAMP')->setNotnull(true),
            (new Column('lastmodified', self::type(Types::DATETIME_IMMUTABLE)))->setNotnull(false)->setDefault(null),
            (new Column('originallastmodified', self::type(Types::DATETIME_IMMUTABLE)))->setNotnull(false)->setDefault(null)
        ]);

        return $table
            ->setPrimaryKey(['relationanchorpoint'])
            ->addIndex(['nodeaggregateid'])
            ->addIndex(['nodetypename']);
    }

    private function createHierarchyRelationTable(AbstractPlatform $platform): Table
    {
        $table = self::createTable($this->tableNames->hierarchyRelation(), [
            /** No auto-increment see {@see \Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjection::determineNextHierarchyRelationId()} */
            (new Column('id', Type::getType(Types::INTEGER)))->setNotnull(true),
            (new Column('contentstreamlayer', self::type(Types::INTEGER)))->setNotnull(true),
            (new Column('position', self::type(Types::INTEGER)))->setNotnull(false),
            DbalSchemaFactory::columnForDimensionSpacePointHash('dimensionspacepointhash', $platform)->setNotnull(false),
            DbalSchemaFactory::columnForNodeAnchorPoint('parentnodeanchor', $platform)->setNotnull(false),
            DbalSchemaFactory::columnForNodeAnchorPoint('childnodeanchor', $platform)->setNotnull(false),
            (new Column('subtreetags', self::type(Types::JSON)))->setNotnull(false),
        ]);

        return $table
            ->addIndex(['id'])
            ->addUniqueIndex(['id', 'contentstreamlayer'], 'UNIQ_id_layer')
            ->addIndex(['childnodeanchor'])
            ->addIndex(['contentstreamlayer'])
            ->addIndex(['parentnodeanchor'])
            ->addIndex(['position'])
            /** Optimize the $rightmostSucceedingSiblingRelationStatement in {@see \Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph::determineHierarchyRelationPosition()} */
            ->addIndex(['parentnodeanchor', 'position'])
            ->addIndex(['childnodeanchor', 'contentstreamlayer', 'dimensionspacepointhash', 'position'])
            ->addIndex(['parentnodeanchor', 'contentstreamlayer', 'dimensionspacepointhash', 'position'])
            ->addIndex(['contentstreamlayer', 'dimensionspacepointhash']);
    }

    private function createDimensionSpacePointsTable(AbstractPlatform $platform): Table
    {
        $table = self::createTable($this->tableNames->dimensionSpacePoints(), [
            DbalSchemaFactory::columnForDimensionSpacePointHash('hash', $platform)->setNotnull(true),
            DbalSchemaFactory::columnForDimensionSpacePoint('dimensionspacepoint', $platform)->setNotnull(true)
        ]);

        return $table
            ->setPrimaryKey(['hash']);
    }

    private function createReferenceRelationTable(AbstractPlatform $platform): Table
    {
        $table = self::createTable($this->tableNames->referenceRelation(), [
            (new Column('name', self::type(Types::STRING)))->setLength(255)->setNotnull(true),
            (new Column('position', self::type(Types::INTEGER)))->setNotnull(true),
            DbalSchemaFactory::columnForNodeAnchorPoint('nodeanchorpoint', $platform),
            DbalSchemaFactory::columnForProperties('properties', $platform)->setNotnull(false),
            DbalSchemaFactory::columnForNodeAggregateId('destinationnodeaggregateid', $platform)->setNotnull(true)
        ]);

        return $table
            ->setPrimaryKey(['name', 'position', 'nodeanchorpoint'])
            ->addIndex(['nodeanchorpoint', 'destinationnodeaggregateid', 'position'], 'referenceresolution');
    }

    private function createWorkspaceTable(AbstractPlatform $platform): Table
    {
        $workspaceTable = self::createTable($this->tableNames->workspace(), [
            DbalSchemaFactory::columnForWorkspaceName('name', $platform)->setNotnull(true),
            DbalSchemaFactory::columnForWorkspaceName('baseWorkspaceName', $platform)->setNotnull(false),
            DbalSchemaFactory::columnForContentStreamId('currentContentStreamId', $platform)->setNotNull(true),
            (new Column('version', Type::getType(Types::INTEGER)))->setNotnull(true),
        ]);

        $workspaceTable->addUniqueIndex(['currentContentStreamId']);

        return $workspaceTable->setPrimaryKey(['name']);
    }

    private function createContentStreamTable(AbstractPlatform $platform): Table
    {
        $contentStreamTable = self::createTable($this->tableNames->contentStream(), [
            DbalSchemaFactory::columnForContentStreamId('id', $platform)->setNotnull(true),
            (new Column('version', Type::getType(Types::INTEGER)))->setNotnull(true),
            DbalSchemaFactory::columnForContentStreamId('sourceContentStreamId', $platform)->setNotnull(false),
            (new Column('sourceContentStreamVersion', Type::getType(Types::INTEGER)))->setNotnull(false),
            (new Column('closed', Type::getType(Types::BOOLEAN)))->setNotnull(true),
            (new Column('hasChanges', Type::getType(Types::BOOLEAN)))->setNotnull(true),
        ]);

        return $contentStreamTable
            ->setPrimaryKey(['id']);
    }

    private function createContentStreamLayerTable(AbstractPlatform $platform): Table
    {
        $contentStreamLayerTable = self::createTable($this->tableNames->contentStreamLayer(), [
            DbalSchemaFactory::columnForContentStreamId('contentStreamId', $platform)->setNotnull(true),
            (new Column('contentStreamLayer', Type::getType(Types::INTEGER)))->setAutoincrement(true)->setNotnull(true),
        ]);

        return $contentStreamLayerTable
            ->addIndex(['contentStreamLayer'])
            ->setPrimaryKey(['contentStreamId', 'contentStreamLayer']);
    }

    /**
     * @param array<Column> $columns
     */
    private static function createTable(string $tableName, array $columns): Table
    {
        try {
            return new Table($tableName, $columns);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to create table "%s": %s', $tableName, $e->getMessage()), 1716490913, $e);
        }
    }

    private static function type(string $type): Type
    {
        try {
            return Type::getType($type);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to create database type "%s": %s', $type, $e->getMessage()), 1716491053, $e);
        }
    }
}
