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
use Doctrine\DBAL\Schema\Comparator;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\ContentStreamForking;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeCreation;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeDisabling;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeModification;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeReferencing;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeRemoval;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeRenaming;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeTypeChange;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature\NodeVariation;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\SchemaBuilder\HypergraphSchemaBuilder;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\ContentHypergraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\PostgreSQLAdapter\Infrastructure\PostgresDbalClientInterface;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\EventStore\CatchUp\CheckpointStorageInterface;
use Neos\EventStore\DoctrineAdapter\DoctrineCheckpointStorage;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStore\SetupResult;

/**
 * The alternate reality-aware hypergraph projector for the PostgreSQL backend via Doctrine DBAL
 *
 * @implements ProjectionInterface<ContentHypergraph>
 * @internal the parent Content Graph is public
 */
final class HypergraphProjection implements ProjectionInterface
{
    use ContentStreamForking;
    use NodeCreation;
    use NodeDisabling;
    use NodeModification;
    use NodeReferencing;
    use NodeRemoval;
    use NodeRenaming;
    use NodeTypeChange;
    use NodeVariation;

    /**
     * @var ContentHypergraph|null Cache for the content graph returned by {@see getState()},
     * so that always the same instance is returned
     */
    private ?ContentHypergraph $contentHypergraph = null;
    private DoctrineCheckpointStorage $checkpointStorage;
    private ProjectionHypergraph $projectionHypergraph;

    public function __construct(
        private readonly PostgresDbalClientInterface $databaseClient,
        private readonly NodeFactory $nodeFactory,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly string $tableNamePrefix,
    ) {
        $this->projectionHypergraph = new ProjectionHypergraph($this->databaseClient, $this->tableNamePrefix);
        $this->checkpointStorage = new DoctrineCheckpointStorage(
            $this->databaseClient->getConnection(),
            $this->tableNamePrefix . '_checkpoint',
            self::class
        );
    }


    public function setUp(): void
    {
        $this->setupTables();
        $this->checkpointStorage->setup();
    }

    private function setupTables(): SetupResult
    {
        $connection = $this->databaseClient->getConnection();
        HypergraphSchemaBuilder::registerTypes($connection->getDatabasePlatform());
        $schemaManager = $connection->getSchemaManager();
        if (!$schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('Failed to retrieve Schema Manager', 1625653914);
        }

        $schema = (new HypergraphSchemaBuilder($this->tableNamePrefix))->buildSchema();
        $schemaDiff = (new Comparator())->compare($schemaManager->createSchema(), $schema);
        foreach ($schemaDiff->toSaveSql($connection->getDatabasePlatform()) as $statement) {
            $connection->executeStatement($statement);
        }
        $connection->executeStatement('
            CREATE INDEX IF NOT EXISTS node_properties ON ' . $this->tableNamePrefix . '_node USING GIN(properties);

            create index if not exists hierarchy_children
                on ' . $this->tableNamePrefix . '_hierarchyhyperrelation using gin (childnodeanchors);

            create index if not exists restriction_affected
                on ' . $this->tableNamePrefix . '_restrictionhyperrelation using gin (affectednodeaggregateids);
        ');

        return SetupResult::success('');
    }

    public function reset(): void
    {
        $this->truncateDatabaseTables();

        $this->checkpointStorage->acquireLock();
        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());

        //$contentGraph = $this->getState();
        //foreach ($contentGraph->getSubgraphs() as $subgraph) {
        //    $subgraph->inMemoryCache->enable();
        //}
    }

    private function truncateDatabaseTables(): void
    {
        $connection = $this->databaseClient->getConnection();
        $connection->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_node');
        $connection->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_hierarchyhyperrelation');
        $connection->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_referencerelation');
        $connection->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_restrictionhyperrelation');
    }

    public function canHandle(EventInterface $event): bool
    {
        return in_array($event::class, [
            // ContentStreamForking
            ContentStreamWasForked::class,
            // NodeCreation
            RootNodeAggregateWithNodeWasCreated::class,
            NodeAggregateWithNodeWasCreated::class,
            // NodeDisabling
            NodeAggregateWasDisabled::class,
            NodeAggregateWasEnabled::class,
            // NodeModification
            NodePropertiesWereSet::class,
            // NodeReferencing
            NodeReferencesWereSet::class,
            // NodeRemoval
            NodeAggregateWasRemoved::class,
            // NodeRenaming
            NodeAggregateNameWasChanged::class,
            // NodeTypeChange
            NodeAggregateTypeWasChanged::class,
            // NodeVariation
            NodeSpecializationVariantWasCreated::class,
            NodeGeneralizationVariantWasCreated::class,
            NodePeerVariantWasCreated::class,
            // TODO: not yet supported:
            //ContentStreamWasRemoved::class,
            //DimensionSpacePointWasMoved::class,
            //DimensionShineThroughWasAdded::class,
            //NodeAggregateWasMoved::class,
        ]);
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        match ($event::class) {
            // ContentStreamForking
            ContentStreamWasForked::class => $this->whenContentStreamWasForked($event),
            // NodeCreation
            RootNodeAggregateWithNodeWasCreated::class => $this->whenRootNodeAggregateWithNodeWasCreated($event),
            NodeAggregateWithNodeWasCreated::class => $this->whenNodeAggregateWithNodeWasCreated($event),
            // NodeDisabling
            NodeAggregateWasDisabled::class => $this->whenNodeAggregateWasDisabled($event),
            NodeAggregateWasEnabled::class => $this->whenNodeAggregateWasEnabled($event),
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
            default => throw new \InvalidArgumentException(sprintf('Unsupported event %s', get_debug_type($event))),
        };
    }

    public function getCheckpointStorage(): CheckpointStorageInterface
    {
        return $this->checkpointStorage;
    }

    public function getState(): ContentHypergraph
    {
        if (!$this->contentHypergraph) {
            $this->contentHypergraph = new ContentHypergraph(
                $this->databaseClient,
                $this->nodeFactory,
                $this->contentRepositoryId,
                $this->nodeTypeManager,
                $this->tableNamePrefix
            );
        }
        return $this->contentHypergraph;
    }

    protected function getProjectionHypergraph(): ProjectionHypergraph
    {
        return $this->projectionHypergraph;
    }

    /**
     * @throws \Throwable
     */
    protected function transactional(\Closure $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
    }

    protected function getDatabaseConnection(): Connection
    {
        return $this->databaseClient->getConnection();
    }
}
