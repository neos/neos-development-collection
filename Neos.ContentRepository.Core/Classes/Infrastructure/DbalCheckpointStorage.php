<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\DriverException as DBALDriverException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\Projection\CheckpointStorageInterface;
use Neos\ContentRepository\Core\Projection\CheckpointStorageStatus;
use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * Doctrine DBAL implementation of the {@see CheckpointStorageInterface} supporting MySQL, MariaDB and PostgreSQL connections
 * @api
 */
final class DbalCheckpointStorage implements CheckpointStorageInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $tableName,
        private readonly string $subscriberId,
    ) {
        $platform = $this->connection->getDatabasePlatform();
        if (!($platform instanceof MySqlPlatform || $platform instanceof PostgreSqlPlatform)) {
            throw new \InvalidArgumentException(sprintf('The %s only supports the platforms %s and %s currently. Given: %s', $this::class, MySqlPlatform::class, PostgreSqlPlatform::class, get_debug_type($platform)), 1660556004);
        }
        if (strlen($this->subscriberId) > 255) {
            throw new \InvalidArgumentException('The subscriberId must not exceed 255 characters', 1705673456);
        }
    }

    public function setUp(): void
    {
        foreach ($this->determineRequiredSqlStatements() as $statement) {
            $this->connection->executeStatement($statement);
        }
        try {
            $this->connection->insert($this->tableName, ['subscriberid' => $this->subscriberId, 'appliedsequencenumber' => 0]);
        } catch (UniqueConstraintViolationException $e) {
            // table and row already exists, ignore
        }
    }

    public function status(): CheckpointStorageStatus
    {
        try {
            $this->connection->connect();
        } catch (\Throwable $e) {
            return CheckpointStorageStatus::error(sprintf('Failed to connect to database for subscriber "%s": %s', $this->subscriberId, $e->getMessage()));
        }
        try {
            $requiredSqlStatements = $this->determineRequiredSqlStatements();
        } catch (\Throwable $e) {
            return CheckpointStorageStatus::error(sprintf('Failed to compare database schema for subscriber "%s": %s', $this->subscriberId, $e->getMessage()));
        }
        if ($requiredSqlStatements !== []) {
            return CheckpointStorageStatus::setupRequired(sprintf('The following SQL statement%s required for subscriber "%s": %s', count($requiredSqlStatements) !== 1 ? 's are' : ' is', $this->subscriberId, implode(chr(10), $requiredSqlStatements)));
        }
        try {
            $appliedSequenceNumber = $this->connection->fetchOne('SELECT appliedsequencenumber FROM ' . $this->tableName . ' WHERE subscriberid = :subscriberId', ['subscriberId' => $this->subscriberId]);
        } catch (\Throwable $e) {
            return CheckpointStorageStatus::error(sprintf('Failed to determine initial applied sequence number for subscriber "%s": %s', $this->subscriberId, $e->getMessage()));
        }
        if ($appliedSequenceNumber === false) {
            return CheckpointStorageStatus::setupRequired(sprintf('Initial initial applied sequence number not set for subscriber "%s"', $this->subscriberId));
        }
        return CheckpointStorageStatus::ok();
    }

    public function acquireLock(): SequenceNumber
    {
        return $this->getHighestAppliedSequenceNumber();
    }

    public function updateAndReleaseLock(SequenceNumber $sequenceNumber): void
    {
        $this->connection->update($this->tableName, ['appliedsequencenumber' => $sequenceNumber->value], ['subscriberid' => $this->subscriberId]);
    }

    public function getHighestAppliedSequenceNumber(): SequenceNumber
    {
        $highestAppliedSequenceNumber = $this->connection->fetchOne('SELECT appliedsequencenumber FROM ' . $this->connection->quoteIdentifier($this->tableName) . ' WHERE subscriberid = :subscriberId ', [
            'subscriberId' => $this->subscriberId
        ]);
        if (!is_numeric($highestAppliedSequenceNumber)) {
            throw new \RuntimeException(sprintf('Failed to fetch highest applied sequence number for subscriber "%s". Please run %s::setUp()', $this->subscriberId, $this::class), 1652279427);
        }
        return SequenceNumber::fromInteger((int)$highestAppliedSequenceNumber);
    }

    // --------------

    /**
     * @return array<string>
     */
    private function determineRequiredSqlStatements(): array
    {
        $schemaManager = $this->connection->getSchemaManager();
        if (!$schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('Failed to retrieve Schema Manager', 1705681161);
        }
        $tableSchema = new Table(
            $this->tableName,
            [
                (new Column('subscriberid', Type::getType(Types::STRING)))->setLength(255),
                (new Column('appliedsequencenumber', Type::getType(Types::INTEGER)))
            ]
        );
        $tableSchema->setPrimaryKey(['subscriberid']);
        $schema = DbalSchemaFactory::createSchemaWithTables($schemaManager, [$tableSchema]);
        return DbalSchemaDiff::determineRequiredSqlStatements($this->connection, $schema);
    }
}
