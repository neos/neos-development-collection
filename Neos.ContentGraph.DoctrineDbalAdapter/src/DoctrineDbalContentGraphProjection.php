<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayer;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\ContentStream;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeMove;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeRemoval;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeVariation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\Workspace;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelationId;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentStreamLayerFinder;
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
use Neos\ContentRepository\Core\Projection\ContentGraph\VirtualContentGraphProjectionInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ProjectionStatus;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Dbal\DbalSchemaDiff;
use Neos\EventStore\Model\EventEnvelope;

/**
 * @internal but the graph projection is api
 */
final class DoctrineDbalContentGraphProjection implements ContentGraphProjectionInterface, VirtualContentGraphProjectionInterface
{
    use ContentStream;
    use NodeMove;
    use NodeRemoval;
    use NodeVariation;
    use SubtreeTagging;
    use Workspace;

    private HierarchyRelationStatement $hierarchyRelationStatement;

    public const RELATION_DEFAULT_OFFSET = 128;

    public function __construct(
        private readonly Connection $dbal,
        private readonly ProjectionContentGraph $projectionContentGraph,
        private readonly ContentGraphTableNames $tableNames,
        private readonly DimensionSpacePointsRepository $dimensionSpacePointsRepository,
        private readonly ContentStreamLayerFinder $contentStreamLayerFinder,
        private readonly ContentGraphReadModelAdapter $contentGraphReadModel,
        private readonly bool $isInSimulation
    ) {
        $this->hierarchyRelationStatement = HierarchyRelationStatement::for($this->tableNames);
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
            RootWorkspaceWasCreated::class => $this->whenRootWorkspaceWasCreated($event),
            SubtreeWasTagged::class => $this->whenSubtreeWasTagged($event),
            SubtreeWasUntagged::class => $this->whenSubtreeWasUntagged($event),
            WorkspaceBaseWorkspaceWasChanged::class => $this->whenWorkspaceBaseWorkspaceWasChanged($event),
            WorkspaceRebaseFailed::class => $this->whenWorkspaceRebaseFailed($event),
            WorkspaceWasCreated::class => $this->whenWorkspaceWasCreated($event),
            WorkspaceWasDiscarded::class => $this->whenWorkspaceWasDiscarded($event),
            WorkspaceWasPublished::class => $this->whenWorkspaceWasPublished($event),
            WorkspaceWasRebased::class => $this->whenWorkspaceWasRebased($event),
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
            // optimises unnecessary write to the connection which has fatal consequences https://github.com/neos/neos-development-collection/issues/5713
            // during command simulation we don't use the content stream version, and thus we can ignore updating this value in the transaction too.
            && !$this->isInSimulation
        ) {
            $this->updateContentStreamVersion($event->getContentStreamId(), $eventEnvelope->version, $event instanceof PublishableToWorkspaceInterface);
        }
    }

    public function closeVirtualization(): void
    {
        $this->dbal->rollBack();
    }

    public function withVirtualization(): VirtualContentGraphProjectionInterface
    {
        if ($this->isInSimulation) {
            throw new \RuntimeException('DBAL ContentGraph Projection is in simulation already', 1778705684);
        }

        $this->dbal->beginTransaction();
        $this->dbal->setRollbackOnly();

        return new self(
            dbal: $this->dbal,
            projectionContentGraph: $this->projectionContentGraph,
            tableNames: $this->tableNames,
            dimensionSpacePointsRepository: $this->dimensionSpacePointsRepository,
            contentStreamLayerFinder: $this->contentStreamLayerFinder,
            contentGraphReadModel: $this->contentGraphReadModel,
            isInSimulation: true,
        );
    }

    private function whenContentStreamWasClosed(ContentStreamWasClosed $event): void
    {
        $this->closeContentStream($event->contentStreamId);
    }

    private function whenContentStreamWasCreated(ContentStreamWasCreated $event): void
    {
        $this->createContentStream($event->contentStreamId);
        $this->dbal->insert($this->tableNames->contentStreamLayer(), [
            'contentStreamId' => $event->contentStreamId->value,
        ]);
    }

    private function whenContentStreamWasForked(ContentStreamWasForked $event): void
    {
        $this->createContentStream($event->newContentStreamId, $event->sourceContentStreamId, $event->versionOfSourceContentStream);

        $sourceContentStreamLayers = $this->contentStreamLayerFinder->getContentStreamLayers($event->sourceContentStreamId);

        $sourceWriteLayerWasWrittenStatement = <<<SQL
            SELECT 1 FROM {$this->tableNames->hierarchyRelation()} AS h
                WHERE h.contentStreamLayer = :sourceContentStreamWriteLayer
            LIMIT 1
            SQL;
        try {
            $addNewWriteLayerToSourceContentStream = (bool)$this->dbal->fetchOne(
                $sourceWriteLayerWasWrittenStatement,
                [
                    'sourceContentStreamWriteLayer' => $sourceContentStreamLayers->getWriteLayer()->value
                ]
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to determine if source content stream layer has changes: %s', $e->getMessage()), 1776339670, $e);
        }

        if ($addNewWriteLayerToSourceContentStream) {
            $this->dbal->insert($this->tableNames->contentStreamLayer(), [
                'contentStreamId' => $event->sourceContentStreamId,
            ]);
            $forkParentReadLayers = $sourceContentStreamLayers;
        } else {
            $forkParentReadLayers = $sourceContentStreamLayers->getParentReadLayers();
        }

        foreach ($forkParentReadLayers?->items ?? [] as $sourceContentStreamLayer) {
            $this->dbal->insert($this->tableNames->contentStreamLayer(), [
                'contentStreamId' => $event->newContentStreamId,
                'contentStreamLayer' => $sourceContentStreamLayer->value
            ]);
        }

        $this->dbal->insert($this->tableNames->contentStreamLayer(), [
            'contentStreamId' => $event->newContentStreamId,
        ]);
    }

    private function whenContentStreamWasRemoved(ContentStreamWasRemoved $event): void
    {
        $contentStreamLayers = $this->contentStreamLayerFinder->getContentStreamLayers($event->contentStreamId);

        $this->removeContentStream($event->contentStreamId);

        $this->dbal->delete($this->tableNames->contentStreamLayer(), [
            'contentStreamId' => $event->contentStreamId->value,
        ]);

        $contentStreamLayerToMergeFrom = null;
        $contentStreamLayerToMergeInto = $contentStreamLayers->getParentReadLayer();
        if ($contentStreamLayerToMergeInto !== null) {
            // Cleanup, due to the removal of the current write layer we possibly free the immediate parent layer if no other content stream forks from it directly
            // If all content streams use the layer and all their next parent layers are equal we close the gap in the layers
            $contentStreamLayerToMergeFromStatement = <<<SQL
                SELECT MIN(subquery.parentLayer) FROM (
                  SELECT DISTINCT MIN(b.contentStreamLayer) AS parentLayer FROM {$this->tableNames->contentStreamLayer()} AS a
                    LEFT JOIN {$this->tableNames->contentStreamLayer()} AS b
                      ON a.contentStreamId = b.contentStreamId
                  WHERE a.contentStreamLayer = :contentStreamLayerCandidate
                    AND b.contentStreamLayer > :contentStreamLayerCandidate
                  GROUP BY b.contentStreamId
                ) AS subquery
                -- return only if there is a single distict result
                HAVING COUNT(parentLayer) = 1
                SQL;

            try {
                $contentStreamLayerToMergeFromResult = $this->dbal->fetchOne(
                    $contentStreamLayerToMergeFromStatement,
                    [
                        'contentStreamLayerCandidate' => $contentStreamLayerToMergeInto->value
                    ]
                );
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf('Failed to load other content stream layer to merge: %s', $e->getMessage()), 1776339670, $e);
            }
            if ($contentStreamLayerToMergeFromResult !== false) {
                $contentStreamLayerToMergeFrom = ContentStreamLayer::fromInt((int)$contentStreamLayerToMergeFromResult);
            }
        }

        if ($contentStreamLayerToMergeFrom !== null && $contentStreamLayerToMergeInto !== null) {
            $mergeHierarchyRelationsStatement = <<<SQL
                INSERT INTO {$this->tableNames->hierarchyRelation()} (
                  id,
                  contentstreamlayer,
                  parentnodeanchor,
                  childnodeanchor,
                  position,
                  subtreetags,
                  dimensionspacepointhash
                )
                SELECT
                  h.id,
                  :contentStreamLayerToMergeInto AS contentstreamlayer,
                  h.parentnodeanchor,
                  h.childnodeanchor,
                  h.position,
                  h.subtreetags,
                  h.dimensionspacepointhash
                -- using table instead of HierarchyRelationStatement because merging is a low level operation and combines exactly two layers without taking any other layers into account
                FROM
                  {$this->tableNames->hierarchyRelation()} AS h
                WHERE
                  h.contentstreamlayer = :contentStreamLayerToMergeFrom
                ON DUPLICATE KEY UPDATE
                  parentnodeanchor = VALUES(parentnodeanchor),
                  childnodeanchor = VALUES(childnodeanchor),
                  position = VALUES(position),
                  subtreetags = VALUES(subtreetags),
                  dimensionspacepointhash = VALUES(dimensionspacepointhash)
                SQL;

            try {
                $this->dbal->executeStatement($mergeHierarchyRelationsStatement, [
                    'contentStreamLayerToMergeInto' => $contentStreamLayerToMergeInto->value,
                    'contentStreamLayerToMergeFrom' => $contentStreamLayerToMergeFrom->value,
                ]);
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf('Failed to merge hierarchy relations: %s', $e->getMessage()), 1776345058, $e);
            }

            if ($contentStreamLayerToMergeInto->equals($contentStreamLayers->getRootLayer())) {
                // when merging into the root layer we remove hierarchies which acted with NULL values as removal marker
                try {
                    $this->dbal->delete($this->tableNames->hierarchyRelation(), [
                        'contentstreamlayer' => $contentStreamLayerToMergeInto->value,
                        'childnodeanchor' => null,
                        'dimensionspacepointhash' => null,
                    ]);
                } catch (DBALException $e) {
                    throw new \RuntimeException(sprintf('Failed to cleanup hierarchy rows with NULL values in root layer after merge: %s', $e->getMessage()), 1776345059, $e);
                }
            }

            try {
                $this->dbal->delete($this->tableNames->hierarchyRelation(), [
                    'contentstreamlayer' => $contentStreamLayerToMergeFrom->value,
                ]);
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf('Failed to delete merged content stream hierarchies: %s', $e->getMessage()), 1776345059, $e);
            }

            try {
                $this->dbal->delete($this->tableNames->contentStreamLayer(), [
                    'contentstreamlayer' => $contentStreamLayerToMergeFrom->value,
                ]);
            } catch (DBALException $e) {
                throw new \RuntimeException(sprintf('Failed to delete merged content stream layer: %s', $e->getMessage()), 1776345059, $e);
            }
        }

        // Drop non-referenced nodes (which will not have a hierarchy relation anymore)
        $deleteNodesStatement = <<<SQL
            DELETE n FROM {$this->tableNames->node()} n
            -- using table instead of HierarchyRelationStatement because node rows can be shared for all layers 
            LEFT JOIN {$this->tableNames->hierarchyRelation()} h
              ON h.childnodeanchor = n.relationanchorpoint
                AND h.contentstreamlayer != :targetContentStreamLayer
            WHERE h.childnodeanchor IS NULL
            SQL;
        try {
            $this->dbal->executeStatement($deleteNodesStatement, [
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to delete non-referenced nodes: %s', $e->getMessage()), 1716489294, $e);
        }

        // Drop non-referenced reference relations (i.e. because the referenced nodes will be gone)
        $deleteReferencesStatement = <<<SQL
            DELETE r FROM {$this->tableNames->referenceRelation()} r
            -- using table instead of HierarchyRelationStatement because node rows can be shared for all layers 
              LEFT JOIN {$this->tableNames->hierarchyRelation()} h
                ON h.childnodeanchor = r.nodeanchorpoint
                AND h.contentstreamlayer != :targetContentStreamLayer
            WHERE h.childnodeanchor IS NULL
            SQL;
        try {
            $this->dbal->executeStatement($deleteReferencesStatement, [
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to delete non-referenced node-references: %s', $e->getMessage()), 1776787534, $e);
        }

        // Drop hierarchy relations
        try {
            $this->dbal->delete($this->tableNames->hierarchyRelation(), [
                'contentstreamlayer' => $contentStreamLayers->getWriteLayer()->value,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to delete hierarchy relations: %s', $e->getMessage()), 1716489265, $e);
        }
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
              contentstreamlayer,
              parentnodeanchor,
              childnodeanchor,
              position,
              subtreetags,
              dimensionspacepointhash
            )
            SELECT
              :targetContentStreamLayer as contentstreamlayer,
              h.parentnodeanchor,
              h.childnodeanchor,
              h.position,
              h.subtreetags,
              :newDimensionSpacePointHash AS dimensionspacepointhash
            FROM
              {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :sourceDimensionSpacePointHash')->toSql()} h
            SQL;
        try {
            $this->dbal->executeStatement($insertHierarchyRelationsStatement, [
                'contentStreamLayers' => $this->getContentStreamLayers($event)->toIntArray(),
                'sourceDimensionSpacePointHash' => $event->source->hash,
                'newDimensionSpacePointHash' => $event->target->hash,
                'targetContentStreamLayer' => $this->getContentStreamLayers($event)->getWriteLayer()->value,
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
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
            INNER JOIN {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :dimensionSpacePointHash')->toSql()} h
                ON h.childnodeanchor = n.relationanchorpoint
                -- find only nodes which have their ORIGIN at the source DimensionSpacePoint,
                -- as we need to rewrite these origins (using copy on write)
                AND n.origindimensionspacepointhash = :dimensionSpacePointHash
            WHERE n.classification != "root"
        SQL;
        try {
            $relationAnchorPoints = $this->dbal->fetchFirstColumn($selectRelationsStatement, [
                'dimensionSpacePointHash' => $event->source->hash,
                'contentStreamLayers' => $this->getContentStreamLayers($event)->toIntArray(),
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to load relation anchor points: %s', $e->getMessage()), 1716489628, $e);
        }
        foreach ($relationAnchorPoints as $relationAnchorPoint) {
            $this->updateNodeRecordWithCopyOnWrite(
                $this->getContentStreamLayers($event),
                NodeRelationAnchorPoint::fromInteger($relationAnchorPoint),
                function (NodeRecord $nodeRecord) use ($event) {
                    $nodeRecord->originDimensionSpacePoint = $event->target->coordinates;
                    $nodeRecord->originDimensionSpacePointHash = $event->target->hash;
                }
            );
        }

        // 2) hierarchy relations
        $updateHierarchyRelationsStatement = <<<SQL
            INSERT INTO {$this->tableNames->hierarchyRelation()}
            (
              id,
              contentstreamlayer,
              parentnodeanchor,
              childnodeanchor,
              position,
              subtreetags,
              dimensionspacepointhash
            )
            SELECT
              h.id,
              :targetContentStreamLayer as contentstreamlayer,
              h.parentnodeanchor,
              h.childnodeanchor,
              h.position,
              h.subtreetags,
              :newDimensionSpacePointHash AS dimensionspacepointhash
            FROM {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :originalDimensionSpacePointHash')->toSql()} AS h
            ON DUPLICATE KEY UPDATE dimensionspacepointhash = VALUES(dimensionspacepointhash)
            SQL;
        try {
            $this->dbal->executeStatement($updateHierarchyRelationsStatement, [
                'originalDimensionSpacePointHash' => $event->source->hash,
                'newDimensionSpacePointHash' => $event->target->hash,
                'contentStreamLayers' => $this->getContentStreamLayers($event)->toIntArray(),
                'targetContentStreamLayer' => $this->getContentStreamLayers($event)->getWriteLayer()->value,
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER
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
                $this->getContentStreamLayers($event),
            ) as $anchorPoint
        ) {
            $this->updateNodeRecordWithCopyOnWrite(
                $this->getContentStreamLayers($event),
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
        $anchorPoints = $this->projectionContentGraph->getAnchorPointsForNodeAggregateInContentStream($event->nodeAggregateId, $this->getContentStreamLayers($event));
        foreach ($anchorPoints as $anchorPoint) {
            $this->updateNodeRecordWithCopyOnWrite(
                $this->getContentStreamLayers($event),
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
        $this->moveNodeAggregate($this->getContentStreamLayers($event), $event->nodeAggregateId, $event->newParentNodeAggregateId, $event->succeedingSiblingsForCoverage);
    }

    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        $this->removeNodeAggregate($this->getContentStreamLayers($event), $event->nodeAggregateId, $event->affectedCoveredDimensionSpacePoints);
    }

    private function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->createNodeWithHierarchy(
            $this->getContentStreamLayers($event),
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
        $this->createNodeGeneralizationVariant($this->getContentStreamLayers($event), $event->nodeAggregateId, $event->sourceOrigin, $event->generalizationOrigin, $event->variantSucceedingSiblings, $eventEnvelope);
    }

    private function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $this->createNodePeerVariant($this->getContentStreamLayers($event), $event->nodeAggregateId, $event->sourceOrigin, $event->peerOrigin, $event->peerSucceedingSiblings, $eventEnvelope);
    }

    private function whenNodePropertiesWereSet(NodePropertiesWereSet $event, EventEnvelope $eventEnvelope): void
    {
        $anchorPoint = $this->projectionContentGraph
            ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                $event->getNodeAggregateId(),
                $event->getOriginDimensionSpacePoint(),
                $this->getContentStreamLayers($event)
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
            $this->getContentStreamLayers($event),
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
                    $this->getContentStreamLayers($event)
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
                $this->getContentStreamLayers($event),
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
                    $this->getContentStreamLayers($event)
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
        $this->createNodeSpecializationVariant($this->getContentStreamLayers($event), $event->nodeAggregateId, $event->sourceOrigin, $event->specializationOrigin, $event->specializationSiblings, $eventEnvelope);
    }

    private function whenRootNodeAggregateDimensionsWereUpdated(RootNodeAggregateDimensionsWereUpdated $event): void
    {
        $rootNodeAnchorPoint = $this->projectionContentGraph
            ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                $event->nodeAggregateId,
                /** the origin DSP of the root node is always the empty dimension ({@see whenRootNodeAggregateWithNodeWasCreated}) */
                OriginDimensionSpacePoint::createWithoutDimensions(),
                $this->getContentStreamLayers($event)
            );
        if ($rootNodeAnchorPoint === null) {
            // should never happen.
            return;
        }

        $ingoingRelations = $this->projectionContentGraph->findIngoingHierarchyRelationsForNode(
            $rootNodeAnchorPoint,
            $this->getContentStreamLayers($event)
        );

        $currentlyCoveredDimensionSpacePoints = [];
        foreach ($ingoingRelations as $ingoingRelation) {
            $currentlyCoveredDimensionSpacePoints[] = $ingoingRelation->dimensionSpacePoint;
        }

        $newlyCoveredDimensionSpacePoints = $event->coveredDimensionSpacePoints->getDifference(DimensionSpacePointSet::fromArray($currentlyCoveredDimensionSpacePoints));

        // add hierarchy edges for newly added dimensions
        $this->connectHierarchy(
            $this->getContentStreamLayers($event),
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
            $this->getContentStreamLayers($event),
            NodeRelationAnchorPoint::forRootEdge(),
            $node->relationAnchorPoint,
            $event->coveredDimensionSpacePoints,
            null
        );
    }

    private function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event): void
    {
        $this->createWorkspace($event->workspaceName, null, $event->newContentStreamId);
    }

    private function whenSubtreeWasTagged(SubtreeWasTagged $event): void
    {
        $this->addSubtreeTag($this->getContentStreamLayers($event), $event->nodeAggregateId, $event->affectedDimensionSpacePoints, $event->tag);
    }

    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
    {
        $this->removeSubtreeTag($this->getContentStreamLayers($event), $event->nodeAggregateId, $event->affectedDimensionSpacePoints, $event->tag);
    }

    private function whenWorkspaceBaseWorkspaceWasChanged(WorkspaceBaseWorkspaceWasChanged $event): void
    {
        $this->updateBaseWorkspace($event->workspaceName, $event->baseWorkspaceName, $event->newContentStreamId);
    }

    private function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event): void
    {
        // legacy handling:
        // before https://github.com/neos/neos-development-collection/pull/4965 this event was emitted and set the content stream status to `REBASE_ERROR`
        // instead of setting the error state on replay for old events we make it almost behave like if the rebase had failed today: reopen the workspaces content stream id
        // the candidateContentStreamId will be removed by the ContentStreamPruner
        $this->reopenContentStream($event->sourceContentStreamId);
    }

    private function whenWorkspaceWasCreated(WorkspaceWasCreated $event): void
    {
        $this->createWorkspace($event->workspaceName, $event->baseWorkspaceName, $event->newContentStreamId);
    }

    private function whenWorkspaceWasDiscarded(WorkspaceWasDiscarded $event): void
    {
        $this->updateWorkspaceContentStreamId($event->workspaceName, $event->newContentStreamId);
    }

    private function whenWorkspaceWasPublished(WorkspaceWasPublished $event): void
    {
        $this->updateWorkspaceContentStreamId($event->sourceWorkspaceName, $event->newSourceContentStreamId);
    }

    private function whenWorkspaceWasRebased(WorkspaceWasRebased $event): void
    {
        $this->updateWorkspaceContentStreamId($event->workspaceName, $event->newContentStreamId);
    }

    private function whenWorkspaceWasRemoved(WorkspaceWasRemoved $event): void
    {
        $this->removeWorkspace($event->workspaceName);
    }

    /** --------------------------------- */

    public function getContentStreamLayers(EmbedsContentStreamId $event): ContentStreamLayers
    {
        return $this->contentStreamLayerFinder->getContentStreamLayers($event->getContentStreamId());
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
            $this->dbal->executeQuery('TRUNCATE table ' . $this->tableNames->contentStreamLayer());
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
        ContentStreamLayers $contentStreamLayersWhereWriteOccurs,
        NodeRelationAnchorPoint $anchorPoint,
        callable $operations
    ): mixed {
        $contentStreamLayersWithMaterializedNode = $this->projectionContentGraph->getAllContentStreamLayersAnchorPointIsContainedIn($anchorPoint);
        if (!$contentStreamLayersWithMaterializedNode->equalsSingle($contentStreamLayersWhereWriteOccurs->getWriteLayer())) {
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
            $copyHierarchyRelationStatement = <<<SQL
                INSERT INTO {$this->tableNames->hierarchyRelation()} (
                  id,
                  parentnodeanchor,
                  childnodeanchor,
                  position,
                  subtreetags,
                  dimensionspacepointhash,
                  contentstreamlayer
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
                  :targetContentStreamLayer as contentstreamlayer
                FROM
                  {$this->hierarchyRelationStatement->toSql()} h
                WHERE 
                  :originalNodeAnchor IN (h.childnodeanchor, h.parentnodeanchor)
                  AND h.contentstreamlayer IN (:contentStreamLayers)
                ON DUPLICATE KEY UPDATE parentnodeanchor = VALUES(parentnodeanchor), childnodeanchor = VALUES(childnodeanchor)
                SQL;

            try {
                $this->dbal->executeStatement($copyHierarchyRelationStatement, [
                    'newNodeAnchor' => $copiedNode->relationAnchorPoint->value,
                    'originalNodeAnchor' => $anchorPoint->value,
                    'contentStreamLayers' => $contentStreamLayersWhereWriteOccurs->toIntArray(),
                    'targetContentStreamLayer' => $contentStreamLayersWhereWriteOccurs->getWriteLayer()->value,
                ], [
                    'contentStreamLayers' => ArrayParameterType::INTEGER,
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
        ContentStreamLayers $contentStreamLayers,
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
                    $contentStreamLayers,
                    $parentNodeAggregateId,
                    $dimensionSpacePoint
                );

                $succeedingSiblingNodeAggregateId = $coverageSucceedingSiblings->getSucceedingSiblingIdForDimensionSpacePoint($dimensionSpacePoint);
                $succeedingSibling = $succeedingSiblingNodeAggregateId
                    ? $this->projectionContentGraph->findNodeInAggregate(
                        $contentStreamLayers,
                        $succeedingSiblingNodeAggregateId,
                        $dimensionSpacePoint
                    )
                    : null;

                if ($parentNode) {
                    $this->connectHierarchy(
                        $contentStreamLayers,
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
        ContentStreamLayers $contentStreamLayers,
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
                $contentStreamLayers,
                $dimensionSpacePoint
            );

            $parentSubtreeTags = $this->subtreeTagsForHierarchyRelation($contentStreamLayers, $parentNodeAnchorPoint, $dimensionSpacePoint);
            $inheritedSubtreeTags = NodeTags::create(SubtreeTags::createEmpty(), $parentSubtreeTags->all());

            $hierarchyRelation = new HierarchyRelation(
                HierarchyRelationId::createAutoIncremented(),
                $contentStreamLayers->getWriteLayer(),
                $parentNodeAnchorPoint,
                $childNodeAnchorPoint,
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
        ContentStreamLayers $contentStreamLayers,
        DimensionSpacePoint $dimensionSpacePoint
    ): int {
        $position = $this->projectionContentGraph->determineHierarchyRelationPosition(
            $parentAnchorPoint,
            $childAnchorPoint,
            $succeedingSiblingAnchorPoint,
            $contentStreamLayers,
            $dimensionSpacePoint
        );

        if ($position % 2 !== 0) {
            $position = $this->getRelationPositionAfterRecalculation(
                $parentAnchorPoint,
                $childAnchorPoint,
                $succeedingSiblingAnchorPoint,
                $contentStreamLayers,
                $dimensionSpacePoint
            );
        }

        return $position;
    }

    private function getRelationPositionAfterRecalculation(
        ?NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $childAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamLayers $contentStreamLayers,
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
                $contentStreamLayers,
                $dimensionSpacePoint
            )
            : $this->projectionContentGraph->getIngoingHierarchyRelationsForNodeAndSubgraph(
                $childAnchorPoint,
                $contentStreamLayers,
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
        ContentStreamLayers $contentStreamLayers,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $newParent,
        NodeRelationAnchorPoint $newChild,
        ?NodeRelationAnchorPoint $newSucceedingSibling = null,
    ): HierarchyRelation {
        $parentSubtreeTags = $this->subtreeTagsForHierarchyRelation($contentStreamLayers, $newParent, $dimensionSpacePoint);
        $inheritedSubtreeTags = NodeTags::create($sourceHierarchyRelation->subtreeTags->withoutInherited()->all(), $parentSubtreeTags->withoutInherited()->all());
        $copy = new HierarchyRelation(
            HierarchyRelationId::createAutoIncremented(),
            $contentStreamLayers->getWriteLayer(),
            $newParent,
            $newChild,
            $dimensionSpacePoint,
            $dimensionSpacePoint->hash,
            $this->getRelationPosition(
                $newParent,
                $newChild,
                $newSucceedingSibling,
                $contentStreamLayers,
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
