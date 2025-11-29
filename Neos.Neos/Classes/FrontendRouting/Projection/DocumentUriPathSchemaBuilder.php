<?php

declare(strict_types=1);

namespace Neos\Neos\FrontendRouting\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Dbal\DbalSchemaFactory;

/**
 * @internal implementation detail to manage document node uris. For resolving please use the NodeUriBuilder and for matching the Router.
 */
final class DocumentUriPathSchemaBuilder
{
    public function __construct(
        private readonly string $tableNamePrefix,
    ) {
    }

    /**
     * @param Connection $connection
     * @return Schema
     * @throws DBALException
     * @throws SchemaException
     */
    public function buildSchema(Connection $connection): Schema
    {
        return DbalSchemaFactory::createSchemaWithTables($connection, [
            $this->createUriTable($connection->getDatabasePlatform())
        ]);
    }

    private function createUriTable(AbstractPlatform $platform): Table
    {
        $table = new Table($this->tableNamePrefix . '_uri', [
            DbalSchemaFactory::columnForNodeAggregateId('nodeaggregateid', $platform)->setNotNull(true),
            DbalSchemaFactory::columnForGenericString('uripath', $platform)->setLength(4000)->setDefault('')->setNotnull(true),
            DbalSchemaFactory::columnForDimensionSpacePointHash('dimensionspacepointhash', $platform)->setNotNull(true),
            (new Column('disabled', Type::getType(Types::INTEGER)))->setLength(4)->setUnsigned(true)->setDefault(0)->setNotnull(true),
            (new Column('removed', Type::getType(Types::INTEGER)))->setLength(4)->setUnsigned(true)->setDefault(0)->setNotnull(true),
            DbalSchemaFactory::columnForGenericString('nodeaggregateidpath', $platform)->setLength(4000)->setDefault('')->setNotnull(true),
            DbalSchemaFactory::columnForGenericString('sitenodename', $platform)->setLength(255)->setDefault('')->setNotnull(true),
            DbalSchemaFactory::columnForDimensionSpacePointHash('origindimensionspacepointhash', $platform)->setNotNull(true),
            DbalSchemaFactory::columnForNodeAggregateId('parentnodeaggregateid', $platform)->setNotNull(false),
            DbalSchemaFactory::columnForNodeAggregateId('precedingnodeaggregateid', $platform)->setNotNull(false),
            DbalSchemaFactory::columnForNodeAggregateId('succeedingnodeaggregateid', $platform)->setNotNull(false),
            DbalSchemaFactory::columnForGenericString('shortcuttarget', $platform)->setLength(1000)->setNotnull(false),
            DbalSchemaFactory::columnForNodeTypeName('nodetypename', $platform),
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
}
