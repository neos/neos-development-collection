<?php

declare(strict_types=1);

namespace Neos\ContentRepository\DbalTools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\SequenceNumber;

/*
* @internal Because we might need to check for platform later on and generally change the input and output format of functions within.
*/
final class CheckpointHelper
{
    private const CHECKPOINT_TABLE_NAME_SUFFIX = '_checkpoint';

    // This class only contains static members and should not be constructed
    private function __construct()
    {
    }

    public static function checkpointTableSchema(string $tableNamePrefix): Table
    {
        return (new Table($tableNamePrefix . self::CHECKPOINT_TABLE_NAME_SUFFIX, [
            (new Column('id', Type::getType(Types::INTEGER)))->setCustomSchemaOption('check', 'CHECK (id = 0)'),
            (new Column('appliedsequencenumber', Type::getType(Types::INTEGER))),
        ]))
            ->setPrimaryKey(['id']);
    }

    public static function resetCheckpoint(Connection $connection, string $tableNamePrefix): void
    {
        $connection->executeStatement('INSERT INTO ' . $connection->quoteIdentifier($tableNamePrefix . self::CHECKPOINT_TABLE_NAME_SUFFIX) . ' (id, appliedsequencenumber) VALUES (0, 0) ON DUPLICATE KEY UPDATE appliedsequencenumber = 0');
    }

    public static function updateCheckpoint(Connection $connection, string $tableNamePrefix, SequenceNumber $sequenceNumber): void
    {
        $connection->executeStatement('UPDATE ' . $connection->quoteIdentifier($tableNamePrefix . self::CHECKPOINT_TABLE_NAME_SUFFIX) . ' SET appliedsequencenumber = :sequenceNumber WHERE id = 0', ['sequenceNumber' => $sequenceNumber->value]);
    }

    public static function getCheckpoint(Connection $connection, string $tableNamePrefix): SequenceNumber
    {
        $highestAppliedSequenceNumber = $connection->fetchOne('SELECT appliedsequencenumber FROM ' . $connection->quoteIdentifier($tableNamePrefix . self::CHECKPOINT_TABLE_NAME_SUFFIX) . ' LIMIT 1 ');
        if (!is_numeric($highestAppliedSequenceNumber)) {
            throw new \RuntimeException('Failed to fetch highest applied sequence number', 1712942681);
        }
        return SequenceNumber::fromInteger((int)$highestAppliedSequenceNumber);
    }

}
