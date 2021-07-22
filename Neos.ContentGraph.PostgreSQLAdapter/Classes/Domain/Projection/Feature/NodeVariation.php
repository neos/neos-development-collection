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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeGeneralizationVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeSpecializationVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\Flow\Annotations as Flow;

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
        $this->transactional(function () use($event) {
            $sourceNode = $this->getProjectionHyperGraph()->findNodeRecordByOrigin(
                $event->getContentStreamIdentifier(),
                $event->getSourceOrigin(),
                $event->getNodeAggregateIdentifier()
            );
            $specializedNode = $this->copyNodeToOriginDimensionSpacePoint($sourceNode, $event->getSpecializationOrigin());

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
        $this->transactional(function () use($event) {
            $sourceNode = $this->getProjectionHyperGraph()->findNodeRecordByOrigin(
                $event->getContentStreamIdentifier(),
                $event->getSourceOrigin(),
                $event->getNodeAggregateIdentifier()
            );
            $generalizedNode = $this->copyNodeToOriginDimensionSpacePoint($sourceNode, $event->getGeneralizationOrigin());

            $this->assignNewChildNodeToAffectedHierarchyRelations(
                $event->getContentStreamIdentifier(),
                $sourceNode->relationAnchorPoint,
                $generalizedNode->relationAnchorPoint,
                $event->getGeneralizationCoverage()
            );
            $this->assignNewParentNodeToAffectedHierarchyRelations(
                $event->getContentStreamIdentifier(),
                $sourceNode->relationAnchorPoint,
                $generalizedNode->relationAnchorPoint,
                $event->getGeneralizationCoverage()
            );

            $missingCoverage = $event->getGeneralizationCoverage()->getDifference(
                $this->getProjectionHyperGraph()->findCoverageByNodeAggregateIdentifier(
                    $event->getContentStreamIdentifier(),
                    $event->getNodeAggregateIdentifier()
                )
            );
            if ($missingCoverage->count() > 0) {
                $sourceParentNode = $this->getProjectionHyperGraph()->findParentNodeRecordByOrigin(
                    $event->getContentStreamIdentifier(),
                    $event->getSourceOrigin(),
                    $event->getNodeAggregateIdentifier()
                );
                $parentNodeAggregateIdentifier = $sourceParentNode->nodeAggregateIdentifier;
                $sourceSucceedingSiblingNode = $this->getProjectionHyperGraph()->findParentNodeRecordByOrigin(
                    $event->getContentStreamIdentifier(),
                    $event->getSourceOrigin(),
                    $event->getNodeAggregateIdentifier()
                );
                foreach ($missingCoverage as $uncoveredDimensionSpacePoint) {
                    // The parent node aggregate might be varied as well, so we need to find a parent node for each covered dimension space point
                    $targetParentNode = $this->getProjectionHyperGraph()->findNodeRecordByCoverage(
                        $event->getContentStreamIdentifier(),
                        $uncoveredDimensionSpacePoint,
                        $parentNodeAggregateIdentifier
                    );

                    $this->copyHierarchyRelationToDimensionSpacePoint(
                        $ingoingSourceHierarchyRelation,
                        $event->getContentStreamIdentifier(),
                        $uncoveredDimensionSpacePoint,
                        $parentNodeForCoverage->relationAnchorPoint,
                        $generalizedNode->relationAnchorPoint
                    );
                }
            }



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
            $targetOrigin->getHash(),
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
