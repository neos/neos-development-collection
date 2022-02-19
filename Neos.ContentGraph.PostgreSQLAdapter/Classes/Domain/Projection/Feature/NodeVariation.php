<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoints;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeGeneralizationVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePeerVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeSpecializationVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;

/**
 * The node disabling feature set for the hypergraph projector
 */
trait NodeVariation
{
    abstract protected function getProjectionHyperGraph(): ProjectionHypergraph;

    abstract protected function transactional(callable $operations): void;

    abstract protected function getDatabaseConnection(): Connection;

    /**
     * @throws \Throwable
     */
    public function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            $sourceNode = $this->getProjectionHyperGraph()->findNodeRecordByOrigin(
                $event->getContentStreamIdentifier(),
                $event->getSourceOrigin(),
                $event->getNodeAggregateIdentifier()
            );
            $specializedNode = $this->copyNodeToOriginDimensionSpacePoint(
                $sourceNode,
                $event->getSpecializationOrigin()
            );

            $this->assignNewChildNodeToAffectedHierarchyRelations(
                $event->getContentStreamIdentifier(),
                $sourceNode->relationAnchorPoint,
                $specializedNode->relationAnchorPoint,
                $event->getSpecializationCoverage()
            );
            $this->assignNewParentNodeToAffectedHierarchyRelations(
                $event->getContentStreamIdentifier(),
                $sourceNode->relationAnchorPoint,
                $specializedNode->relationAnchorPoint,
                $event->getSpecializationCoverage()
            );
        });
    }

    public function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            $sourceNode = $this->getProjectionHyperGraph()->findNodeRecordByOrigin(
                $event->getContentStreamIdentifier(),
                $event->getSourceOrigin(),
                $event->getNodeAggregateIdentifier()
            );
            $generalizedNode = $this->copyNodeToOriginDimensionSpacePoint(
                $sourceNode,
                $event->getGeneralizationOrigin()
            );

            $this->replaceNodeRelationAnchorPoint(
                $event->getContentStreamIdentifier(),
                $event->getNodeAggregateIdentifier(),
                $event->getGeneralizationCoverage(),
                $generalizedNode->relationAnchorPoint
            );

            $this->addMissingHierarchyRelations(
                $event->getContentStreamIdentifier(),
                $event->getNodeAggregateIdentifier(),
                $event->getSourceOrigin(),
                $generalizedNode->relationAnchorPoint,
                $event->getGeneralizationCoverage()
            );
        });
    }

    public function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        $this->transactional(function () use ($event) {
            $sourceNode = $this->getProjectionHyperGraph()->findNodeRecordByOrigin(
                $event->getContentStreamIdentifier(),
                $event->getSourceOrigin(),
                $event->getNodeAggregateIdentifier()
            );
            $peerNode = $this->copyNodeToOriginDimensionSpacePoint(
                $sourceNode,
                $event->getPeerOrigin()
            );

            $this->replaceNodeRelationAnchorPoint(
                $event->getContentStreamIdentifier(),
                $event->getNodeAggregateIdentifier(),
                $event->getPeerCoverage(),
                $peerNode->relationAnchorPoint
            );

            $this->addMissingHierarchyRelations(
                $event->getContentStreamIdentifier(),
                $event->getNodeAggregateIdentifier(),
                $event->getSourceOrigin(),
                $peerNode->relationAnchorPoint,
                $event->getPeerCoverage()
            );
        });
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function copyNodeToOriginDimensionSpacePoint(
        NodeRecord $sourceNode,
        OriginDimensionSpacePoint $targetOrigin
    ): NodeRecord {
        $copy = new NodeRecord(
            NodeRelationAnchorPoint::create(),
            $sourceNode->nodeAggregateIdentifier,
            $targetOrigin,
            $targetOrigin->hash,
            $sourceNode->properties,
            $sourceNode->nodeTypeName,
            $sourceNode->classification,
            $sourceNode->nodeName
        );
        $copy->addToDatabase($this->getDatabaseConnection());

        return $copy;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function replaceNodeRelationAnchorPoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $affectedNodeAggregateIdentifier,
        DimensionSpacePointSet $affectedDimensionSpacePointSet,
        NodeRelationAnchorPoint $newNodeRelationAnchorPoint
    ): void {
        $currentNodeAnchorPointStatement = '
            WITH currentNodeAnchorPoint AS (
                SELECT relationanchorpoint FROM ' . NodeRecord::TABLE_NAME . ' n
                    JOIN ' . HierarchyHyperrelationRecord::TABLE_NAME . ' p
                    ON n.relationanchorpoint = ANY(p.childnodeanchors)
                WHERE p.contentstreamidentifier = :contentStreamIdentifier
                AND p.dimensionspacepointhash = :affectedDimensionSpacePointHash
                AND n.nodeaggregateidentifier = :affectedNodeAggregateIdentifier
            )';
        $parameters = [
            'contentStreamIdentifier' => (string)$contentStreamIdentifier,
            'newNodeRelationAnchorPoint' => (string)$newNodeRelationAnchorPoint,
            'affectedNodeAggregateIdentifier' => (string)$affectedNodeAggregateIdentifier
        ];
        foreach ($affectedDimensionSpacePointSet as $affectedDimensionSpacePoint) {
            $parentStatement = /** @lang PostgreSQL */
                $currentNodeAnchorPointStatement . '
                UPDATE ' . HierarchyHyperrelationRecord::TABLE_NAME . '
                    SET parentnodeanchor = :newNodeRelationAnchorPoint
                    WHERE contentstreamidentifier = :contentStreamIdentifier
                        AND dimensionspacepointhash = :affectedDimensionSpacePointHash
                        AND parentnodeanchor = (SELECT relationanchorpoint FROM currentNodeAnchorPoint)
                ';
            $childStatement = /** @lang PostgreSQL */
                $currentNodeAnchorPointStatement . '
                UPDATE ' . HierarchyHyperrelationRecord::TABLE_NAME . '
                    SET childnodeanchors = array_replace(
                        childnodeanchors,
                        (SELECT relationanchorpoint FROM currentNodeAnchorPoint),
                        :newNodeRelationAnchorPoint
                    )
                    WHERE contentstreamidentifier = :contentStreamIdentifier
                        AND dimensionspacepointhash = :affectedDimensionSpacePointHash
                        AND (SELECT relationanchorpoint FROM currentNodeAnchorPoint) = ANY(childnodeanchors)
                ';
            $parameters['affectedDimensionSpacePointHash'] = $affectedDimensionSpacePoint->hash;
            $this->getDatabaseConnection()->executeStatement($parentStatement, $parameters);
            $this->getDatabaseConnection()->executeStatement($childStatement, $parameters);
        }
    }

    protected function addMissingHierarchyRelations(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $sourceOrigin,
        NodeRelationAnchorPoint $targetRelationAnchor,
        DimensionSpacePointSet $coverage
    ): void {
        $missingCoverage = $coverage->getDifference(
            $this->getProjectionHyperGraph()->findCoverageByNodeAggregateIdentifier(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier
            )
        );
        if ($missingCoverage->count() > 0) {
            $sourceParentNode = $this->getProjectionHyperGraph()->findParentNodeRecordByOrigin(
                $contentStreamIdentifier,
                $sourceOrigin,
                $nodeAggregateIdentifier
            );
            $parentNodeAggregateIdentifier = $sourceParentNode->nodeAggregateIdentifier;
            $sourceSucceedingSiblingNode = $this->getProjectionHyperGraph()->findParentNodeRecordByOrigin(
                $contentStreamIdentifier,
                $sourceOrigin,
                $nodeAggregateIdentifier
            );
            foreach ($missingCoverage as $uncoveredDimensionSpacePoint) {
                // The parent node aggregate might be varied as well,
                // so we need to find a parent node for each covered dimension space point

                // First we check for an already existing hyperrelation
                $hierarchyRelation = $this->getProjectionHyperGraph()->findChildHierarchyHyperrelationRecord(
                    $contentStreamIdentifier,
                    $uncoveredDimensionSpacePoint,
                    $parentNodeAggregateIdentifier
                );

                if ($hierarchyRelation) {
                    // If it exists, we need to look for a succeeding sibling to keep some order of nodes
                    $targetSucceedingSibling = $this->getProjectionHyperGraph()->findNodeRecordByCoverage(
                        $contentStreamIdentifier,
                        $uncoveredDimensionSpacePoint,
                        $sourceSucceedingSiblingNode->nodeAggregateIdentifier
                    );

                    $hierarchyRelation->addChildNodeAnchor(
                        $targetRelationAnchor,
                        $targetSucceedingSibling?->relationAnchorPoint,
                        $this->getDatabaseConnection()
                    );
                } else {
                    $targetParentNode = $this->getProjectionHyperGraph()->findNodeRecordByCoverage(
                        $contentStreamIdentifier,
                        $uncoveredDimensionSpacePoint,
                        $parentNodeAggregateIdentifier
                    );

                    $hierarchyRelation = new HierarchyHyperrelationRecord(
                        $contentStreamIdentifier,
                        $targetParentNode->relationAnchorPoint,
                        $uncoveredDimensionSpacePoint,
                        NodeRelationAnchorPoints::fromArray([$targetRelationAnchor])
                    );
                    $hierarchyRelation->addToDatabase($this->getDatabaseConnection());
                }
            }
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function assignNewChildNodeToAffectedHierarchyRelations(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeRelationAnchorPoint $oldChildAnchor,
        NodeRelationAnchorPoint $newChildAnchor,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        foreach ($this->getProjectionHyperGraph()->findIngoingHierarchyHyperrelationRecords(
            $contentStreamIdentifier,
            $oldChildAnchor,
            $affectedDimensionSpacePoints
        ) as $ingoingHierarchyHyperrelationRecord) {
            $ingoingHierarchyHyperrelationRecord->replaceChildNodeAnchor(
                $oldChildAnchor,
                $newChildAnchor,
                $this->getDatabaseConnection()
            );
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function assignNewParentNodeToAffectedHierarchyRelations(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeRelationAnchorPoint $oldParentAnchor,
        NodeRelationAnchorPoint $newParentAnchor,
        DimensionSpacePointSet $affectedDimensionSpacePoints
    ): void {
        foreach ($this->getProjectionHyperGraph()->findOutgoingHierarchyHyperrelationRecords(
            $contentStreamIdentifier,
            $oldParentAnchor,
            $affectedDimensionSpacePoints
        ) as $outgoingHierarchyHyperrelationRecord) {
            $outgoingHierarchyHyperrelationRecord->replaceParentNodeAnchor(
                $newParentAnchor,
                $this->getDatabaseConnection()
            );
        }
    }
}
