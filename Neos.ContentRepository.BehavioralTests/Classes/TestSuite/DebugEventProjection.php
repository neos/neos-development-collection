<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\TestSuite;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Dbal\DbalSchemaDiff;
use Neos\ContentRepository\Dbal\DbalSchemaFactory;
use Neos\EventStore\Model\EventEnvelope;
use Neos\Flow\Annotations as Flow;

/**
 * Testing projection to assert (via primary key) that each event is only handled once, also in error cases via rollback
 *
 * TODO check also that order of inserted sequence numbers is correct and no holes
 *
 * @implements ProjectionInterface<DebugEventProjectionState>
 * @internal
 * @Flow\Proxy(false)
 */
final class DebugEventProjection implements ProjectionInterface
{
    private DebugEventProjectionState $state;

    private \Closure|null $saboteur = null;

    /**
     * @var array<Column>
     */
    private array $additionalColumnsForSchema = [];

    public function __construct(
        private string $tableNamePrefix,
        private Connection $dbal
    ) {
        $this->state = new DebugEventProjectionState($this->tableNamePrefix, $this->dbal);
    }

    public function setUp(): void
    {
        foreach ($this->determineRequiredSqlStatements() as $statement) {
            $this->dbal->executeStatement($statement);
        }
        if ($this->saboteur) {
            ($this->saboteur)();
        }
    }

    public function status(): ProjectionStatus
    {
        $requiredSqlStatements = $this->determineRequiredSqlStatements();
        if ($requiredSqlStatements !== []) {
            return ProjectionStatus::setupRequired(sprintf('Requires %d SQL statements', count($requiredSqlStatements)));
        }
        return ProjectionStatus::ok();
    }

    /**
     * @return array<string>
     */
    private function determineRequiredSqlStatements(): array
    {
        $schemaManager = $this->dbal->createSchemaManager();

        $table = new Table($this->tableNamePrefix, [
            (new Column('sequencenumber', Type::getType(Types::INTEGER))),
            (new Column('stream', Type::getType(Types::STRING))),
            (new Column('type', Type::getType(Types::STRING))),
            ...$this->additionalColumnsForSchema
        ]);

        $table->setPrimaryKey([
            'sequencenumber'
        ]);

        $schema = DbalSchemaFactory::createSchemaWithTables($this->dbal, [$table]);
        $statements = DbalSchemaDiff::determineRequiredSqlStatements($this->dbal, $schema);

        return $statements;
    }

    public function resetState(): void
    {
        $this->dbal->executeStatement('TRUNCATE ' . $this->tableNamePrefix);
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        try {
            $this->dbal->insert($this->tableNamePrefix, [
               'sequencenumber' => $eventEnvelope->sequenceNumber->value,
               'stream' => $eventEnvelope->streamName->value,
               'type' => $eventEnvelope->event->type->value,
            ]);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $exception) {
            throw new \RuntimeException(sprintf('Must not happen! Debug projection detected duplicate event %s of type %s', $eventEnvelope->sequenceNumber->value, $eventEnvelope->event->type->value), 1732360282, $exception);
        }
        if ($this->saboteur) {
            ($this->saboteur)($eventEnvelope);
        }
    }

    public function getState(): ProjectionStateInterface
    {
        return $this->state;
    }

    public function injectSaboteur(\Closure $saboteur): void
    {
        $this->saboteur = $saboteur;
    }

    public function killSaboteur(): void
    {
        $this->saboteur = null;
    }

    public function schemaNeedsAdditionalColumn(string $name): void
    {
        $this->additionalColumnsForSchema[$name] = (new Column($name, Type::getType(Types::STRING)))->setNotnull(false);
    }

    public function dropTables(): void
    {
        $this->dbal->executeStatement('DROP TABLE ' . $this->tableNamePrefix);
    }
}
