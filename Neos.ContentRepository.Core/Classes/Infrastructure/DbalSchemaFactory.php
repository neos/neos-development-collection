<?php
namespace Neos\ContentRepository\Core\Infrastructure;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

/**
 * Provide doctrine DBAL column schema definitions for common types in the content repository to
 * produce consistent columns across projections.
 *
 * @internal Because we might need to check for platform later on and generally change the input and output format of functions within.
 */
final class DbalSchemaFactory
{
    public static function addColumnForNodeAggregateId(Table $table, string $columnName, bool $notNull): Table
    {
        $table->addColumn($columnName, Types::STRING)
                ->setLength(64)
                ->setNotnull($notNull)
                ->setCustomSchemaOption('charset', 'ascii')
                ->setCustomSchemaOption('collation', 'ascii_general_ci');

        return $table;
    }

    public static function addColumnForContentStreamId(Table $table, string $columnName, bool $notNull): Table
    {
        $table->addColumn($columnName, Types::STRING)
            ->setLength(36)
            ->setNotnull($notNull)
            ->setCustomSchemaOption('charset', 'binary');

        return $table;
    }

    public static function addColumnForNodeAnchorPoint(Table $table, string $columnName): Table
    {
        $table->addColumn($columnName, Types::BINARY)
            ->setLength(36)
            ->setNotnull(true);

        return $table;
    }

    public static function addColumnForDimensionSpacePoint(Table $table, string $columnName, bool $notNull): Table
    {
        $table->addColumn($columnName, Types::TEXT)
            ->setNotnull($notNull)
            ->setDefault('{}')
            ->setCustomSchemaOption('collation', 'utf8mb4_unicode_520_ci');

        return $table;
    }

    public static function addColumnForDimensionSpacePointHash(Table $table, string $columnName, bool $notNull): Table
    {
        $table->addColumn($columnName, Types::BINARY)
            ->setLength(32)
            ->setDefault('')
            ->setNotnull($notNull);

        return $table;
    }

    public static function addColumnForNodeTypeName(Table $table, string $columnName): Table
    {
        $table->addColumn($columnName, Types::STRING)
            ->setLength(255)
            ->setNotnull(true)
            ->setCustomSchemaOption('charset', 'ascii')
            ->setCustomSchemaOption('collation', 'ascii_general_ci');

        return $table;
    }

    public static function createEmptySchema(AbstractSchemaManager $schemaManager): Schema
    {
        $schemaConfig = $schemaManager->createSchemaConfig();
        $schemaConfig->setDefaultTableOptions([
            'charset' => 'utf8mb4'
        ]);

        return new Schema([], [], $schemaConfig);
    }
}
