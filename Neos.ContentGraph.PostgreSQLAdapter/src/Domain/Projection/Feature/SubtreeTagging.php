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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The subtree tagging feature set for the hypergraph projector.
 *
 * Subtree tags are stored as per-anchor-keyed JSONB on the hierarchy relation's `subtreetags` column.
 * Format: {"<childAnchor>": {"<tagName>": true/null}, ...}
 *   - true = explicitly tagged
 *   - null = inherited from ancestor
 *
 * @internal
 */
trait SubtreeTagging
{
    private function whenSubtreeWasTagged(SubtreeWasTagged $event): void
    {
        $this->addSubtreeTag(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->affectedDimensionSpacePoints,
            $event->tag
        );
    }

    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
    {
        $this->removeSubtreeTag(
            $event->contentStreamId,
            $event->nodeAggregateId,
            $event->affectedDimensionSpacePoints,
            $event->tag
        );
    }

    private function addSubtreeTag(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $affectedDimensionSpacePoints,
        SubtreeTag $tag
    ): void {
        $tableHierarchy = $this->getTableNames()->hierarchyRelation();
        $tableNode = $this->getTableNames()->node();

        // Step 1: Tag all descendants with inherited tag (null value).
        // The recursive CTE traverses the tree starting from the tagged node's children,
        // stopping when a node already has this tag (explicit or inherited).
        $addTagToDescendantsStatement = <<<SQL
            WITH RECURSIVE desc_children AS (
                -- Base: children of the tagged node
                SELECT
                    child_anchor,
                    ch.dimensionspacepointhash
                FROM {$tableHierarchy} ch
                INNER JOIN {$tableNode} n ON ch.parentnodeanchor = n.relationanchorpoint
                CROSS JOIN unnest(ch.childnodeanchors) AS child_anchor
                WHERE n.nodeaggregateid = :nodeAggregateId
                  AND ch.contentstreamid = :contentStreamId
                  AND ch.dimensionspacepointhash IN (:dimensionSpacePointHashes)
                  AND NOT jsonb_exists(COALESCE(ch.subtreetags->(child_anchor::text), '{}'), :tagName)

                UNION ALL

                -- Recurse: children of children
                SELECT
                    gc_anchor,
                    dh.dimensionspacepointhash
                FROM desc_children dc
                JOIN {$tableHierarchy} dh
                    ON dh.parentnodeanchor = dc.child_anchor
                    AND dh.contentstreamid = :contentStreamId
                    AND dh.dimensionspacepointhash = dc.dimensionspacepointhash
                CROSS JOIN unnest(dh.childnodeanchors) AS gc_anchor
                WHERE NOT jsonb_exists(COALESCE(dh.subtreetags->(gc_anchor::text), '{}'), :tagName)
            ),
            -- Collect which hierarchy relations need updating and which child anchors within them
            affected_relations AS (
                SELECT DISTINCT h.parentnodeanchor, h.dimensionspacepointhash
                FROM {$tableHierarchy} h
                WHERE h.contentstreamid = :contentStreamId
                  AND EXISTS (
                      SELECT 1 FROM desc_children dc
                      WHERE dc.child_anchor = ANY(h.childnodeanchors)
                        AND dc.dimensionspacepointhash = h.dimensionspacepointhash
                  )
            )
            UPDATE {$tableHierarchy} h
            SET subtreetags = (
                SELECT COALESCE(
                    jsonb_object_agg(
                        anchor::text,
                        CASE
                            WHEN anchor IN (
                                SELECT dc.child_anchor FROM desc_children dc
                                WHERE dc.dimensionspacepointhash = h.dimensionspacepointhash
                            )
                            THEN COALESCE(h.subtreetags->(anchor::text), '{}') || jsonb_build_object(:tagName::text, null)
                            ELSE h.subtreetags->(anchor::text)
                        END
                    ) FILTER (WHERE h.subtreetags->(anchor::text) IS NOT NULL
                        OR anchor IN (SELECT dc.child_anchor FROM desc_children dc WHERE dc.dimensionspacepointhash = h.dimensionspacepointhash)),
                    h.subtreetags
                )
                FROM unnest(h.childnodeanchors) AS anchor
            )
            FROM affected_relations ar
            WHERE h.parentnodeanchor = ar.parentnodeanchor
              AND h.dimensionspacepointhash = ar.dimensionspacepointhash
              AND h.contentstreamid = :contentStreamId
        SQL;

        try {
            $this->getDatabaseConnection()->executeStatement($addTagToDescendantsStatement, [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagName' => $tag->value,
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to add inherited subtree tag "%s" to descendants: %s', $tag->value, $e->getMessage()), 1716479749, $e);
        }

        // Step 2: Tag the node itself with explicit tag (true value).
        // Find the hierarchy relation where this node is a child and set its tag entry.
        $addTagToNodeStatement = <<<SQL
            UPDATE {$tableHierarchy} h
            SET subtreetags = jsonb_set(
                COALESCE(h.subtreetags, '{}'),
                ARRAY[n.relationanchorpoint::text],
                COALESCE(h.subtreetags->(n.relationanchorpoint::text), '{}') || jsonb_build_object(:tagName::text, true)
            )
            FROM {$tableNode} n
            WHERE n.nodeaggregateid = :nodeAggregateId
              AND n.relationanchorpoint = ANY(h.childnodeanchors)
              AND h.contentstreamid = :contentStreamId
              AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)
        SQL;

        try {
            $this->getDatabaseConnection()->executeStatement($addTagToNodeStatement, [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagName' => $tag->value,
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to add explicit subtree tag "%s" to node: %s', $tag->value, $e->getMessage()), 1716479840, $e);
        }
    }

    private function removeSubtreeTag(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $affectedDimensionSpacePoints,
        SubtreeTag $tag
    ): void {
        $tableHierarchy = $this->getTableNames()->hierarchyRelation();
        $tableNode = $this->getTableNames()->node();

        // Recursive CTE: traverse descendants, stopping when a node has the tag explicitly (value = true).
        // For each descendant: remove the tag key from its JSONB entry.
        // For the node itself: check if parent has the tag → set null (inherited) or remove entirely.
        $removeTagStatement = <<<SQL
            WITH RECURSIVE desc_children AS (
                -- Base: children of the tagged node
                SELECT
                    child_anchor,
                    ch.dimensionspacepointhash
                FROM {$tableHierarchy} ch
                INNER JOIN {$tableNode} n ON ch.parentnodeanchor = n.relationanchorpoint
                CROSS JOIN unnest(ch.childnodeanchors) AS child_anchor
                WHERE n.nodeaggregateid = :nodeAggregateId
                  AND ch.contentstreamid = :contentStreamId
                  AND ch.dimensionspacepointhash IN (:dimensionSpacePointHashes)
                  -- Stop when child has tag explicitly (value = true)
                  AND (ch.subtreetags->(child_anchor::text)->>:tagName) IS DISTINCT FROM 'true'

                UNION ALL

                SELECT
                    gc_anchor,
                    dh.dimensionspacepointhash
                FROM desc_children dc
                JOIN {$tableHierarchy} dh
                    ON dh.parentnodeanchor = dc.child_anchor
                    AND dh.contentstreamid = :contentStreamId
                    AND dh.dimensionspacepointhash = dc.dimensionspacepointhash
                CROSS JOIN unnest(dh.childnodeanchors) AS gc_anchor
                WHERE (dh.subtreetags->(gc_anchor::text)->>:tagName) IS DISTINCT FROM 'true'
            ),
            affected_relations AS (
                SELECT DISTINCT h.parentnodeanchor, h.dimensionspacepointhash
                FROM {$tableHierarchy} h
                WHERE h.contentstreamid = :contentStreamId
                  AND EXISTS (
                      SELECT 1 FROM desc_children dc
                      WHERE dc.child_anchor = ANY(h.childnodeanchors)
                        AND dc.dimensionspacepointhash = h.dimensionspacepointhash
                  )
            )
            UPDATE {$tableHierarchy} h
            SET subtreetags = (
                SELECT COALESCE(
                    jsonb_object_agg(
                        anchor::text,
                        CASE
                            WHEN anchor IN (
                                SELECT dc.child_anchor FROM desc_children dc
                                WHERE dc.dimensionspacepointhash = h.dimensionspacepointhash
                            )
                            THEN (h.subtreetags->(anchor::text)) - :tagName
                            ELSE h.subtreetags->(anchor::text)
                        END
                    ) FILTER (WHERE h.subtreetags->(anchor::text) IS NOT NULL),
                    h.subtreetags
                )
                FROM unnest(h.childnodeanchors) AS anchor
            )
            FROM affected_relations ar
            WHERE h.parentnodeanchor = ar.parentnodeanchor
              AND h.dimensionspacepointhash = ar.dimensionspacepointhash
              AND h.contentstreamid = :contentStreamId
        SQL;

        try {
            $this->getDatabaseConnection()->executeStatement($removeTagStatement, [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagName' => $tag->value,
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove subtree tag "%s" from descendants: %s', $tag->value, $e->getMessage()), 1716482293, $e);
        }

        // Remove the explicit tag from the node itself.
        // Check if the parent still has this tag → if so, set to null (inherited); otherwise remove.
        $removeTagFromNodeStatement = <<<SQL
            UPDATE {$tableHierarchy} h
            SET subtreetags = (
                CASE
                    -- Check if the grandparent hierarchy has this tag for the parent node
                    WHEN EXISTS (
                        SELECT 1
                        FROM {$tableHierarchy} gph
                        JOIN {$tableNode} pn ON pn.relationanchorpoint = h.parentnodeanchor
                        WHERE pn.relationanchorpoint = ANY(gph.childnodeanchors)
                          AND gph.contentstreamid = :contentStreamId
                          AND jsonb_exists(COALESCE(gph.subtreetags->(pn.relationanchorpoint::text), '{}'), :tagName)
                    )
                    THEN jsonb_set(
                        COALESCE(h.subtreetags, '{}'),
                        ARRAY[n.relationanchorpoint::text],
                        (COALESCE(h.subtreetags->(n.relationanchorpoint::text), '{}') - :tagName) || jsonb_build_object(:tagName::text, null)
                    )
                    ELSE jsonb_set(
                        COALESCE(h.subtreetags, '{}'),
                        ARRAY[n.relationanchorpoint::text],
                        COALESCE(h.subtreetags->(n.relationanchorpoint::text), '{}') - :tagName
                    )
                END
            )
            FROM {$tableNode} n
            WHERE n.nodeaggregateid = :nodeAggregateId
              AND n.relationanchorpoint = ANY(h.childnodeanchors)
              AND h.contentstreamid = :contentStreamId
              AND h.dimensionspacepointhash IN (:dimensionSpacePointHashes)
        SQL;

        try {
            $this->getDatabaseConnection()->executeStatement($removeTagFromNodeStatement, [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagName' => $tag->value,
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove explicit subtree tag "%s" from node: %s', $tag->value, $e->getMessage()), 1716482574, $e);
        }
    }

    /**
     * Read the subtree tags for a specific node (identified by its anchor) from its incoming hierarchy relation.
     * Used during node creation/variation to inherit parent tags.
     */
    private function subtreeTagsForNode(
        ContentStreamId $contentStreamId,
        int $nodeAnchor,
        DimensionSpacePoint $dimensionSpacePoint
    ): NodeTags {
        $tableHierarchy = $this->getTableNames()->hierarchyRelation();

        try {
            $subtreeTagsJson = $this->getDatabaseConnection()->fetchOne(
                'SELECT h.subtreetags->(:nodeAnchor::text)
                 FROM ' . $tableHierarchy . ' h
                 WHERE :nodeAnchor::bigint = ANY(h.childnodeanchors)
                   AND h.contentstreamid = :contentStreamId
                   AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                [
                    'nodeAnchor' => $nodeAnchor,
                    'contentStreamId' => $contentStreamId->value,
                    'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
                ]
            );
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch subtree tags for node anchor %d: %s', $nodeAnchor, $e->getMessage()), 1716478760, $e);
        }

        if (!is_string($subtreeTagsJson)) {
            return NodeTags::createEmpty();
        }

        return self::extractNodeTagsFromJson($subtreeTagsJson);
    }

    /**
     * Parse a JSONB tag object into NodeTags.
     * Format: {"tagName": true, "otherTag": null}
     *   - true = explicit
     *   - null = inherited
     */
    public static function extractNodeTagsFromJson(string $subtreeTagsJson): NodeTags
    {
        $tagsArray = json_decode($subtreeTagsJson, true, 512, JSON_THROW_ON_ERROR);
        if (empty($tagsArray)) {
            return NodeTags::createEmpty();
        }

        $explicitTags = [];
        $inheritedTags = [];
        foreach ($tagsArray as $tagName => $explicit) {
            if ($explicit) {
                $explicitTags[] = $tagName;
            } else {
                $inheritedTags[] = $tagName;
            }
        }

        return NodeTags::create(
            tags: \Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags::fromStrings(...$explicitTags),
            inheritedTags: \Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTags::fromStrings(...$inheritedTags)
        );
    }

    abstract protected function getDatabaseConnection(): Connection;
    abstract protected function getTableNames(): ContentGraphTableNames;
}
