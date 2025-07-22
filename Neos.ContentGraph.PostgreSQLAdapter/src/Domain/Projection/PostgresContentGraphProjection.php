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
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\ContentStream;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\ContentStreamForking;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeCreation;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeModification;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeReferencing;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeRemoval;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeRenaming;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeTypeChange;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeVariation;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\SubtreeTagging;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\Workspace;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\SchemaBuilder\HypergraphSchemaBuilder;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasReopened;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
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
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Dbal\DbalSchemaDiff;
use Neos\EventStore\Model\EventEnvelope;

/**
 * Postgres implementation for the {@link ContentGraphProjectionInterface}.
 *
 * This class has three responsibilities:
 * <ol>
 * <li>It provides DB schema creation and reset queries to initialize the content repository.</li>
 * <li>It implements the WRITE side of the content repository by reacting to emitted events.</li>
 * <li>It provides access to the READ side by exposing the {@link ContentGraphReadModelInterface}.</li>
 * </ol>
 *
 *
 *
 * @internal the parent Content Graph is public
 */
final readonly class PostgresContentGraphProjection implements ContentGraphProjectionInterface
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
    use Workspace;
    use ContentStream;

    private ProjectionReadQueries $readQueries;
    private ProjectionWriteQueries $writeQueries;
    private ContentGraphTableNames $tableNames;

    public function __construct(
        private Connection $dbal,
        private ContentRepositoryId $contentRepositoryId,
        private ContentGraphReadModelInterface $contentGraphReadModel
    ) {
        $this->tableNames = ContentGraphTableNames::create($contentRepositoryId);
        $this->readQueries = new ProjectionReadQueries($this->dbal, $contentRepositoryId);
        $this->writeQueries = new ProjectionWriteQueries($contentRepositoryId);
    }


    public function setUp(): void
    {
        foreach ($this->determineRequiredSqlStatements() as $statement) {
            $this->dbal->executeStatement($statement);
        }
        $tableNode = $this->tableNames->node();
        $tableHierarchyRelation = $this->tableNames->hierarchyRelation();
        $this->dbal->executeStatement(<<<SQL
            create index if not exists node_properties on $tableNode using gin(properties);
            create index if not exists hierarchy_children on $tableHierarchyRelation using gin(childnodeanchors);
        SQL);
        $this->dbal->executeStatement(<<<SQL
            create or replace function insert_into_array_before_successor(ids bigint[], value bigint, successor bigint)
                returns bigint[]
            as
            $$
            declare
                successor_idx integer := array_position(ids, successor);
            begin
                return case when successor is null or successor_idx is null then
                    (select ids || value)
                else
                    (select ids[:successor_idx - 1] || value || ids[successor_idx:])
                end;
            end;
            $$ language plpgsql;
        SQL);
    }

    public function status(): ProjectionStatus
    {
        try {
            $this->getDatabaseConnection()->connect();
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
        return ProjectionStatus::ok();
    }

    /**
     * @return array<string>
     */
    private function determineRequiredSqlStatements(): array
    {
        try {
            $schema = (new HypergraphSchemaBuilder($this->tableNames))->buildSchema();
            $queries = DbalSchemaDiff::determineRequiredSqlStatements($this->dbal, $schema);
            return $queries;
        } catch (\Throwable $e) {
            // TODO error handling
            throw $e;
        }
    }

    public function resetState(): void
    {
        $this->truncateDatabaseTables();
    }

    private function truncateDatabaseTables(): void
    {
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->node());
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->hierarchyRelation());
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->referenceRelation());
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->workspace());
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->contentStream());
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->dimensionSpacePoints());
        // TODO implement sub-tree tags
        //$this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->subTreeTags());
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        match ($event::class) {
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
            // Workspaces
            RootWorkspaceWasCreated::class => $this->whenRootWorkspaceWasCreated($event),
            WorkspaceWasCreated::class => $this->whenWorkspaceWasCreated($event),
            WorkspaceWasDiscarded::class => $this->whenWorkspaceWasDiscarded($event),
            WorkspaceWasPublished::class => $this->whenWorkspaceWasPublished($event),
            WorkspaceWasRebased::class => $this->whenWorkspaceWasRebased($event),
            // ContentStream
            ContentStreamWasClosed::class => $this->whenContentStreamWasClosed($event),
            ContentStreamWasCreated::class => $this->whenContentStreamWasCreated($event),
            ContentStreamWasRemoved::class => $this->whenContentStreamWasRemoved($event),
            ContentStreamWasReopened::class => $this->whenContentStreamWasReopened($event),
            // ContentStreamForking
            ContentStreamWasForked::class => $this->whenContentStreamWasForked($event),
            default => null,
        };
    }

    public function inSimulation(\Closure $fn): mixed
    {
        if ($this->dbal->isTransactionActive()) {
            throw new \RuntimeException(sprintf('Invoking %s is not allowed to be invoked recursively. Current transaction nesting %d.', __FUNCTION__, $this->dbal->getTransactionNestingLevel()));
        }
        $this->dbal->beginTransaction();
        $this->dbal->setRollbackOnly();
        try {
            return $fn();
        } finally {
            // unsets rollback only flag and allows the connection to work regular again
            $this->dbal->rollBack();
        }
    }

    public function getState(): ContentGraphReadModelInterface
    {
        return $this->contentGraphReadModel;
    }

    protected function getReadQueries(): ProjectionReadQueries
    {
        return $this->readQueries;
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->dbal;
    }

    protected function getWriteQueries(): ProjectionWriteQueries
    {
        return $this->writeQueries;
    }

    protected function getTableNames(): ContentGraphTableNames
    {
        return $this->tableNames;
    }

}
