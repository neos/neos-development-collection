<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Types;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeMove;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeRemoval;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeVariation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\DimensionSpacePointsRepository;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReference;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateDimensionsWereUpdated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\ContentRepository\Core\Infrastructure\DbalCheckpointStorage;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaDiff;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\CheckpointStorageStatusType;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Projection\WithMarkStaleInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @implements ProjectionInterface<ContentGraph>
 * @internal but the graph projection is api
 */
final class DoctrineDbalContentGraphProjection implements ProjectionInterface, WithMarkStaleInterface
{
    use NodeVariation;
    use SubtreeTagging;
    use NodeRemoval;
    use NodeMove;


    public const RELATION_DEFAULT_OFFSET = 128;

    /**
     * @var ContentGraph|null Cache for the content graph returned by {@see getState()},
     * so that always the same instance is returned
     */
    private ?ContentGraph $contentGraph = null;

    private DbalCheckpointStorage $checkpointStorage;

    private DimensionSpacePointsRepository $dimensionSpacePointsRepository;

    public function __construct(
        private readonly DbalClientInterface $dbalClient,
        private readonly NodeFactory $nodeFactory,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly ProjectionContentGraph $projectionContentGraph,
        private readonly string $tableNamePrefix,
    ) {
        $this->checkpointStorage = new DbalCheckpointStorage(
            $this->dbalClient->getConnection(),
            $this->tableNamePrefix . '_checkpoint',
            self::class
        );

        $this->dimensionSpacePointsRepository = new DimensionSpacePointsRepository($this->dbalClient->getConnection(), $this->tableNamePrefix);
    }

    protected function getProjectionContentGraph(): ProjectionContentGraph
    {
        return $this->projectionContentGraph;
    }

    protected function getTableNamePrefix(): string
    {
        return $this->tableNamePrefix;
    }

    public function setUp(): void
    {
        foreach ($this->determineRequiredSqlStatements() as $statement) {
            $this->getDatabaseConnection()->executeStatement($statement);
        }
        $this->checkpointStorage->setUp();
    }

    /**
     * @return array<string>
     */
    private function determineRequiredSqlStatements(): array
    {
        $connection = $this->dbalClient->getConnection();
        $schemaManager = $connection->getSchemaManager();
        if (!$schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('Failed to retrieve Schema Manager', 1625653914);
        }
        $schema = (new DoctrineDbalContentGraphSchemaBuilder($this->tableNamePrefix))->buildSchema($schemaManager);
        return DbalSchemaDiff::determineRequiredSqlStatements($connection, $schema);
    }

    public function status(): ProjectionStatus
    {
        $checkpointStorageStatus = $this->checkpointStorage->status();
        if ($checkpointStorageStatus->type === CheckpointStorageStatusType::ERROR) {
            return ProjectionStatus::error($checkpointStorageStatus->details);
        }
        if ($checkpointStorageStatus->type === CheckpointStorageStatusType::SETUP_REQUIRED) {
            return ProjectionStatus::setupRequired($checkpointStorageStatus->details);
        }
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

    public function reset(): void
    {
        $this->truncateDatabaseTables();

        $this->checkpointStorage->acquireLock();
        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());

        $contentGraph = $this->getState();
        foreach ($contentGraph->getSubgraphs() as $subgraph) {
            $subgraph->inMemoryCache->enable();
        }
    }

    private function truncateDatabaseTables(): void
    {
        $connection = $this->dbalClient->getConnection();
        $connection->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_node');
        $connection->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_hierarchyrelation');
        $connection->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_referencerelation');
        $connection->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_dimensionspacepoints');
    }

    public function canHandle(EventInterface $event): bool
    {
        return in_array($event::class, [
            RootNodeAggregateWithNodeWasCreated::class,
            RootNodeAggregateDimensionsWereUpdated::class,
            NodeAggregateWithNodeWasCreated::class,
            NodeAggregateNameWasChanged::class,
            ContentStreamWasForked::class,
            ContentStreamWasRemoved::class,
            NodePropertiesWereSet::class,
            NodeReferencesWereSet::class,
            NodeAggregateTypeWasChanged::class,
            DimensionSpacePointWasMoved::class,
            DimensionShineThroughWasAdded::class,
            NodeAggregateWasRemoved::class,
            NodeAggregateWasMoved::class,
            NodeSpecializationVariantWasCreated::class,
            NodeGeneralizationVariantWasCreated::class,
            NodePeerVariantWasCreated::class,
            SubtreeWasTagged::class,
            SubtreeWasUntagged::class,
        ]);
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        match ($event::class) {
            RootNodeAggregateWithNodeWasCreated::class => $this->whenRootNodeAggregateWithNodeWasCreated($event, $eventEnvelope),
            RootNodeAggregateDimensionsWereUpdated::class => $this->whenRootNodeAggregateDimensionsWereUpdated($event),
            NodeAggregateWithNodeWasCreated::class => $this->whenNodeAggregateWithNodeWasCreated($event, $eventEnvelope),
            NodeAggregateNameWasChanged::class => $this->whenNodeAggregateNameWasChanged($event, $eventEnvelope),
            ContentStreamWasForked::class => $this->whenContentStreamWasForked($event),
            ContentStreamWasRemoved::class => $this->whenContentStreamWasRemoved($event),
            NodePropertiesWereSet::class => $this->whenNodePropertiesWereSet($event, $eventEnvelope),
            NodeReferencesWereSet::class => $this->whenNodeReferencesWereSet($event, $eventEnvelope),
            NodeAggregateTypeWasChanged::class => $this->whenNodeAggregateTypeWasChanged($event, $eventEnvelope),
            DimensionSpacePointWasMoved::class => $this->whenDimensionSpacePointWasMoved($event),
            DimensionShineThroughWasAdded::class => $this->whenDimensionShineThroughWasAdded($event),
            NodeAggregateWasRemoved::class => $this->whenNodeAggregateWasRemoved($event),
            NodeAggregateWasMoved::class => $this->whenNodeAggregateWasMoved($event),
            NodeSpecializationVariantWasCreated::class => $this->whenNodeSpecializationVariantWasCreated($event, $eventEnvelope),
            NodeGeneralizationVariantWasCreated::class => $this->whenNodeGeneralizationVariantWasCreated($event, $eventEnvelope),
            NodePeerVariantWasCreated::class => $this->whenNodePeerVariantWasCreated($event, $eventEnvelope),
            SubtreeWasTagged::class => $this->whenSubtreeWasTagged($event),
            SubtreeWasUntagged::class => $this->whenSubtreeWasUntagged($event),
            default => throw new \InvalidArgumentException(sprintf('Unsupported event %s', get_debug_type($event))),
        };
    }

    public function getCheckpointStorage(): DbalCheckpointStorage
    {
        return $this->checkpointStorage;
    }

    public function getState(): ContentGraph
    {
        if (!$this->contentGraph) {
            $this->contentGraph = new ContentGraph(
                $this->dbalClient,
                $this->nodeFactory,
                $this->contentRepositoryId,
                $this->nodeTypeManager,
                $this->tableNamePrefix
            );
        }
        return $this->contentGraph;
    }

    public function markStale(): void
    {
        $contentGraph = $this->getState();
        foreach ($contentGraph->getSubgraphs() as $subgraph) {
            $subgraph->inMemoryCache->disable();
        }
    }

    /**
     * @throws \Throwable
     */
    private function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->transactional(function () use ($event, $eventEnvelope) {
            $originDimensionSpacePoint = OriginDimensionSpacePoint::createWithoutDimensions();
            $node = NodeRecord::createNewInDatabase(
                $this->getDatabaseConnection(),
                $this->tableNamePrefix,
                $event->nodeAggregateId,
                $originDimensionSpacePoint->coordinates,
                $originDimensionSpacePoint->hash,
                SerializedPropertyValues::createEmpty(),
                $event->nodeTypeName,
                $event->nodeAggregateClassification,
                null,
                Timestamps::create(
                    $eventEnvelope->recordedAt,
                    self::initiatingDateTime($eventEnvelope),
                    null,
                    null,
                ),
            );

            $this->connectHierarchy(
                $event->contentStreamId,
                NodeRelationAnchorPoint::forRootEdge(),
                $node->relationAnchorPoint,
                $event->coveredDimensionSpacePoints,
                null
            );
        });
    }

    /**
     * @throws \Throwable
     */
    private function whenRootNodeAggregateDimensionsWereUpdated(RootNodeAggregateDimensionsWereUpdated $event): void
    {
        $rootNodeAnchorPoint = $this->projectionContentGraph
            ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                $event->nodeAggregateId,
                /** the origin DSP of the root node is always the empty dimension ({@see whenRootNodeAggregateWithNodeWasCreated}) */
                OriginDimensionSpacePoint::createWithoutDimensions(),
                $event->contentStreamId
            );
        if ($rootNodeAnchorPoint === null) {
            // should never happen.
            return;
        }

        $this->transactional(function () use ($rootNodeAnchorPoint, $event) {
            // delete all hierarchy edges of the root node
            $this->getDatabaseConnection()->executeUpdate('
                DELETE FROM ' . $this->tableNamePrefix . '_hierarchyrelation
                WHERE
                    parentnodeanchor = :parentNodeAnchor
                    AND childnodeanchor = :childNodeAnchor
                    AND contentstreamid = :contentStreamId
            ', [
                'parentNodeAnchor' => NodeRelationAnchorPoint::forRootEdge()->value,
                'childNodeAnchor' => $rootNodeAnchorPoint->value,
                'contentStreamId' => $event->contentStreamId->value,
            ]);
            // recreate hierarchy edges for the root node
            $this->connectHierarchy(
                $event->contentStreamId,
                NodeRelationAnchorPoint::forRootEdge(),
                $rootNodeAnchorPoint,
                $event->coveredDimensionSpacePoints,
                null
            );
        });
    }

    /**
     * @throws \Throwable
     */
    private function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->transactional(function () use ($event, $eventEnvelope) {
            $this->createNodeWithHierarchy(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->nodeTypeName,
                $event->parentNodeAggregateId,
                $event->originDimensionSpacePoint,
                $event->coveredDimensionSpacePoints,
                $event->initialPropertyValues,
                $event->nodeAggregateClassification,
                $event->succeedingNodeAggregateId,
                $event->nodeName,
                $eventEnvelope,
            );
        });
    }

    /**
     * @throws \Throwable
     */
    private function whenNodeAggregateNameWasChanged(NodeAggregateNameWasChanged $event, EventEnvelope $eventEnvelope): void
    {
        $this->transactional(function () use ($event, $eventEnvelope) {
            $this->getDatabaseConnection()->executeStatement('
                UPDATE ' . $this->tableNamePrefix . '_hierarchyrelation h
                INNER JOIN ' . $this->tableNamePrefix . '_node n on
                    h.childnodeanchor = n.relationanchorpoint
                SET
                  h.name = :newName,
                  n.lastmodified = :lastModified,
                  n.originallastmodified = :originalLastModified

                WHERE
                    n.nodeaggregateid = :nodeAggregateId
                    and h.contentstreamid = :contentStreamId
            ', [
                'newName' => $event->newNodeName->value,
                'nodeAggregateId' => $event->nodeAggregateId->value,
                'contentStreamId' => $event->contentStreamId->value,
                'lastModified' => $eventEnvelope->recordedAt,
                'originalLastModified' => self::initiatingDateTime($eventEnvelope),
            ], [
                'lastModified' => Types::DATETIME_IMMUTABLE,
                'originalLastModified' => Types::DATETIME_IMMUTABLE,
            ]);
        });
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createNodeWithHierarchy(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        NodeTypeName $nodeTypeName,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $visibleInDimensionSpacePoints,
        SerializedPropertyValues $propertyDefaultValuesAndTypes,
        NodeAggregateClassification $nodeAggregateClassification,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        ?NodeName $nodeName,
        EventEnvelope $eventEnvelope,
    ): void {
        $node = NodeRecord::createNewInDatabase(
            $this->getDatabaseConnection(),
            $this->tableNamePrefix,
            $nodeAggregateId,
            $originDimensionSpacePoint->jsonSerialize(),
            $originDimensionSpacePoint->hash,
            $propertyDefaultValuesAndTypes,
            $nodeTypeName,
            $nodeAggregateClassification,
            $nodeName,
            Timestamps::create(
                $eventEnvelope->recordedAt,
                self::initiatingDateTime($eventEnvelope),
                null,
                null,
            ),
        );

        // reconnect parent relations
        $missingParentRelations = $visibleInDimensionSpacePoints->points;

        if (!empty($missingParentRelations)) {
            // add yet missing parent relations

            foreach ($missingParentRelations as $dimensionSpacePoint) {
                $parentNode = $this->projectionContentGraph->findNodeInAggregate(
                    $contentStreamId,
                    $parentNodeAggregateId,
                    $dimensionSpacePoint
                );

                $succeedingSibling = $succeedingSiblingNodeAggregateId
                    ? $this->projectionContentGraph->findNodeInAggregate(
                        $contentStreamId,
                        $succeedingSiblingNodeAggregateId,
                        $dimensionSpacePoint
                    )
                    : null;

                if ($parentNode) {
                    $this->connectHierarchy(
                        $contentStreamId,
                        $parentNode->relationAnchorPoint,
                        $node->relationAnchorPoint,
                        new DimensionSpacePointSet([$dimensionSpacePoint]),
                        $succeedingSibling?->relationAnchorPoint,
                        $nodeName
                    );
                }
            }
        }
    }

    /**
     * @param NodeRelationAnchorPoint $parentNodeAnchorPoint
     * @param NodeRelationAnchorPoint $childNodeAnchorPoint
     * @param NodeRelationAnchorPoint|null $succeedingSiblingNodeAnchorPoint
     * @param NodeName|null $relationName
     * @param ContentStreamId $contentStreamId
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     * @throws \Doctrine\DBAL\DBALException
     */
    private function connectHierarchy(
        ContentStreamId $contentStreamId,
        NodeRelationAnchorPoint $parentNodeAnchorPoint,
        NodeRelationAnchorPoint $childNodeAnchorPoint,
        DimensionSpacePointSet $dimensionSpacePointSet,
        ?NodeRelationAnchorPoint $succeedingSiblingNodeAnchorPoint,
        NodeName $relationName = null
    ): void {
        foreach ($dimensionSpacePointSet as $dimensionSpacePoint) {
            $position = $this->getRelationPosition(
                $parentNodeAnchorPoint,
                null,
                $succeedingSiblingNodeAnchorPoint,
                $contentStreamId,
                $dimensionSpacePoint
            );

            $parentSubtreeTags = $this->subtreeTagsForHierarchyRelation($contentStreamId, $parentNodeAnchorPoint, $dimensionSpacePoint);
            $inheritedSubtreeTags = NodeTags::create(SubtreeTags::createEmpty(), $parentSubtreeTags->all());

            $hierarchyRelation = new HierarchyRelation(
                $parentNodeAnchorPoint,
                $childNodeAnchorPoint,
                $relationName,
                $contentStreamId,
                $dimensionSpacePoint,
                $dimensionSpacePoint->hash,
                $position,
                $inheritedSubtreeTags,
            );

            $hierarchyRelation->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
        }
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getRelationPosition(
        ?NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $childAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint
    ): int {
        $position = $this->projectionContentGraph->determineHierarchyRelationPosition(
            $parentAnchorPoint,
            $childAnchorPoint,
            $succeedingSiblingAnchorPoint,
            $contentStreamId,
            $dimensionSpacePoint
        );

        if ($position % 2 !== 0) {
            $position = $this->getRelationPositionAfterRecalculation(
                $parentAnchorPoint,
                $childAnchorPoint,
                $succeedingSiblingAnchorPoint,
                $contentStreamId,
                $dimensionSpacePoint
            );
        }

        return $position;
    }

    /**
     * @param NodeRelationAnchorPoint|null $parentAnchorPoint
     * @param NodeRelationAnchorPoint|null $childAnchorPoint
     * @param NodeRelationAnchorPoint|null $succeedingSiblingAnchorPoint
     * @param ContentStreamId $contentStreamId
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getRelationPositionAfterRecalculation(
        ?NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $childAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint
    ): int {
        if (!$childAnchorPoint && !$parentAnchorPoint) {
            throw new \InvalidArgumentException(
                'You must either specify a parent or child node anchor'
                . ' to get relation positions after recalculation.',
                1519847858
            );
        }
        $offset = 0;
        $position = 0;
        $hierarchyRelations = $parentAnchorPoint
            ? $this->projectionContentGraph->getOutgoingHierarchyRelationsForNodeAndSubgraph(
                $parentAnchorPoint,
                $contentStreamId,
                $dimensionSpacePoint
            )
            : $this->projectionContentGraph->getIngoingHierarchyRelationsForNodeAndSubgraph(
                $childAnchorPoint,
                $contentStreamId,
                $dimensionSpacePoint
            );

        usort(
            $hierarchyRelations,
            fn (HierarchyRelation $relationA, HierarchyRelation $relationB): int
                => $relationA->position <=> $relationB->position
        );

        foreach ($hierarchyRelations as $relation) {
            $offset += self::RELATION_DEFAULT_OFFSET;
            if (
                $succeedingSiblingAnchorPoint
                && $relation->childNodeAnchor->equals($succeedingSiblingAnchorPoint)
            ) {
                $position = $offset;
                $offset += self::RELATION_DEFAULT_OFFSET;
            }
            $relation->assignNewPosition($offset, $this->getDatabaseConnection(), $this->tableNamePrefix);
        }

        return $position;
    }

    /**
     * @throws \Throwable
     */
    private function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        $this->transactional(function () use ($event) {

            //
            // 1) Copy HIERARCHY RELATIONS (this is the MAIN OPERATION here)
            //
            $this->getDatabaseConnection()->executeUpdate('
                INSERT INTO ' . $this->tableNamePrefix . '_hierarchyrelation (
                  parentnodeanchor,
                  childnodeanchor,
                  `name`,
                  position,
                  dimensionspacepointhash,
                  subtreetags,
                  contentstreamid
                )
                SELECT
                  h.parentnodeanchor,
                  h.childnodeanchor,
                  h.name,
                  h.position,
                  h.dimensionspacepointhash,
                  h.subtreetags,
                  "' . $event->newContentStreamId->value . '" AS contentstreamid
                FROM
                    ' . $this->tableNamePrefix . '_hierarchyrelation h
                    WHERE h.contentstreamid = :sourceContentStreamId
            ', [
                'sourceContentStreamId' => $event->sourceContentStreamId->value
            ]);

            // NOTE: as reference edges are attached to Relation Anchor Points (and they are lazily copy-on-written),
            // we do not need to copy reference edges here (but we need to do it during copy on write).
        });
    }

    private function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
    {
        $this->transactional(function () use ($event) {

            // Drop hierarchy relations
            $this->getDatabaseConnection()->executeUpdate('
                DELETE FROM ' . $this->tableNamePrefix . '_hierarchyrelation
                WHERE
                    contentstreamid = :contentStreamId
            ', [
                'contentStreamId' => $event->contentStreamId->value
            ]);

            // Drop non-referenced nodes (which do not have a hierarchy relation anymore)
            $this->getDatabaseConnection()->executeUpdate('
                DELETE FROM ' . $this->tableNamePrefix . '_node
                WHERE NOT EXISTS
                    (
                        SELECT 1 FROM ' . $this->tableNamePrefix . '_hierarchyrelation
                        WHERE ' . $this->tableNamePrefix . '_hierarchyrelation.childnodeanchor
                                  = ' . $this->tableNamePrefix . '_node.relationanchorpoint
                    )
            ');

            // Drop non-referenced reference relations (i.e. because the referenced nodes are gone by now)
            $this->getDatabaseConnection()->executeUpdate('
                DELETE FROM ' . $this->tableNamePrefix . '_referencerelation
                WHERE NOT EXISTS
                    (
                        SELECT 1 FROM ' . $this->tableNamePrefix . '_node
                        WHERE ' . $this->tableNamePrefix . '_node.relationanchorpoint
                                  = ' . $this->tableNamePrefix . '_referencerelation.nodeanchorpoint
                    )
            ');
        });
    }

    /**
     * @throws \Throwable
     */
    private function whenNodePropertiesWereSet(NodePropertiesWereSet $event, EventEnvelope $eventEnvelope): void
    {
        $this->transactional(function () use ($event, $eventEnvelope) {
            $anchorPoint = $this->projectionContentGraph
                ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                    $event->getNodeAggregateId(),
                    $event->getOriginDimensionSpacePoint(),
                    $event->getContentStreamId()
                );
            if (is_null($anchorPoint)) {
                throw new \InvalidArgumentException(
                    'Cannot update node with copy on write since no anchor point could be resolved for node '
                    . $event->getNodeAggregateId()->value . ' in content stream '
                    . $event->getContentStreamId()->value,
                    1645303332
                );
            }
            $this->updateNodeRecordWithCopyOnWrite(
                $event->getContentStreamId(),
                $anchorPoint,
                function (NodeRecord $node) use ($event, $eventEnvelope) {
                    $node->properties = $node->properties
                        ->merge($event->propertyValues)
                        ->unsetProperties($event->propertiesToUnset);
                    $node->timestamps = $node->timestamps->with(
                        lastModified: $eventEnvelope->recordedAt,
                        originalLastModified: self::initiatingDateTime($eventEnvelope)
                    );
                }
            );
        });
    }

    /**
     * @throws \Throwable
     */
    private function whenNodeReferencesWereSet(NodeReferencesWereSet $event, EventEnvelope $eventEnvelope): void
    {
        $this->transactional(function () use ($event, $eventEnvelope) {
            foreach ($event->affectedSourceOriginDimensionSpacePoints as $originDimensionSpacePoint) {
                $nodeAnchorPoint = $this->projectionContentGraph
                    ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                        $event->sourceNodeAggregateId,
                        $originDimensionSpacePoint,
                        $event->contentStreamId
                    );

                if (is_null($nodeAnchorPoint)) {
                    throw new \InvalidArgumentException(
                        'Could not apply event of type "' . get_class($event)
                        . '" since no anchor point could be resolved for node '
                        . $event->getNodeAggregateId()->value . ' in content stream '
                        . $event->getContentStreamId()->value,
                        1658580583
                    );
                }

                $this->updateNodeRecordWithCopyOnWrite(
                    $event->contentStreamId,
                    $nodeAnchorPoint,
                    function (NodeRecord $node) use ($eventEnvelope) {
                        $node->timestamps = $node->timestamps->with(
                            lastModified: $eventEnvelope->recordedAt,
                            originalLastModified: self::initiatingDateTime($eventEnvelope)
                        );
                    }
                );

                $nodeAnchorPoint = $this->projectionContentGraph
                    ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                        $event->sourceNodeAggregateId,
                        $originDimensionSpacePoint,
                        $event->contentStreamId
                    );

                // remove old
                $this->getDatabaseConnection()->delete($this->tableNamePrefix . '_referencerelation', [
                    'nodeanchorpoint' => $nodeAnchorPoint?->value,
                    'name' => $event->referenceName->value
                ]);

                // set new
                $position = 0;
                /** @var SerializedNodeReference $reference */
                foreach ($event->references as $reference) {
                    $this->getDatabaseConnection()->insert($this->tableNamePrefix . '_referencerelation', [
                        'name' => $event->referenceName->value,
                        'position' => $position,
                        'nodeanchorpoint' => $nodeAnchorPoint?->value,
                        'destinationnodeaggregateid' => $reference->targetNodeAggregateId->value,
                        'properties' => $reference->properties
                            ? \json_encode($reference->properties, JSON_THROW_ON_ERROR & JSON_FORCE_OBJECT)
                            : null
                    ]);
                    $position++;
                }
            }
        });
    }

    /**
     * @param HierarchyRelation $sourceHierarchyRelation
     * @param ContentStreamId $contentStreamId
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param NodeRelationAnchorPoint|null $newParent
     * @param NodeRelationAnchorPoint|null $newChild
     * @return HierarchyRelation
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function copyHierarchyRelationToDimensionSpacePoint(
        HierarchyRelation $sourceHierarchyRelation,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        ?NodeRelationAnchorPoint $newParent = null,
        ?NodeRelationAnchorPoint $newChild = null
    ): HierarchyRelation {
        if ($newParent === null) {
            $newParent = $sourceHierarchyRelation->parentNodeAnchor;
        }
        if ($newChild === null) {
            $newChild = $sourceHierarchyRelation->childNodeAnchor;
        }
        $parentSubtreeTags = $this->subtreeTagsForHierarchyRelation($contentStreamId, $newParent, $dimensionSpacePoint);
        $inheritedSubtreeTags = NodeTags::create($sourceHierarchyRelation->subtreeTags->withoutInherited()->all(), $parentSubtreeTags->withoutInherited()->all());
        $copy = new HierarchyRelation(
            $newParent,
            $newChild,
            $sourceHierarchyRelation->name,
            $contentStreamId,
            $dimensionSpacePoint,
            $dimensionSpacePoint->hash,
            $this->getRelationPosition(
                $newParent,
                $newChild,
                null, // todo: find proper sibling
                $contentStreamId,
                $dimensionSpacePoint
            ),
            $inheritedSubtreeTags,
        );
        $copy->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

        return $copy;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function copyNodeToDimensionSpacePoint(
        NodeRecord $sourceNode,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        EventEnvelope $eventEnvelope,
    ): NodeRecord {
        return NodeRecord::createNewInDatabase(
            $this->getDatabaseConnection(),
            $this->tableNamePrefix,
            $sourceNode->nodeAggregateId,
            $originDimensionSpacePoint->coordinates,
            $originDimensionSpacePoint->hash,
            $sourceNode->properties,
            $sourceNode->nodeTypeName,
            $sourceNode->classification,
            $sourceNode->nodeName,
            Timestamps::create(
                $eventEnvelope->recordedAt,
                self::initiatingDateTime($eventEnvelope),
                null,
                null,
            ),
        );
    }

    private function whenNodeAggregateTypeWasChanged(NodeAggregateTypeWasChanged $event, EventEnvelope $eventEnvelope): void
    {
        $this->transactional(function () use ($event, $eventEnvelope) {
            $anchorPoints = $this->projectionContentGraph->getAnchorPointsForNodeAggregateInContentStream($event->nodeAggregateId, $event->contentStreamId);
            foreach ($anchorPoints as $anchorPoint) {
                $this->updateNodeRecordWithCopyOnWrite(
                    $event->contentStreamId,
                    $anchorPoint,
                    function (NodeRecord $node) use ($event, $eventEnvelope) {
                        $node->nodeTypeName = $event->newNodeTypeName;
                        $node->timestamps = $node->timestamps->with(
                            lastModified: $eventEnvelope->recordedAt,
                            originalLastModified: self::initiatingDateTime($eventEnvelope)
                        );
                    }
                );
            }
        });
    }

    private function updateNodeRecordWithCopyOnWrite(
        ContentStreamId $contentStreamIdWhereWriteOccurs,
        NodeRelationAnchorPoint $anchorPoint,
        callable $operations
    ): mixed {
        $contentStreamIds = $this->projectionContentGraph
            ->getAllContentStreamIdsAnchorPointIsContainedIn($anchorPoint);
        if (count($contentStreamIds) > 1) {
            // Copy on Write needed!
            // Copy on Write is a purely "Content Stream" related concept;
            // thus we do not care about different DimensionSpacePoints here (but we copy all edges)

            // 1) fetch node, adjust properties, assign new Relation Anchor Point
            /** @var NodeRecord $originalNode The anchor point appears in a content stream, so there must be a node */
            $originalNode = $this->projectionContentGraph->getNodeByAnchorPoint($anchorPoint);
            $copiedNode = NodeRecord::createCopyFromNodeRecord($this->getDatabaseConnection(), $this->tableNamePrefix, $originalNode);
            $result = $operations($copiedNode);
            $copiedNode->updateToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

            // 2) reconnect all edges belonging to this content stream to the new "copied node".
            // IMPORTANT: We need to reconnect BOTH the incoming and outgoing edges.
            $this->getDatabaseConnection()->executeStatement(
                '
                UPDATE ' . $this->tableNamePrefix . '_hierarchyrelation h
                    SET
                        -- if our (copied) node is the child, we update h.childNodeAnchor
                        h.childnodeanchor
                            = IF(h.childnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.childnodeanchor),

                        -- if our (copied) node is the parent, we update h.parentNodeAnchor
                        h.parentnodeanchor
                            = IF(h.parentnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.parentnodeanchor)
                    WHERE
                      :originalNodeAnchor IN (h.childnodeanchor, h.parentnodeanchor)
                      AND h.contentstreamid = :contentStreamId',
                [
                    'newNodeAnchor' => $copiedNode->relationAnchorPoint->value,
                    'originalNodeAnchor' => $anchorPoint->value,
                    'contentStreamId' => $contentStreamIdWhereWriteOccurs->value,
                ]
            );

            // reference relation rows need to be copied as well!
            $this->copyReferenceRelations(
                $anchorPoint,
                $copiedNode->relationAnchorPoint
            );
        } else {
            // No copy on write needed :)

            $node = $this->projectionContentGraph->getNodeByAnchorPoint($anchorPoint);
            if (!$node) {
                throw new \Exception("TODO NODE NOT FOUND - shall never happen");
            }

            $result = $operations($node);
            $node->updateToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
        }
        return $result;
    }


    private function copyReferenceRelations(
        NodeRelationAnchorPoint $sourceRelationAnchorPoint,
        NodeRelationAnchorPoint $destinationRelationAnchorPoint
    ): void {
        $this->getDatabaseConnection()->executeStatement('
                INSERT INTO ' . $this->tableNamePrefix . '_referencerelation (
                  nodeanchorpoint,
                  name,
                  position,
                  destinationnodeaggregateid
                )
                SELECT
                  :destinationRelationAnchorPoint AS nodeanchorpoint,
                  ref.name,
                  ref.position,
                  ref.destinationnodeaggregateid
                FROM
                    ' . $this->tableNamePrefix . '_referencerelation ref
                    WHERE ref.nodeanchorpoint = :sourceNodeAnchorPoint
            ', [
            'sourceNodeAnchorPoint' => $sourceRelationAnchorPoint->value,
            'destinationRelationAnchorPoint' => $destinationRelationAnchorPoint->value
        ]);
    }

    private function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event): void
    {
        $this->transactional(function () use ($event) {
            $this->dimensionSpacePointsRepository->insertDimensionSpacePoint($event->target);

            // the ordering is important - we first update the OriginDimensionSpacePoints, as we need the
            // hierarchy relations for this query. Then, we update the Hierarchy Relations.

            // 1) originDimensionSpacePoint on Node
            $rel = $this->getDatabaseConnection()->executeQuery(
                'SELECT n.relationanchorpoint, n.origindimensionspacepointhash
                     FROM ' . $this->tableNamePrefix . '_node n
                     INNER JOIN ' . $this->tableNamePrefix . '_hierarchyrelation h
                        ON h.childnodeanchor = n.relationanchorpoint

                     AND h.contentstreamid = :contentStreamId
                     AND h.dimensionspacepointhash = :dimensionSpacePointHash
                     -- find only nodes which have their ORIGIN at the source DimensionSpacePoint,
                     -- as we need to rewrite these origins (using copy on write)
                     AND n.origindimensionspacepointhash = :dimensionSpacePointHash
                ',
                [
                    'dimensionSpacePointHash' => $event->source->hash,
                    'contentStreamId' => $event->contentStreamId->value
                ]
            );
            while ($res = $rel->fetchAssociative()) {
                $relationAnchorPoint = NodeRelationAnchorPoint::fromInteger($res['relationanchorpoint']);
                $this->updateNodeRecordWithCopyOnWrite(
                    $event->contentStreamId,
                    $relationAnchorPoint,
                    function (NodeRecord $nodeRecord) use ($event) {
                        $nodeRecord->originDimensionSpacePoint = $event->target->coordinates;
                        $nodeRecord->originDimensionSpacePointHash = $event->target->hash;
                    }
                );
            }

            // 2) hierarchy relations
            $this->getDatabaseConnection()->executeStatement(
                '
                UPDATE ' . $this->tableNamePrefix . '_hierarchyrelation h
                    SET
                        h.dimensionspacepointhash = :newDimensionSpacePointHash
                    WHERE
                      h.dimensionspacepointhash = :originalDimensionSpacePointHash
                      AND h.contentstreamid = :contentStreamId
                      ',
                [
                    'originalDimensionSpacePointHash' => $event->source->hash,
                    'newDimensionSpacePointHash' => $event->target->hash,
                    'contentStreamId' => $event->contentStreamId->value
                ]
            );
        });
    }

    private function whenDimensionShineThroughWasAdded(DimensionShineThroughWasAdded $event): void
    {
        $this->transactional(function () use ($event) {
            $this->dimensionSpacePointsRepository->insertDimensionSpacePoint($event->target);

            // 1) hierarchy relations
            $this->getDatabaseConnection()->executeStatement(
                '
                INSERT INTO ' . $this->tableNamePrefix . '_hierarchyrelation (
                  parentnodeanchor,
                  childnodeanchor,
                  `name`,
                  position,
                  subtreetags,
                  dimensionspacepointhash,
                  contentstreamid
                )
                SELECT
                  h.parentnodeanchor,
                  h.childnodeanchor,
                  h.name,
                  h.position,
                  h.subtreetags,
                 :newDimensionSpacePointHash AS dimensionspacepointhash,
                  h.contentstreamid
                FROM
                    ' . $this->tableNamePrefix . '_hierarchyrelation h
                    WHERE h.contentstreamid = :contentStreamId
                    AND h.dimensionspacepointhash = :sourceDimensionSpacePointHash',
                [
                    'contentStreamId' => $event->contentStreamId->value,
                    'sourceDimensionSpacePointHash' => $event->source->hash,
                    'newDimensionSpacePointHash' => $event->target->hash,
                ]
            );
        });
    }

    /**
     * @throws \Throwable
     */
    private function transactional(\Closure $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
    }

    private function getDatabaseConnection(): Connection
    {
        return $this->dbalClient->getConnection();
    }

    private static function initiatingDateTime(EventEnvelope $eventEnvelope): \DateTimeImmutable
    {
        $initiatingTimestamp = $eventEnvelope->event->metadata?->get('initiatingTimestamp');
        $result = $initiatingTimestamp !== null ? \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $initiatingTimestamp) : $eventEnvelope->recordedAt;
        if (!$result instanceof \DateTimeImmutable) {
            throw new \RuntimeException(sprintf('Failed to extract initiating timestamp from event "%s"', $eventEnvelope->event->id->value), 1678902291);
        }
        return $result;
    }
}
