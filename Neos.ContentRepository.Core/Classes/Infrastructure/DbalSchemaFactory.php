<?php
namespace Neos\ContentRepository\Core\Infrastructure;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * Provide doctrine DBAL column schema definitions for common types in the content repository to
 * produce consistent columns across projections.
 *
 * @internal Because we might need to check for platform later on and generally change the input and output format of functions within.
 */
final class DbalSchemaFactory
{
    /**
     * The NodeAggregateId is limited to 64 ascii characters and therefore we should do the same in the database.
     * @see NodeAggregateId
     */
    public static function addColumnForNodeAggregateId(Table $table, string $columnName, bool $notNull): Table
    {
        $table->addColumn($columnName, Types::STRING)
                ->setLength(64)
                ->setNotnull($notNull)
                ->setCustomSchemaOption('charset', 'ascii')
                ->setCustomSchemaOption('collation', 'ascii_general_ci');

        return $table;
    }

    /**
     * The ContentStreamId is generally a UUID, therefore not a real "string" but at the moment a strified identifier,
     * we can safely store it as binary string as the charset doesn't matter
     * for the characters we use (they are all asscii).
     *
     * We should however reduce the allowed size to 36 like suggested
     * here in the schema and as further improvement store the UUID in a more DB friendly format.
     *
     * @see ContentStreamId
     */
    public static function addColumnForContentStreamId(Table $table, string $columnName, bool $notNull): Table
    {
        $table->addColumn($columnName, Types::STRING)
            ->setLength(36)
            ->setNotnull($notNull)
            ->setCustomSchemaOption('charset', 'binary');

        return $table;
    }

    /**
     * An anchorpoint can be used in a given projection to link two nodes, it is a purely internal identifier and should
     * be as performant as possible in queries, the current code uses UUIDs,
     * so we will store the stringified UUID as binary for now.
     *
     * A simpler and faster format would be preferable though, int or a shorter binary format if possible. Fortunately
     * this is a pure projection information and therefore could change by replay.
     */
    public static function addColumnForNodeAnchorPoint(Table $table, string $columnName): Table
    {
        $table->addColumn($columnName, Types::BINARY)
            ->setLength(36)
            ->setNotnull(true);

        return $table;
    }

    /**
     * DimensionSpacePoints are PHP objects that need to be serialized as JSON, therefore we store them as TEXT for now,
     * with a sensible collation for case insensitive text search.
     *
     * Using a dedicated JSON column format should be considered for the future.
     *
     * @see DimensionSpacePoint
     */
    public static function addColumnForDimensionSpacePoint(Table $table, string $columnName, bool $notNull): Table
    {
        $table->addColumn($columnName, Types::TEXT)
            ->setNotnull($notNull)
            ->setDefault('{}')
            ->setCustomSchemaOption('collation', 'utf8mb4_unicode_520_ci');

        return $table;
    }

    /**
     * The hash for a given dimension space point for better query performance. As this is a hash, the size and type of
     * content is deterministic, a binary type can be used as the actual content is not so important.
     *
     * We could imrpove by actually storing the hash in binary form and shortening and fixing the length.
     *
     * @see DimensionSpacePoint
     */
    public static function addColumnForDimensionSpacePointHash(Table $table, string $columnName, bool $notNull): Table
    {
        $table->addColumn($columnName, Types::BINARY)
            ->setLength(32)
            ->setDefault('')
            ->setNotnull($notNull);

        return $table;
    }

    /**
     * The NodeTypeName is an ascii string, we should be able to sort it properly, but we don't need unicode here.
     *
     * @see NodeTypeName
     */
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
