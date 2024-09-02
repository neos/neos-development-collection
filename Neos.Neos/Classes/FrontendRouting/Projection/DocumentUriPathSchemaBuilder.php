<?php

namespace Neos\Neos\FrontendRouting\Projection;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaFactory;

class DocumentUriPathSchemaBuilder
{
    private const DEFAULT_TEXT_COLLATION = 'utf8mb4_unicode_520_ci';

    public function __construct(
        private readonly string $tableNamePrefix,
    ) {
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     * @return Schema
     * @throws DBALException
     * @throws SchemaException
     */
    public function buildSchema(AbstractSchemaManager $schemaManager): Schema
    {
        $schema = DbalSchemaFactory::createSchemaWithTables($schemaManager, [
            $this->createUriTable(),
            $this->createLiveContentStreamsTable()
        ]);

        return $schema;
    }

    private function createUriTable(): Table
    {
        $table = new Table($this->tableNamePrefix . '_uri', [
            DbalSchemaFactory::columnForNodeAggregateId('nodeaggregateid')->setNotNull(true),
            (new Column('uripath', Type::getType(Types::STRING)))->setLength(4000)->setDefault('')->setNotnull(true)->setPlatformOption('collation', self::DEFAULT_TEXT_COLLATION),
            DbalSchemaFactory::columnForDimensionSpacePointHash('dimensionspacepointhash')->setNotNull(true),
            (new Column('disabled', Type::getType(Types::INTEGER)))->setLength(4)->setUnsigned(true)->setDefault(0)->setNotnull(true),
            (new Column('nodeaggregateidpath', Type::getType(Types::STRING)))->setLength(4000)->setDefault('')->setNotnull(true)->setPlatformOption('collation', self::DEFAULT_TEXT_COLLATION),
            (new Column('sitenodename', Type::getType(Types::STRING)))->setLength(255)->setDefault('')->setNotnull(true)->setPlatformOption('collation', self::DEFAULT_TEXT_COLLATION),
            DbalSchemaFactory::columnForDimensionSpacePointHash('origindimensionspacepointhash')->setNotNull(true),
            DbalSchemaFactory::columnForNodeAggregateId('parentnodeaggregateid')->setNotNull(false),
            DbalSchemaFactory::columnForNodeAggregateId('precedingnodeaggregateid')->setNotNull(false),
            DbalSchemaFactory::columnForNodeAggregateId('succeedingnodeaggregateid')->setNotNull(false),
            (new Column('shortcuttarget', Type::getType(Types::STRING)))->setLength(1000)->setNotnull(false)->setPlatformOption('collation', self::DEFAULT_TEXT_COLLATION),
            DbalSchemaFactory::columnForNodeTypeName('nodetypename'),
            (new Column('isplaceholder', Type::getType(Types::INTEGER)))->setLength(4)->setUnsigned(true)->setDefault(0)->setNotnull(true),
        ]);

        return $table
            ->addUniqueIndex(['nodeaggregateid', 'dimensionspacepointhash'], 'variant')
            ->addIndex([
                'parentnodeaggregateid',
                'precedingnodeaggregateid',
                'succeedingnodeaggregateid'
            ], 'preceding_succeeding')
            ->addIndex(['sitenodename', 'uripath'], null, [], ['lengths' => [null, 100]]);
    }

    private function createLiveContentStreamsTable(): Table
    {
        $table = new Table($this->tableNamePrefix . '_livecontentstreams', [
            DbalSchemaFactory::columnForContentStreamId('contentstreamid')->setNotNull(true),
            DbalSchemaFactory::columnForWorkspaceName('workspacename')->setDefault('')
        ]);
        return $table->setPrimaryKey(['contentstreamid']);
    }
}
