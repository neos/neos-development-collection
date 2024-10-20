<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

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
    private const DEFAULT_TEXT_COLLATION = 'utf8mb4_unicode_520_ci';

    public function __construct(
        private readonly ContentGraphTableNames $tableNames
    ) {
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     * @return Schema
     */
    public function buildSchema(AbstractSchemaManager $schemaManager): Schema
    {
        return DbalSchemaFactory::createSchemaWithTables($schemaManager, [
            $this->createNodeTable(),
            $this->createHierarchyRelationTable(),
            $this->createReferenceRelationTable(),
            $this->createDimensionSpacePointsTable(),
            $this->createWorkspaceTable(),
            $this->createContentStreamTable(),
        ]);
    }

    private function createNodeTable(): Table
    {
        $table = self::createTable($this->tableNames->node(), [
            DbalSchemaFactory::columnForNodeAnchorPoint('relationanchorpoint')->setAutoincrement(true),
            DbalSchemaFactory::columnForNodeAggregateId('nodeaggregateid')->setNotnull(false),
            DbalSchemaFactory::columnForDimensionSpacePointHash('origindimensionspacepointhash')->setNotnull(false),
            DbalSchemaFactory::columnForNodeTypeName('nodetypename'),
            (new Column('name', self::type(Types::STRING)))->setLength(255)->setNotnull(false)->setPlatformOption('charset', 'ascii')->setPlatformOption('collation', 'ascii_general_ci'),
            (new Column('properties', self::type(Types::TEXT)))->setNotnull(true)->setPlatformOption('collation', self::DEFAULT_TEXT_COLLATION),
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

    private function createHierarchyRelationTable(): Table
    {
        $table = self::createTable($this->tableNames->hierarchyRelation(), [
            (new Column('position', self::type(Types::INTEGER)))->setNotnull(true),
            DbalSchemaFactory::columnForContentStreamId('contentstreamid')->setNotnull(true),
            DbalSchemaFactory::columnForDimensionSpacePointHash('dimensionspacepointhash')->setNotnull(true),
            DbalSchemaFactory::columnForNodeAnchorPoint('parentnodeanchor'),
            DbalSchemaFactory::columnForNodeAnchorPoint('childnodeanchor'),
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

    private function createDimensionSpacePointsTable(): Table
    {
        $table = self::createTable($this->tableNames->dimensionSpacePoints(), [
            DbalSchemaFactory::columnForDimensionSpacePointHash('hash')->setNotnull(true),
            DbalSchemaFactory::columnForDimensionSpacePoint('dimensionspacepoint')->setNotnull(true)
        ]);

        return $table
            ->setPrimaryKey(['hash']);
    }

    private function createReferenceRelationTable(): Table
    {
        $table = self::createTable($this->tableNames->referenceRelation(), [
            (new Column('name', self::type(Types::STRING)))->setLength(255)->setNotnull(true)->setPlatformOption('charset', 'ascii')->setPlatformOption('collation', 'ascii_general_ci'),
            (new Column('position', self::type(Types::INTEGER)))->setNotnull(true),
            DbalSchemaFactory::columnForNodeAnchorPoint('nodeanchorpoint'),
            (new Column('properties', self::type(Types::TEXT)))->setNotnull(false)->setPlatformOption('collation', self::DEFAULT_TEXT_COLLATION),
            DbalSchemaFactory::columnForNodeAggregateId('destinationnodeaggregateid')->setNotnull(true)
        ]);

        return $table
            ->setPrimaryKey(['name', 'position', 'nodeanchorpoint']);
    }

    private function createWorkspaceTable(): Table
    {
        $workspaceTable = self::createTable($this->tableNames->workspace(), [
            DbalSchemaFactory::columnForWorkspaceName('name')->setNotnull(true),
            DbalSchemaFactory::columnForWorkspaceName('baseWorkspaceName')->setNotnull(false),
            DbalSchemaFactory::columnForContentStreamId('currentContentStreamId')->setNotNull(true),
            (new Column('status', self::type(Types::BINARY)))->setLength(20)->setNotnull(false),
        ]);

        $workspaceTable->addUniqueIndex(['currentContentStreamId']);

        return $workspaceTable->setPrimaryKey(['name']);
    }

    private function createContentStreamTable(): Table
    {
        $contentStreamTable = self::createTable($this->tableNames->contentStream(), [
            DbalSchemaFactory::columnForContentStreamId('id')->setNotnull(true),
            (new Column('version', Type::getType(Types::INTEGER)))->setNotnull(true),
            DbalSchemaFactory::columnForContentStreamId('sourceContentStreamId')->setNotnull(false),
            // Should become a DB ENUM (unclear how to configure with DBAL) or int (latter needs adaption to code)
            (new Column('status', Type::getType(Types::BINARY)))->setLength(20)->setNotnull(true),
            (new Column('removed', Type::getType(Types::BOOLEAN)))->setDefault(false)->setNotnull(false)
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
