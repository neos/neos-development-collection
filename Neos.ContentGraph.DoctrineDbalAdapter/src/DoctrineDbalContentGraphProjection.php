<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamDbIds;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\ContentStream;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeMove;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeRemoval;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeVariation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\Workspace;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelationDbId;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentStreamDbIdFinder;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\DimensionSpacePointsRepository;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\EventStore\InitiatingEventMetadata;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamId;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSiblings;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
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
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
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
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Event\WorkspaceWasPublished;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceRebaseFailed;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event\WorkspaceWasRebased;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjectionInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Dbal\DbalSchemaDiff;
use Neos\ContentRepository\Dbal\MysqlPlatformContentRepositoryLocker;
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

    public function __construct(
        private readonly Connection $dbal,
        private readonly MysqlPlatformContentRepositoryLocker $contentRepositoryLocker,
        private readonly ProjectionContentGraph $projectionContentGraph,
        private readonly ContentGraphTableNames $tableNames,
        private readonly DimensionSpacePointsRepository $dimensionSpacePointsRepository,
        private readonly ContentStreamDbIdFinder $contentStreamDbIdFinder,
        private readonly ContentGraphReadModelInterface $contentGraphReadModel
    ) {
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

        return ProjectionStatus::ok();
    }

    public function resetState(): void
    {
        $this->truncateDatabaseTables();
    }

    public function getState(): ContentGraphReadModelInterface
    {
        return $this->contentGraphReadModel;
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
            RootWorkspaceWasCreated::class => $this->whenRootWorkspaceWasCreated($event, $eventEnvelope),
            SubtreeWasTagged::class => $this->whenSubtreeWasTagged($event),
            SubtreeWasUntagged::class => $this->whenSubtreeWasUntagged($event),
            WorkspaceBaseWorkspaceWasChanged::class => $this->whenWorkspaceBaseWorkspaceWasChanged($event, $eventEnvelope),
            WorkspaceRebaseFailed::class => $this->whenWorkspaceRebaseFailed($event),
            WorkspaceWasCreated::class => $this->whenWorkspaceWasCreated($event, $eventEnvelope),
            WorkspaceWasDiscarded::class => $this->whenWorkspaceWasDiscarded($event, $eventEnvelope),
            WorkspaceWasPublished::class => $this->whenWorkspaceWasPublished($event, $eventEnvelope),
            WorkspaceWasRebased::class => $this->whenWorkspaceWasRebased($event, $eventEnvelope),
            WorkspaceWasRemoved::class => $this->whenWorkspaceWasRemoved($event),
            default => null,
        };
        if (
            $event instanceof EmbedsContentStreamId
            && ContentStreamEventStreamName::isContentStreamStreamName($eventEnvelope->streamName)
            && !(
                // special case as we dont need to update anything. The handling above takes care of setting the version to 0
                $event instanceof ContentStreamWasForked
                || $event instanceof ContentStreamWasCreated
            )
        ) {
            $this->updateContentStreamVersion($event->getContentStreamId(), $eventEnvelope->version, $event instanceof PublishableToWorkspaceInterface);
        }
    }

    public function inSimulation(\Closure $fn): mixed
    {
        if ($this->dbal->isTransactionActive()) {
            throw new \RuntimeException(sprintf('Invoking %s is not allowed to be invoked recursively. Current transaction nesting %d.', __FUNCTION__, $this->dbal->getTransactionNestingLevel()));
        }

        $this->contentRepositoryLocker->acquireLock(timeoutInSeconds: 120);
        $this->dbal->beginTransaction();
        $this->dbal->setRollbackOnly();
        try {
            return $fn();
        } finally {
            // unsets rollback only flag and allows the connection to work regular again
            $this->dbal->rollBack();
            $this->contentRepositoryLocker->releaseLock();
        }
    }

    private function whenContentStreamWasClosed(ContentStreamWasClosed $event): void
    {
        $this->closeContentStream($event->contentStreamId);
    }

    private function whenContentStreamWasCreated(ContentStreamWasCreated $event): void
    {
        $this->createContentStream($event->contentStreamId);
        $this->dbal->insert($this->tableNames->contentStreamId(), [
            'id' => $event->contentStreamId->value,
        ]);
    }

    private function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        $this->createContentStream($event->newContentStreamId, $event->sourceContentStreamId, $event->versionOfSourceContentStream);

        $sourceContentStreamDbId = $this->contentStreamDbIdFinder->getContentStreamDbId($event->sourceContentStreamId);

        $this->dbal->insert($this->tableNames->contentStreamId(), [
            'id' => $event->sourceContentStreamId,
        ]);

        foreach ($sourceContentStreamDbId->items as $sourceDbId) {
            $this->dbal->insert($this->tableNames->contentStreamId(), [
                'id' => $event->newContentStreamId,
                'dbId' => $sourceDbId->value
            ]);
        }

        $this->dbal->insert($this->tableNames->contentStreamId(), [
            'id' => $event->newContentStreamId,
        ]);
    }

    private function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
    {
        $contentStreamDbIds = $this->contentStreamDbIdFinder->getContentStreamDbId($event->contentStreamId);

        // Drop hierarchy relations
        // TODO reimplement
        // $deleteHierarchyRelationStatement = <<<SQL
        //     DELETE FROM {$this->tableNames->hierarchyRelation()} WHERE contentstreamdbid IN (:contentStreamDbIds)
        // SQL;
        // try {
        //     $this->dbal->executeStatement($deleteHierarchyRelationStatement, [
        //         'contentStreamDbIds' => $contentStreamDbIds->toIntArray()
        //     ], [
        //         'contentStreamDbIds' => ArrayParameterType::INTEGER,
        //     ]);
        // } catch (DBALException $e) {
        //     throw new \RuntimeException(sprintf('Failed to delete hierarchy relations: %s', $e->getMessage()), 1716489265, $e);
        // }

        // Drop non-referenced nodes (which do not have a hierarchy relation anymore)
        // TODO reimplement
        // $deleteNodesStatement = <<<SQL
        //     DELETE FROM {$this->tableNames->node()}
        //     WHERE NOT EXISTS (
        //         SELECT 1 FROM {$this->tableNames->hierarchyRelation()}
        //         WHERE {$this->tableNames->hierarchyRelation()}.childnodeanchor = {$this->tableNames->node()}.relationanchorpoint
        //     )
        // SQL;
        // try {
        //     $this->dbal->executeStatement($deleteNodesStatement);
        // } catch (DBALException $e) {
        //     throw new \RuntimeException(sprintf('Failed to delete non-referenced nodes: %s', $e->getMessage()), 1716489294, $e);
        // }

        // Drop non-referenced reference relations (i.e. because the referenced nodes are gone by now)
        // TODO reimplement
        // $deleteReferenceRelationsStatement = <<<SQL
        //     DELETE FROM {$this->tableNames->referenceRelation()}
        //     WHERE NOT EXISTS (
        //         SELECT 1 FROM {$this->tableNames->node()}
        //         WHERE {$this->tableNames->node()}.relationanchorpoint = {$this->tableNames->referenceRelation()}.nodeanchorpoint
        //     )
        // SQL;
        // try {
        //     $this->dbal->executeStatement($deleteReferenceRelationsStatement);
        // } catch (DBALException $e) {
        //     throw new \RuntimeException(sprintf('Failed to delete non-referenced reference relations: %s', $e->getMessage()), 1716489328, $e);
        // }

        $this->removeContentStream($event->contentStreamId);

        $this->dbal->delete($this->tableNames->contentStreamId(), [
            'id' => $event->contentStreamId->value,
        ]);
    }

    private function whenContentStreamWasReopened(ContentStreamWasReopened $event): void
    {
        $this->reopenContentStream($event->contentStreamId);
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
              contentstreamdbid
            )
            SELECT
              h.parentnodeanchor,
              h.childnodeanchor,
              h.position,
              h.subtreetags,
              :newDimensionSpacePointHash AS dimensionspacepointhash,
              h.contentstreamdbid
            FROM
                {$this->tableNames->hierarchyRelation()} h
                WHERE h.contentstreamdbid IN (:contentStreamDbIds)
                AND h.dimensionspacepointhash = :sourceDimensionSpacePointHash
        SQL;
        try {
            $this->dbal->executeStatement($insertHierarchyRelationsStatement, [
                'contentStreamDbId' => $this->getContentStreamDbId($event)->value,
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
                AND h.contentstreamdbid IN (:contentStreamDbIds)
                AND h.dimensionspacepointhash = :dimensionSpacePointHash
                -- find only nodes which have their ORIGIN at the source DimensionSpacePoint,
                -- as we need to rewrite these origins (using copy on write)
                AND n.origindimensionspacepointhash = :dimensionSpacePointHash
            WHERE n.classification != "root"
        SQL;
        try {
            $relationAnchorPoints = $this->dbal->fetchFirstColumn($selectRelationsStatement, [
                'dimensionSpacePointHash' => $event->source->hash,
                'contentStreamDbId' => $this->getContentStreamDbId($event)->value
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load relation anchor points: %s', $e->getMessage()), 1716489628, $e);
        }
        foreach ($relationAnchorPoints as $relationAnchorPoint) {
            $this->updateNodeRecordWithCopyOnWrite(
                $this->getContentStreamDbId($event),
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
              AND h.contentstreamdbid IN (:contentStreamDbIds)
        SQL;
        try {
            $this->dbal->executeStatement($updateHierarchyRelationsStatement, [
                'originalDimensionSpacePointHash' => $event->source->hash,
                'newDimensionSpacePointHash' => $event->target->hash,
                'contentStreamDbId' => $this->getContentStreamDbId($event)->value,
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
                $this->getContentStreamDbId($event),
            ) as $anchorPoint
        ) {
            $this->updateNodeRecordWithCopyOnWrite(
                $this->getContentStreamDbId($event),
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
        $anchorPoints = $this->projectionContentGraph->getAnchorPointsForNodeAggregateInContentStream($event->nodeAggregateId, $this->getContentStreamDbId($event));
        foreach ($anchorPoints as $anchorPoint) {
            $this->updateNodeRecordWithCopyOnWrite(
                $this->getContentStreamDbId($event),
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
        $this->moveNodeAggregate($this->getContentStreamDbId($event), $event->nodeAggregateId, $event->newParentNodeAggregateId, $event->succeedingSiblingsForCoverage);
    }

    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->removeNodeAggregate($this->getContentStreamDbId($event), $event->nodeAggregateId, $event->affectedCoveredDimensionSpacePoints);
    }

    private function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->createNodeWithHierarchy(
            $this->getContentStreamDbId($event),
            $event->nodeAggregateId,
            $event->nodeTypeName,
            $event->parentNodeAggregateId,
            $event->originDimensionSpacePoint,
            $event->succeedingSiblingsForCoverage,
            $event->initialPropertyValues,
            $event->nodeReferences,
            $event->nodeAggregateClassification,
            $event->nodeName,
            $eventEnvelope,
        );
    }

    private function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->createNodeGeneralizationVariant($this->getContentStreamDbId($event), $event->nodeAggregateId, $event->sourceOrigin, $event->generalizationOrigin, $event->variantSucceedingSiblings, $eventEnvelope);
    }

    private function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->createNodePeerVariant($this->getContentStreamDbId($event), $event->nodeAggregateId, $event->sourceOrigin, $event->peerOrigin, $event->peerSucceedingSiblings, $eventEnvelope);
    }

    private function whenNodePropertiesWereSet(NodePropertiesWereSet $event, EventEnvelope $eventEnvelope): void
    {
        $anchorPoint = $this->projectionContentGraph
            ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                $event->getNodeAggregateId(),
                $event->getOriginDimensionSpacePoint(),
                $this->getContentStreamDbId($event)
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
            $this->getContentStreamDbId($event),
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
                    $this->getContentStreamDbId($event)
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
                $this->getContentStreamDbId($event),
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
                    $this->getContentStreamDbId($event)
                );


            // remove old
            $deleteOldReferencesSql = <<<SQL
            DELETE FROM {$this->tableNames->referenceRelation()}
                WHERE nodeanchorpoint = :nodeanchorpoint
                AND name in (:names)
            SQL;
            try {
                $this->dbal->executeStatement(
                    $deleteOldReferencesSql,
                    [
                        'nodeanchorpoint' => $nodeAnchorPoint?->value,
                        'names' => array_map(fn (ReferenceName $name) => $name->value, $event->references->getReferenceNames())
                    ],
                    [
                        'names' => ArrayParameterType::STRING
                    ]
                );
            } catch (DbalException $e) {
                throw new \RuntimeException(sprintf('Failed to remove reference relation: %s', $e->getMessage()), 1716486309, $e);
            }

            // set new
            $nodeAnchorPoint && $this->writeReferencesForTargetAnchorPoint($event->references, $nodeAnchorPoint);
        }
    }

    private function writeReferencesForTargetAnchorPoint(SerializedNodeReferences $nodeReferences, NodeRelationAnchorPoint $nodeAnchorPoint): void
    {
        $position = 0;
        foreach ($nodeReferences as $referencesByProperty) {
            foreach ($referencesByProperty->references as $reference) {
                $referencePropertiesJson = null;
                if ($reference->properties !== null && $reference->properties->count() > 0) {
                    try {
                        $referencePropertiesJson = \json_encode($reference->properties, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);
                    } catch (\JsonException $e) {
                        throw new \RuntimeException(sprintf('Failed to JSON-encode reference properties: %s', $e->getMessage()), 1716486271, $e);
                    }
                }
                try {
                    $this->dbal->insert($this->tableNames->referenceRelation(), [
                        'name' => $referencesByProperty->referenceName->value,
                        'position' => $position,
                        'nodeanchorpoint' => $nodeAnchorPoint->value,
                        'destinationnodeaggregateid' => $reference->targetNodeAggregateId->value,
                        'properties' => $referencePropertiesJson,
                    ]);
                } catch (DbalException $e) {
                    throw new \RuntimeException(sprintf('Failed to insert reference relation: %s', $e->getMessage()), 1716486309, $e);
                }
                $position++;
            }
        }
    }

    private function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->createNodeSpecializationVariant($this->getContentStreamDbId($event), $event->nodeAggregateId, $event->sourceOrigin, $event->specializationOrigin, $event->specializationSiblings, $eventEnvelope);
    }

    private function whenRootNodeAggregateDimensionsWereUpdated(RootNodeAggregateDimensionsWereUpdated $event): void
    {
        $rootNodeAnchorPoint = $this->projectionContentGraph
            ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                $event->nodeAggregateId,
                /** the origin DSP of the root node is always the empty dimension ({@see whenRootNodeAggregateWithNodeWasCreated}) */
                OriginDimensionSpacePoint::createWithoutDimensions(),
                $this->getContentStreamDbId($event)
            );
        if ($rootNodeAnchorPoint === null) {
            // should never happen.
            return;
        }

        $ingoingRelations = $this->projectionContentGraph->findIngoingHierarchyRelationsForNode(
            $rootNodeAnchorPoint,
            $this->getContentStreamDbId($event)
        );

        $currentlyCoveredDimensionSpacePoints = [];
        foreach ($ingoingRelations as $ingoingRelation) {
            $currentlyCoveredDimensionSpacePoints[] = $ingoingRelation->dimensionSpacePoint;
        }

        $newlyCoveredDimensionSpacePoints = $event->coveredDimensionSpacePoints->getDifference(DimensionSpacePointSet::fromArray($currentlyCoveredDimensionSpacePoints));

        // add hierarchy edges for newly added dimensions
        $this->connectHierarchy(
            $this->getContentStreamDbId($event),
            NodeRelationAnchorPoint::forRootEdge(),
            $rootNodeAnchorPoint,
            $newlyCoveredDimensionSpacePoints,
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
            $this->getContentStreamDbId($event),
            NodeRelationAnchorPoint::forRootEdge(),
            $node->relationAnchorPoint,
            $event->coveredDimensionSpacePoints,
            null
        );
    }

    private function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->createWorkspace($event->workspaceName, null, $event->newContentStreamId, $eventEnvelope->version);
    }

    private function whenSubtreeWasTagged(SubtreeWasTagged $event): void
    {
        $this->addSubtreeTag($this->getContentStreamDbId($event), $event->nodeAggregateId, $event->affectedDimensionSpacePoints, $event->tag);
    }

    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
    {
        $this->removeSubtreeTag($this->getContentStreamDbId($event), $event->nodeAggregateId, $event->affectedDimensionSpacePoints, $event->tag);
    }

    private function whenWorkspaceBaseWorkspaceWasChanged(WorkspaceBaseWorkspaceWasChanged $event, EventEnvelope $eventEnvelope): void
    {
        $this->updateBaseWorkspace($event->workspaceName, $event->baseWorkspaceName, $event->newContentStreamId, $eventEnvelope->version);
    }

    private function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        // legacy handling:
        // before https://github.com/neos/neos-development-collection/pull/4965 this event was emitted and set the content stream status to `REBASE_ERROR`
        // instead of setting the error state on replay for old events we make it almost behave like if the rebase had failed today: reopen the workspaces content stream id
        // the candidateContentStreamId will be removed by the ContentStreamPruner
        $this->reopenContentStream($event->sourceContentStreamId);
    }

    private function whenWorkspaceWasCreated(WorkspaceWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->createWorkspace($event->workspaceName, $event->baseWorkspaceName, $event->newContentStreamId, $eventEnvelope->version);
    }

    private function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event, EventEnvelope $eventEnvelope): void
    {
        $this->updateWorkspaceContentStreamId($event->workspaceName, $event->newContentStreamId, $eventEnvelope->version);
    }

    private function whenWorkspaceWasPublished(WorkspaceWasPublished $event, EventEnvelope $eventEnvelope): void
    {
        $this->updateWorkspaceContentStreamId($event->sourceWorkspaceName, $event->newSourceContentStreamId, $eventEnvelope->version);
    }

    private function whenWorkspaceWasRebased(WorkspaceWasRebased $event, EventEnvelope $eventEnvelope): void
    {
        $this->updateWorkspaceContentStreamId($event->workspaceName, $event->newContentStreamId, $eventEnvelope->version);
    }

    private function whenWorkspaceWasRemoved(WorkspaceWasRemoved $event): void
    {
        $this->removeWorkspace($event->workspaceName);
    }

    /** --------------------------------- */

    public function getContentStreamDbId(EmbedsContentStreamId $event): ContentStreamDbIds
    {
        return $this->contentStreamDbIdFinder->getContentStreamDbId($event->getContentStreamId());
    }

    /**
     * @return array<string>
     */
    private function determineRequiredSqlStatements(): array
    {
        $schema = (new DoctrineDbalContentGraphSchemaBuilder($this->tableNames))->buildSchema($this->dbal);
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
            $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->contentStreamId());
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
        ContentStreamDbIds $contentStreamDbIdsWhereWriteOccurs,
        NodeRelationAnchorPoint $anchorPoint,
        callable $operations
    ): mixed {
        $contentStreamDbIds = $this->projectionContentGraph->getAllContentStreamDbIdsAnchorPointIsContainedIn($anchorPoint);
        if (!$contentStreamDbIds->equals($contentStreamDbIdsWhereWriteOccurs->current())) {
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

            if ($contentStreamDbIds->contain($contentStreamDbIdsWhereWriteOccurs->current())) {
                $updateHierarchyRelationStatement = <<<SQL
                    UPDATE {$this->tableNames->hierarchyRelation()} h
                    SET
                        -- if our (copied) node is the child, we update h.childNodeAnchor
                        h.childnodeanchor = IF(h.childnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.childnodeanchor),

                        -- if our (copied) node is the parent, we update h.parentNodeAnchor
                        h.parentnodeanchor = IF(h.parentnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.parentnodeanchor)
                    WHERE
                      :originalNodeAnchor IN (h.childnodeanchor, h.parentnodeanchor)
                      AND h.contentstreamdbid = :targetContentStreamDbId
                SQL;

                try {
                    $this->dbal->executeStatement($updateHierarchyRelationStatement, [
                        'newNodeAnchor' => $copiedNode->relationAnchorPoint->value,
                        'originalNodeAnchor' => $anchorPoint->value,
                        'targetContentStreamDbId' => $contentStreamDbIdsWhereWriteOccurs->current()->value,
                    ]);
                } catch (DBALException $e) {
                    throw new \RuntimeException(sprintf('Failed to update hierarchy relation: %s', $e->getMessage()), 1716486444, $e);
                }
            } else {
                // todo is this correct
                $copyHierarchyRelationStatement = <<<SQL
                    INSERT INTO {$this->tableNames->hierarchyRelation()} (
                      id,
                      parentnodeanchor,
                      childnodeanchor,
                      position,
                      subtreetags,
                      dimensionspacepointhash,
                      contentstreamdbid
                    )
                    SELECT
                      h.id,
                      -- if our (copied) node is the parent, we update h.parentNodeAnchor
                      IF(h.parentnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.parentnodeanchor) as parentnodeanchor,
                      -- if our (copied) node is the child, we update h.childNodeAnchor
                      IF(h.childnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.childnodeanchor) as childnodeanchor,
                      h.position,
                      h.subtreetags,
                      h.dimensionspacepointhash,
                      :targetContentStreamDbId as contentstreamdbid
                    FROM
                        {$this->tableNames->hierarchyRelation()} h
                    WHERE 
                        :originalNodeAnchor IN (h.childnodeanchor, h.parentnodeanchor)
                        AND h.contentstreamdbid IN (:contentStreamDbIds)
                SQL;

                try {
                    $this->dbal->executeStatement($copyHierarchyRelationStatement, [
                        'newNodeAnchor' => $copiedNode->relationAnchorPoint->value,
                        'originalNodeAnchor' => $anchorPoint->value,
                        'contentStreamDbIds' => $contentStreamDbIdsWhereWriteOccurs->toIntArray(),
                        'targetContentStreamDbId' => $contentStreamDbIdsWhereWriteOccurs->current()->value,
                    ], [
                        'contentStreamDbIds' => ArrayParameterType::INTEGER,
                    ]);
                } catch (DBALException $e) {
                    throw new \RuntimeException(sprintf('Failed to update hierarchy relation: %s', $e->getMessage()), 1716486444, $e);
                }
            }



            // reference relation rows need to be copied as well!
            // todo
            // $this->copyReferenceRelations(
            //     $anchorPoint,
            //     $copiedNode->relationAnchorPoint
            // );
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
        if ($eventEnvelope->event->metadata?->has(InitiatingEventMetadata::INITIATING_TIMESTAMP) !== true) {
            return $eventEnvelope->recordedAt;
        }
        $initiatingTimestamp = InitiatingEventMetadata::getInitiatingTimestamp($eventEnvelope->event->metadata);
        if ($initiatingTimestamp === null) {
            throw new \RuntimeException(sprintf('Failed to extract initiating timestamp from event "%s"', $eventEnvelope->event->id->value), 1678902291);
        }
        return $initiatingTimestamp;
    }

    private function createNodeWithHierarchy(
        ContentStreamDbIds $contentStreamDbIds,
        NodeAggregateId $nodeAggregateId,
        NodeTypeName $nodeTypeName,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        InterdimensionalSiblings $coverageSucceedingSiblings,
        SerializedPropertyValues $propertyDefaultValuesAndTypes,
        SerializedNodeReferences $references,
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
                    $contentStreamDbIds,
                    $parentNodeAggregateId,
                    $dimensionSpacePoint
                );

                $succeedingSiblingNodeAggregateId = $coverageSucceedingSiblings->getSucceedingSiblingIdForDimensionSpacePoint($dimensionSpacePoint);
                $succeedingSibling = $succeedingSiblingNodeAggregateId
                    ? $this->projectionContentGraph->findNodeInAggregate(
                        $contentStreamDbIds,
                        $succeedingSiblingNodeAggregateId,
                        $dimensionSpacePoint
                    )
                    : null;

                if ($parentNode) {
                    $this->connectHierarchy(
                        $contentStreamDbIds,
                        $parentNode->relationAnchorPoint,
                        $node->relationAnchorPoint,
                        new DimensionSpacePointSet([$dimensionSpacePoint]),
                        $succeedingSibling?->relationAnchorPoint,
                    );
                }
            }
        }

        $this->writeReferencesForTargetAnchorPoint($references, $node->relationAnchorPoint);
    }

    private function connectHierarchy(
        ContentStreamDbIds $contentStreamDbIds,
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
                $contentStreamDbIds,
                $dimensionSpacePoint
            );

            $parentSubtreeTags = $this->subtreeTagsForHierarchyRelation($contentStreamDbIds, $parentNodeAnchorPoint, $dimensionSpacePoint);
            $inheritedSubtreeTags = NodeTags::create(SubtreeTags::createEmpty(), $parentSubtreeTags->all());

            $hierarchyRelation = new HierarchyRelation(
                HierarchyRelationDbId::createAutoIncremented(),
                $parentNodeAnchorPoint,
                $childNodeAnchorPoint,
                $contentStreamDbIds->current(),
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
        ContentStreamDbIds $contentStreamDbIds,
        DimensionSpacePoint $dimensionSpacePoint
    ): int {
        $position = $this->projectionContentGraph->determineHierarchyRelationPosition(
            $parentAnchorPoint,
            $childAnchorPoint,
            $succeedingSiblingAnchorPoint,
            $contentStreamDbIds,
            $dimensionSpacePoint
        );

        if ($position % 2 !== 0) {
            $position = $this->getRelationPositionAfterRecalculation(
                $parentAnchorPoint,
                $childAnchorPoint,
                $succeedingSiblingAnchorPoint,
                $contentStreamDbIds,
                $dimensionSpacePoint
            );
        }

        return $position;
    }

    private function getRelationPositionAfterRecalculation(
        ?NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $childAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamDbIds $contentStreamDbIds,
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
                $contentStreamDbIds,
                $dimensionSpacePoint
            )
            : $this->projectionContentGraph->getIngoingHierarchyRelationsForNodeAndSubgraph(
                $childAnchorPoint,
                $contentStreamDbIds,
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
        ContentStreamDbIds $contentStreamDbIds,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $newParent,
        NodeRelationAnchorPoint $newChild,
        ?NodeRelationAnchorPoint $newSucceedingSibling = null,
    ): HierarchyRelation {
        $parentSubtreeTags = $this->subtreeTagsForHierarchyRelation($contentStreamDbIds, $newParent, $dimensionSpacePoint);
        $inheritedSubtreeTags = NodeTags::create($sourceHierarchyRelation->subtreeTags->withoutInherited()->all(), $parentSubtreeTags->withoutInherited()->all());
        $copy = new HierarchyRelation(
            HierarchyRelationDbId::createAutoIncremented(),
            $newParent,
            $newChild,
            $contentStreamDbIds->current(),
            $dimensionSpacePoint,
            $dimensionSpacePoint->hash,
            $this->getRelationPosition(
                $newParent,
                $newChild,
                $newSucceedingSibling,
                $contentStreamDbIds,
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
