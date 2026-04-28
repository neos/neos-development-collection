<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyRelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoints;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionWriteQueries;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\Common\InterdimensionalSibling;
use Neos\ContentRepository\Core\Feature\NodeMove\Event\NodeAggregateWasMoved;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The NodeMove projection feature trait for the hypergraph projector.
 *
 * In the hypergraph model, children are stored as ordered arrays (`childnodeanchors`)
 * within a single hierarchy relation per parent+dimension. Moving a node means:
 * - Removing its anchor from the old parent's `childnodeanchors` array
 * - Inserting it into the (new or same) parent's array at the correct position
 *
 * @internal
 */
trait NodeMove
{
    private function whenNodeAggregateWasMoved(NodeAggregateWasMoved $event): void
    {
        foreach ($event->succeedingSiblingsForCoverage as $succeedingSiblingForCoverage) {
            $nodeToBeMoved = $this->getReadQueries()->findNodeRecordByCoverage(
                $event->contentStreamId,
                $succeedingSiblingForCoverage->dimensionSpacePoint,
                $event->nodeAggregateId
            );

            if ($nodeToBeMoved === null) {
                throw new \RuntimeException(
                    sprintf(
                        'Failed to move node "%s" in sub graph %s@%s because it does not exist',
                        $event->nodeAggregateId->value,
                        $succeedingSiblingForCoverage->dimensionSpacePoint->toJson(),
                        $event->contentStreamId->value
                    ),
                    1716471638
                );
            }

            if ($event->newParentNodeAggregateId) {
                // Read the moved node's current tags BEFORE the move, because
                // removeChildNodeAnchor strips them from the old parent's hierarchy relation.
                $movedNodeTags = $this->readNodeSubtreeTagsFromIngoingHierarchy(
                    $event->contentStreamId,
                    $succeedingSiblingForCoverage->dimensionSpacePoint,
                    $nodeToBeMoved->relationAnchorPoint
                );
                $this->moveNodeBeneathParent(
                    $event->contentStreamId,
                    $nodeToBeMoved,
                    $event->newParentNodeAggregateId,
                    $succeedingSiblingForCoverage
                );
                $this->moveSubtreeTags(
                    $event->contentStreamId,
                    $event->newParentNodeAggregateId,
                    $succeedingSiblingForCoverage->dimensionSpacePoint,
                    $nodeToBeMoved,
                    $movedNodeTags
                );
            } else {
                $this->moveNodeBeforeSucceedingSibling(
                    $event->contentStreamId,
                    $nodeToBeMoved,
                    $succeedingSiblingForCoverage
                );
            }
        }
    }

    /**
     * Move a node to a new position within the same parent (reorder only).
     * Removes the node's anchor from the parent's childnodeanchors array
     * and re-inserts it before the specified succeeding sibling.
     */
    private function moveNodeBeforeSucceedingSibling(
        ContentStreamId $contentStreamId,
        NodeRecord $nodeToBeMoved,
        InterdimensionalSibling $succeedingSiblingForCoverage,
    ): void {
        $ingoingHierarchyRelation = $this->findIngoingHierarchyRelationToBeMoved(
            $nodeToBeMoved,
            $contentStreamId,
            $succeedingSiblingForCoverage->dimensionSpacePoint
        );

        $newSucceedingSiblingAnchor = null;
        if ($succeedingSiblingForCoverage->nodeAggregateId) {
            $newSucceedingSibling = $this->getReadQueries()->findNodeRecordByCoverage(
                $contentStreamId,
                $succeedingSiblingForCoverage->dimensionSpacePoint,
                $succeedingSiblingForCoverage->nodeAggregateId
            );
            if ($newSucceedingSibling === null) {
                throw new \RuntimeException(
                    sprintf(
                        'Failed to move node "%s" in sub graph %s@%s because target succeeding sibling node "%s" is missing',
                        $nodeToBeMoved->nodeAggregateId->value,
                        $succeedingSiblingForCoverage->dimensionSpacePoint->toJson(),
                        $contentStreamId->value,
                        $succeedingSiblingForCoverage->nodeAggregateId->value
                    ),
                    1716471881
                );
            }
            $newSucceedingSiblingAnchor = $newSucceedingSibling->relationAnchorPoint;
        }

        $hierarchyRelationId = $ingoingHierarchyRelation->getDatabaseIdentifier();

        // Remove from current position
        $this->getWriteQueries()->removeChildNodeAnchorFromHierarchyRecord(
            $this->getDatabaseConnection(),
            $hierarchyRelationId,
            $nodeToBeMoved->relationAnchorPoint
        );

        // Re-insert at new position
        $this->getWriteQueries()->addChildNodeAnchorBeforeSuccessor(
            $this->getDatabaseConnection(),
            $hierarchyRelationId,
            $nodeToBeMoved->relationAnchorPoint,
            $newSucceedingSiblingAnchor
        );
    }

    /**
     * Move a node beneath a new parent, positioned before the specified succeeding sibling.
     * Removes the node's anchor from the old parent's childnodeanchors array
     * and inserts it into the new parent's array.
     */
    private function moveNodeBeneathParent(
        ContentStreamId $contentStreamId,
        NodeRecord $nodeToBeMoved,
        NodeAggregateId $newParentNodeAggregateId,
        InterdimensionalSibling $succeedingSiblingForCoverage,
    ): void {
        $ingoingHierarchyRelation = $this->findIngoingHierarchyRelationToBeMoved(
            $nodeToBeMoved,
            $contentStreamId,
            $succeedingSiblingForCoverage->dimensionSpacePoint
        );

        $newParent = $this->getReadQueries()->findNodeRecordByCoverage(
            $contentStreamId,
            $succeedingSiblingForCoverage->dimensionSpacePoint,
            $newParentNodeAggregateId
        );
        if ($newParent === null) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to move node "%s" in sub graph %s@%s because target parent node "%s" is missing',
                    $nodeToBeMoved->nodeAggregateId->value,
                    $succeedingSiblingForCoverage->dimensionSpacePoint->toJson(),
                    $contentStreamId->value,
                    $newParentNodeAggregateId->value
                ),
                1716471955
            );
        }

        $newSucceedingSiblingAnchor = null;
        if ($succeedingSiblingForCoverage->nodeAggregateId) {
            $newSucceedingSibling = $this->getReadQueries()->findNodeRecordByCoverage(
                $contentStreamId,
                $succeedingSiblingForCoverage->dimensionSpacePoint,
                $succeedingSiblingForCoverage->nodeAggregateId
            );
            if ($newSucceedingSibling === null) {
                throw new \RuntimeException(
                    sprintf(
                        'Failed to move node "%s" in sub graph %s@%s because target succeeding sibling node "%s" is missing',
                        $nodeToBeMoved->nodeAggregateId->value,
                        $succeedingSiblingForCoverage->dimensionSpacePoint->toJson(),
                        $contentStreamId->value,
                        $succeedingSiblingForCoverage->nodeAggregateId->value
                    ),
                    1716471995
                );
            }
            $newSucceedingSiblingAnchor = $newSucceedingSibling->relationAnchorPoint;
        }

        // Remove from old parent's hierarchy relation (also removes subtree tags for this anchor)
        $ingoingHierarchyRelation->removeChildNodeAnchor(
            $nodeToBeMoved->relationAnchorPoint,
            $this->getDatabaseConnection(),
            $this->getTableNames()
        );

        // Find or verify the new parent's outgoing hierarchy relation exists
        $newParentHierarchyRelation = $this->getReadQueries()->findHierarchyHyperrelationRecordByParentNodeAnchor(
            $contentStreamId,
            $succeedingSiblingForCoverage->dimensionSpacePoint,
            $newParent->relationAnchorPoint
        );

        if ($newParentHierarchyRelation !== null) {
            // Add to existing hierarchy relation at the correct position
            $this->getWriteQueries()->addChildNodeAnchorBeforeSuccessor(
                $this->getDatabaseConnection(),
                $newParentHierarchyRelation->getDatabaseIdentifier(),
                $nodeToBeMoved->relationAnchorPoint,
                $newSucceedingSiblingAnchor
            );
        } else {
            // Create a new hierarchy relation for the new parent (it had no children in this dimension before)
            $this->getWriteQueries()->addHierarchyRelationRecordToDatabase(
                $this->getDatabaseConnection(),
                new HierarchyRelationRecord(
                    $contentStreamId,
                    $newParent->relationAnchorPoint,
                    $succeedingSiblingForCoverage->dimensionSpacePoint,
                    NodeRelationAnchorPoints::fromArray(
                        [$nodeToBeMoved->relationAnchorPoint]
                    )
                )
            );
        }
    }

    /**
     * Find the single ingoing hierarchy relation for the node in the given dimension.
     */
    private function findIngoingHierarchyRelationToBeMoved(
        NodeRecord $nodeToBeMoved,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint
    ): HierarchyRelationRecord {
        $ingoingHierarchyRelation = $this->getReadQueries()->findHierarchyHyperrelationRecordByChildNodeAnchor(
            $contentStreamId,
            $dimensionSpacePoint,
            $nodeToBeMoved->relationAnchorPoint
        );

        if ($ingoingHierarchyRelation === null) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to move node "%s" in sub graph %s@%s because ingoing source hierarchy relation is missing',
                    $nodeToBeMoved->nodeAggregateId->value,
                    $dimensionSpacePoint->toJson(),
                    $contentStreamId->value
                ),
                1716472138
            );
        }

        return $ingoingHierarchyRelation;
    }

    /**
     * Read the subtree tags for a node from its ingoing hierarchy relation.
     *
     * @return array<string, bool|null> tag name => true (explicit) or null (inherited)
     */
    private function readNodeSubtreeTagsFromIngoingHierarchy(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $nodeAnchor
    ): array {
        $tableHierarchy = $this->getTableNames()->hierarchyRelation();

        $currentTagsJson = $this->getDatabaseConnection()->fetchOne(
            <<<SQL
                SELECT h.subtreetags->(:nodeAnchor::text)
                FROM {$tableHierarchy} h
                WHERE :nodeAnchor::bigint = ANY(h.childnodeanchors)
                  AND h.contentstreamid = :contentStreamId
                  AND h.dimensionspacepointhash = :dimensionSpacePointHash
            SQL,
            [
                'nodeAnchor' => $nodeAnchor->value,
                'contentStreamId' => $contentStreamId->value,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            ]
        );

        return is_string($currentTagsJson) ? (json_decode($currentTagsJson, true) ?: []) : [];
    }

    /**
     * After moving a node to a new parent, recalculate inherited subtree tags.
     *
     * When a node moves to a new parent, its inherited tags change because the ancestry changed.
     * This method:
     * 1. Determines the new parent's tags (which become inherited for the moved node)
     * 2. Updates the moved node's tag entry in the new parent's hierarchy relation
     * 3. Recursively propagates tag changes to descendants
     *
     * @param array<string, bool|null> $movedNodeTags the moved node's tags read BEFORE the move
     *        (because removeChildNodeAnchor strips them from the old parent's hierarchy relation)
     */
    private function moveSubtreeTags(
        ContentStreamId $contentStreamId,
        NodeAggregateId $newParentNodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRecord $movedNode,
        array $movedNodeTags
    ): void {
        $tableHierarchy = $this->getTableNames()->hierarchyRelation();
        $tableNode = $this->getTableNames()->node();

        // Step 1: Get all tags the new parent has (both explicit and inherited).
        // These become the "tags to inherit" for the moved node.
        $newParentTagsJson = $this->getDatabaseConnection()->fetchOne(
            <<<SQL
                SELECT h.subtreetags->(pn.relationanchorpoint::text)
                FROM {$tableNode} pn
                JOIN {$tableHierarchy} h
                    ON pn.relationanchorpoint = ANY(h.childnodeanchors)
                WHERE pn.nodeaggregateid = :newParentNodeAggregateId
                  AND h.contentstreamid = :contentStreamId
                  AND h.dimensionspacepointhash = :dimensionSpacePointHash
            SQL,
            [
                'newParentNodeAggregateId' => $newParentNodeAggregateId->value,
                'contentStreamId' => $contentStreamId->value,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            ]
        );

        /** @var array<string, true> $tagsToInherit tag name => true/null from the new parent */
        $tagsToInherit = [];
        if (is_string($newParentTagsJson)) {
            $parentTags = json_decode($newParentTagsJson, true) ?: [];
            // All of the parent's tags (explicit or inherited) become inherited for children
            foreach (array_keys($parentTags) as $tagName) {
                $tagsToInherit[strval($tagName)] = true;
            }
        }

        // Step 2: Recursively update subtree tags starting from the moved node.
        // Pass the pre-read tags for the moved node since they were removed from
        // the database by removeChildNodeAnchor during the move.
        $this->updateSubtreeTagsRecursive(
            $contentStreamId,
            $dimensionSpacePoint,
            $movedNode->relationAnchorPoint,
            $tagsToInherit,
            $movedNodeTags
        );
    }

    /**
     * Recursively update inherited subtree tags for a node and its descendants.
     *
     * @param array<string, true> $tagsToInherit tag names that should be inherited from ancestors
     * @param array<string, bool|null>|null $preReadTags if provided, use these instead of reading
     *        from the database (used for the moved node whose tags were removed during the move)
     */
    private function updateSubtreeTagsRecursive(
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        NodeRelationAnchorPoint $nodeAnchor,
        array $tagsToInherit,
        ?array $preReadTags = null
    ): void {
        $tableHierarchy = $this->getTableNames()->hierarchyRelation();

        if ($preReadTags !== null) {
            $currentTags = $preReadTags;
        } else {
            // Read current tags for this node from its incoming hierarchy relation.
            // Following the established pattern in SubtreeTagging.php: a single :nodeAnchor parameter
            // with PostgreSQL casts (::text for JSONB key, ::bigint for array membership).
            $currentTagsJson = $this->getDatabaseConnection()->fetchOne(
                <<<SQL
                    SELECT h.subtreetags->(:nodeAnchor::text)
                    FROM {$tableHierarchy} h
                    WHERE :nodeAnchor::bigint = ANY(h.childnodeanchors)
                      AND h.contentstreamid = :contentStreamId
                      AND h.dimensionspacepointhash = :dimensionSpacePointHash
                SQL,
                [
                    'nodeAnchor' => $nodeAnchor->value,
                    'contentStreamId' => $contentStreamId->value,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                ]
            );

            /** @var array<string, bool|null> $currentTags */
            $currentTags = is_string($currentTagsJson) ? (json_decode($currentTagsJson, true) ?: []) : [];
        }

        // Build new tags: keep explicit tags (value=true), replace inherited with tagsToInherit
        $newTags = [];
        // First, add all inherited tags from ancestors
        foreach ($tagsToInherit as $tagName => $_) {
            if (isset($currentTags[$tagName]) && $currentTags[$tagName] === true) {
                // Node has this tag explicitly - keep it explicit
                $newTags[$tagName] = true;
            } else {
                // Inherited from ancestor
                $newTags[$tagName] = null;
            }
        }
        // Then, keep any explicit tags that aren't in the inherited set
        foreach ($currentTags as $tagName => $value) {
            if ($value === true && !isset($newTags[$tagName])) {
                $newTags[$tagName] = true;
            }
        }

        // Update the subtree tags for this node's anchor in its incoming hierarchy relation
        $newTagsJson = empty($newTags) ? null : json_encode($newTags);

        if ($newTagsJson !== null) {
            $this->getDatabaseConnection()->executeStatement(
                <<<SQL
                    UPDATE {$tableHierarchy}
                    SET subtreetags = COALESCE(subtreetags, '{}'::jsonb) || jsonb_build_object(:nodeAnchor::text, :newTags::jsonb)
                    WHERE :nodeAnchor::bigint = ANY(childnodeanchors)
                      AND contentstreamid = :contentStreamId
                      AND dimensionspacepointhash = :dimensionSpacePointHash
                SQL,
                [
                    'nodeAnchor' => $nodeAnchor->value,
                    'contentStreamId' => $contentStreamId->value,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                    'newTags' => $newTagsJson,
                ]
            );
        } else {
            // Remove the tag entry entirely if no tags
            $this->getDatabaseConnection()->executeStatement(
                <<<SQL
                    UPDATE {$tableHierarchy}
                    SET subtreetags = subtreetags - :nodeAnchor::text
                    WHERE :nodeAnchor::bigint = ANY(childnodeanchors)
                      AND contentstreamid = :contentStreamId
                      AND dimensionspacepointhash = :dimensionSpacePointHash
                SQL,
                [
                    'nodeAnchor' => $nodeAnchor->value,
                    'contentStreamId' => $contentStreamId->value,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                ]
            );
        }

        // Build the tags to inherit for children: all tags this node has (both explicit and inherited)
        $childTagsToInherit = [];
        foreach ($newTags as $tagName => $_) {
            $childTagsToInherit[$tagName] = true;
        }

        // Recurse into children
        $childHierarchyRelation = $this->getReadQueries()->findHierarchyHyperrelationRecordByParentNodeAnchor(
            $contentStreamId,
            $dimensionSpacePoint,
            $nodeAnchor
        );

        if ($childHierarchyRelation !== null) {
            foreach ($childHierarchyRelation->childNodeAnchors as $childAnchor) {
                $this->updateSubtreeTagsRecursive(
                    $contentStreamId,
                    $dimensionSpacePoint,
                    $childAnchor,
                    $childTagsToInherit
                );
            }
        }
    }

    abstract protected function getReadQueries(): ProjectionReadQueries;
    abstract protected function getWriteQueries(): ProjectionWriteQueries;
    abstract protected function getDatabaseConnection(): Connection;
    abstract protected function getTableNames(): ContentGraphTableNames;
}
