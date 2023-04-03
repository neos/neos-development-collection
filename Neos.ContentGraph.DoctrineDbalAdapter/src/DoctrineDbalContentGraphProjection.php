<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeDisabling;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeMove;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeRemoval;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeVariation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\RestrictionRelations;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\EventNormalizer;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Core\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
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
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\CatchUpHookInterface;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\WithMarkStaleInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\CatchUp\CatchUp;
use Neos\EventStore\DoctrineAdapter\DoctrineCheckpointStorage;
use Neos\EventStore\Model\Event;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStore\SetupResult;
use Neos\EventStore\Model\EventStream\EventStreamInterface;
use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * @implements ProjectionInterface<ContentGraph>
 * @internal but the graph projection is api
 */
final class DoctrineDbalContentGraphProjection implements ProjectionInterface, WithMarkStaleInterface
{
    use NodeVariation;
    use NodeDisabling;
    use RestrictionRelations;
    use NodeRemoval;
    use NodeMove;


    public const RELATION_DEFAULT_OFFSET = 128;

    /**
     * @var ContentGraph|null Cache for the content graph returned by {@see getState()},
     * so that always the same instance is returned
     */
    private ?ContentGraph $contentGraph = null;

    private DoctrineCheckpointStorage $checkpointStorage;

    public function __construct(
        private readonly EventNormalizer $eventNormalizer,
        private readonly DbalClientInterface $dbalClient,
        private readonly NodeFactory $nodeFactory,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly ProjectionContentGraph $projectionContentGraph,
        private readonly CatchUpHookFactoryInterface $catchUpHookFactory,
        private readonly string $tableNamePrefix,
    ) {
        $this->checkpointStorage = new DoctrineCheckpointStorage(
            $this->dbalClient->getConnection(),
            $this->tableNamePrefix . '_checkpoint',
            self::class
        );
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
        $this->setupTables();
        $this->checkpointStorage->setup();
    }

    private function setupTables(): SetupResult
    {
        $connection = $this->dbalClient->getConnection();
        $schemaManager = $connection->getSchemaManager();
        if (!$schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('Failed to retrieve Schema Manager', 1625653914);
        }

        $schema = (new DoctrineDbalContentGraphSchemaBuilder($this->tableNamePrefix))->buildSchema();

        $schemaDiff = (new Comparator())->compare($schemaManager->createSchema(), $schema);
        foreach ($schemaDiff->toSaveSql($connection->getDatabasePlatform()) as $statement) {
            $connection->executeStatement($statement);
        }
        return SetupResult::success('');
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
        $connection->executeQuery('TRUNCATE table ' . $this->tableNamePrefix . '_restrictionrelation');
    }

    public function canHandle(Event $event): bool
    {
        $eventClassName = $this->eventNormalizer->getEventClassName($event);
        return in_array($eventClassName, [
            RootNodeAggregateWithNodeWasCreated::class,
            RootNodeAggregateDimensionsWereUpdated::class,
            NodeAggregateWithNodeWasCreated::class,
            NodeAggregateNameWasChanged::class,
            ContentStreamWasForked::class,
            ContentStreamWasRemoved::class,
            NodePropertiesWereSet::class,
            NodeReferencesWereSet::class,
            NodeAggregateWasEnabled::class,
            NodeAggregateTypeWasChanged::class,
            DimensionSpacePointWasMoved::class,
            DimensionShineThroughWasAdded::class,
            NodeAggregateWasRemoved::class,
            NodeAggregateWasMoved::class,
            NodeSpecializationVariantWasCreated::class,
            NodeGeneralizationVariantWasCreated::class,
            NodePeerVariantWasCreated::class,
            NodeAggregateWasDisabled::class
        ]);
    }

    public function catchUp(EventStreamInterface $eventStream, ContentRepository $contentRepository): void
    {
        $catchUpHook = $this->catchUpHookFactory->build($contentRepository);
        $catchUpHook->onBeforeCatchUp();
        $catchUp = CatchUp::create(
            fn(EventEnvelope $eventEnvelope) => $this->apply($eventEnvelope, $catchUpHook),
            $this->checkpointStorage
        );
        $catchUp = $catchUp->withOnBeforeBatchCompleted(fn() => $catchUpHook->onBeforeBatchCompleted());
        $catchUp->run($eventStream);
        $catchUpHook->onAfterCatchUp();
    }

    private function apply(EventEnvelope $eventEnvelope, CatchUpHookInterface $catchUpHook): void
    {
        if (!$this->canHandle($eventEnvelope->event)) {
            return;
        }

        $eventInstance = $this->eventNormalizer->denormalize($eventEnvelope->event);

        $catchUpHook->onBeforeEvent($eventInstance, $eventEnvelope);

        if ($eventInstance instanceof RootNodeAggregateWithNodeWasCreated) {
            $this->whenRootNodeAggregateWithNodeWasCreated($eventInstance);
        } elseif ($eventInstance instanceof RootNodeAggregateDimensionsWereUpdated) {
            $this->whenRootNodeAggregateDimensionsWereUpdated($eventInstance);
        } elseif ($eventInstance instanceof NodeAggregateWithNodeWasCreated) {
            $this->whenNodeAggregateWithNodeWasCreated($eventInstance);
        } elseif ($eventInstance instanceof NodeAggregateNameWasChanged) {
            $this->whenNodeAggregateNameWasChanged($eventInstance);
        } elseif ($eventInstance instanceof ContentStreamWasForked) {
            $this->whenContentStreamWasForked($eventInstance);
        } elseif ($eventInstance instanceof ContentStreamWasRemoved) {
            $this->whenContentStreamWasRemoved($eventInstance);
        } elseif ($eventInstance instanceof NodePropertiesWereSet) {
            $this->whenNodePropertiesWereSet($eventInstance);
        } elseif ($eventInstance instanceof NodeReferencesWereSet) {
            $this->whenNodeReferencesWereSet($eventInstance);
        } elseif ($eventInstance instanceof NodeAggregateWasEnabled) {
            $this->whenNodeAggregateWasEnabled($eventInstance);
        } elseif ($eventInstance instanceof NodeAggregateTypeWasChanged) {
            $this->whenNodeAggregateTypeWasChanged($eventInstance);
        } elseif ($eventInstance instanceof DimensionSpacePointWasMoved) {
            $this->whenDimensionSpacePointWasMoved($eventInstance);
        } elseif ($eventInstance instanceof DimensionShineThroughWasAdded) {
            $this->whenDimensionShineThroughWasAdded($eventInstance);
        } elseif ($eventInstance instanceof NodeAggregateWasRemoved) {
            $this->whenNodeAggregateWasRemoved($eventInstance);
        } elseif ($eventInstance instanceof NodeAggregateWasMoved) {
            $this->whenNodeAggregateWasMoved($eventInstance);
        } elseif ($eventInstance instanceof NodeSpecializationVariantWasCreated) {
            $this->whenNodeSpecializationVariantWasCreated($eventInstance);
        } elseif ($eventInstance instanceof NodeGeneralizationVariantWasCreated) {
            $this->whenNodeGeneralizationVariantWasCreated($eventInstance);
        } elseif ($eventInstance instanceof NodePeerVariantWasCreated) {
            $this->whenNodePeerVariantWasCreated($eventInstance);
        } elseif ($eventInstance instanceof NodeAggregateWasDisabled) {
            $this->whenNodeAggregateWasDisabled($eventInstance);
        } else {
            throw new \RuntimeException('Not supported: ' . get_class($eventInstance));
        }

        $catchUpHook->onAfterEvent($eventInstance, $eventEnvelope);
    }

    public function getSequenceNumber(): SequenceNumber
    {
        return $this->checkpointStorage->getHighestAppliedSequenceNumber();
    }

    public function getState(): ContentGraph
    {
        if (!$this->contentGraph) {
            $this->contentGraph = new ContentGraph(
                $this->dbalClient,
                $this->nodeFactory,
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
    private function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event): void
    {
        $nodeRelationAnchorPoint = NodeRelationAnchorPoint::create();
        $originDimensionSpacePoint = OriginDimensionSpacePoint::fromArray([]);
        $node = new NodeRecord(
            $nodeRelationAnchorPoint,
            $event->nodeAggregateId,
            $originDimensionSpacePoint->coordinates,
            $originDimensionSpacePoint->hash,
            SerializedPropertyValues::fromArray([]),
            $event->nodeTypeName,
            $event->nodeAggregateClassification
        );

        $this->transactional(function () use ($node, $event) {
            $node->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
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
                OriginDimensionSpacePoint::fromArray([]),
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
    private function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
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
                $event->nodeName
            );

            $this->connectRestrictionRelationsFromParentNodeToNewlyCreatedNode(
                $event->contentStreamId,
                $event->parentNodeAggregateId,
                $event->nodeAggregateId,
                $event->coveredDimensionSpacePoints
            );
        });
    }

    /**
     * @throws \Throwable
     */
    private function whenNodeAggregateNameWasChanged(NodeAggregateNameWasChanged $event): void
    {
        $this->transactional(function () use ($event) {
            $this->getDatabaseConnection()->executeUpdate('
                UPDATE ' . $this->tableNamePrefix . '_hierarchyrelation h
                inner join ' . $this->tableNamePrefix . '_node n on
                    h.childnodeanchor = n.relationanchorpoint
                SET
                  h.name = :newName
                WHERE
                    n.nodeaggregateid = :nodeAggregateId
                    and h.contentstreamid = :contentStreamId
            ', [
                'newName' => $event->newNodeName->value,
                'nodeAggregateId' => $event->nodeAggregateId->value,
                'contentStreamId' => $event->contentStreamId->value
            ]);
        });
    }

    /**
     * Copy the restriction edges from the parent Node to the newly created child node;
     * so that newly created nodes inherit the visibility constraints of the parent.
     * @throws \Doctrine\DBAL\DBALException
     */
    private function connectRestrictionRelationsFromParentNodeToNewlyCreatedNode(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId,
        NodeAggregateId $newlyCreatedNodeAggregateId,
        DimensionSpacePointSet $dimensionSpacePointsInWhichNewlyCreatedNodeAggregateIsVisible
    ): void {
        // TODO: still unsure why we need an "INSERT IGNORE" here;
        // normal "INSERT" can trigger a duplicate key constraint exception
        $this->getDatabaseConnection()->executeUpdate('
                INSERT IGNORE INTO ' . $this->tableNamePrefix . '_restrictionrelation (
                  contentstreamid,
                  dimensionspacepointhash,
                  originnodeaggregateid,
                  affectednodeaggregateid
                )
                SELECT
                  r.contentstreamid,
                  r.dimensionspacepointhash,
                  r.originnodeaggregateid,
                  "' . $newlyCreatedNodeAggregateId->value . '" as affectednodeaggregateid
                FROM
                    ' . $this->tableNamePrefix . '_restrictionrelation r
                    WHERE
                        r.contentstreamid = :sourceContentStreamId
                        and r.dimensionspacepointhash IN (:visibleDimensionSpacePoints)
                        and r.affectednodeaggregateid = :parentNodeAggregateId
            ', [
            'sourceContentStreamId' => $contentStreamId->value,
            'visibleDimensionSpacePoints' => $dimensionSpacePointsInWhichNewlyCreatedNodeAggregateIsVisible
                ->getPointHashes(),
            'parentNodeAggregateId' => $parentNodeAggregateId->value
        ], [
            'visibleDimensionSpacePoints' => Connection::PARAM_STR_ARRAY
        ]);
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
        NodeAggregateId $succeedingSiblingNodeAggregateId = null,
        NodeName $nodeName = null
    ): void {
        $nodeRelationAnchorPoint = NodeRelationAnchorPoint::create();
        $node = new NodeRecord(
            $nodeRelationAnchorPoint,
            $nodeAggregateId,
            $originDimensionSpacePoint->jsonSerialize(),
            $originDimensionSpacePoint->hash,
            $propertyDefaultValuesAndTypes,
            $nodeTypeName,
            $nodeAggregateClassification,
            $nodeName
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
                        $nodeRelationAnchorPoint,
                        new DimensionSpacePointSet([$dimensionSpacePoint]),
                        $succeedingSibling?->relationAnchorPoint,
                        $nodeName
                    );
                }
            }
        }

        $node->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
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
    protected function connectHierarchy(
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

            $hierarchyRelation = new HierarchyRelation(
                $parentNodeAnchorPoint,
                $childNodeAnchorPoint,
                $relationName,
                $contentStreamId,
                $dimensionSpacePoint,
                $dimensionSpacePoint->hash,
                $position
            );

            $hierarchyRelation->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
        }
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
    protected function getRelationPosition(
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
    protected function getRelationPositionAfterRecalculation(
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
    public function whenContentStreamWasForked(ContentStreamWasForked $event): void
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
                  dimensionspacepoint,
                  dimensionspacepointhash,
                  contentstreamid
                )
                SELECT
                  h.parentnodeanchor,
                  h.childnodeanchor,
                  h.name,
                  h.position,
                  h.dimensionspacepoint,
                  h.dimensionspacepointhash,
                  "' . $event->newContentStreamId->value . '" AS contentstreamid
                FROM
                    ' . $this->tableNamePrefix . '_hierarchyrelation h
                    WHERE h.contentstreamid = :sourceContentStreamId
            ', [
                'sourceContentStreamId' => $event->sourceContentStreamId->value
            ]);

            //
            // 2) copy Hidden Node information to second content stream
            //
            $this->getDatabaseConnection()->executeUpdate('
                INSERT INTO ' . $this->tableNamePrefix . '_restrictionrelation (
                  contentstreamid,
                  dimensionspacepointhash,
                  originnodeaggregateid,
                  affectednodeaggregateid
                )
                SELECT
                  "' . $event->newContentStreamId->value . '" AS contentstreamid,
                  r.dimensionspacepointhash,
                  r.originnodeaggregateid,
                  r.affectednodeaggregateid
                FROM
                    ' . $this->tableNamePrefix . '_restrictionrelation r
                    WHERE r.contentstreamid = :sourceContentStreamId
            ', [
                'sourceContentStreamId' => $event->sourceContentStreamId->value
            ]);

            // NOTE: as reference edges are attached to Relation Anchor Points (and they are lazily copy-on-written),
            // we do not need to copy reference edges here (but we need to do it during copy on write).
        });
    }

    public function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
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

            // Drop restriction relations
            $this->getDatabaseConnection()->executeUpdate('
                DELETE FROM ' . $this->tableNamePrefix . '_restrictionrelation
                WHERE
                    contentstreamid = :contentStreamId
            ', [
                'contentStreamId' => $event->contentStreamId->value
            ]);
        });
    }

    /**
     * @throws \Throwable
     */
    public function whenNodePropertiesWereSet(NodePropertiesWereSet $event): void
    {
        $this->transactional(function () use ($event) {
            $this->updateNodeWithCopyOnWrite($event, function (NodeRecord $node) use ($event) {
                $node->properties = $node->properties->merge($event->propertyValues);
            });
        });
    }

    /**
     * @throws \Throwable
     */
    public function whenNodeReferencesWereSet(NodeReferencesWereSet $event): void
    {
        $this->transactional(function () use ($event) {
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
                    function (NodeRecord $node) {
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
                    'nodeanchorpoint' => $nodeAnchorPoint,
                    'name' => $event->referenceName
                ]);

                // set new
                $position = 0;
                /** @var SerializedNodeReference $reference */
                foreach ($event->references as $reference) {
                    $this->getDatabaseConnection()->insert($this->tableNamePrefix . '_referencerelation', [
                        'name' => $event->referenceName->value,
                        'position' => $position,
                        'nodeanchorpoint' => $nodeAnchorPoint->value,
                        'destinationnodeaggregateid' => $reference->targetNodeAggregateId->value,
                        'properties' => $reference->properties
                            ? \json_encode($reference->properties, JSON_THROW_ON_ERROR)
                            : null
                    ]);
                    $position++;
                }
            }
        });
    }

    private function cascadeRestrictionRelations(
        ContentStreamId $contentStreamId,
        NodeAggregateId $parentNodeAggregateId,
        NodeAggregateId $entryNodeAggregateId,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        $this->getDatabaseConnection()->executeUpdate(
            '
            -- GraphProjector::cascadeRestrictionRelations
            INSERT INTO ' . $this->tableNamePrefix . '_restrictionrelation
            (
              contentstreamid,
              dimensionspacepointhash,
              originnodeaggregateid,
              affectednodeaggregateid
            )
            -- we build a recursive tree
            with recursive tree as (
                 -- --------------------------------
                 -- INITIAL query: select the nodes of the given entry node aggregate as roots of the tree
                 -- --------------------------------
                 select
                    n.relationanchorpoint,
                    n.nodeaggregateid,
                    h.dimensionspacepointhash
                 from
                    ' . $this->tableNamePrefix . '_node n
                 -- we need to join with the hierarchy relation, because we need the dimensionspacepointhash.
                 inner join ' . $this->tableNamePrefix . '_hierarchyrelation h
                    on h.childnodeanchor = n.relationanchorpoint
                 where
                    n.nodeaggregateid = :entryNodeAggregateId
                    and h.contentstreamid = :contentStreamId
                    and h.dimensionspacepointhash in (:dimensionSpacePointHashes)
            union
                 -- --------------------------------
                 -- RECURSIVE query: do one "child" query step
                 -- --------------------------------
                 select
                    c.relationanchorpoint,
                    c.nodeaggregateid,
                    h.dimensionspacepointhash
                 from
                    tree p
                 inner join ' . $this->tableNamePrefix . '_hierarchyrelation h
                    on h.parentnodeanchor = p.relationanchorpoint
                 inner join ' . $this->tableNamePrefix . '_node c
                    on h.childnodeanchor = c.relationanchorpoint
                 where
                    h.contentstreamid = :contentStreamId
                    and h.dimensionspacepointhash in (:dimensionSpacePointHashes)
            )

                 -- --------------------------------
                 -- create new restriction relations...
                 -- --------------------------------
            SELECT
                "' . $contentStreamId->value . '" as contentstreamid,
                tree.dimensionspacepointhash,
                originnodeaggregateid,
                tree.nodeaggregateid as affectednodeaggregateid
            FROM tree
                 -- --------------------------------
                 -- ...by joining the tree with all restriction relations ingoing to the given parent
                 -- --------------------------------
                INNER JOIN (
                    SELECT originnodeaggregateid FROM ' . $this->tableNamePrefix . '_restrictionrelation
                        WHERE contentstreamid = :contentStreamId
                        AND affectednodeaggregateid = :parentNodeAggregateId
                        AND dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)
                ) AS joinedrestrictingancestors
            ',
            [
                'contentStreamId' => $contentStreamId->value,
                'parentNodeAggregateId' => $parentNodeAggregateId->value,
                'entryNodeAggregateId' => $entryNodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'affectedDimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes()
            ],
            [
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY,
                'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
            ]
        );
    }

    /**
     * @throws \Throwable
     */
    public function whenNodeAggregateWasEnabled(NodeAggregateWasEnabled $event): void
    {
        $this->transactional(function () use ($event) {
            $this->removeOutgoingRestrictionRelationsOfNodeAggregateInDimensionSpacePoints(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->affectedDimensionSpacePoints
            );
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
        $copy = new HierarchyRelation(
            $newParent ?: $sourceHierarchyRelation->parentNodeAnchor,
            $newChild ?: $sourceHierarchyRelation->childNodeAnchor,
            $sourceHierarchyRelation->name,
            $contentStreamId,
            $dimensionSpacePoint,
            $dimensionSpacePoint->hash,
            $this->getRelationPosition(
                $newParent ?: $sourceHierarchyRelation->parentNodeAnchor,
                $newChild ?: $sourceHierarchyRelation->childNodeAnchor,
                null, // todo: find proper sibling
                $contentStreamId,
                $dimensionSpacePoint
            )
        );
        $copy->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

        return $copy;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function copyNodeToDimensionSpacePoint(
        NodeRecord $sourceNode,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): NodeRecord {
        $copyRelationAnchorPoint = NodeRelationAnchorPoint::create();
        $copy = new NodeRecord(
            $copyRelationAnchorPoint,
            $sourceNode->nodeAggregateId,
            $originDimensionSpacePoint->coordinates,
            $originDimensionSpacePoint->hash,
            $sourceNode->properties,
            $sourceNode->nodeTypeName,
            $sourceNode->classification,
            $sourceNode->nodeName
        );
        $copy->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

        return $copy;
    }

    public function whenNodeAggregateTypeWasChanged(NodeAggregateTypeWasChanged $event): void
    {
        $this->transactional(function () use ($event) {
            $anchorPoints = $this->projectionContentGraph->getAnchorPointsForNodeAggregateInContentStream(
                $event->nodeAggregateId,
                $event->contentStreamId
            );

            foreach ($anchorPoints as $anchorPoint) {
                $this->updateNodeRecordWithCopyOnWrite(
                    $event->contentStreamId,
                    $anchorPoint,
                    function (NodeRecord $node) use ($event) {
                        $node->nodeTypeName = $event->newNodeTypeName;
                    }
                );
            }
        });
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    protected function updateNodeWithCopyOnWrite(EventInterface $event, callable $operations): mixed
    {
        if (
            method_exists($event, 'getNodeAggregateId')
            && method_exists($event, 'getOriginDimensionSpacePoint')
            && method_exists($event, 'getContentStreamId')
        ) {
            $anchorPoint = $this->projectionContentGraph
                ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                    $event->getNodeAggregateId(),
                    $event->getOriginDimensionSpacePoint(),
                    $event->getContentStreamId()
                );
        } else {
            throw new \InvalidArgumentException(
                'Cannot update node with copy on write for events of type '
                    . get_class($event) . ' since they provide no NodeAggregateId, '
                    . 'OriginDimensionSpacePoint or ContentStreamId',
                1645303167
            );
        }

        if (is_null($anchorPoint)) {
            throw new \InvalidArgumentException(
                'Cannot update node with copy on write since no anchor point could be resolved for node '
                . $event->getNodeAggregateId() . ' in content stream '
                . $event->getContentStreamId(),
                1645303332
            );
        }

        return $this->updateNodeRecordWithCopyOnWrite(
            $event->getContentStreamId(),
            $anchorPoint,
            $operations
        );
    }

    protected function updateNodeRecordWithCopyOnWrite(
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
            /** @var NodeRecord $copiedNode The anchor point appears in a content stream, so there must be a node */
            $copiedNode = $this->projectionContentGraph->getNodeByAnchorPoint($anchorPoint);
            $copiedNode->relationAnchorPoint = NodeRelationAnchorPoint::create();
            $result = $operations($copiedNode);
            $copiedNode->addToDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

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


    protected function copyReferenceRelations(
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

    public function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event): void
    {
        $this->transactional(function () use ($event) {
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
                $relationAnchorPoint = NodeRelationAnchorPoint::fromString($res['relationanchorpoint']);
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
                        h.dimensionspacepoint = :newDimensionSpacePoint,
                        h.dimensionspacepointhash = :newDimensionSpacePointHash
                    WHERE
                      h.dimensionspacepointhash = :originalDimensionSpacePointHash
                      AND h.contentstreamid = :contentStreamId
                      ',
                [
                    'originalDimensionSpacePointHash' => $event->source->hash,
                    'newDimensionSpacePointHash' => $event->target->hash,
                    'newDimensionSpacePoint' => $event->target->toJson(),
                    'contentStreamId' => $event->contentStreamId->value
                ]
            );

            // 3) restriction relations
            $this->getDatabaseConnection()->executeStatement(
                '
                UPDATE ' . $this->tableNamePrefix . '_restrictionrelation r
                    SET
                        r.dimensionspacepointhash = :newDimensionSpacePointHash
                    WHERE
                      r.dimensionspacepointhash = :originalDimensionSpacePointHash
                      AND r.contentstreamid = :contentStreamId
                      ',
                [
                    'originalDimensionSpacePointHash' => $event->source->hash,
                    'newDimensionSpacePointHash' => $event->target->hash,
                    'contentStreamId' => $event->contentStreamId->value
                ]
            );
        });
    }

    public function whenDimensionShineThroughWasAdded(DimensionShineThroughWasAdded $event): void
    {
        $this->transactional(function () use ($event) {
            // 1) hierarchy relations
            $this->getDatabaseConnection()->executeStatement(
                '
                INSERT INTO ' . $this->tableNamePrefix . '_hierarchyrelation (
                  parentnodeanchor,
                  childnodeanchor,
                  `name`,
                  position,
                  dimensionspacepoint,
                  dimensionspacepointhash,
                  contentstreamid
                )
                SELECT
                  h.parentnodeanchor,
                  h.childnodeanchor,
                  h.name,
                  h.position,
                 :newDimensionSpacePoint AS dimensionspacepoint,
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
                    'newDimensionSpacePoint' => $event->target->toJson(),
                ]
            );

            // 2) restriction relations
            $this->getDatabaseConnection()->executeUpdate('
                INSERT INTO ' . $this->tableNamePrefix . '_restrictionrelation (
                  contentstreamid,
                  dimensionspacepointhash,
                  originnodeaggregateid,
                  affectednodeaggregateid
                )
                SELECT
                  r.contentstreamid,
                  :targetDimensionSpacePointHash,
                  r.originnodeaggregateid,
                  r.affectednodeaggregateid
                FROM
                    ' . $this->tableNamePrefix . '_restrictionrelation r
                    WHERE r.contentstreamid = :contentStreamId
                    AND r.dimensionspacepointhash = :sourceDimensionSpacePointHash

            ', [
                'contentStreamId' => $event->contentStreamId->value,
                'sourceDimensionSpacePointHash' => $event->source->hash,
                'targetDimensionSpacePointHash' => $event->target->hash
            ]);
        });
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
        return $this->dbalClient->getConnection();
    }
}
