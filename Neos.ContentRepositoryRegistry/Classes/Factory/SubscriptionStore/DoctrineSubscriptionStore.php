<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\SubscriptionStore;

use Closure;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaDiff;
use Neos\ContentRepository\Core\Subscription\RunMode;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionCriteria;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionStoreInterface;
use Neos\ContentRepository\Core\Subscription\Subscription;
use Neos\ContentRepository\Core\Subscription\SubscriptionError;
use Neos\ContentRepository\Core\Subscription\SubscriptionGroup;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\Subscriptions;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;
use Psr\Clock\ClockInterface;

final class DoctrineSubscriptionStore implements SubscriptionStoreInterface
{
    public function __construct(
        private string $tableName,
        private readonly Connection $dbal,
        private readonly ClockInterface $clock,
    ) {
    }

    public function setup(): void
    {
        $schemaConfig = $this->dbal->getSchemaManager()->createSchemaConfig();
        assert($schemaConfig !== null);
        $schemaConfig->setDefaultTableOptions([
            'charset' => 'utf8mb4'
        ]);
        $isSqlite = $this->dbal->getDatabasePlatform() instanceof SqlitePlatform;
        $tableSchema = new Table($this->tableName, [
            (new Column('id', Type::getType(Types::STRING)))->setNotnull(true)->setLength(SubscriptionId::MAX_LENGTH)->setPlatformOption('charset', 'ascii')->setPlatformOption('collation', $isSqlite ? null : 'ascii_general_ci'),
            (new Column('group_name', Type::getType(Types::STRING)))->setNotnull(true)->setLength(100)->setPlatformOption('charset', 'ascii')->setPlatformOption('collation', $isSqlite ? null : 'ascii_general_ci'),
            (new Column('run_mode', Type::getType(Types::STRING)))->setNotnull(true)->setLength(16)->setPlatformOption('charset', 'ascii')->setPlatformOption('collation', $isSqlite ? null : 'ascii_general_ci'),
            (new Column('position', Type::getType(Types::INTEGER)))->setNotnull(true),
            (new Column('locked', Type::getType(Types::BOOLEAN)))->setNotnull(true),
            (new Column('status', Type::getType(Types::STRING)))->setNotnull(true)->setLength(32)->setPlatformOption('charset', 'ascii')->setPlatformOption('collation', $isSqlite ? null : 'ascii_general_ci'),
            (new Column('error_message', Type::getType(Types::TEXT)))->setNotnull(false),
            (new Column('error_previous_status', Type::getType(Types::STRING)))->setNotnull(false)->setLength(32)->setPlatformOption('charset', 'ascii')->setPlatformOption('collation', $isSqlite ? null : 'ascii_general_ci'),
            (new Column('error_trace', Type::getType(Types::TEXT)))->setNotnull(false),
            (new Column('retry_attempt', Type::getType(Types::INTEGER)))->setNotnull(true),
            (new Column('last_saved_at', Type::getType(Types::DATETIME_IMMUTABLE)))->setNotnull(true),
        ]);
        $tableSchema->setPrimaryKey(['id']);
        $tableSchema->addIndex(['group_name']);
        $tableSchema->addIndex(['status']);
        $schema = new Schema(
            [$tableSchema],
            [],
            $schemaConfig,
        );
        foreach (DbalSchemaDiff::determineRequiredSqlStatements($this->dbal, $schema) as $statement) {
            $this->dbal->executeStatement($statement);
        }
    }

    public function findOneById(SubscriptionId $subscriptionId): ?Subscription
    {
        $row = $this->dbal->fetchAssociative('SELECT * FROM ' . $this->tableName . ' WHERE id = :subscriptionId', ['subscriptionId' => $subscriptionId->value]);
        if ($row === false) {
            return null;
        }
        return self::fromDatabase($row);
    }

    public function findByCriteria(SubscriptionCriteria $criteria): Subscriptions
    {
        $queryBuilder = $this->dbal->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->orderBy('id');
        if ($criteria->ids !== null) {
            $queryBuilder->andWhere('id IN (:ids)')
                ->setParameter(
                    'ids',
                    $criteria->ids->toStringArray(),
                    Connection::PARAM_STR_ARRAY,
                );
        }
        if ($criteria->groups !== null) {
            $queryBuilder->andWhere('group_name IN (:groups)')
                ->setParameter(
                    'groups',
                    $criteria->groups->toStringArray(),
                    Connection::PARAM_STR_ARRAY,
                );
        }
        if ($criteria->status !== null) {
            $queryBuilder->andWhere('status IN (:status)')
                ->setParameter(
                    'status',
                    array_map(static fn (SubscriptionStatus $status) => $status->name, $criteria->status),
                    Connection::PARAM_STR_ARRAY,
                );
        }
        $result = $queryBuilder->executeQuery();
        assert($result instanceof Result);
        $rows = $result->fetchAllAssociative();
        if ($rows === []) {
            return Subscriptions::none();
        }
        return Subscriptions::fromArray(array_map(self::fromDatabase(...), $rows));
    }

    public function acquireLock(SubscriptionId $subscriptionId): bool
    {
        $data = [
            'locked' => 1,
            'last_saved_at' => $this->clock->now()->format('Y-m-d H:i:s'),
        ];
        $acquired = $this->dbal->update($this->tableName, $data, [
            'id' => $subscriptionId->value,
            'locked' => 0,
        ]);
        return $acquired >= 1;
    }

    public function releaseLock(SubscriptionId $subscriptionId): void
    {
        $data = [
            'locked' => 0,
            'last_saved_at' => $this->clock->now()->format('Y-m-d H:i:s'),
        ];
        $this->dbal->update($this->tableName, $data, ['id' => $subscriptionId->value]);
    }

    public function add(Subscription $subscription): void
    {
        $row = self::toDatabase($subscription);
        $row['id'] = $subscription->id->value;
        $row['locked'] = 0;
        $row['last_saved_at'] = $this->clock->now()->format('Y-m-d H:i:s');
        $this->dbal->insert(
            $this->tableName,
            $row,
        );
    }

    public function update(SubscriptionId $subscriptionId, Closure $updater): void
    {
        $subscription = $this->findOneById($subscriptionId);
        if ($subscription === null) {
            throw new \InvalidArgumentException(sprintf('Failed to update subscription with id "%s" because it does not exist', $subscriptionId->value), 1721672347);
        }
        /** @var Subscription $subscription */
        $subscription = $updater($subscription);
        $row = self::toDatabase($subscription);
        $row['last_saved_at'] = $this->clock->now()->format('Y-m-d H:i:s');
        $this->dbal->update(
            $this->tableName,
            $row,
            [
                'id' => $subscriptionId->value,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function toDatabase(Subscription $subscription): array
    {
        return [
            'group_name' => $subscription->group->value,
            'run_mode' => $subscription->runMode->name,
            'status' => $subscription->status->name,
            'position' => $subscription->position->value,
            'error_message' => $subscription->error?->errorMessage,
            'error_previous_status' => $subscription->error?->previousStatus?->name,
            'error_trace' => $subscription->error?->errorTrace,
            'retry_attempt' => $subscription->retryAttempt,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function fromDatabase(array $row): Subscription
    {
        if (isset($row['error_message'])) {
            assert(is_string($row['error_message']));
            assert(!isset($row['error_previous_status']) || is_string($row['error_previous_status']));
            assert(is_string($row['error_trace']));
            $subscriptionError = new SubscriptionError($row['error_message'], SubscriptionStatus::from($row['error_previous_status']), $row['error_trace']);
        } else {
            $subscriptionError = null;
        }
        assert(is_string($row['id']));
        assert(is_string($row['group_name']));
        assert(is_string($row['run_mode']));
        assert(is_string($row['status']));
        assert(is_int($row['position']));
        assert(is_int($row['locked']));
        assert(is_int($row['retry_attempt']));
        assert(is_string($row['last_saved_at']));
        $lastSavedAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['last_saved_at']);
        assert($lastSavedAt instanceof DateTimeImmutable);

        return new Subscription(
            SubscriptionId::fromString($row['id']),
            SubscriptionGroup::fromString($row['group_name']),
            RunMode::from($row['run_mode']),
            SubscriptionStatus::from($row['status']),
            SequenceNumber::fromInteger($row['position']),
            (bool)$row['locked'],
            $subscriptionError,
            $row['retry_attempt'],
            $lastSavedAt,
        );
    }
}
