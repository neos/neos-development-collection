<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

use Doctrine\DBAL\Connection;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeDisabling;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeMove;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeRemoval;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeVariation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\RestrictionRelations;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Feature\NodeRenaming\Event\NodeAggregateNameWasChanged;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionShineThroughWasAdded;
use Neos\ContentRepository\Feature\DimensionSpaceAdjustment\Event\DimensionSpacePointWasMoved;
use Neos\ContentRepository\Feature\NodeTypeChange\Event\NodeAggregateTypeWasChanged;
use Neos\ContentRepository\Feature\NodeDisabling\Event\NodeAggregateWasEnabled;
use Neos\ContentRepository\Feature\NodeModification\Event\NodePropertiesWereSet;
use Neos\ContentRepository\Feature\NodeReferencing\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\Infrastructure\Projection\AbstractProcessedEventsAwareProjector;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\EventListener\BeforeInvokeInterface;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\EventSourcing\Projection\ProjectionManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\ThrowableStorageInterface;

/**
 * The alternate reality-aware graph projector for the general Doctrine DBAL backend
 */
#[Flow\Scope('singleton')]
class GraphProjector extends AbstractProcessedEventsAwareProjector implements BeforeInvokeInterface
{
    use NodeVariation;
    use NodeDisabling;
    use RestrictionRelations;
    use NodeRemoval;
    use NodeMove;
    use ProjectorEventHandlerTrait;

    public const RELATION_DEFAULT_OFFSET = 128;

    protected ProjectionContentGraph $projectionContentGraph;

    private ThrowableStorageInterface $throwableStorage;

    private DbalClientInterface $databaseClient;

    private bool $doingFullReplayOfProjection = false;

    public function __construct(
        DbalClientInterface $eventStorageDatabaseClient,
        VariableFrontend $processedEventsCache,
        ProjectionContentGraph $projectionContentGraph,
        ThrowableStorageInterface $throwableStorage
    ) {
        $this->databaseClient = $eventStorageDatabaseClient;
        $this->projectionContentGraph = $projectionContentGraph;
        $this->throwableStorage = $throwableStorage;
        parent::__construct($eventStorageDatabaseClient, $processedEventsCache);
    }

    public function beforeInvoke(EventEnvelope $eventEnvelope): void
    {
        $this->triggerBeforeInvokeHandlers(
            $eventEnvelope,
            $this->doingFullReplayOfProjection
        );
    }

    public function afterInvoke(EventEnvelope $eventEnvelope): void
    {
        try {
            $this->triggerAfterInvokeHandlers(
                $eventEnvelope,
                $this->doingFullReplayOfProjection
            );
        } catch (\Throwable $e) {
            $this->systemLogger->critical($this->throwableStorage->logThrowable($e));
        }

        parent::afterInvoke($eventEnvelope);
    }

    /**
     * @throws \Throwable
     */
    public function reset(): void
    {
        parent::reset();
        $this->truncateDatabaseTables();

        /**
         * Performance optimization: reset() is only called at the start of a {@see ProjectionManager::replay()}.
         * In this case, we do not need to trigger cache flushes;
         * so we need to remember here whether we run a full replay right now
         */
        $this->doingFullReplayOfProjection = true;
        $this->assumeProjectorRunsSynchronously();
    }

    public function resetForTests(): void
    {
        parent::reset();
        $this->truncateDatabaseTables();
    }

    private function truncateDatabaseTables(): void
    {
        $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_node');
        $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_hierarchyrelation');
        $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_referencerelation');
        $this->getDatabaseConnection()->executeQuery('TRUNCATE table neos_contentgraph_restrictionrelation');
    }

    /**
     * @throws \Throwable
     */
    final public function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event): void
    {
        $nodeRelationAnchorPoint = NodeRelationAnchorPoint::create();
        $dimensionSpacePoint = DimensionSpacePoint::fromArray([]);
        $node = new NodeRecord(
            $nodeRelationAnchorPoint,
            $event->nodeAggregateIdentifier,
            $dimensionSpacePoint->coordinates,
            $dimensionSpacePoint->hash,
            SerializedPropertyValues::fromArray([]),
            $event->nodeTypeName,
            $event->nodeAggregateClassification
        );

        $this->transactional(function () use ($node, $event) {
            $node->addToDatabase($this->getDatabaseConnection());
            $this->connectHierarchy(
                $event->contentStreamIdentifier,
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
    final public function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            $this->createNodeWithHierarchy(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->nodeTypeName,
                $event->parentNodeAggregateIdentifier,
                $event->originDimensionSpacePoint,
                $event->coveredDimensionSpacePoints,
                $event->initialPropertyValues,
                $event->nodeAggregateClassification,
                $event->succeedingNodeAggregateIdentifier,
                $event->nodeName
            );

            $this->connectRestrictionRelationsFromParentNodeToNewlyCreatedNode(
                $event->contentStreamIdentifier,
                $event->parentNodeAggregateIdentifier,
                $event->nodeAggregateIdentifier,
                $event->coveredDimensionSpacePoints
            );
        });
    }

    /**
     * @throws \Throwable
     */
    final public function whenNodeAggregateNameWasChanged(NodeAggregateNameWasChanged $event): void
    {
        $this->transactional(function () use ($event) {
            $this->getDatabaseConnection()->executeUpdate('
                UPDATE neos_contentgraph_hierarchyrelation h
                inner join neos_contentgraph_node n on
                    h.childnodeanchor = n.relationanchorpoint
                SET
                  h.name = :newName
                WHERE
                    n.nodeaggregateidentifier = :nodeAggregateIdentifier
                    and h.contentstreamidentifier = :contentStreamIdentifier
            ', [
                'newName' => (string)$event->getNewNodeName(),
                'nodeAggregateIdentifier' => (string)$event->getNodeAggregateIdentifier(),
                'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
            ]);
        });
    }

    /**
     * Copy the restriction edges from the parent Node to the newly created child node;
     * so that newly created nodes inherit the visibility constraints of the parent.
     * @throws \Doctrine\DBAL\DBALException
     */
    private function connectRestrictionRelationsFromParentNodeToNewlyCreatedNode(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeAggregateIdentifier $newlyCreatedNodeAggregateIdentifier,
        DimensionSpacePointSet $dimensionSpacePointsInWhichNewlyCreatedNodeAggregateIsVisible
    ): void {
        // TODO: still unsure why we need an "INSERT IGNORE" here;
        // normal "INSERT" can trigger a duplicate key constraint exception
        $this->getDatabaseConnection()->executeUpdate('
                INSERT IGNORE INTO neos_contentgraph_restrictionrelation (
                  contentstreamidentifier,
                  dimensionspacepointhash,
                  originnodeaggregateidentifier,
                  affectednodeaggregateidentifier
                )
                SELECT
                  r.contentstreamidentifier,
                  r.dimensionspacepointhash,
                  r.originnodeaggregateidentifier,
                  "' . $newlyCreatedNodeAggregateIdentifier . '" as affectednodeaggregateidentifier
                FROM
                    neos_contentgraph_restrictionrelation r
                    WHERE
                        r.contentstreamidentifier = :sourceContentStreamIdentifier
                        and r.dimensionspacepointhash IN (:visibleDimensionSpacePoints)
                        and r.affectednodeaggregateidentifier = :parentNodeAggregateIdentifier
            ', [
            'sourceContentStreamIdentifier' => (string)$contentStreamIdentifier,
            'visibleDimensionSpacePoints' => $dimensionSpacePointsInWhichNewlyCreatedNodeAggregateIsVisible
                ->getPointHashes(),
            'parentNodeAggregateIdentifier' => (string)$parentNodeAggregateIdentifier
        ], [
            'visibleDimensionSpacePoints' => Connection::PARAM_STR_ARRAY
        ]);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createNodeWithHierarchy(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeTypeName $nodeTypeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $visibleInDimensionSpacePoints,
        SerializedPropertyValues $propertyDefaultValuesAndTypes,
        NodeAggregateClassification $nodeAggregateClassification,
        NodeAggregateIdentifier $succeedingSiblingNodeAggregateIdentifier = null,
        NodeName $nodeName = null
    ): void {
        $nodeRelationAnchorPoint = NodeRelationAnchorPoint::create();
        $node = new NodeRecord(
            $nodeRelationAnchorPoint,
            $nodeAggregateIdentifier,
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
                    $contentStreamIdentifier,
                    $parentNodeAggregateIdentifier,
                    $dimensionSpacePoint
                );

                $succeedingSibling = $succeedingSiblingNodeAggregateIdentifier
                    ? $this->projectionContentGraph->findNodeInAggregate(
                        $contentStreamIdentifier,
                        $succeedingSiblingNodeAggregateIdentifier,
                        $dimensionSpacePoint
                    )
                    : null;

                if ($parentNode) {
                    $this->connectHierarchy(
                        $contentStreamIdentifier,
                        $parentNode->relationAnchorPoint,
                        $nodeRelationAnchorPoint,
                        new DimensionSpacePointSet([$dimensionSpacePoint]),
                        $succeedingSibling?->relationAnchorPoint,
                        $nodeName
                    );
                }
            }
        }

        $node->addToDatabase($this->getDatabaseConnection());
    }

    /**
     * @param NodeRelationAnchorPoint $parentNodeAnchorPoint
     * @param NodeRelationAnchorPoint $childNodeAnchorPoint
     * @param NodeRelationAnchorPoint|null $succeedingSiblingNodeAnchorPoint
     * @param NodeName|null $relationName
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePointSet $dimensionSpacePointSet
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function connectHierarchy(
        ContentStreamIdentifier $contentStreamIdentifier,
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
                $contentStreamIdentifier,
                $dimensionSpacePoint
            );

            $hierarchyRelation = new HierarchyRelation(
                $parentNodeAnchorPoint,
                $childNodeAnchorPoint,
                $relationName,
                $contentStreamIdentifier,
                $dimensionSpacePoint,
                $dimensionSpacePoint->hash,
                $position
            );

            $hierarchyRelation->addToDatabase($this->getDatabaseConnection());
        }
    }

    /**
     * @param NodeRelationAnchorPoint|null $parentAnchorPoint
     * @param NodeRelationAnchorPoint|null $childAnchorPoint
     * @param NodeRelationAnchorPoint|null $succeedingSiblingAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getRelationPosition(
        ?NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $childAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): int {
        $position = $this->projectionContentGraph->determineHierarchyRelationPosition(
            $parentAnchorPoint,
            $childAnchorPoint,
            $succeedingSiblingAnchorPoint,
            $contentStreamIdentifier,
            $dimensionSpacePoint
        );

        if ($position % 2 !== 0) {
            $position = $this->getRelationPositionAfterRecalculation(
                $parentAnchorPoint,
                $childAnchorPoint,
                $succeedingSiblingAnchorPoint,
                $contentStreamIdentifier,
                $dimensionSpacePoint
            );
        }

        return $position;
    }

    /**
     * @param NodeRelationAnchorPoint|null $parentAnchorPoint
     * @param NodeRelationAnchorPoint|null $childAnchorPoint
     * @param NodeRelationAnchorPoint|null $succeedingSiblingAnchorPoint
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getRelationPositionAfterRecalculation(
        ?NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $childAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamIdentifier $contentStreamIdentifier,
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
                $contentStreamIdentifier,
                $dimensionSpacePoint
            )
            : $this->projectionContentGraph->getIngoingHierarchyRelationsForNodeAndSubgraph(
                $childAnchorPoint,
                $contentStreamIdentifier,
                $dimensionSpacePoint
            );

        foreach ($hierarchyRelations as $relation) {
            $offset += self::RELATION_DEFAULT_OFFSET;
            if (
                $succeedingSiblingAnchorPoint
                && (string)$relation->childNodeAnchor === (string)$succeedingSiblingAnchorPoint
            ) {
                $position = $offset;
                $offset += self::RELATION_DEFAULT_OFFSET;
            }
            $relation->assignNewPosition($offset, $this->getDatabaseConnection());
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
                INSERT INTO neos_contentgraph_hierarchyrelation (
                  parentnodeanchor,
                  childnodeanchor,
                  `name`,
                  position,
                  dimensionspacepoint,
                  dimensionspacepointhash,
                  contentstreamidentifier
                )
                SELECT
                  h.parentnodeanchor,
                  h.childnodeanchor,
                  h.name,
                  h.position,
                  h.dimensionspacepoint,
                  h.dimensionspacepointhash,
                  "' . (string)$event->getContentStreamIdentifier() . '" AS contentstreamidentifier
                FROM
                    neos_contentgraph_hierarchyrelation h
                    WHERE h.contentstreamidentifier = :sourceContentStreamIdentifier
            ', [
                'sourceContentStreamIdentifier' => (string)$event->getSourceContentStreamIdentifier()
            ]);

            //
            // 2) copy Hidden Node information to second content stream
            //
            $this->getDatabaseConnection()->executeUpdate('
                INSERT INTO neos_contentgraph_restrictionrelation (
                  contentstreamidentifier,
                  dimensionspacepointhash,
                  originnodeaggregateidentifier,
                  affectednodeaggregateidentifier
                )
                SELECT
                  "' . (string)$event->getContentStreamIdentifier() . '" AS contentstreamidentifier,
                  r.dimensionspacepointhash,
                  r.originnodeaggregateidentifier,
                  r.affectednodeaggregateidentifier
                FROM
                    neos_contentgraph_restrictionrelation r
                    WHERE r.contentstreamidentifier = :sourceContentStreamIdentifier
            ', [
                'sourceContentStreamIdentifier' => (string)$event->getSourceContentStreamIdentifier()
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
                DELETE FROM neos_contentgraph_hierarchyrelation
                WHERE
                    contentstreamidentifier = :contentStreamIdentifier
            ', [
                'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
            ]);

            // Drop non-referenced nodes (which do not have a hierarchy relation anymore)
            $this->getDatabaseConnection()->executeUpdate('
                DELETE FROM neos_contentgraph_node
                WHERE NOT EXISTS
                    (
                        SELECT 1 FROM neos_contentgraph_hierarchyrelation
                        WHERE neos_contentgraph_hierarchyrelation.childnodeanchor
                                  = neos_contentgraph_node.relationanchorpoint
                    )
            ');

            // Drop non-referenced reference relations (i.e. because the referenced nodes are gone by now)
            $this->getDatabaseConnection()->executeUpdate('
                DELETE FROM neos_contentgraph_referencerelation
                WHERE NOT EXISTS
                    (
                        SELECT 1 FROM neos_contentgraph_node
                        WHERE neos_contentgraph_node.relationanchorpoint
                                  = neos_contentgraph_referencerelation.nodeanchorpoint
                    )
            ');

            // Drop restriction relations
            $this->getDatabaseConnection()->executeUpdate('
                DELETE FROM neos_contentgraph_restrictionrelation
                WHERE
                    contentstreamidentifier = :contentStreamIdentifier
            ', [
                'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
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
            $this->updateNodeWithCopyOnWrite($event, function (NodeRecord $node) {
            });

            $nodeAnchorPoint = $this->projectionContentGraph
                ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                    $event->sourceNodeAggregateIdentifier,
                    $event->sourceOriginDimensionSpacePoint,
                    $event->contentStreamIdentifier
                );

            // remove old
            $this->getDatabaseConnection()->delete('neos_contentgraph_referencerelation', [
                'nodeanchorpoint' => $nodeAnchorPoint,
                'name' => $event->referenceName
            ]);

            // set new
            $position = 0;
            foreach ($event->destinationNodeAggregateIdentifiers as $destinationNodeIdentifier) {
                $this->getDatabaseConnection()->insert('neos_contentgraph_referencerelation', [
                    'name' => $event->referenceName,
                    'position' => $position,
                    'nodeanchorpoint' => $nodeAnchorPoint,
                    'destinationnodeaggregateidentifier' => $destinationNodeIdentifier,
                ]);
                $position++;
            }
        });
    }

    private function cascadeRestrictionRelations(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeAggregateIdentifier $entryNodeAggregateIdentifier,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        $this->getDatabaseConnection()->executeUpdate(
            '
            -- GraphProjector::cascadeRestrictionRelations
            INSERT INTO neos_contentgraph_restrictionrelation
            (
                -- we build a recursive tree
                with recursive tree as (
                     -- --------------------------------
                     -- INITIAL query: select the nodes of the given entry node aggregate as roots of the tree
                     -- --------------------------------
                     select
                        n.relationanchorpoint,
                        n.nodeaggregateidentifier,
                        h.dimensionspacepointhash
                     from
                        neos_contentgraph_node n
                     -- we need to join with the hierarchy relation, because we need the dimensionspacepointhash.
                     inner join neos_contentgraph_hierarchyrelation h
                        on h.childnodeanchor = n.relationanchorpoint
                     where
                        n.nodeaggregateidentifier = :entryNodeAggregateIdentifier
                        and h.contentstreamidentifier = :contentStreamIdentifier
                        and h.dimensionspacepointhash in (:dimensionSpacePointHashes)
                union
                     -- --------------------------------
                     -- RECURSIVE query: do one "child" query step
                     -- --------------------------------
                     select
                        c.relationanchorpoint,
                        c.nodeaggregateidentifier,
                        h.dimensionspacepointhash
                     from
                        tree p
                     inner join neos_contentgraph_hierarchyrelation h
                        on h.parentnodeanchor = p.relationanchorpoint
                     inner join neos_contentgraph_node c
                        on h.childnodeanchor = c.relationanchorpoint
                     where
                        h.contentstreamidentifier = :contentStreamIdentifier
                        and h.dimensionspacepointhash in (:dimensionSpacePointHashes)
                )

                     -- --------------------------------
                     -- create new restriction relations...
                     -- --------------------------------
                SELECT
                    "' . (string)$contentStreamIdentifier . '" as contentstreamidentifier,
                    tree.dimensionspacepointhash,
                    originnodeaggregateidentifier,
                    tree.nodeaggregateidentifier as affectednodeaggregateidentifier
                FROM tree
                     -- --------------------------------
                     -- ...by joining the tree with all restriction relations ingoing to the given parent
                     -- --------------------------------
                    INNER JOIN (
                        SELECT originnodeaggregateidentifier FROM neos_contentgraph_restrictionrelation
                            WHERE contentstreamidentifier = :contentStreamIdentifier
                            AND affectednodeaggregateidentifier = :parentNodeAggregateIdentifier
                            AND dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)
                    ) AS joinedrestrictingancestors
            )',
            [
                'contentStreamIdentifier' => (string)$contentStreamIdentifier,
                'parentNodeAggregateIdentifier' => (string)$parentNodeAggregateIdentifier,
                'entryNodeAggregateIdentifier' => (string)$entryNodeAggregateIdentifier,
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
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->affectedDimensionSpacePoints
            );
        });
    }

    /**
     * @param HierarchyRelation $sourceHierarchyRelation
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param NodeRelationAnchorPoint|null $newParent
     * @param NodeRelationAnchorPoint|null $newChild
     * @return HierarchyRelation
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function copyHierarchyRelationToDimensionSpacePoint(
        HierarchyRelation $sourceHierarchyRelation,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        ?NodeRelationAnchorPoint $newParent = null,
        ?NodeRelationAnchorPoint $newChild = null
    ): HierarchyRelation {
        $copy = new HierarchyRelation(
            $newParent ?: $sourceHierarchyRelation->parentNodeAnchor,
            $newChild ?: $sourceHierarchyRelation->childNodeAnchor,
            $sourceHierarchyRelation->name,
            $contentStreamIdentifier,
            $dimensionSpacePoint,
            $dimensionSpacePoint->hash,
            $this->getRelationPosition(
                $newParent ?: $sourceHierarchyRelation->parentNodeAnchor,
                $newChild ?: $sourceHierarchyRelation->childNodeAnchor,
                null, // todo: find proper sibling
                $contentStreamIdentifier,
                $dimensionSpacePoint
            )
        );
        $copy->addToDatabase($this->getDatabaseConnection());

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
            $sourceNode->nodeAggregateIdentifier,
            $originDimensionSpacePoint->jsonSerialize(),
            $originDimensionSpacePoint->hash,
            $sourceNode->properties,
            $sourceNode->nodeTypeName,
            $sourceNode->classification,
            $sourceNode->nodeName
        );
        $copy->addToDatabase($this->getDatabaseConnection());

        return $copy;
    }

    public function whenNodeAggregateTypeWasChanged(NodeAggregateTypeWasChanged $event): void
    {
        $this->transactional(function () use ($event) {
            $anchorPoints = $this->projectionContentGraph->getAnchorPointsForNodeAggregateInContentStream(
                $event->nodeAggregateIdentifier,
                $event->contentStreamIdentifier
            );

            foreach ($anchorPoints as $anchorPoint) {
                $this->updateNodeRecordWithCopyOnWrite(
                    $event->contentStreamIdentifier,
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
    protected function updateNodeWithCopyOnWrite(DomainEventInterface $event, callable $operations): mixed
    {
        switch (get_class($event)) {
            case NodeReferencesWereSet::class:
                /** @var NodeReferencesWereSet $event */
                $anchorPoint = $this->projectionContentGraph
                    ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                        $event->sourceNodeAggregateIdentifier,
                        $event->sourceOriginDimensionSpacePoint,
                        $event->contentStreamIdentifier
                    );
                break;
            default:
                if (
                    method_exists($event, 'getNodeAggregateIdentifier')
                    && method_exists($event, 'getOriginDimensionSpacePoint')
                    && method_exists($event, 'getContentStreamIdentifier')
                ) {
                    $anchorPoint = $this->projectionContentGraph
                        ->getAnchorPointForNodeAndOriginDimensionSpacePointAndContentStream(
                            $event->getNodeAggregateIdentifier(),
                            $event->getOriginDimensionSpacePoint(),
                            $event->getContentStreamIdentifier()
                        );
                } else {
                    throw new \InvalidArgumentException(
                        'Cannot update node with copy on write for events of type '
                            . get_class($event) . ' since they provide no NodeAggregateIdentifier, '
                            . 'OriginDimensionSpacePoint or ContentStreamIdentifier',
                        1645303167
                    );
                }
        }
        if (is_null($anchorPoint)) {
            throw new \InvalidArgumentException(
                'Cannot update node with copy on write since no anchor point could be resolved for node '
                    . $event->getNodeAggregateIdentifier() . ' in content stream '
                    . $event->getContentStreamIdentifier(),
                1645303332
            );
        }

        return $this->updateNodeRecordWithCopyOnWrite(
            $event->getContentStreamIdentifier(),
            $anchorPoint,
            $operations
        );
    }

    protected function updateNodeRecordWithCopyOnWrite(
        ContentStreamIdentifier $contentStreamIdentifierWhereWriteOccurs,
        NodeRelationAnchorPoint $anchorPoint,
        callable $operations
    ): mixed {
        $contentStreamIdentifiers = $this->projectionContentGraph
            ->getAllContentStreamIdentifiersAnchorPointIsContainedIn($anchorPoint);
        if (count($contentStreamIdentifiers) > 1) {
            // Copy on Write needed!
            // Copy on Write is a purely "Content Stream" related concept;
            // thus we do not care about different DimensionSpacePoints here (but we copy all edges)

            // 1) fetch node, adjust properties, assign new Relation Anchor Point
            /** @var NodeRecord $copiedNode The anchor point appears in a content stream, so there must be a node */
            $copiedNode = $this->projectionContentGraph->getNodeByAnchorPoint($anchorPoint);
            $copiedNode->relationAnchorPoint = NodeRelationAnchorPoint::create();
            $result = $operations($copiedNode);
            $copiedNode->addToDatabase($this->getDatabaseConnection());

            // 2) reconnect all edges belonging to this content stream to the new "copied node".
            // IMPORTANT: We need to reconnect BOTH the incoming and outgoing edges.
            $this->getDatabaseConnection()->executeStatement(
                '
                UPDATE neos_contentgraph_hierarchyrelation h
                    SET
                        -- if our (copied) node is the child, we update h.childNodeAnchor
                        h.childnodeanchor
                            = IF(h.childnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.childnodeanchor),

                        -- if our (copied) node is the parent, we update h.parentNodeAnchor
                        h.parentnodeanchor
                            = IF(h.parentnodeanchor = :originalNodeAnchor, :newNodeAnchor, h.parentnodeanchor)
                    WHERE
                      :originalNodeAnchor IN (h.childnodeanchor, h.parentnodeanchor)
                      AND h.contentstreamidentifier = :contentStreamIdentifier',
                [
                    'newNodeAnchor' => (string)$copiedNode->relationAnchorPoint,
                    'originalNodeAnchor' => (string)$anchorPoint,
                    'contentStreamIdentifier' => (string)$contentStreamIdentifierWhereWriteOccurs
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
            $node->updateToDatabase($this->getDatabaseConnection());
        }
        return $result;
    }


    protected function copyReferenceRelations(
        NodeRelationAnchorPoint $sourceRelationAnchorPoint,
        NodeRelationAnchorPoint $destinationRelationAnchorPoint
    ): void {
        $this->getDatabaseConnection()->executeStatement('
                INSERT INTO neos_contentgraph_referencerelation (
                  nodeanchorpoint,
                  name,
                  position,
                  destinationnodeaggregateidentifier
                )
                SELECT
                  :destinationRelationAnchorPoint AS nodeanchorpoint,
                  ref.name,
                  ref.position,
                  ref.destinationnodeaggregateidentifier
                FROM
                    neos_contentgraph_referencerelation ref
                    WHERE ref.nodeanchorpoint = :sourceNodeAnchorPoint
            ', [
            'sourceNodeAnchorPoint' => (string)$sourceRelationAnchorPoint,
            'destinationRelationAnchorPoint' => (string)$destinationRelationAnchorPoint
        ]);
    }

    public function whenDimensionSpacePointWasMoved(DimensionSpacePointWasMoved $event): void
    {
        $this->transactional(function () use ($event) {
            // the ordering is important - we first update the OriginDimensionSpacePoints, as we need the
            // hierarchy relations for this query. Then, we update the Hierarchy Relations.

            // 1) originDimensionSpacePoint on Node
            $rel = $this->getDatabaseConnection()->executeQuery(
                'SELECT n.relationanchorpoint, n.origindimensionspacepointhash FROM neos_contentgraph_node n
                     INNER JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint

                     AND h.contentstreamidentifier = :contentStreamIdentifier
                     AND h.dimensionspacepointhash = :dimensionSpacePointHash
                     -- find only nodes which have their ORIGIN at the source DimensionSpacePoint,
                     -- as we need to rewrite these origins (using copy on write)
                     AND n.origindimensionspacepointhash = :dimensionSpacePointHash
                ',
                [
                    'dimensionSpacePointHash' => $event->getSource()->hash,
                    'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
                ]
            );
            while ($res = $rel->fetchAssociative()) {
                $relationAnchorPoint = NodeRelationAnchorPoint::fromString($res['relationanchorpoint']);
                $this->updateNodeRecordWithCopyOnWrite(
                    $event->getContentStreamIdentifier(),
                    $relationAnchorPoint,
                    function (NodeRecord $nodeRecord) use ($event) {
                        $nodeRecord->originDimensionSpacePoint = $event->getTarget()->jsonSerialize();
                        $nodeRecord->originDimensionSpacePointHash = $event->getTarget()->hash;
                    }
                );
            }

            // 2) hierarchy relations
            $this->getDatabaseConnection()->executeStatement(
                '
                UPDATE neos_contentgraph_hierarchyrelation h
                    SET
                        h.dimensionspacepoint = :newDimensionSpacePoint,
                        h.dimensionspacepointhash = :newDimensionSpacePointHash
                    WHERE
                      h.dimensionspacepointhash = :originalDimensionSpacePointHash
                      AND h.contentstreamidentifier = :contentStreamIdentifier
                      ',
                [
                    'originalDimensionSpacePointHash' => $event->getSource()->hash,
                    'newDimensionSpacePointHash' => $event->getTarget()->hash,
                    'newDimensionSpacePoint' => json_encode($event->getTarget()->jsonSerialize()),
                    'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
                ]
            );

            // 3) restriction relations
            $this->getDatabaseConnection()->executeStatement(
                '
                UPDATE neos_contentgraph_restrictionrelation r
                    SET
                        r.dimensionspacepointhash = :newDimensionSpacePointHash
                    WHERE
                      r.dimensionspacepointhash = :originalDimensionSpacePointHash
                      AND r.contentstreamidentifier = :contentStreamIdentifier
                      ',
                [
                    'originalDimensionSpacePointHash' => $event->getSource()->hash,
                    'newDimensionSpacePointHash' => $event->getTarget()->hash,
                    'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier()
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
                INSERT INTO neos_contentgraph_hierarchyrelation (
                  parentnodeanchor,
                  childnodeanchor,
                  `name`,
                  position,
                  dimensionspacepoint,
                  dimensionspacepointhash,
                  contentstreamidentifier
                )
                SELECT
                  h.parentnodeanchor,
                  h.childnodeanchor,
                  h.name,
                  h.position,
                 :newDimensionSpacePoint AS dimensionspacepoint,
                 :newDimensionSpacePointHash AS dimensionspacepointhash,
                  h.contentstreamidentifier
                FROM
                    neos_contentgraph_hierarchyrelation h
                    WHERE h.contentstreamidentifier = :contentStreamIdentifier
                    AND h.dimensionspacepointhash = :sourceDimensionSpacePointHash',
                [
                    'contentStreamIdentifier' => $event->getContentStreamIdentifier()->jsonSerialize(),
                    'sourceDimensionSpacePointHash' => $event->getSource()->hash,
                    'newDimensionSpacePointHash' => $event->getTarget()->hash,
                    'newDimensionSpacePoint' => json_encode($event->getTarget()->jsonSerialize()),
                ]
            );

            // 2) restriction relations
            $this->getDatabaseConnection()->executeUpdate('
                INSERT INTO neos_contentgraph_restrictionrelation (
                  contentstreamidentifier,
                  dimensionspacepointhash,
                  originnodeaggregateidentifier,
                  affectednodeaggregateidentifier
                )
                SELECT
                  r.contentstreamidentifier,
                  :targetDimensionSpacePointHash,
                  r.originnodeaggregateidentifier,
                  r.affectednodeaggregateidentifier
                FROM
                    neos_contentgraph_restrictionrelation r
                    WHERE r.contentstreamidentifier = :contentStreamIdentifier
                    AND r.dimensionspacepointhash = :sourceDimensionSpacePointHash

            ', [
                'contentStreamIdentifier' => (string)$event->getContentStreamIdentifier(),
                'sourceDimensionSpacePointHash' => $event->getSource()->hash,
                'targetDimensionSpacePointHash' => $event->getTarget()->hash
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
        return $this->databaseClient->getConnection();
    }
}
