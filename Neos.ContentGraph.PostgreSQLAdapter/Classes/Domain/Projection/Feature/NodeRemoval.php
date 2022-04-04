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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyHyperrelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionHypergraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWasRemoved;

/**
 * The node removal feature set for the hypergraph projector
 */
trait NodeRemoval
{
    /**
     * @throws \Throwable
     */
    public function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        /* $this->transactional(function () use ($event) {
            $nodeRecordsToBeRemoved = [];
            foreach ($event->getAffectedCoveredDimensionSpacePoints() as $dimensionSpacePoint) {
                $nodeRecord = $this->getProjectionHypergraph()->findNodeRecordByCoverage(
                    $event->getContentStreamIdentifier(),
                    $dimensionSpacePoint,
                    $event->getNodeAggregateIdentifier()
                );
                if (is_null($nodeRecord)) {
                    throw EventCouldNotBeAppliedToContentGraph::becauseTheSourceNodeIsMissing(get_class($event));
                }

                @var HierarchyHyperrelationRecord $ingoingHierarchyRelation
                $ingoingHierarchyRelation = $this->getProjectionHypergraph()
                    ->findHierarchyHyperrelationRecordByChildNodeAnchor(
                        $event->getContentStreamIdentifier(),
                        $dimensionSpacePoint,
                        $nodeRecord->relationAnchorPoint
                    );
                $ingoingHierarchyRelation->removeChildNodeAnchor(
                    $nodeRecord->relationAnchorPoint,
                    $this->getDatabaseConnection()
                );
                $this->removeFromRestrictions(
                    $event->getContentStreamIdentifier(),
                    $dimensionSpacePoint,
                    $event->getNodeAggregateIdentifier()
                );

                if ($event->getAffectedOccupiedDimensionSpacePoints()->contains(
                    $nodeRecord->originDimensionSpacePoint
                )) {
                    $nodeRecordsToBeRemoved[$nodeRecord->originDimensionSpacePoint->hash] = $nodeRecord;
                }

                $this->cascadeHierarchy(
                    $event->getContentStreamIdentifier(),
                    $dimensionSpacePoint,
                    $nodeRecord->relationAnchorPoint
                );
            }
            foreach ($nodeRecordsToBeRemoved as $nodeRecord) {
                $nodeRecord->removeFromDatabase($this->getDatabaseConnection());
            }
        });*/
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function cascadeHierarchy(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $nodeRelationAnchorPoint
    ): void {
        $childHierarchyRelation = $this->getProjectionHypergraph()->findHierarchyHyperrelationRecordByParentNodeAnchor(
            $contentStreamIdentifier,
            $dimensionSpacePoint,
            $nodeRelationAnchorPoint
        );
        if ($childHierarchyRelation) {
            $childHierarchyRelation->removeFromDatabase($this->getDatabaseConnection());

            foreach ($childHierarchyRelation->childNodeAnchors as $childNodeAnchor) {
                /** @var NodeRecord $nodeRecord */
                $nodeRecord = $this->getProjectionHypergraph()
                    ->findNodeRecordByRelationAnchorPoint($childNodeAnchor);
                $ingoingHierarchyRelations = $this->getProjectionHypergraph()
                    ->findHierarchyHyperrelationRecordsByChildNodeAnchor($childNodeAnchor);
                if (empty($ingoingHierarchyRelations)) {
                    $nodeRecord->removeFromDatabase($this->getDatabaseConnection());
                }
                $this->removeFromRestrictions(
                    $contentStreamIdentifier,
                    $dimensionSpacePoint,
                    $nodeRecord->nodeAggregateIdentifier
                );
                $this->cascadeHierarchy(
                    $contentStreamIdentifier,
                    $dimensionSpacePoint,
                    $nodeRecord->relationAnchorPoint
                );
            }
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function removeFromRestrictions(
        ContentStreamIdentifier $contentStreamIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): void {
        foreach ($this->getProjectionHypergraph()->findIngoingRestrictionRelations(
            $contentStreamIdentifier,
            $dimensionSpacePoint,
            $nodeAggregateIdentifier
        ) as $restrictionRelation) {
            $restrictionRelation->removeAffectedNodeAggregateIdentifier(
                $nodeAggregateIdentifier,
                $this->getDatabaseConnection()
            );
        }
    }

    abstract protected function getProjectionHypergraph(): ProjectionHypergraph;

    /**
     * @throws \Throwable
     */
    abstract protected function transactional(\Closure $operations): void;

    abstract protected function getDatabaseConnection(): Connection;
}
