<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\HierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeSortPath;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeSortPathResult;
use Neos\ContentGraph\DoctrineDbalAdapter\FractionalIndexing;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Dbal\Query\StaticWhereCondition;

trait SortPath
{
    private function determineRelationNodeSortPath(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamLayers $contentStreamLayers,
        DimensionSpacePoint $dimensionSpacePoint,
    ): NodeSortPathResult {
        $sortPath = $this->projectionContentGraph->determineHierarchySortPath(
            $parentAnchorPoint,
            $succeedingSiblingAnchorPoint,
            $contentStreamLayers,
            $dimensionSpacePoint
        );

        if ($sortPath->nodeSortKeyExceedsMaxKeyLength()) {
            return NodeSortPathResult::create(
                $this->getRelationSortPathAfterRecalculation(
                    $parentAnchorPoint,
                    $succeedingSiblingAnchorPoint,
                    $contentStreamLayers,
                    $dimensionSpacePoint
                ),
                true
            );
        }

        return NodeSortPathResult::create($sortPath, false);
    }

    /**
     * Reassigns evenly distributed keys over all siblings of the given parent. This shortens all fractional indexing
     * keys to their minimum length for the given amount of siblings.
     *
     * The reassigning of the keys changes the path of the direct siblings and all of their descendants (full subgraph
     * of the parent), which makes it a very expensive operation. In best case the rebalancing is never needed as
     * prepending or appending keeps a short fractional indexing key. But in worst case a rebalancing is required after
     * 170 consecutive insertions into the same right-bounded gap, which might not be expected in practice.
     */
    private function getRelationSortPathAfterRecalculation(
        NodeRelationAnchorPoint $parentAnchorPoint,
        ?NodeRelationAnchorPoint $succeedingSiblingAnchorPoint,
        ContentStreamLayers $contentStreamLayers,
        DimensionSpacePoint $dimensionSpacePoint,
    ): NodeSortPath {
        // Must be in ascending order by sort path. The assignment of the new keys relies on it.
        $hierarchyRelations = $this->projectionContentGraph->getOutgoingHierarchyRelationsForNodeAndSubgraph(
            $parentAnchorPoint,
            $contentStreamLayers,
            $dimensionSpacePoint
        );

        if ($hierarchyRelations === []) {
            throw new \RuntimeException('Cannot rebalance an empty sibling set', 1790151374);
        }

        $keys = FractionalIndexing::generateNKeysBetween(null, null, count($hierarchyRelations) + 1);

        $firstSortPath = current($hierarchyRelations)->sortPath;
        $parentSortPath = $firstSortPath->isRoot() ? null : $firstSortPath->getParent();
        $keyIndex = 0;
        $reservedSortPath = null;
        $pathRenameMapUp = [];
        $pathRenameMapDown = [];
        foreach ($hierarchyRelations as $relation) {
            if (
                $succeedingSiblingAnchorPoint
                && $relation->childNodeAnchor->equals($succeedingSiblingAnchorPoint)
            ) {
                $reservedSortPath = $this->sortPathForSibling($parentSortPath, $keys[$keyIndex]);
                $keyIndex++;
            }

            $newSortPath = $this->sortPathForSibling($parentSortPath, $keys[$keyIndex]);
            match(strcmp($newSortPath->value, $relation->sortPath->value) <=> 0) {
                1 => $pathRenameMapUp[] = ['relation' => $relation, 'newSortPath' => $newSortPath],
                -1 => $pathRenameMapDown[] = ['relation' => $relation, 'newSortPath' => $newSortPath],
                0 => null,
            };

            $keyIndex++;
        }

        /**
         * Down-movers ascending, then up-movers descending. Old and new keys keep their relative order, so a
         * relation's new path is never the current path of a relation that has not been processed yet. This
         * prevents siblings getting the same path during rebalance processing.
         */
        foreach ([...$pathRenameMapDown, ...array_reverse($pathRenameMapUp)] as $pathRename) {
            $this->assignRebalancedSortPath(
                $pathRename['relation'],
                $pathRename['newSortPath'],
                $contentStreamLayers,
                $dimensionSpacePoint
            );
        }

        // Place current node before succeedingSibling or at the end if no succeedingSibling
        return $reservedSortPath ?? $this->sortPathForSibling($parentSortPath, $keys[$keyIndex]);
    }

    private function sortPathForSibling(?NodeSortPath $parentSortPath, string $key): NodeSortPath
    {
        return $parentSortPath?->withAddedNodeSortKeySegment($key) ?? NodeSortPath::fromString($key);
    }

    private function assignRebalancedSortPath(
        HierarchyRelation $relation,
        NodeSortPath $newSortPath,
        ContentStreamLayers $contentStreamLayers,
        DimensionSpacePoint $dimensionSpacePoint,
    ): void {
        $oldSortPath = $relation->sortPath;
        if ($oldSortPath->equals($newSortPath)) {
            return;
        }

        $this->repathDescendants($contentStreamLayers, $dimensionSpacePoint, $oldSortPath, $newSortPath);

        if ($contentStreamLayers->getWriteLayer()->equals($relation->contentStreamLayer)) {
            $relation->assignNewSortPath($newSortPath, $this->dbal, $this->tableNames);
            return;
        }

        // The relation lives in a shared lower layer, which must stay immutable, so copy it up into new current layer
        $relation
            ->with(contentStreamLayer: $contentStreamLayers->getWriteLayer(), sortPath: $newSortPath)
            ->addToDatabase($this->dbal, $this->tableNames);
    }

    private function repathDescendants(
        ContentStreamLayers $contentStreamLayers,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeSortPath $oldSortPath,
        NodeSortPath $newSortPath,
    ): void {
        if ($oldSortPath->equals($newSortPath)) {
            return;
        }

        $hierarchyRelationQuery = $this->subqueries->forHierarchyRelation($contentStreamLayers)
            ->withDimensionSpacePoint($dimensionSpacePoint)
            ->withWhereCondition(StaticWhereCondition::fromString('h', 'h.sortpath >= :descendantRangeStart AND h.sortpath < :descendantRangeEnd'));
        $rangeParameters = [
            'descendantRangeStart' => $oldSortPath->rangeStart(),
            'descendantRangeEnd' => $oldSortPath->rangeEnd(),
        ];

        /*
         * TODO: Do we need to verify the resulting path lengths (strlen(sortpath) < MAX length) before inserting them into hierarchy relations table?
         *       We can also rely on STRICT_TRANS_TABLES, which will throw an error on DB level.
         */
        $repathStatement = <<<SQL
            INSERT INTO {$this->tableNames->hierarchyRelation()} (
              id,
              parentnodeanchor,
              childnodeanchor,
              sortpath,
              subtreetags,
              dimensionspacepointhash,
              contentstreamlayer
            )
            SELECT
              h.id,
              h.parentnodeanchor,
              h.childnodeanchor,
              CONCAT(:newSortPath, SUBSTRING(h.sortpath, :oldSortPathLength + 1)) AS sortpath,
              h.subtreetags,
              h.dimensionspacepointhash,
              :targetContentStreamLayer AS contentstreamlayer
            FROM
              {$hierarchyRelationQuery->toSql()} h
            ON DUPLICATE KEY UPDATE sortpath = VALUES(sortpath)
            SQL;
        try {
            $this->dbal->executeStatement($repathStatement, [
                'newSortPath' => $newSortPath->value,
                'oldSortPathLength' => strlen($oldSortPath->value),
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
                ...$rangeParameters,
                ...$hierarchyRelationQuery->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyRelationQuery->getParameters()->toDbalTypes(),
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to re-path the subtree of "%s" to "%s": %s', $oldSortPath->value, $newSortPath->value, $e->getMessage()), 1775980023, $e);
        }
    }
}
