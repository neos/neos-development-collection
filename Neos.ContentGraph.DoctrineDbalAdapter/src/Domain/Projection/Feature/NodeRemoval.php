<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The NodeRemoval projection feature trait
 *
 * @internal
 */
trait NodeRemoval
{
    private function removeNodeAggregate(ContentStreamId $contentStreamId, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $affectedCoveredDimensionSpacePoints): void
    {
        // the focus here is to be correct; that's why the method is not overly performant (for now at least). We might
        // lateron find tricks to improve performance
        $ingoingRelations = $this->projectionContentGraph->findIngoingHierarchyRelationsForNodeAggregate(
            $contentStreamId,
            $nodeAggregateId,
            $affectedCoveredDimensionSpacePoints
        );

        foreach ($ingoingRelations as $ingoingRelation) {
            $this->removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes($ingoingRelation);
        }
    }

    private function removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes(
        HierarchyRelation $ingoingRelation
    ): void {
        $ingoingRelation->removeFromDatabase($this->dbal, $this->tableNames);

        foreach (
            $this->projectionContentGraph->findOutgoingHierarchyRelationsForNode(
                $ingoingRelation->childNodeAnchor,
                $ingoingRelation->contentStreamId,
                new DimensionSpacePointSet([$ingoingRelation->dimensionSpacePoint])
            ) as $outgoingRelation
        ) {
            $this->removeRelationRecursivelyFromDatabaseIncludingNonReferencedNodes($outgoingRelation);
        }

        // TODO Write tests that we only remove in the correct dimension with copy on write before or without
        // TODO Only remove back references if no other hierarchy uses that node row (no copy on write happened)
        //  -> Or trigger copy on write as if we still keep the back reference because that node row is shared the back references can silently become valid again and the projection integrity violator complains
        $deleteBackReferencesStatement = <<<SQL
            DELETE
                r
            FROM
                {$this->tableNames->node()} n
                LEFT JOIN {$this->tableNames->referenceRelation()} r ON r.destinationnodeaggregateid = n.nodeaggregateid
                LEFT JOIN {$this->tableNames->hierarchyRelation()} h
                    ON h.contentstreamid = :contentStreamId
                    AND h.dimensionspacepointhash = :dimensionSpacePointHash
                    AND h.childnodeanchor = r.nodeanchorpoint
            WHERE
                n.relationanchorpoint = :anchorPointForNode
        SQL;
        try {
            $this->dbal->executeStatement($deleteBackReferencesStatement, [
                'anchorPointForNode' => $ingoingRelation->childNodeAnchor->value,
                'contentStreamId' => $ingoingRelation->contentStreamId->value,
                'dimensionSpacePointHash' => $ingoingRelation->dimensionSpacePoint->hash,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove backreferences from database: %s', $e->getMessage()), 1777050925, $e);
        }

        // remove node itself if it does not have any incoming hierarchy relations anymore
        // also remove outbound reference relations
        $deleteRelationsStatement = <<<SQL
            DELETE
                n, r
            FROM
                {$this->tableNames->node()} n
                LEFT JOIN {$this->tableNames->referenceRelation()} r ON r.nodeanchorpoint = n.relationanchorpoint
                LEFT JOIN {$this->tableNames->hierarchyRelation()} h ON h.childnodeanchor = n.relationanchorpoint
            WHERE
                n.relationanchorpoint = :anchorPointForNode
                -- the following line means "left join leads to NO MATCHING hierarchyrelation"
                AND h.contentstreamid IS NULL
        SQL;
        try {
            $this->dbal->executeStatement($deleteRelationsStatement, [
                'anchorPointForNode' => $ingoingRelation->childNodeAnchor->value,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove relations from database: %s', $e->getMessage()), 1716473385, $e);
        }
    }
}
