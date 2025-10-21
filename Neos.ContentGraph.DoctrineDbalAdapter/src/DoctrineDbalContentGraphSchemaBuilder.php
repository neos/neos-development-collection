<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
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
            (new Column('position', self::type(Types::INTEGER)))->setNotnull(true),
            DbalSchemaFactory::columnForContentStreamId('contentstreamid', $platform)->setNotnull(true),
            DbalSchemaFactory::columnForDimensionSpacePointHash('dimensionspacepointhash', $platform)->setNotnull(true),
            DbalSchemaFactory::columnForNodeAnchorPoint('parentnodeanchor', $platform),
            DbalSchemaFactory::columnForNodeAnchorPoint('childnodeanchor', $platform),
            (new Column('subtreetags', self::type(Types::JSON))),
        ]);

        return $table
            ->addIndex(['childnodeanchor'])
            ->addIndex(['contentstreamid'])
            ->addIndex(['parentnodeanchor'])
            ->addIndex(['childnodeanchor', 'contentstreamid', 'dimensionspacepointhash', 'position'])
            ->addIndex(['parentnodeanchor', 'contentstreamid', 'dimensionspacepointhash', 'position'])
            ->addIndex(['contentstreamid', 'dimensionspacepointhash']);
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
            ->setPrimaryKey(['name', 'position', 'nodeanchorpoint']);
    }

    private function createWorkspaceTable(AbstractPlatform $platform): Table
    {
        $workspaceTable = self::createTable($this->tableNames->workspace(), [
            DbalSchemaFactory::columnForWorkspaceName('name', $platform)->setNotnull(true),
            DbalSchemaFactory::columnForWorkspaceName('baseWorkspaceName', $platform)->setNotnull(false),
            DbalSchemaFactory::columnForContentStreamId('currentContentStreamId', $platform)->setNotNull(true),
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

        return $contentStreamTable->setPrimaryKey(['id']);
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
