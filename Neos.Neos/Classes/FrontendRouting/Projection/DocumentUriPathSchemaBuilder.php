<?php

namespace Neos\Neos\FrontendRouting\Projection;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaFactory;

class DocumentUriPathSchemaBuilder
{
    private const DEFAULT_TEXT_COLLATION = 'utf8mb4_unicode_520_ci';

    public function __construct(
        private readonly string $tableNamePrefix,
    ) {
    }

    public function buildSchema(AbstractSchemaManager $schemaManager): Schema
    {
        $schema = DbalSchemaFactory::createEmptySchema($schemaManager);

        $this->createUriTable($schema);
        $this->createLiveContentStreamsTable($schema);

        return $schema;
    }

    private function createUriTable(Schema $schema): void
    {

        $table = $schema->createTable($this->tableNamePrefix . '_uri');
        $table = DbalSchemaFactory::addColumnForNodeAggregateId($table, 'nodeaggregateid', true);

        $table->addColumn('uripath', Types::STRING)
            ->setLength(4000)
            ->setDefault('')
            ->setNotnull(true)
            ->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION);
        $table = DbalSchemaFactory::addColumnForDimensionSpacePointHash($table, 'dimensionspacepointhash', true);
        $table->addColumn('disabled', Types::INTEGER)
            ->setLength(4)
            ->setUnsigned(true)
            ->setDefault(0)
            ->setNotnull(true);
        $table->addColumn('nodeaggregateidpath', Types::STRING)
            ->setLength(4000)
            ->setDefault('')
            ->setNotnull(true)
            ->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION);
        $table->addColumn('sitenodename', Types::STRING)
            ->setLength(255)
            ->setDefault('')
            ->setNotnull(true)
            ->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION);
        $table = DbalSchemaFactory::addColumnForDimensionSpacePointHash($table, 'origindimensionspacepointhash', true);
        $table = DbalSchemaFactory::addColumnForNodeAggregateId($table, 'parentnodeaggregateid', false);
        $table = DbalSchemaFactory::addColumnForNodeAggregateId($table, 'precedingnodeaggregateid', false);
        $table = DbalSchemaFactory::addColumnForNodeAggregateId($table, 'succeedingnodeaggregateid', false);
        $table->addColumn('shortcuttarget', Types::STRING)
            ->setLength(1000)
            ->setNotnull(false)
            ->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION);
        $table = DbalSchemaFactory::addColumnForNodeTypeName($table, 'nodetypename');

        $table
            ->addUniqueIndex(['nodeaggregateid', 'dimensionspacepointhash'], 'variant')
            ->addIndex([
                'parentnodeaggregateid',
                'precedingnodeaggregateid',
                'succeedingnodeaggregateid'
            ], 'preceding_succeeding')
            ->addIndex(['sitenodename', 'uripath'], null, [], ['lengths' => [null,100]]);
    }

    private function createLiveContentStreamsTable(Schema $schema): void
    {
        $table = $schema->createTable($this->tableNamePrefix . '_livecontentstreams');
        $table = DbalSchemaFactory::addColumnForContentStreamId($table, 'contentstreamid', true);
        $table->addColumn('workspacename', Types::STRING)
            ->setLength(255)
            ->setDefault('')
            ->setNotnull(true);
        $table->setPrimaryKey(['contentstreamid']);
    }
}
