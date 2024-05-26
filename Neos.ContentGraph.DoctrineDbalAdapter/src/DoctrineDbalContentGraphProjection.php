<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeMove;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeRemoval;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeVariation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\DimensionSpacePointsRepository;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\ContentGraphFinder;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
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
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaDiff;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Projection\WithMarkStaleInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\DbalTools\CheckpointHelper;
use Neos\ContentRepository\DbalTools\DbalSchemaDiff;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @implements ProjectionInterface<ContentGraphFinder>
 * @internal but the graph projection is api
 */
final class DoctrineDbalContentGraphProjection implements ProjectionInterface, WithMarkStaleInterface
{
    use NodeVariation;
    use SubtreeTagging;
    use NodeRemoval;
    use NodeMove;


    public const RELATION_DEFAULT_OFFSET = 128;

    public function __construct(
        private readonly Connection $dbal,
        private readonly ProjectionContentGraph $projectionContentGraph,
        private readonly ContentGraphTableNames $tableNames,
        private readonly DimensionSpacePointsRepository $dimensionSpacePointsRepository,
        private readonly ContentGraphFinder $contentGraphFinder
    ) {
    }

    protected function getProjectionContentGraph(): ProjectionContentGraph
    {
        return $this->projectionContentGraph;
    }

    public function setUp(): void
    {
        foreach ($this->determineRequiredSqlStatements() as $statement) {
            $this->dbal->executeStatement($statement);
        }
    }

    /**
     * @return array<string>
     */
    private function determineRequiredSqlStatements(): array
    {
        $schemaManager = $this->dbal->getSchemaManager();
        if (!$schemaManager instanceof AbstractSchemaManager) {
            throw new \RuntimeException('Failed to retrieve Schema Manager', 1625653914);
        }
        $schema = (new DoctrineDbalContentGraphSchemaBuilder($this->tableNames))->buildSchema($schemaManager);
        return DbalSchemaDiff::determineRequiredSqlStatements($this->dbal, $schema);
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

    public function reset(): void
    {
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->node());
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->hierarchyRelation());
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->referenceRelation());
        $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->dimensionSpacePoints());
        CheckpointHelper::resetCheckpoint($this->dbal, $this->tableNames->checkpoint());
        $this->getState()->forgetInstances();
    }

    public function markStale(): void
    {
        $this->getState()->forgetInstances();
    }


    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        $this->dbal->beginTransaction();
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
            default => null,
        };
        CheckpointHelper::updateCheckpoint($this->dbal, $this->tableNames->checkpoint(), $eventEnvelope->sequenceNumber);
        $this->dbal->commit();
    }

    public function getCheckpoint(): SequenceNumber
    {
        return CheckpointHelper::getCheckpoint($this->dbal, $this->tableNames->checkpoint());
    }

    public function getState(): ContentGraphFinder
    {
        return $this->contentGraphFinder;
    }

    /**
     * @throws \Throwable
     */
    private function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $originDimensionSpacePoint = OriginDimensionSpacePoint::createWithoutDimensions();
        $node = NodeRecord::createNewInDatabase(
            $this->getDatabaseConnection(),
            $this->tableNames,
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

        // delete all hierarchy edges of the root node
        $this->dbal->executeUpdate('
            DELETE FROM ' . $this->tableNames->hierarchyRelation() . '
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
    }

    /**
     * @throws \Throwable
     */
    private function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->createNodeWithHierarchy(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->nodeTypeName,
            $event->parentNodeAggregateId,
            $event->originDimensionSpacePoint,
            $event->succeedingSiblingsForCoverage,
            $event->initialPropertyValues,
            $event->nodeAggregateClassification,
            $event->nodeName,
            $eventEnvelope,
        );
    }

    /**
     * @throws \Throwable
     */
    private function whenNodeAggregateNameWasChanged(NodeAggregateNameWasChanged $event, EventEnvelope $eventEnvelope): void
    {
        foreach (
            $this->projectionContentGraph->getAnchorPointsForNodeAggregateInContentStream(
                $event->nodeAggregateId,
                $event->contentStreamId,
            ) as $anchorPoint
        ) {
            $this->updateNodeRecordWithCopyOnWrite(
                $event->contentStreamId,
                $anchorPoint,
                function (NodeRecord $node) use ($event, $eventEnvelope) {
                    $node->nodeName = $event->newNodeName;
                    $node->timestamps = $node->timestamps->with(
                        lastModified: $eventEnvelope->recordedAt,
                        originalLastModified: self::initiatingDateTime($eventEnvelope)
                    );
                }
            );
        }
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
        InterdimensionalSiblings $coverageSucceedingSiblings,
        SerializedPropertyValues $propertyDefaultValuesAndTypes,
        NodeAggregateClassification $nodeAggregateClassification,
        ?NodeName $nodeName,
        EventEnvelope $eventEnvelope,
    ): void {
        $node = NodeRecord::createNewInDatabase(
            $this->dbal,
            $this->tableNames,
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
        $missingParentRelations = $coverageSucceedingSiblings->toDimensionSpacePointSet()->points;

        if (!empty($missingParentRelations)) {
            // add yet missing parent relations

            foreach ($missingParentRelations as $dimensionSpacePoint) {
                $parentNode = $this->projectionContentGraph->findNodeInAggregate(
                    $contentStreamId,
                    $parentNodeAggregateId,
                    $dimensionSpacePoint
                );

                $succeedingSiblingNodeAggregateId = $coverageSucceedingSiblings->getSucceedingSiblingIdForDimensionSpacePoint($dimensionSpacePoint);
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
                    );
                }
            }
        }
    }

    /**
     * @param NodeRelationAnchorPoint $parentNodeAnchorPoint
     * @param NodeRelationAnchorPoint $childNodeAnchorPoint
     * @param NodeRelationAnchorPoint|null $succeedingSiblingNodeAnchorPoint
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
                $contentStreamId,
                $dimensionSpacePoint,
                $dimensionSpacePoint->hash,
                $position,
                $inheritedSubtreeTags,
            );

            $hierarchyRelation->addToDatabase($this->dbal, $this->tableNames);
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
            $relation->assignNewPosition($offset, $this->dbal, $this->tableNames);
        }

        return $position;
    }

    /**
     * @throws \Throwable
     */
    private function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        //
        // 1) Copy HIERARCHY RELATIONS (this is the MAIN OPERATION here)
        //
        $this->dbal->executeUpdate('
            INSERT INTO ' . $this->tableNames->hierarchyRelation() . ' (
              parentnodeanchor,
              childnodeanchor,

              position,
              dimensionspacepointhash,
              subtreetags,
              contentstreamid
            )
            SELECT
              h.parentnodeanchor,
              h.childnodeanchor,

              h.position,
              h.dimensionspacepointhash,
              h.subtreetags,
              "' . $event->newContentStreamId->value . '" AS contentstreamid
            FROM
                ' . $this->tableNames->hierarchyRelation() . ' h
                WHERE h.contentstreamid = :sourceContentStreamId
        ', [
            'sourceContentStreamId' => $event->sourceContentStreamId->value
        ]);
        // NOTE: as reference edges are attached to Relation Anchor Points (and they are lazily copy-on-written),
        // we do not need to copy reference edges here (but we need to do it during copy on write).
    }

    private function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
    {
        // Drop hierarchy relations
        $this->getDatabaseConnection()->executeUpdate('
            DELETE FROM ' . $this->tableNames->hierarchyRelation() . '
            WHERE
                contentstreamid = :contentStreamId
        ', [
            'contentStreamId' => $event->contentStreamId->value
        ]);

        // Drop non-referenced nodes (which do not have a hierarchy relation anymore)
        $this->getDatabaseConnection()->executeUpdate('
            DELETE FROM ' . $this->tableNames->node() . '
            WHERE NOT EXISTS
                (
                    SELECT 1 FROM ' . $this->tableNames->hierarchyRelation() . '
                    WHERE ' . $this->tableNames->hierarchyRelation() . '.childnodeanchor
                              = ' . $this->tableNames->node() . '.relationanchorpoint
                )
        ');

        // Drop non-referenced reference relations (i.e. because the referenced nodes are gone by now)
        $this->getDatabaseConnection()->executeUpdate('
            DELETE FROM ' . $this->tableNames->referenceRelation() . '
            WHERE NOT EXISTS
                (
                    SELECT 1 FROM ' . $this->tableNames->node() . '
                    WHERE ' . $this->tableNames->node() . '.relationanchorpoint
                              = ' . $this->tableNames->referenceRelation() . '.nodeanchorpoint
                )
        ');
    }

    /**
     * @throws \Throwable
     */
    private function whenNodePropertiesWereSet(NodePropertiesWereSet $event, EventEnvelope $eventEnvelope): void
    {
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
    }

    /**
     * @throws \Throwable
     */
    private function whenNodeReferencesWereSet(NodeReferencesWereSet $event, EventEnvelope $eventEnvelope): void
    {
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
            $this->getDatabaseConnection()->delete($this->tableNames->referenceRelation(), [
                'nodeanchorpoint' => $nodeAnchorPoint?->value,
                'name' => $event->referenceName->value
            ]);

            // set new
            $position = 0;
            /** @var SerializedNodeReference $reference */
            foreach ($event->references as $reference) {
                $this->getDatabaseConnection()->insert($this->tableNames->referenceRelation(), [
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
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function copyHierarchyRelationToDimensionSpacePoint(
        HierarchyRelation $sourceHierarchyRelation,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $newParent,
        NodeRelationAnchorPoint $newChild,
        ?NodeRelationAnchorPoint $newSucceedingSibling = null,
    ): HierarchyRelation {
        $parentSubtreeTags = $this->subtreeTagsForHierarchyRelation($contentStreamId, $newParent, $dimensionSpacePoint);
        $inheritedSubtreeTags = NodeTags::create($sourceHierarchyRelation->subtreeTags->withoutInherited()->all(), $parentSubtreeTags->withoutInherited()->all());
        $copy = new HierarchyRelation(
            $newParent,
            $newChild,
            $contentStreamId,
            $dimensionSpacePoint,
            $dimensionSpacePoint->hash,
            $this->getRelationPosition(
                $newParent,
                $newChild,
                $newSucceedingSibling,
                $contentStreamId,
                $dimensionSpacePoint
            ),
            $inheritedSubtreeTags,
        );
        $copy->addToDatabase($this->getDatabaseConnection(), $this->tableNames);

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
            $this->tableNames,
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
            $copiedNode = NodeRecord::createCopyFromNodeRecord($this->getDatabaseConnection(), $this->tableNames, $originalNode);
            $result = $operations($copiedNode);
            $copiedNode->updateToDatabase($this->getDatabaseConnection(), $this->tableNames);

            // 2) reconnect all edges belonging to this content stream to the new "copied node".
            // IMPORTANT: We need to reconnect BOTH the incoming and outgoing edges.
            $this->getDatabaseConnection()->executeStatement(
                '
                UPDATE ' . $this->tableNames->hierarchyRelation() . ' h
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
            $node->updateToDatabase($this->getDatabaseConnection(), $this->tableNames);
        }
        return $result;
    }


    private function copyReferenceRelations(
        NodeRelationAnchorPoint $sourceRelationAnchorPoint,
        NodeRelationAnchorPoint $destinationRelationAnchorPoint
    ): void {
        $this->getDatabaseConnection()->executeStatement('
                INSERT INTO ' . $this->tableNames->referenceRelation() . ' (
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
                    ' . $this->tableNames->referenceRelation() . ' ref
                    WHERE ref.nodeanchorpoint = :sourceNodeAnchorPoint
            ', [
            'sourceNodeAnchorPoint' => $sourceRelationAnchorPoint->value,
            'destinationRelationAnchorPoint' => $destinationRelationAnchorPoint->value
        ]);
    }

    private function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event): void
    {
        $this->dimensionSpacePointsRepository->insertDimensionSpacePoint($event->target);

        // the ordering is important - we first update the OriginDimensionSpacePoints, as we need the
        // hierarchy relations for this query. Then, we update the Hierarchy Relations.

        // 1) originDimensionSpacePoint on Node
        $rel = $this->getDatabaseConnection()->executeQuery(
            'SELECT n.relationanchorpoint, n.origindimensionspacepointhash
                 FROM ' . $this->tableNames->node() . ' n
                 INNER JOIN ' . $this->tableNames->hierarchyRelation() . ' h
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
            UPDATE ' . $this->tableNames->hierarchyRelation() . ' h
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
    }

    private function whenDimensionShineThroughWasAdded(DimensionShineThroughWasAdded $event): void
    {
        $this->dimensionSpacePointsRepository->insertDimensionSpacePoint($event->target);

        // 1) hierarchy relations
        $this->getDatabaseConnection()->executeStatement(
            '
            INSERT INTO ' . $this->tableNames->hierarchyRelation() . ' (
              parentnodeanchor,
              childnodeanchor,
              position,
              subtreetags,
              dimensionspacepointhash,
              contentstreamid
            )
            SELECT
              h.parentnodeanchor,
              h.childnodeanchor,
              h.position,
              h.subtreetags,
             :newDimensionSpacePointHash AS dimensionspacepointhash,
              h.contentstreamid
            FROM
                ' . $this->tableNames->hierarchyRelation() . ' h
                WHERE h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :sourceDimensionSpacePointHash',
            [
                'contentStreamId' => $event->contentStreamId->value,
                'sourceDimensionSpacePointHash' => $event->source->hash,
                'newDimensionSpacePointHash' => $event->target->hash,
            ]
        );
    }

    private function getDatabaseConnection(): Connection
    {
        return $this->dbal;
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
