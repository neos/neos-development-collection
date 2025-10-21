<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Provide doctrine DBAL column schema definitions for common types in the content repository to
 * produce consistent columns across projections.
 *
 * @internal Because platform checks are limited for now and the input and output format of functions might change.
 */
final class DbalSchemaFactory
{
    public const DEFAULT_MYSQL_COLLATION = 'utf8mb4_unicode_520_ci';

    // This class only contains static members and should not be constructed
    private function __construct()
    {
    }

    /**
     * The NodeAggregateId is limited to 64 ascii characters and therefore we should do the same in the database.
     *
     * @see NodeAggregateId
     */
    public static function columnForNodeAggregateId(string $columnName, AbstractPlatform $platform): Column
    {
        $column = (new Column($columnName, Type::getType(Types::STRING)))
            ->setLength(64);

        if ($platform instanceof AbstractMySQLPlatform) {
            $column = $column
                ->setPlatformOption('charset', 'ascii')
                ->setPlatformOption('collation', 'ascii_general_ci');
        }

        return $column;
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
    public static function columnForContentStreamId(string $columnName, AbstractPlatform $platform): Column
    {
        return (new Column($columnName, Type::getType(Types::BINARY)))
            ->setLength(36);
    }

    /**
     * An anchorpoint can be used in a given projection to link two nodes, it is a purely internal identifier and should
     * be as performant as possible in queries, the current code uses UUIDs,
     * so we will store the stringified UUID as binary for now.
     *
     * A simpler and faster format would be preferable though, int or a shorter binary format if possible. Fortunately
     * this is a pure projection information and therefore could change by replay.
     */
    public static function columnForNodeAnchorPoint(string $columnName, AbstractPlatform $platform): Column
    {
        return (new Column($columnName, Type::getType(Types::BIGINT)))
            ->setNotnull(true);
    }

    /**
     * DimensionSpacePoints are PHP objects that need to be serialized as JSON, therefore we store them as TEXT for now,
     * with a sensible collation for case insensitive text search.
     *
     * Using a dedicated JSON column format should be considered for the future.
     *
     * @see DimensionSpacePoint
     */
    public static function columnForDimensionSpacePoint(string $columnName, AbstractPlatform $platform): Column
    {
        return (new Column($columnName, Type::getType(Types::JSON)));
    }

    /**
     * The hash for a given dimension space point for better query performance. As this is a hash, the size and type of
     * content is deterministic, a binary type can be used as the actual content is not so important.
     *
     * We could imrpove by actually storing the hash in binary form and shortening and fixing the length.
     *
     * @see DimensionSpacePoint
     */
    public static function columnForDimensionSpacePointHash(string $columnName, AbstractPlatform $platform): Column
    {
        return (new Column($columnName, Type::getType(Types::BINARY)))
            ->setLength(32)
            ->setDefault('');
    }

    /**
     * The NodeTypeName is an ascii string, we should be able to sort it properly, but we don't need unicode here.
     *
     * @see NodeTypeName
     */
    public static function columnForNodeTypeName(string $columnName, AbstractPlatform $platform): Column
    {
        $column = (new Column($columnName, Type::getType(Types::STRING)))
            ->setLength(255)
            ->setNotnull(true);

        if ($platform instanceof AbstractMySQLPlatform) {
            $column = $column
                ->setPlatformOption('charset', 'ascii')
                ->setPlatformOption('collation', 'ascii_general_ci');
        }

        return $column;
    }

    /**
     * @see WorkspaceName
     */
    public static function columnForWorkspaceName(string $columnName, AbstractPlatform $platform): Column
    {
        $column = (new Column($columnName, Type::getType(Types::STRING)))
            ->setLength(WorkspaceName::MAX_LENGTH)
            ->setNotnull(true);

        if ($platform instanceof AbstractMySQLPlatform) {
            $column = $column
                ->setPlatformOption('charset', 'ascii')
                ->setPlatformOption('collation', 'ascii_general_ci');
        }

        return $column;
    }

    public static function columnForProperties(string $columnName, AbstractPlatform $platform): Column
    {
        $column = (new Column($columnName, Type::getType(Types::JSON)));

        if ($platform instanceof AbstractMySQLPlatform) {
            $column = (new Column($columnName, Type::getType(Types::TEXT)))
                ->setPlatformOption('collation', self::DEFAULT_MYSQL_COLLATION);
        }

        return $column;
    }

    public static function columnForGenericString(string $columnName, AbstractPlatform $platform): Column
    {
        $column = (new Column($columnName, Type::getType(Types::STRING)));

        if ($platform instanceof AbstractMySQLPlatform) {
            $column = $column->setPlatformOption('collation', self::DEFAULT_MYSQL_COLLATION);
        }

        return $column;
    }

    public static function columnForGenericText(string $columnName, AbstractPlatform $platform): Column
    {
        $column = (new Column($columnName, Type::getType(Types::TEXT)));
        if ($platform instanceof AbstractMySQLPlatform) {
            $column = $column->setPlatformOption('collation', self::DEFAULT_MYSQL_COLLATION);
        }

        return $column;
    }

    /**
     * @param Connection $dbal
     * @param Table[] $tables
     * @return Schema
     * @throws Exception
     * @throws SchemaException
     */
    public static function createSchemaWithTables(Connection $dbal, array $tables): Schema
    {
        $schemaManager = $dbal->createSchemaManager();
        $schemaConfig = $schemaManager->createSchemaConfig();

        if ($dbal->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            $schemaConfig->setDefaultTableOptions([
                'charset' => 'utf8mb4'
            ]);
        }

        return new Schema($tables, [], $schemaConfig);
    }
}
