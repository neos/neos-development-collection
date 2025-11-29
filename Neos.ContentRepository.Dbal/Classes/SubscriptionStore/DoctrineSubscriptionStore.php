<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Dbal\SubscriptionStore;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionCriteria;
use Neos\ContentRepository\Core\Subscription\Store\SubscriptionStoreInterface;
use Neos\ContentRepository\Core\Subscription\Subscription;
use Neos\ContentRepository\Core\Subscription\SubscriptionError;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\ContentRepository\Core\Subscription\Subscriptions;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\ContentRepository\Dbal\DbalSchemaDiff;
use Neos\EventStore\Model\Event\SequenceNumber;
use Psr\Clock\ClockInterface;

/**
 * @internal only API for custom content repository integrations
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
            (new Column('id', Type::getType(Types::STRING)))->setNotnull(true)->setLength(SubscriptionId::MAX_LENGTH),
            (new Column('position', Type::getType(Types::INTEGER)))->setNotnull(true),
            (new Column('status', Type::getType(Types::STRING)))->setNotnull(true)->setLength(32),
            (new Column('error_message', Type::getType(Types::TEXT)))->setNotnull(false),
            (new Column('error_previous_status', Type::getType(Types::STRING)))->setNotnull(false)->setLength(32),
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

    public function findByCriteriaForUpdate(SubscriptionCriteria $criteria): Subscriptions
    {
        $queryBuilder = $this->dbal->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->orderBy('id');
        if (!$this->dbal->getDatabasePlatform() instanceof SqlitePlatform) {
            $queryBuilder->forUpdate();
        }
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
            return Subscriptions::createEmpty();
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
        $row['status'] = $status->value;
        $row['position'] = $position->value;
        $row['error_message'] = $subscriptionError?->errorMessage;
        $row['error_previous_status'] = $subscriptionError?->previousStatus?->value;
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
            'status' => $subscription->status->value,
            'position' => $subscription->position->value,
            'error_message' => $subscription->error?->errorMessage,
            'error_previous_status' => $subscription->error?->previousStatus?->value,
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
        $lastSavedAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['last_saved_at']);
        if ($lastSavedAt === false) {
            throw new \RuntimeException(sprintf('last_saved_at %s is not a valid date', $row['last_saved_at']), 1733602968);
        }

        return new Subscription(
            SubscriptionId::fromString($row['id']),
            SubscriptionStatus::from($row['status']),
            SequenceNumber::fromInteger($row['position']),
            $subscriptionError,
            $lastSavedAt,
        );
    }

    public function beginTransaction(): void
    {
        $this->dbal->beginTransaction();
    }

    public function commit(): void
    {
        $this->dbal->commit();
    }
}
