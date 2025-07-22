<?php

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\SchemaBuilder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\PostgresTypes\IntArrayType;
use Doctrine\DBAL\PostgresTypes\TextArrayType;
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

    public function buildSchema(): Schema
    {
        $schema = new Schema();

        $this->createNodeTable($schema);
        $this->createHierarchyRelationTable($schema);
        $this->createReferenceRelationTable($schema);
        // TODO implement subtreetags
        //$this->createRestrictionHyperrelationTable($schema);
        $this->createContentStreamTable($schema);
        $this->createWorkspaceTable($schema);
        $this->createDimensionSpacePointsTable($schema);

        return $schema;
    }

    public static function registerTypes(Connection $databaseConnection): void
    {
        self::registerTypeIfNotPresent($databaseConnection, 'hypergraphjsonb', JsonbType::class);
        self::registerTypeIfNotPresent($databaseConnection, 'hypergraphvarchars', VarcharArrayType::class);
        self::registerTypeIfNotPresent($databaseConnection, 'text_array', TextArrayType::class);
        self::registerTypeIfNotPresent($databaseConnection, 'int_array', IntArrayType::class);
        self::registerTypeIfNotPresent($databaseConnection, 'bigint_array', BigintArrayType::class);
        // do NOT RELY ON THESE TYPES BEING PRESENT - we only load them to build the schema.
        // TODO comment why we need type wrappers?
        // FIXME this is currently a bit messy, other packages seem also require the
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
        $table->addColumn('dimensionspacepoint', Types::JSON)
            ->setNotnull(true);
        $table->addColumn('dimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('childnodeanchors', 'bigint_array')
            ->setNotnull(true);
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

    private function createSubTreeTagsTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNames->subTreeTagsRelation());
        $table->addColumn('contentstreamid', Types::STRING)
            ->setLength(40)
            ->setNotnull(true);
        $table->addColumn('dimensionspacepointhash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('originnodeaggregateid', Types::STRING)
            ->setLength(64)
            ->setNotnull(true);
        $table->addColumn('affectednodeaggregateids', 'hypergraphvarchars')
            ->setNotnull(true);

        $table
            ->setPrimaryKey([
                'contentstreamid',
                'dimensionspacepointhash',
                'originnodeaggregateid'
            ])
            ->addIndex(['contentstreamid'])
            ->addIndex(['dimensionspacepointhash'])
            ->addIndex(['originnodeaggregateid']);
        /** NOTE: the GIN index on affectednodeaggregateids is added in {@see PostgresContentGraphProjection::setupTables()} */
    }

    private function createDimensionSpacePointsTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNames->dimensionSpacePoints());
        $table->addColumn('hash', Types::STRING)
            ->setLength(255)
            ->setNotnull(true);
        $table->addColumn('dimensionspacepoint', Types::JSON)
            ->setNotnull(true);
        $table
            ->setPrimaryKey(['hash']);
    }
}
