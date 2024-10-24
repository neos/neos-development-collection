<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\ContentStream;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeMove;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeRemoval;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeVariation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\Workspace;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\DimensionSpacePointsRepository;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamId;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasReopened;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
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
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Event\WorkspaceWasCreated;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceBaseWorkspaceWasChanged;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Event\WorkspaceWasRemoved;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyDiscarded;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPartiallyPublished;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\Infrastructure\DbalCheckpointStorage;
use Neos\ContentRepository\Core\Infrastructure\DbalSchemaDiff;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\CheckpointStorageStatusType;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionInterface;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamStatus;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @internal but the graph projection is api
 */
final class DoctrineDbalContentGraphProjection implements ContentGraphProjectionInterface
{
    use ContentStream;
    use NodeMove;
    use NodeRemoval;
    use NodeVariation;
    use SubtreeTagging;
    use Workspace;


    public const RELATION_DEFAULT_OFFSET = 128;

    private DbalCheckpointStorage $checkpointStorage;

    public function __construct(
        private readonly Connection $dbal,
        private readonly ProjectionContentGraph $projectionContentGraph,
        private readonly ContentGraphTableNames $tableNames,
        private readonly DimensionSpacePointsRepository $dimensionSpacePointsRepository,
        private readonly ContentGraphReadModelInterface $contentGraphReadModel
    ) {
        $this->checkpointStorage = new DbalCheckpointStorage(
            $this->dbal,
            $this->tableNames->checkpoint(),
            self::class
        );
    }

    public function setUp(): void
    {
        $statements = $this->determineRequiredSqlStatements();

        foreach ($statements as $statement) {
            try {
                $this->dbal->executeStatement($statement);
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf('Failed to setup projection %s: %s', self::class, $e->getMessage()), 1716478255, $e);
            }
        }
        $this->checkpointStorage->setUp();
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
        return ProjectionStatus::ok();
    }

    public function reset(): void
    {
        $this->truncateDatabaseTables();

        $this->checkpointStorage->acquireLock();
        $this->checkpointStorage->updateAndReleaseLock(SequenceNumber::none());
    }

    public function getCheckpointStorage(): DbalCheckpointStorage
    {
        return $this->checkpointStorage;
    }

    public function getState(): ContentGraphReadModelInterface
    {
        return $this->contentGraphReadModel;
    }

    public function canHandle(EventInterface $event): bool
    {
        return in_array($event::class, [
            ContentStreamWasClosed::class,
            ContentStreamWasCreated::class,
            ContentStreamWasForked::class,
            ContentStreamWasRemoved::class,
            ContentStreamWasReopened::class,
            DimensionShineThroughWasAdded::class,
            DimensionSpacePointWasMoved::class,
            NodeAggregateNameWasChanged::class,
            NodeAggregateTypeWasChanged::class,
            NodeAggregateWasMoved::class,
            NodeAggregateWasRemoved::class,
            NodeAggregateWithNodeWasCreated::class,
            NodeGeneralizationVariantWasCreated::class,
            NodePeerVariantWasCreated::class,
            NodePropertiesWereSet::class,
            NodeReferencesWereSet::class,
            NodeSpecializationVariantWasCreated::class,
            RootNodeAggregateDimensionsWereUpdated::class,
            RootNodeAggregateWithNodeWasCreated::class,
            RootWorkspaceWasCreated::class,
            SubtreeWasTagged::class,
            SubtreeWasUntagged::class,
            WorkspaceBaseWorkspaceWasChanged::class,
            WorkspaceRebaseFailed::class,
            WorkspaceWasCreated::class,
            WorkspaceWasDiscarded::class,
            WorkspaceWasPartiallyDiscarded::class,
            WorkspaceWasPartiallyPublished::class,
            WorkspaceWasPublished::class,
            WorkspaceWasRebased::class,
            WorkspaceWasRemoved::class,
        ]) || $event instanceof EmbedsContentStreamId;
    }

    public function apply(EventInterface $event, EventEnvelope $eventEnvelope): void
    {
        match ($event::class) {
            ContentStreamWasClosed::class => $this->whenContentStreamWasClosed($event),
            ContentStreamWasCreated::class => $this->whenContentStreamWasCreated($event),
            ContentStreamWasForked::class => $this->whenContentStreamWasForked($event),
            ContentStreamWasRemoved::class => $this->whenContentStreamWasRemoved($event),
            ContentStreamWasReopened::class => $this->whenContentStreamWasReopened($event),
            DimensionShineThroughWasAdded::class => $this->whenDimensionShineThroughWasAdded($event),
            DimensionSpacePointWasMoved::class => $this->whenDimensionSpacePointWasMoved($event),
            NodeAggregateNameWasChanged::class => $this->whenNodeAggregateNameWasChanged($event, $eventEnvelope),
            NodeAggregateTypeWasChanged::class => $this->whenNodeAggregateTypeWasChanged($event, $eventEnvelope),
            NodeAggregateWasMoved::class => $this->whenNodeAggregateWasMoved($event),
            NodeAggregateWasRemoved::class => $this->whenNodeAggregateWasRemoved($event),
            NodeAggregateWithNodeWasCreated::class => $this->whenNodeAggregateWithNodeWasCreated($event, $eventEnvelope),
            NodeGeneralizationVariantWasCreated::class => $this->whenNodeGeneralizationVariantWasCreated($event, $eventEnvelope),
            NodePeerVariantWasCreated::class => $this->whenNodePeerVariantWasCreated($event, $eventEnvelope),
            NodePropertiesWereSet::class => $this->whenNodePropertiesWereSet($event, $eventEnvelope),
            NodeReferencesWereSet::class => $this->whenNodeReferencesWereSet($event, $eventEnvelope),
            NodeSpecializationVariantWasCreated::class => $this->whenNodeSpecializationVariantWasCreated($event, $eventEnvelope),
            RootNodeAggregateDimensionsWereUpdated::class => $this->whenRootNodeAggregateDimensionsWereUpdated($event),
            RootNodeAggregateWithNodeWasCreated::class => $this->whenRootNodeAggregateWithNodeWasCreated($event, $eventEnvelope),
            RootWorkspaceWasCreated::class => $this->whenRootWorkspaceWasCreated($event),
            SubtreeWasTagged::class => $this->whenSubtreeWasTagged($event),
            SubtreeWasUntagged::class => $this->whenSubtreeWasUntagged($event),
            WorkspaceBaseWorkspaceWasChanged::class => $this->whenWorkspaceBaseWorkspaceWasChanged($event),
            WorkspaceRebaseFailed::class => $this->whenWorkspaceRebaseFailed($event),
            WorkspaceWasCreated::class => $this->whenWorkspaceWasCreated($event),
            WorkspaceWasDiscarded::class => $this->whenWorkspaceWasDiscarded($event),
            WorkspaceWasPartiallyDiscarded::class => $this->whenWorkspaceWasPartiallyDiscarded($event),
            WorkspaceWasPartiallyPublished::class => $this->whenWorkspaceWasPartiallyPublished($event),
            WorkspaceWasPublished::class => $this->whenWorkspaceWasPublished($event),
            WorkspaceWasRebased::class => $this->whenWorkspaceWasRebased($event),
            WorkspaceWasRemoved::class => $this->whenWorkspaceWasRemoved($event),
            default => $event instanceof EmbedsContentStreamId || throw new \InvalidArgumentException(sprintf('Unsupported event %s', get_debug_type($event))),
        };
        if ($event instanceof EmbedsContentStreamId && ContentStreamEventStreamName::isContentStreamStreamName($eventEnvelope->streamName)) {
            $this->updateContentStreamVersion($event->getContentStreamId(), $eventEnvelope->version);
        }
    }

    private function whenContentStreamWasClosed(ContentStreamWasClosed $event): void
    {
        $this->updateContentStreamStatus($event->contentStreamId, ContentStreamStatus::CLOSED);
    }

    private function whenContentStreamWasCreated(ContentStreamWasCreated $event): void
    {
        $this->createContentStream($event->contentStreamId, ContentStreamStatus::CREATED);
    }

    private function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        //
        // 1) Copy HIERARCHY RELATIONS (this is the MAIN OPERATION here)
        //
        $insertRelationStatement = <<<SQL
            INSERT INTO {$this->tableNames->hierarchyRelation()} (
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
              "{$event->newContentStreamId->value}" AS contentstreamid
            FROM
                {$this->tableNames->hierarchyRelation()} h
                WHERE h.contentstreamid = :sourceContentStreamId
        SQL;
        try {
            $this->dbal->executeStatement($insertRelationStatement, [
                'sourceContentStreamId' => $event->sourceContentStreamId->value
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to insert hierarchy relation: %s', $e->getMessage()), 1716489211, $e);
        }

        // NOTE: as reference edges are attached to Relation Anchor Points (and they are lazily copy-on-written),
        // we do not need to copy reference edges here (but we need to do it during copy on write).

        $this->createContentStream($event->newContentStreamId, ContentStreamStatus::FORKED, $event->sourceContentStreamId);
    }

    private function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
    {
        // Drop hierarchy relations
        $deleteHierarchyRelationStatement = <<<SQL
            DELETE FROM {$this->tableNames->hierarchyRelation()} WHERE contentstreamid = :contentStreamId
        SQL;
        try {
            $this->dbal->executeStatement($deleteHierarchyRelationStatement, [
                'contentStreamId' => $event->contentStreamId->value
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to delete hierarchy relations: %s', $e->getMessage()), 1716489265, $e);
        }

        // Drop non-referenced nodes (which do not have a hierarchy relation anymore)
        $deleteNodesStatement = <<<SQL
            DELETE FROM {$this->tableNames->node()}
            WHERE NOT EXISTS (
                SELECT 1 FROM {$this->tableNames->hierarchyRelation()}
                WHERE {$this->tableNames->hierarchyRelation()}.childnodeanchor = {$this->tableNames->node()}.relationanchorpoint
            )
        SQL;
        try {
            $this->dbal->executeStatement($deleteNodesStatement);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to delete non-referenced nodes: %s', $e->getMessage()), 1716489294, $e);
        }

        // Drop non-referenced reference relations (i.e. because the referenced nodes are gone by now)
        $deleteReferenceRelationsStatement = <<<SQL
            DELETE FROM {$this->tableNames->referenceRelation()}
            WHERE NOT EXISTS (
                SELECT 1 FROM {$this->tableNames->node()}
                WHERE {$this->tableNames->node()}.relationanchorpoint = {$this->tableNames->referenceRelation()}.nodeanchorpoint
            )
        SQL;
        try {
            $this->dbal->executeStatement($deleteReferenceRelationsStatement);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to delete non-referenced reference relations: %s', $e->getMessage()), 1716489328, $e);
        }

        $this->removeContentStream($event->contentStreamId);
    }

    private function whenContentStreamWasReopened(ContentStreamWasReopened $event): void
    {
        $this->updateContentStreamStatus($event->contentStreamId, $event->previousState);
    }

    private function whenDimensionShineThroughWasAdded(DimensionShineThroughWasAdded $event): void
    {
        $this->dimensionSpacePointsRepository->insertDimensionSpacePoint($event->target);

        // 1) hierarchy relations
        $insertHierarchyRelationsStatement = <<<SQL
            INSERT INTO {$this->tableNames->hierarchyRelation()} (
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
                {$this->tableNames->hierarchyRelation()} h
                WHERE h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :sourceDimensionSpacePointHash
        SQL;
        try {
            $this->dbal->executeStatement($insertHierarchyRelationsStatement, [
                'contentStreamId' => $event->contentStreamId->value,
                'sourceDimensionSpacePointHash' => $event->source->hash,
                'newDimensionSpacePointHash' => $event->target->hash,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to insert hierarchy relations: %s', $e->getMessage()), 1716490758, $e);
        }
    }

    private function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event): void
    {
        $this->dimensionSpacePointsRepository->insertDimensionSpacePoint($event->target);

        // the ordering is important - we first update the OriginDimensionSpacePoints, as we need the
        // hierarchy relations for this query. Then, we update the Hierarchy Relations.

        // 1) originDimensionSpacePoint on Node
        $selectRelationsStatement = <<<SQL
            SELECT n.relationanchorpoint
            FROM {$this->tableNames->node()} n
            INNER JOIN {$this->tableNames->hierarchyRelation()} h
                ON h.childnodeanchor = n.relationanchorpoint
                AND h.contentstreamid = :contentStreamId
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                -- find only nodes which have their ORIGIN at the source DimensionSpacePoint,
                -- as we need to rewrite these origins (using copy on write)
                AND n.origindimensionspacepointhash = :dimensionSpacePointHash
        SQL;
        try {
            $relationAnchorPoints = $this->dbal->fetchFirstColumn($selectRelationsStatement, [
                'dimensionSpacePointHash' => $event->source->hash,
                'contentStreamId' => $event->contentStreamId->value
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load relation anchor points: %s', $e->getMessage()), 1716489628, $e);
        }
        foreach ($relationAnchorPoints as $relationAnchorPoint) {
            $this->updateNodeRecordWithCopyOnWrite(
                $event->contentStreamId,
                NodeRelationAnchorPoint::fromInteger($relationAnchorPoint),
                function (NodeRecord $nodeRecord) use ($event) {
                    $nodeRecord->originDimensionSpacePoint = $event->target->coordinates;
                    $nodeRecord->originDimensionSpacePointHash = $event->target->hash;
                }
            );
        }

        // 2) hierarchy relations
        $updateHierarchyRelationsStatement = <<<SQL
            UPDATE {$this->tableNames->hierarchyRelation()} h
            SET
                h.dimensionspacepointhash = :newDimensionSpacePointHash
            WHERE
              h.dimensionspacepointhash = :originalDimensionSpacePointHash
              AND h.contentstreamid = :contentStreamId
        SQL;
        try {
            $this->dbal->executeStatement($updateHierarchyRelationsStatement, [
                'originalDimensionSpacePointHash' => $event->source->hash,
                'newDimensionSpacePointHash' => $event->target->hash,
                'contentStreamId' => $event->contentStreamId->value,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to update hierarchy relations: %s', $e->getMessage()), 1716489951, $e);
        }
    }

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

    private function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        $this->moveNodeAggregate($event->contentStreamId, $event->nodeAggregateId, $event->newParentNodeAggregateId, $event->succeedingSiblingsForCoverage);
    }

    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->removeNodeAggregate($event->contentStreamId, $event->nodeAggregateId, $event->affectedCoveredDimensionSpacePoints);
    }

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

    private function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->createNodeGeneralizationVariant($event->contentStreamId, $event->nodeAggregateId, $event->sourceOrigin, $event->generalizationOrigin, $event->variantSucceedingSiblings, $eventEnvelope);
    }

    private function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->createNodePeerVariant($event->contentStreamId, $event->nodeAggregateId, $event->sourceOrigin, $event->peerOrigin, $event->peerSucceedingSiblings, $eventEnvelope);
    }

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

    private function whenNodeReferencesWereSet(NodeReferencesWereSet $event, EventEnvelope $eventEnvelope): void
    {
        foreach ($event->affectedSourceOriginDimensionSpacePoints as $originDimensionSpacePoint) {
            $nodeAnchorPoint = $this->projectionContentGraph
                ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                    $event->nodeAggregateId,
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
                    $event->nodeAggregateId,
                    $originDimensionSpacePoint,
                    $event->contentStreamId
                );

            // remove old
            try {
                $this->dbal->delete($this->tableNames->referenceRelation(), [
                    'nodeanchorpoint' => $nodeAnchorPoint?->value,
                    'name' => $event->referenceName->value
                ]);
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf('Failed to remove reference relation: %s', $e->getMessage()), 1716486309, $e);
            }

            // set new
            $position = 0;
            /** @var SerializedNodeReference $reference */
            foreach ($event->references as $reference) {
                $referencePropertiesJson = null;
                if ($reference->properties !== null) {
                    try {
                        $referencePropertiesJson = \json_encode($reference->properties, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);
                    } catch (\JsonException $e) {
                        throw new \RuntimeException(sprintf('Failed to JSON-encode reference properties: %s', $e->getMessage()), 1716486271, $e);
                    }
                }
                try {
                    $this->dbal->insert($this->tableNames->referenceRelation(), [
                        'name' => $event->referenceName->value,
                        'position' => $position,
                        'nodeanchorpoint' => $nodeAnchorPoint?->value,
                        'destinationnodeaggregateid' => $reference->targetNodeAggregateId->value,
                        'properties' => $referencePropertiesJson,
                    ]);
                } catch (DBALException $e) {
                    throw new \RuntimeException(sprintf('Failed to insert reference relation: %s', $e->getMessage()), 1716486309, $e);
                }
                $position++;
            }
        }
    }

    private function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->createNodeSpecializationVariant($event->contentStreamId, $event->nodeAggregateId, $event->sourceOrigin, $event->specializationOrigin, $event->specializationSiblings, $eventEnvelope);
    }

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
        $deleteHierarchyRelationsStatement = <<<SQL
            DELETE FROM {$this->tableNames->hierarchyRelation()}
            WHERE
                parentnodeanchor = :parentNodeAnchor
                AND childnodeanchor = :childNodeAnchor
                AND contentstreamid = :contentStreamId
        SQL;
        try {
            $this->dbal->executeStatement($deleteHierarchyRelationsStatement, [
                'parentNodeAnchor' => NodeRelationAnchorPoint::forRootEdge()->value,
                'childNodeAnchor' => $rootNodeAnchorPoint->value,
                'contentStreamId' => $event->contentStreamId->value,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to delete hierarchy relation: %s', $e->getMessage()), 1716488943, $e);
        }
        // recreate hierarchy edges for the root node
        $this->connectHierarchy(
            $event->contentStreamId,
            NodeRelationAnchorPoint::forRootEdge(),
            $rootNodeAnchorPoint,
            $event->coveredDimensionSpacePoints,
            null
        );
    }

    private function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $originDimensionSpacePoint = OriginDimensionSpacePoint::createWithoutDimensions();
        $node = NodeRecord::createNewInDatabase(
            $this->dbal,
            $this->tableNames,
            $event->nodeAggregateId,
            $originDimensionSpacePoint->coordinates,
            $originDimensionSpacePoint->hash,
            SerializedPropertyValues::createEmpty(),
            $event->nodeTypeName,
            $event->nodeAggregateClassification,
            null,
            Timestamps::create($eventEnvelope->recordedAt, self::initiatingDateTime($eventEnvelope), null, null),
        );

        $this->connectHierarchy(
            $event->contentStreamId,
            NodeRelationAnchorPoint::forRootEdge(),
            $node->relationAnchorPoint,
            $event->coveredDimensionSpacePoints,
            null
        );
    }

    private function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        $this->createWorkspace($event->workspaceName, null, $event->newContentStreamId);

        // the content stream is in use now
        $this->updateContentStreamStatus($event->newContentStreamId, ContentStreamStatus::IN_USE_BY_WORKSPACE);
    }

    private function whenSubtreeWasTagged(SubtreeWasTagged $event): void
    {
        $this->addSubtreeTag($event->contentStreamId, $event->nodeAggregateId, $event->affectedDimensionSpacePoints, $event->tag);
    }

    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
    {
        $this->removeSubtreeTag($event->contentStreamId, $event->nodeAggregateId, $event->affectedDimensionSpacePoints, $event->tag);
    }

    private function whenWorkspaceBaseWorkspaceWasChanged(WorkspaceBaseWorkspaceWasChanged $event): void
    {
        $this->updateBaseWorkspace($event->workspaceName, $event->baseWorkspaceName, $event->newContentStreamId);
    }

    private function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        $this->markWorkspaceAsOutdatedConflict($event->workspaceName);
        $this->updateContentStreamStatus($event->candidateContentStreamId, ContentStreamStatus::REBASE_ERROR);
    }

    private function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        $this->createWorkspace($event->workspaceName, $event->baseWorkspaceName, $event->newContentStreamId);

        // the content stream is in use now
        $this->updateContentStreamStatus($event->newContentStreamId, ContentStreamStatus::IN_USE_BY_WORKSPACE);
    }

    private function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        $this->updateWorkspaceContentStreamId($event->workspaceName, $event->newContentStreamId);
        $this->markWorkspaceAsOutdated($event->workspaceName);
        $this->markDependentWorkspacesAsOutdated($event->workspaceName);

        // the new content stream is in use now
        $this->updateContentStreamStatus($event->newContentStreamId, ContentStreamStatus::IN_USE_BY_WORKSPACE);
        // the previous content stream is no longer in use
        $this->updateContentStreamStatus($event->previousContentStreamId, ContentStreamStatus::NO_LONGER_IN_USE);
    }

    private function whenWorkspaceWasPartiallyDiscarded(WorkspaceWasPartiallyDiscarded $event): void
    {
        $this->updateWorkspaceContentStreamId($event->workspaceName, $event->newContentStreamId);
        $this->markDependentWorkspacesAsOutdated($event->workspaceName);

        // the new content stream is in use now
        $this->updateContentStreamStatus($event->newContentStreamId, ContentStreamStatus::IN_USE_BY_WORKSPACE);

        // the previous content stream is no longer in use
        $this->updateContentStreamStatus($event->previousContentStreamId, ContentStreamStatus::NO_LONGER_IN_USE);
    }

    private function whenWorkspaceWasPartiallyPublished(WorkspaceWasPartiallyPublished $event): void
    {
        // TODO: How do we test this method? – It's hard to design a BDD testcase that fails if this method is commented out...
        $this->updateWorkspaceContentStreamId($event->sourceWorkspaceName, $event->newSourceContentStreamId);
        $this->markDependentWorkspacesAsOutdated($event->targetWorkspaceName);

        // NASTY: we need to set the source workspace name as non-outdated; as it has been made up-to-date again.
        $this->markWorkspaceAsUpToDate($event->sourceWorkspaceName);

        $this->markDependentWorkspacesAsOutdated($event->sourceWorkspaceName);

        // the new content stream is in use now
        $this->updateContentStreamStatus($event->newSourceContentStreamId, ContentStreamStatus::IN_USE_BY_WORKSPACE);

        // the previous content stream is no longer in use
        $this->updateContentStreamStatus($event->previousSourceContentStreamId, ContentStreamStatus::NO_LONGER_IN_USE);
    }

    private function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        // TODO: How do we test this method? – It's hard to design a BDD testcase that fails if this method is commented out...
        $this->updateWorkspaceContentStreamId($event->sourceWorkspaceName, $event->newSourceContentStreamId);
        $this->markDependentWorkspacesAsOutdated($event->targetWorkspaceName);

        // NASTY: we need to set the source workspace name as non-outdated; as it has been made up-to-date again.
        $this->markWorkspaceAsUpToDate($event->sourceWorkspaceName);

        $this->markDependentWorkspacesAsOutdated($event->sourceWorkspaceName);

        // the new content stream is in use now
        $this->updateContentStreamStatus($event->newSourceContentStreamId, ContentStreamStatus::IN_USE_BY_WORKSPACE);

        // the previous content stream is no longer in use
        $this->updateContentStreamStatus($event->previousSourceContentStreamId, ContentStreamStatus::NO_LONGER_IN_USE);
    }

    private function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        $this->updateWorkspaceContentStreamId($event->workspaceName, $event->newContentStreamId);
        $this->markDependentWorkspacesAsOutdated($event->workspaceName);

        // When the rebase is successful, we can set the status of the workspace back to UP_TO_DATE.
        $this->markWorkspaceAsUpToDate($event->workspaceName);

        // the new content stream is in use now
        $this->updateContentStreamStatus($event->newContentStreamId, ContentStreamStatus::IN_USE_BY_WORKSPACE);

        // the previous content stream is no longer in use
        $this->updateContentStreamStatus($event->previousContentStreamId, ContentStreamStatus::NO_LONGER_IN_USE);
    }

    private function whenWorkspaceWasRemoved(WorkspaceWasRemoved $event): void
    {
        $this->removeWorkspace($event->workspaceName);
    }

    /** --------------------------------- */

    /**
     * @return array<string>
     */
    private function determineRequiredSqlStatements(): array
    {
        $schemaManager = $this->dbal->createSchemaManager();
        $schema = (new DoctrineDbalContentGraphSchemaBuilder($this->tableNames))->buildSchema($schemaManager);
        return DbalSchemaDiff::determineRequiredSqlStatements($this->dbal, $schema);
    }

    private function truncateDatabaseTables(): void
    {
        try {
            $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->node());
            $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->hierarchyRelation());
            $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->referenceRelation());
            $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->dimensionSpacePoints());
            $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->workspace());
            $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->contentStream());
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to truncate database tables for projection %s: %s', self::class, $e->getMessage()), 1716478318, $e);
        }
    }

    /**
     * @param callable(NodeRecord): T $operations
     * @return T
     * @template T
     */
    private function updateNodeRecordWithCopyOnWrite(
        ContentStreamId $contentStreamIdWhereWriteOccurs,
        NodeRelationAnchorPoint $anchorPoint,
        callable $operations
    ): mixed {
        $contentStreamIds = $this->projectionContentGraph->getAllContentStreamIdsAnchorPointIsContainedIn($anchorPoint);
        if (count($contentStreamIds) > 1) {
            // Copy on Write needed!
            // Copy on Write is a purely "Content Stream" related concept;
            // thus we do not care about different DimensionSpacePoints here (but we copy all edges)

            // 1) fetch node, adjust properties, assign new Relation Anchor Point
            /** @var NodeRecord $originalNode The anchor point appears in a content stream, so there must be a node */
            $originalNode = $this->projectionContentGraph->getNodeByAnchorPoint($anchorPoint);
            $copiedNode = NodeRecord::createCopyFromNodeRecord($this->dbal, $this->tableNames, $originalNode);
            $result = $operations($copiedNode);
            $copiedNode->updateToDatabase($this->dbal, $this->tableNames);

            // 2) reconnect all edges belonging to this content stream to the new "copied node".
            // IMPORTANT: We need to reconnect BOTH the incoming and outgoing edges.
            $updateHierarchyRelationStatement = <<<SQL
                UPDATE {$this->tableNames->hierarchyRelation()} h
                SET
                    -- if our (copied) node is the child, we update h.childNodeAnchor
                    h.childnodeanchor = IF(h.childnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.childnodeanchor),

                    -- if our (copied) node is the parent, we update h.parentNodeAnchor
                    h.parentnodeanchor = IF(h.parentnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.parentnodeanchor)
                WHERE
                  :originalNodeAnchor IN (h.childnodeanchor, h.parentnodeanchor)
                  AND h.contentstreamid = :contentStreamId
            SQL;
            try {
                $this->dbal->executeStatement($updateHierarchyRelationStatement, [
                    'newNodeAnchor' => $copiedNode->relationAnchorPoint->value,
                    'originalNodeAnchor' => $anchorPoint->value,
                    'contentStreamId' => $contentStreamIdWhereWriteOccurs->value,
                ]);
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf('Failed to update hierarchy relation: %s', $e->getMessage()), 1716486444, $e);
            }
            // reference relation rows need to be copied as well!
            $this->copyReferenceRelations(
                $anchorPoint,
                $copiedNode->relationAnchorPoint
            );
            return $result;
        }

        // else: No copy on write needed :)

        $node = $this->projectionContentGraph->getNodeByAnchorPoint($anchorPoint);
        if (!$node) {
            throw new \RuntimeException(sprintf('Failed to find node for anchor point %s. This is probably a bug in the %s', $anchorPoint->value, self::class), 1716488997);
        }
        $result = $operations($node);
        $node->updateToDatabase($this->dbal, $this->tableNames);
        return $result;
    }

    private function copyReferenceRelations(
        NodeRelationAnchorPoint $sourceRelationAnchorPoint,
        NodeRelationAnchorPoint $destinationRelationAnchorPoint
    ): void {
        $copyReferenceRelationStatement = <<<SQL
            INSERT INTO {$this->tableNames->referenceRelation()} (
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
                {$this->tableNames->referenceRelation()} ref
                WHERE ref.nodeanchorpoint = :sourceNodeAnchorPoint
        SQL;
        try {
            $this->dbal->executeStatement($copyReferenceRelationStatement, [
                'sourceNodeAnchorPoint' => $sourceRelationAnchorPoint->value,
                'destinationRelationAnchorPoint' => $destinationRelationAnchorPoint->value
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to copy reference relations: %s', $e->getMessage()), 1716489394, $e);
        }
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
            static fn (HierarchyRelation $relationA, HierarchyRelation $relationB): int => $relationA->position <=> $relationB->position
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

    private function copyHierarchyRelationToDimensionSpacePoint(
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
        $copy->addToDatabase($this->dbal, $this->tableNames);

        return $copy;
    }

    private function copyNodeToDimensionSpacePoint(
        NodeRecord $sourceNode,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        EventEnvelope $eventEnvelope,
    ): NodeRecord {
        return NodeRecord::createNewInDatabase(
            $this->dbal,
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
}
