<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ProjectionContentGraph;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Feature\NodeRemoval\Event\NodeAggregateWasRemoved;
use Psr\Log\LoggerInterface;

/**
 * The NodeRemoval projection feature trait
 *
 * Requires RestrictionRelations to work
 *
 * @internal
 */
trait NodeRemoval
{
    abstract protected function getProjectionContentGraph(): ProjectionContentGraph;

    abstract protected function getTableNamePrefix(): string;

    protected LoggerInterface $systemLogger;

    /**
     * @throws \Throwable
     */
    private function whenNodeAggregateWasRemoved(NodeAggregateWasRemoved $event): void
    {
        // the focus here is to be correct; that's why the method is not overly performant (for now at least). We might
        // lateron find tricks to improve performance
        $this->transactional(function () use ($event) {
            $this->removeOutgoingRestrictionRelationsOfNodeAggregateInDimensionSpacePoints(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->affectedCoveredDimensionSpacePoints
            );

            $ingoingRelations = $this->getProjectionContentGraph()->findIngoingHierarchyRelationsForNodeAggregate(
                $event->contentStreamIdentifier,
                $event->nodeAggregateIdentifier,
                $event->affectedCoveredDimensionSpacePoints
            );

            foreach ($ingoingRelations as $ingoingRelation) {
                $this->removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes($ingoingRelation);
            }
        });
    }

    /**
     * @param HierarchyRelation $ingoingRelation
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes(
        HierarchyRelation $ingoingRelation
    ): void {
        $ingoingRelation->removeFromDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);

        foreach (
            $this->getProjectionContentGraph()->findOutgoingHierarchyRelationsForNode(
                $ingoingRelation->childNodeAnchor,
                $ingoingRelation->contentStreamIdentifier,
                new DimensionSpacePointSet([$ingoingRelation->dimensionSpacePoint])
            ) as $outgoingRelation
        ) {
            $this->removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes($outgoingRelation);
        }

        // remove node itself if it does not have any incoming hierarchy relations anymore
        // also remove outbound reference relations
        $this->getDatabaseConnection()->executeStatement(
            '
            DELETE n, r FROM ' . $this->getTableNamePrefix() . '_node n
                LEFT JOIN ' . $this->getTableNamePrefix() . '_referencerelation r
                    ON r.nodeanchorpoint = n.relationanchorpoint
                LEFT JOIN
                    ' . $this->getTableNamePrefix() . '_hierarchyrelation h
                        ON h.childnodeanchor = n.relationanchorpoint
                WHERE
                    n.relationanchorpoint = :anchorPointForNode
                    -- the following line means "left join leads to NO MATCHING hierarchyrelation"
                    AND h.contentstreamidentifier IS NULL
                ',
            [
                'anchorPointForNode' => (string)$ingoingRelation->childNodeAnchor,
            ]
        );
    }

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(\Closure $operations): void;
}
