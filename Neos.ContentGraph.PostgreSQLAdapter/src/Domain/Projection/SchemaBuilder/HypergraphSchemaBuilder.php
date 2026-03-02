<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\SchemaBuilder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\PostgresContentGraphProjection;

/**
 * Let's try to be as consistent as possible to the MariaDB/MySQL adapter.
 * It might help to recognize table names ppl. already know from other projects in case of debugging etc.
 *
 * @internal
 */
final readonly class HypergraphSchemaBuilder
{
    public function __construct(
        private ContentGraphTableNames $tableNames
    ) {
    }

    public function buildSchema(Connection $databaseConnection): Schema
    {

        self::registerTypes($databaseConnection);
        $schema = new Schema();

        $this->createNodeTable($schema);
        $this->createHierarchyRelationTable($schema);
        $this->createReferenceRelationTable($schema);
        $this->createContentStreamTable($schema);
        $this->createWorkspaceTable($schema);
        $this->createDimensionSpacePointsTable($schema);

        return $schema;
    }

    /**
     * Register custom Doctrine DBAL types required for the hypergraph schema.
     *
     * These types (JSONB, bigint arrays, etc.) are needed by Doctrine's schema
     * comparison so it can interpret PostgreSQL-specific column types. Registration
     * is idempotent and called early by {@see \Neos\ContentGraph\PostgreSQLAdapter\PostgresContentGraphProjectionFactory::build()}.
     */
    public static function registerTypes(Connection $databaseConnection): void
    {
        self::registerTypeIfNotPresent($databaseConnection, 'hypergraphjsonb', JsonbType::class);
        self::registerTypeIfNotPresent($databaseConnection, 'varchar64_array', Varchar64ArrayType::class);
        self::registerTypeIfNotPresent($databaseConnection, 'varchar36_array', Varchar36ArrayType::class);
        self::registerTypeIfNotPresent($databaseConnection, 'bigint_array', BigintArrayType::class);
    }

    private static function registerTypeIfNotPresent(
        Connection $databaseConnection,
        string $doctrineTypeName,
        string $typeClass
    ): void {
        $platform = $databaseConnection->getDatabasePlatform();
        if (!Type::hasType($doctrineTypeName)) {
            Type::addType($doctrineTypeName, $typeClass);
        }
        $type = Type::getType($doctrineTypeName);
        foreach ($type->getMappedDatabaseTypes($platform) as $dbType) {
            if (!$platform->hasDoctrineTypeMappingFor($dbType)) {
                $platform->registerDoctrineTypeMapping($dbType, $doctrineTypeName);
            }
        }
    }

    private function createNodeTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNames->node());
        $table->addColumn('relationanchorpoint', Types::BIGINT)
            ->setAutoincrement(true)
            ->setNotnull(true);
        $table->addColumn('nodeaggregateid', Types::STRING)
            ->setLength(64)
            ->setNotnull(true);
        $table->addColumn('origindimensionspacepoint', 'hypergraphjsonb')
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
            ->addIndex(['origindimensionspacepointhash'])
            ->addIndex(['nodeaggregateid'])
            /** NOTE: the GIN index on properties is added in {@see PostgresContentGraphProjection::setupTables()} */
            ->addIndex(['nodename']);
    }

    private function createHierarchyRelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNames->hierarchyRelation());
        $table->addColumn('contentstreamid', Types::STRING)
            ->setLength(40)
            ->setNotnull(true);
        $table->addColumn('parentnodeanchor', Types::BIGINT)
            ->setNotnull(true);
        $table->addColumn('dimensionspacepoint', 'hypergraphjsonb')
            ->setNotnull(true);
        $table->addColumn('dimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('childnodeanchors', 'bigint_array')
            ->setNotnull(true);
        // TODO remove this column?
        // $table->addColumn('parent_nodepath_absolute', Types::TEXT)
        //    ->setNotnull(true);
        $table->addColumn('subtreetags', 'hypergraphjsonb')
            ->setNotnull(false);
        $table
            ->setPrimaryKey(['contentstreamid', 'parentnodeanchor', 'dimensionspacepointhash'])
            ->addIndex(['contentstreamid'])
            ->addIndex(['parentnodeanchor'])
            /** NOTE: the GIN index on childnodeanchors is added in {@see PostgresContentGraphProjection::setupTables()} */
            ->addIndex(['dimensionspacepointhash']);
    }

    private function createReferenceRelationTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNames->referenceRelation());
        $table->addColumn('sourcenodeanchor', Types::BIGINT)
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
            ->setLength(64)
            ->setNotnull(true);

        $table
            ->setPrimaryKey(['sourcenodeanchor', 'name', 'position'])
            ->addIndex(['sourcenodeanchor'])
            ->addIndex(['targetnodeaggregateid']);
    }

    private function createWorkspaceTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNames->workspace());
        $table->addColumn('name', Types::STRING)
            ->setNotnull(true);
        $table->addColumn('baseworkspacename', Types::STRING)
            ->setNotnull(false);
        $table->addColumn('currentcontentstreamid', Types::STRING)
            ->setLength(40)
            ->setNotnull(true);
        $table
            ->setPrimaryKey(['name'])
            ->addUniqueIndex(['currentcontentstreamid']);
    }

    private function createContentStreamTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNames->contentStream());
        $table->addColumn('id', Types::STRING)
            ->setLength(40)
            ->setNotnull(true);
        $table->addColumn('version', Types::INTEGER)
            ->setNotnull(true);
        $table->addColumn('sourcecontentstreamid', Types::STRING)
            ->setLength(40)
            ->setNotnull(false);
        $table->addColumn('sourcecontentstreamversion', Types::INTEGER)
            ->setNotnull(false);
        $table->addColumn('isclosed', Types::BOOLEAN)
            ->setNotnull(true);
        $table->addColumn('haschanges', Types::BOOLEAN)
            ->setNotnull(true);

        $table
            ->setPrimaryKey(['id']);
    }

    private function createDimensionSpacePointsTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNames->dimensionSpacePoints());
        $table->addColumn('hash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('dimensionspacepoint', 'hypergraphjsonb')
            ->setNotnull(true);
        $table
            ->setPrimaryKey(['hash']);
    }
}
