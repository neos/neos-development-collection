<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\ContentStreamForking;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeCreation;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeModification;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeReferencing;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeRemoval;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeRenaming;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeTypeChange;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeVariation;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\SubtreeTagging;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\SchemaBuilder\HypergraphSchemaBuilder;
use Neos\ContentRepository\Core\ContentGraphFinder;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\DbalTools\CheckpointHelper;
use Neos\ContentRepository\DbalTools\DbalSchemaDiff;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

/**
 * The alternate reality-aware hypergraph projector for the PostgreSQL backend via Doctrine DBAL
 *
 * @implements ProjectionInterface<ContentGraphFinder>
 * @internal the parent Content Graph is public
 */
final class HypergraphProjection implements ProjectionInterface
{
    use ContentStreamForking;
    use NodeCreation;
    use SubtreeTagging;
    use NodeModification;
    use NodeReferencing;
    use NodeRemoval;
    use NodeRenaming;
    use NodeTypeChange;
    use NodeVariation;

    private ProjectionHypergraph $projectionHypergraph;

    public function __construct(
        private readonly Connection $dbal,
        private readonly string $tableNamePrefix,
        private readonly ContentGraphFinder $contentGraphFinder
    ) {
        $this->projectionHypergraph = new ProjectionHypergraph($this->dbal, $this->tableNamePrefix);
    }


    public function setUp(): void
    {
        foreach ($this->determineRequiredSqlStatements() as $statement) {
            $this->dbal->executeStatement($statement);
        }
        $this->dbal->executeStatement('
            CREATE INDEX IF NOT EXISTS node_properties ON ' . $this->tableNamePrefix . '_node USING GIN(properties);

            create index if not exists hierarchy_children
                on ' . $this->tableNamePrefix . '_hierarchyhyperrelation using gin (childnodeanchors);

            create index if not exists restriction_affected
                on ' . $this->tableNamePrefix . '_restrictionhyperrelation using gin (affectednodeaggregateids);
        ');
    }

    public function status(): ProjectionStatus
    {
        try {
            $this->dbal->connect();
        } catch (\Throwable $e) {
            return ProjectionStatus::error(sprintf('Failed to connect to database: %s', $e->getMessage()));
        }
        try {
            $requiredSqlStatements = $this->determineRequiredSqlStatements();
        } catch (\Throwable $e) {
            return ProjectionStatus::error(sprintf('Failed to determine required SQL statements: %s', $e->getMessage()));
        }
        if ($requiredSqlStatements !== []) {
            return ProjectionStatus::setupRequired(sprintf('The following SQL statement%s required: %s', count($requiredSqlStatements) !== 1 ? 's are' : ' is', implode(chr(10), $requiredSqlStatements)));
        }
        try {
            $this->getCheckpoint();
        } catch (\Exception $exception) {
            return ProjectionStatus::error('Error while retrieving checkpoint: ' . $exception->getMessage());
        }
        return ProjectionStatus::ok();
    }

    /**
     * @return array<string>
     */
    private function determineRequiredSqlStatements(): array
    {
        HypergraphSchemaBuilder::registerTypes($this->dbal->getDatabasePlatform());
        $schemaManager = $this->dbal->getSchemaManager();
        if (!$schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('Failed to retrieve Schema Manager', 1625653914);
        }

        $schema = (new HypergraphSchemaBuilder($this->tableNamePrefix))->buildSchema();
        return DbalSchemaDiff::determineRequiredSqlStatements($this->dbal, $schema);
    }

    public function reset(): void
    {
        $this->truncateDatabaseTables();

        CheckpointHelper::resetCheckpoint($this->dbal, $this->tableNamePrefix . '_checkpoint');
    }

    private function truncateDatabaseTables(): void
    {
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_node');
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_hierarchyhyperrelation');
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_referencerelation');
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_restrictionhyperrelation');
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        $this->dbal->beginTransaction();
        match ($event::class) {
            // ContentStreamForking
            ContentStreamWasForked::class => $this->whenContentStreamWasForked($event),
            // NodeCreation
            RootNodeAggregateWithNodeWasCreated::class => $this->whenRootNodeAggregateWithNodeWasCreated($event),
            NodeAggregateWithNodeWasCreated::class => $this->whenNodeAggregateWithNodeWasCreated($event),
            // SubtreeTagging
            SubtreeWasTagged::class => $this->whenSubtreeWasTagged($event),
            SubtreeWasUntagged::class => $this->whenSubtreeWasUntagged($event),
            // NodeModification
            NodePropertiesWereSet::class => $this->whenNodePropertiesWereSet($event),
            // NodeReferencing
            NodeReferencesWereSet::class => $this->whenNodeReferencesWereSet($event),
            // NodeRemoval
            NodeAggregateWasRemoved::class => $this->whenNodeAggregateWasRemoved($event),
            // NodeRenaming
            NodeAggregateNameWasChanged::class => $this->whenNodeAggregateNameWasChanged($event),
            // NodeTypeChange
            NodeAggregateTypeWasChanged::class => $this->whenNodeAggregateTypeWasChanged($event),
            // NodeVariation
            NodeSpecializationVariantWasCreated::class => $this->whenNodeSpecializationVariantWasCreated($event),
            NodeGeneralizationVariantWasCreated::class => $this->whenNodeGeneralizationVariantWasCreated($event),
            NodePeerVariantWasCreated::class => $this->whenNodePeerVariantWasCreated($event),
            default => null,
        };
        CheckpointHelper::updateCheckpoint($this->dbal, $this->tableNamePrefix . '_checkpoint', $eventEnvelope->sequenceNumber);
        $this->dbal->commit();
    }

    public function getCheckpoint(): SequenceNumber
    {
        return CheckpointHelper::getCheckpoint($this->dbal, $this->tableNamePrefix . '_checkpoint');
    }

    public function getState(): ContentGraphFinder
    {
        return $this->contentGraphFinder;
    }

    protected function getProjectionHypergraph(): ProjectionHypergraph
    {
        return $this->projectionHypergraph;
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->dbal;
    }
}
