<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelationRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ReferenceRelation;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeVariantWasReset;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Psr\Log\LoggerInterface;

/**
 * The NodeVariation projection feature trait
 */
trait NodeVariation
{
    protected LoggerInterface $systemLogger;

    /**
     * @param NodeSpecializationVariantWasCreated $event
     * @throws \Exception
     * @throws \Throwable
     */
    public function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            // Do the actual specialization
            $sourceNode = $this->projectionContentGraph->findNodeInAggregate(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->sourceOrigin->toDimensionSpacePoint()
            );
            if (is_null($sourceNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }

            $specializedNode = $this->copyNodeToDimensionSpacePoint(
                $sourceNode,
                $event->specializationOrigin
            );

            $uncoveredDimensionSpacePoints = $event->specializationCoverage->points;
            foreach (
                $this->projectionContentGraph->findIngoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamIdentifier,
                    $sourceNode->nodeAggregateIdentifier,
                    $event->specializationCoverage
                ) as $hierarchyRelation
            ) {
                $hierarchyRelation->assignNewChildNode(
                    $specializedNode->relationAnchorPoint,
                    $this->getDatabaseConnection()
                );
                unset($uncoveredDimensionSpacePoints[$hierarchyRelation->dimensionSpacePointHash]);
            }
            if (!empty($uncoveredDimensionSpacePoints)) {
                $sourceParent = $this->projectionContentGraph->findParentNode(
                    $event->contentStreamIdentifier,
                    $event->nodeAggregateIdentifier,
                    $event->sourceOrigin,
                );
                if (is_null($sourceParent)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing(get_class($event));
                }
                foreach ($uncoveredDimensionSpacePoints as $uncoveredDimensionSpacePoint) {
                    $parentNode = $this->projectionContentGraph->findNodeInAggregate(
                        $event->contentStreamIdentifier,
                        $sourceParent->nodeAggregateIdentifier,
                        $uncoveredDimensionSpacePoint
                    );
                    if (is_null($parentNode)) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                            get_class($event)
                        );
                    }

                    $hierarchyRelation = new HierarchyRelationRecord(
                        $parentNode->relationAnchorPoint,
                        $specializedNode->relationAnchorPoint,
                        $sourceNode->nodeName,
                        $event->contentStreamIdentifier,
                        $uncoveredDimensionSpacePoint,
                        $uncoveredDimensionSpacePoint->hash,
                        $this->projectionContentGraph->determineHierarchyRelationPosition(
                            $parentNode->relationAnchorPoint,
                            $specializedNode->relationAnchorPoint,
                            null,
                            $event->contentStreamIdentifier,
                            $uncoveredDimensionSpacePoint
                        )
                    );
                    $hierarchyRelation->addToDatabase($this->getDatabaseConnection());
                }
            }

            foreach (
                $this->projectionContentGraph->findOutgoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamIdentifier,
                    $sourceNode->nodeAggregateIdentifier,
                    $event->specializationCoverage
                ) as $hierarchyRelation
            ) {
                $hierarchyRelation->assignNewParentNode(
                    $specializedNode->relationAnchorPoint,
                    null,
                    $this->getDatabaseConnection()
                );
            }

            // Copy Reference Edges
            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $specializedNode->relationAnchorPoint
            );
        });
    }

    /**
     * @param NodeGeneralizationVariantWasCreated $event
     * @throws \Exception
     * @throws \Throwable
     */
    public function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            // do the generalization
            $sourceNode = $this->projectionContentGraph->findNodeInAggregate(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->sourceOrigin->toDimensionSpacePoint()
            );
            if (is_null($sourceNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }
            $sourceParentNode = $this->projectionContentGraph->findParentNode(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->sourceOrigin
            );
            if (is_null($sourceParentNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing(get_class($event));
            }
            $generalizedNode = $this->copyNodeToDimensionSpacePoint(
                $sourceNode,
                $event->generalizationOrigin
            );

            $unassignedIngoingDimensionSpacePoints = $event->generalizationCoverage;
            foreach (
                $this->projectionContentGraph->findIngoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamIdentifier,
                    $event->nodeAggregateIdentifier,
                    $event->generalizationCoverage
                ) as $existingIngoingHierarchyRelation
            ) {
                $existingIngoingHierarchyRelation->assignNewChildNode(
                    $generalizedNode->relationAnchorPoint,
                    $this->getDatabaseConnection()
                );
                $unassignedIngoingDimensionSpacePoints = $unassignedIngoingDimensionSpacePoints->getDifference(
                    new DimensionSpacePointSet([
                        $existingIngoingHierarchyRelation->dimensionSpacePoint
                    ])
                );
            }

            foreach (
                $this->projectionContentGraph->findOutgoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamIdentifier,
                    $event->nodeAggregateIdentifier,
                    $event->generalizationCoverage
                ) as $existingOutgoingHierarchyRelation
            ) {
                $existingOutgoingHierarchyRelation->assignNewParentNode(
                    $generalizedNode->relationAnchorPoint,
                    null,
                    $this->getDatabaseConnection()
                );
            }

            if (count($unassignedIngoingDimensionSpacePoints) > 0) {
                $ingoingSourceHierarchyRelation = $this->projectionContentGraph->findIngoingHierarchyRelationsForNode(
                    $sourceNode->relationAnchorPoint,
                    $event->contentStreamIdentifier,
                    new DimensionSpacePointSet([$event->sourceOrigin->toDimensionSpacePoint()])
                )[$event->sourceOrigin->hash] ?? null;
                if (is_null($ingoingSourceHierarchyRelation)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheIngoingSourceHierarchyRelationIsMissing(
                        get_class($event)
                    );
                }
                // the null case is caught by the NodeAggregate or its command handler
                foreach ($unassignedIngoingDimensionSpacePoints as $unassignedDimensionSpacePoint) {
                    // The parent node aggregate might be varied as well,
                    // so we need to find a parent node for each covered dimension space point
                    $generalizationParentNode = $this->projectionContentGraph->findNodeInAggregate(
                        $event->contentStreamIdentifier,
                        $sourceParentNode->nodeAggregateIdentifier,
                        $unassignedDimensionSpacePoint
                    );
                    if (is_null($generalizationParentNode)) {
                        throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                            get_class($event)
                        );
                    }

                    $this->copyHierarchyRelationToDimensionSpacePoint(
                        $ingoingSourceHierarchyRelation,
                        $event->contentStreamIdentifier,
                        $unassignedDimensionSpacePoint,
                        $generalizationParentNode->relationAnchorPoint,
                        $generalizedNode->relationAnchorPoint
                    );
                }
            }

            // Copy Reference Edges
            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $generalizedNode->relationAnchorPoint
            );
        });
    }

    /**
     * @throws \Throwable
     */
    public function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            // Do the peer variant creation itself
            $sourceNode = $this->projectionContentGraph->findNodeInAggregate(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->sourceOrigin->toDimensionSpacePoint()
            );
            if (is_null($sourceNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
            }
            $sourceParentNode = $this->projectionContentGraph->findParentNode(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->sourceOrigin
            );
            if (is_null($sourceParentNode)) {
                throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceParentNodeIsMissing(get_class($event));
            }
            $peerNode = $this->copyNodeToDimensionSpacePoint(
                $sourceNode,
                $event->peerOrigin
            );

            $unassignedIngoingDimensionSpacePoints = $event->peerCoverage;
            foreach (
                $this->projectionContentGraph->findIngoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamIdentifier,
                    $event->nodeAggregateIdentifier,
                    $event->peerCoverage
                ) as $existingIngoingHierarchyRelation
            ) {
                $existingIngoingHierarchyRelation->assignNewChildNode(
                    $peerNode->relationAnchorPoint,
                    $this->getDatabaseConnection()
                );
                $unassignedIngoingDimensionSpacePoints = $unassignedIngoingDimensionSpacePoints->getDifference(
                    new DimensionSpacePointSet([
                        $existingIngoingHierarchyRelation->dimensionSpacePoint
                    ])
                );
            }

            foreach (
                $this->projectionContentGraph->findOutgoingHierarchyRelationsForNodeAggregate(
                    $event->contentStreamIdentifier,
                    $event->nodeAggregateIdentifier,
                    $event->peerCoverage
                ) as $existingOutgoingHierarchyRelation
            ) {
                $existingOutgoingHierarchyRelation->assignNewParentNode(
                    $peerNode->relationAnchorPoint,
                    null,
                    $this->getDatabaseConnection()
                );
            }

            foreach ($unassignedIngoingDimensionSpacePoints as $coveredDimensionSpacePoint) {
                // The parent node aggregate might be varied as well,
                // so we need to find a parent node for each covered dimension space point
                $peerParentNode = $this->projectionContentGraph->findNodeInAggregate(
                    $event->contentStreamIdentifier,
                    $sourceParentNode->nodeAggregateIdentifier,
                    $coveredDimensionSpacePoint
                );
                if (is_null($peerParentNode)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(get_class($event));
                }

                $this->connectHierarchy(
                    $event->contentStreamIdentifier,
                    $peerParentNode->relationAnchorPoint,
                    $peerNode->relationAnchorPoint,
                    new DimensionSpacePointSet([$coveredDimensionSpacePoint]),
                    null, // @todo fetch appropriate sibling
                    $sourceNode->nodeName
                );
            }

            // Copy Reference Edges
            $this->copyReferenceRelations(
                $sourceNode->relationAnchorPoint,
                $peerNode->relationAnchorPoint
            );
        });
    }

    public function whenNodeVariantWasReset(NodeVariantWasReset $event): void
    {
        $this->transactional(function () use ($event) {
            $replacements = $this->getDatabaseConnection()->executeQuery(
                /** @lang MariaDB */
                '
                /**
                 * This provides a list of all relation anchor points to be replaced in hierarchy relations
                 * and potentially be deleted as well as their replacements
                 */
                WITH RECURSIVE replacements(tobereplaced, replacement) AS (
                    /**
                     * Initial query: find all replacement pairs from origin anchor to generalization anchor
                     */
                    SELECT n.relationanchorpoint AS tobereplaced, gen.relationanchorpoint AS replacement
                    FROM neos_contentgraph_node n
                        JOIN neos_contentgraph_hierarchyrelation h ON h.childnodeanchor = n.relationanchorpoint
                        JOIN neos_contentgraph_node gen ON gen.nodeaggregateidentifier = n.nodeaggregateidentifier
                        JOIN neos_contentgraph_hierarchyrelation genh ON genh.childnodeanchor = gen.relationanchorpoint
                    WHERE h.contentstreamidentifier = :contentStreamIdentifier
                        AND h.dimensionspacepointhash = :sourceOriginDimensionSpacePointHash
                        AND n.nodeaggregateidentifier = :nodeAggregateIdentifier
                        AND genh.contentstreamidentifier = :contentStreamIdentifier
                        AND genh.dimensionspacepointhash = :generalizationOriginDimensionSpacePointHash
                    UNION ALL
                        /**
                         * Iteration query: find all replacement pairs for tethered descendants
                         */
                        SELECT c.relationanchorpoint AS tobereplaced, genc.relationanchorpoint AS replacement
                        FROM replacements p
                            JOIN neos_contentgraph_hierarchyrelation ch ON ch.parentnodeanchor = p.tobereplaced
                            JOIN neos_contentgraph_node c ON c.relationanchorpoint = ch.childnodeanchor
                            JOIN neos_contentgraph_node genc ON genc.nodeaggregateidentifier = c.nodeaggregateidentifier
                            JOIN neos_contentgraph_hierarchyrelation genh
                                ON genh.childnodeanchor = genc.relationanchorpoint
                        WHERE ch.contentstreamidentifier = :contentStreamIdentifier
                          AND ch.dimensionspacepointhash = :sourceOriginDimensionSpacePointHash
                          AND genh.contentstreamidentifier = :contentStreamIdentifier
                          AND genh.dimensionspacepointhash = :generalizationOriginDimensionSpacePointHash
                          AND c.classification = :tetheredClassification
                ) SELECT * FROM replacements
                ',
                [
                    'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                    'sourceOriginDimensionSpacePointHash' => $event->sourceOrigin->hash,
                    'nodeAggregateIdentifier' => (string)$event->nodeAggregateIdentifier,
                    'generalizationOriginDimensionSpacePointHash' => $event->generalizationOrigin->hash,
                    'tetheredClassification' => NodeAggregateClassification::CLASSIFICATION_TETHERED->value
                ]
            )->fetchAllAssociative();

            $replacementSelectionStatement = 'SELECT ' . implode(
                ' UNION ALL SELECT ',
                array_map(
                    fn (array $replacement): string
                    => '"' . $replacement['tobereplaced'] . '" AS tobereplaced, "'
                        . $replacement['replacement'] . '" AS replacement',
                    $replacements
                )
            );

            // adjust the inbound hierarchy relations
            $this->getDatabaseConnection()->executeStatement(
                /** @lang MariaDB */
                '
                    UPDATE neos_contentgraph_hierarchyrelation h
                    JOIN (' . $replacementSelectionStatement . ') replacements ON h.childnodeanchor = replacements.tobereplaced
                    SET childnodeanchor = replacements.replacement
                        WHERE contentstreamidentifier = :contentStreamIdentifier
                        AND dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)
                ',
                [
                    'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                    'sourceDimensionSpacePointHash' => $event->sourceOrigin->hash,
                    'affectedDimensionSpacePointHashes' => $event->affectedCoveredDimensionSpacePoints->getPointHashes()
                ],
                [
                    'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
                ]
            );

            // adjust the outbound hierarchy relations
            $this->getDatabaseConnection()->executeStatement(
                /** @lang MariaDB */
                '
                    UPDATE neos_contentgraph_hierarchyrelation h
                    JOIN (' . $replacementSelectionStatement . ') replacements ON h.parentnodeanchor = replacements.tobereplaced
                    SET parentnodeanchor = replacements.replacement
                        WHERE contentstreamidentifier = :contentStreamIdentifier
                        AND dimensionspacepointhash IN (:affectedDimensionSpacePointHashes)
                ',
                [
                    'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
                    'affectedDimensionSpacePointHashes' => $event->affectedCoveredDimensionSpacePoints->getPointHashes()
                ],
                [
                    'affectedDimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
                ]
            );

            // remove orphaned node records along with their outbound reference relations
            $this->getDatabaseConnection()->executeStatement(
                /** @lang MariaDB */
                    '
                    DELETE n, r FROM neos_contentgraph_node n
                        LEFT JOIN ' . ReferenceRelation::TABLE_NAME . ' r ON r.nodeanchorpoint = n.relationanchorpoint
                        LEFT JOIN ' . HierarchyRelationRecord::TABLE_NAME . ' h ON h.childnodeanchor = n.relationanchorpoint
                    WHERE
                        n.relationanchorpoint IN (:replacedRelationAnchorPoints)
                        -- the following line means "left join leads to NO MATCHING hierarchyrelation"
                        AND h.contentstreamidentifier IS NULL
                    ',
                [
                    'replacedRelationAnchorPoints' => array_map(
                        fn (array $replacement): string => $replacement['tobereplaced'],
                        $replacements
                    ),
                ],
                [
                    'replacedRelationAnchorPoints' => Connection::PARAM_STR_ARRAY
                ]
            );
        });
    }

    abstract protected function copyNodeToDimensionSpacePoint(
        NodeRecord $sourceNode,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): NodeRecord;

    abstract protected function copyHierarchyRelationToDimensionSpacePoint(
        HierarchyRelationRecord $sourceHierarchyRelation,
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        ?NodeRelationAnchorPoint $newParent = null,
        ?NodeRelationAnchorPoint $newChild = null
    ): HierarchyRelationRecord;

    abstract protected function connectHierarchy(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeRelationAnchorPoint $parentNodeAnchorPoint,
        NodeRelationAnchorPoint $childNodeAnchorPoint,
        DimensionSpacePointSet $dimensionSpacePointSet,
        ?NodeRelationAnchorPoint $succeedingSiblingNodeAnchorPoint,
        NodeName $relationName = null
    ): void;

    abstract protected function copyReferenceRelations(
        NodeRelationAnchorPoint $sourceRelationAnchorPoint,
        NodeRelationAnchorPoint $destinationRelationAnchorPoint
    ): void;

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(\Closure $operations): void;
}
