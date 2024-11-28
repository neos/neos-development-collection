<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\SubscriptionStore;

use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaDiff;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionCriteria;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionStoreInterface;
use Neos\ContentRepository\Core\Subscription\Subscription;
use Neos\ContentRepository\Core\Subscription\SubscriptionError;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\Subscriptions;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\Event\SequenceNumber;
use Psr\Clock\ClockInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
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
        $schemaConfig = $this->dbal->createSchemaManager()->createSchemaConfig();
        $schemaConfig->setDefaultTableOptions([
            'charset' => 'utf8mb4'
        ]);
        $tableSchema = new Table($this->tableName, [
            (new Column('id', Type::getType(Types::STRING)))->setNotnull(true)->setLength(SubscriptionId::MAX_LENGTH)->setPlatformOption('charset', 'ascii')->setPlatformOption('collation', 'ascii_general_ci'),
            (new Column('position', Type::getType(Types::INTEGER)))->setNotnull(true),
            (new Column('status', Type::getType(Types::STRING)))->setNotnull(true)->setLength(32)->setPlatformOption('charset', 'ascii')->setPlatformOption('collation', 'ascii_general_ci'),
            (new Column('error_message', Type::getType(Types::TEXT)))->setNotnull(false),
            (new Column('error_previous_status', Type::getType(Types::STRING)))->setNotnull(false)->setLength(32)->setPlatformOption('charset', 'ascii')->setPlatformOption('collation', 'ascii_general_ci'),
            (new Column('error_trace', Type::getType(Types::TEXT)))->setNotnull(false),
            (new Column('last_saved_at', Type::getType(Types::DATETIME_IMMUTABLE)))->setNotnull(true),
        ]);
        $tableSchema->setPrimaryKey(['id']);
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
                    ArrayParameterType::STRING,
                );
        }
        if (!$criteria->status->isEmpty()) {
            $queryBuilder->andWhere('status IN (:status)')
                ->setParameter(
                    'status',
                    $criteria->status->toStringArray(),
                    ArrayParameterType::STRING,
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

    public function add(Subscription $subscription): void
    {
        $row = self::toDatabase($subscription);
        $row['id'] = $subscription->id->value;
        $row['last_saved_at'] = $this->clock->now()->format('Y-m-d H:i:s');
        $this->dbal->insert(
            $this->tableName,
            $row,
        );
    }

    public function update(
        SubscriptionId $subscriptionId,
        SubscriptionStatus $status,
        SequenceNumber $position,
        SubscriptionError|null $subscriptionError,
    ): void {
        $row = [];
        $row['last_saved_at'] = $this->clock->now()->format('Y-m-d H:i:s');
        $row['status'] = $status->name;
        $row['position'] = $position->value;
        $row['error_message'] = $subscriptionError?->errorMessage;
        $row['error_previous_status'] = $subscriptionError?->previousStatus?->name;
        $row['error_trace'] = $subscriptionError?->errorTrace;
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
            'status' => $subscription->status->name,
            'position' => $subscription->position->value,
            'error_message' => $subscription->error?->errorMessage,
            'error_previous_status' => $subscription->error?->previousStatus?->name,
            'error_trace' => $subscription->error?->errorTrace,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function fromDatabase(array $row): Subscription
    {
        if (isset($row['error_message'])) {
            $subscriptionError = new SubscriptionError($row['error_message'], SubscriptionStatus::from($row['error_previous_status']), $row['error_trace']);
        } else {
            $subscriptionError = null;
        }
        $lastSavedAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['last_saved_at']);
        assert($lastSavedAt instanceof DateTimeImmutable);

        return new Subscription(
            SubscriptionId::fromString($row['id']),
            SubscriptionStatus::from($row['status']),
            SequenceNumber::fromInteger($row['position']),
            $subscriptionError,
            $lastSavedAt,
        );
    }

    public function acquireLock(): void
    {
        // todo fully implement https://github.com/patchlevel/event-sourcing/blob/caaf54fcf32c0e42b1036a5c7ff77c1a37af0105/src/Store/DoctrineDbalStore.php#L456
        $result = $this->dbal->fetchOne(sprintf('SELECT GET_LOCK("%s", %d)', 'default', 0));
        if ($result !== 1) {
            throw new \RuntimeException('failed to acquire lock');
        }
    }

    public function releaseLock(): void
    {
        $result = $this->dbal->fetchOne(sprintf('SELECT RELEASE_LOCK("%s")', 'default'));
        if ($result !== 1) {
            throw new \RuntimeException('failed to release lock');
        }
    }

    public function transactional(\Closure $closure): mixed
    {
        return $this->dbal->transactional($closure);
    }
}
