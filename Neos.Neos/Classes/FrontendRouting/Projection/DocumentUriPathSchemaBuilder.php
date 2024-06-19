<?php

namespace Neos\Neos\FrontendRouting\Projection;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
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

    public function buildSchema(AbstractSchemaManager $schemaManager): Schema
    {
        $schema = DbalSchemaFactory::createSchemaWithTables($schemaManager, [
            $this->createUriTable()
        ]);

        return $schema;
    }

    private function createUriTable(): Table
    {
        $table = new Table($this->tableNamePrefix . '_uri', [
            DbalSchemaFactory::columnForNodeAggregateId('nodeaggregateid')->setNotNull(true),
            (new Column('uripath', Type::getType(Types::STRING)))->setLength(4000)->setDefault('')->setNotnull(true)->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION),
            DbalSchemaFactory::columnForDimensionSpacePointHash('dimensionspacepointhash')->setNotNull(true),
            (new Column('disabled', Type::getType(Types::INTEGER)))->setLength(4)->setUnsigned(true)->setDefault(0)->setNotnull(true),
            (new Column('nodeaggregateidpath', Type::getType(Types::STRING)))->setLength(4000)->setDefault('')->setNotnull(true)->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION),
            (new Column('sitenodename', Type::getType(Types::STRING)))->setLength(255)->setDefault('')->setNotnull(true)->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION),
            DbalSchemaFactory::columnForDimensionSpacePointHash('origindimensionspacepointhash')->setNotNull(true),
            DbalSchemaFactory::columnForNodeAggregateId('parentnodeaggregateid')->setNotNull(false),
            DbalSchemaFactory::columnForNodeAggregateId('precedingnodeaggregateid')->setNotNull(false),
            DbalSchemaFactory::columnForNodeAggregateId('succeedingnodeaggregateid')->setNotNull(false),
            (new Column('shortcuttarget', Type::getType(Types::STRING)))->setLength(1000)->setNotnull(false)->setCustomSchemaOption('collation', self::DEFAULT_TEXT_COLLATION),
            DbalSchemaFactory::columnForNodeTypeName('nodetypename')
        ]);

        return $table
            ->addUniqueIndex(['nodeaggregateid', 'dimensionspacepointhash'], 'variant')
            ->addIndex([
                'parentnodeaggregateid',
                'precedingnodeaggregateid',
                'succeedingnodeaggregateid'
            ], 'preceding_succeeding')
            ->addIndex(['sitenodename', 'uripath'], null, [], ['lengths' => [null,100]]);
    }
}
