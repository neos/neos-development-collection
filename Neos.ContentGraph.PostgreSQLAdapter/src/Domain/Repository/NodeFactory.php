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

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\CoverageByOrigin;
use Neos\ContentRepository\Core\Projection\ContentGraph\DimensionSpacePointsBySubtreeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\OriginByCoverage;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The node factory for mapping database rows to nodes and node aggregates
 *
 * @internal
 */
final class NodeFactory
{
    private PropertyConverter $propertyConverter;

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        PropertyConverter $propertyConverter
    ) {
        $this->propertyConverter = $propertyConverter;
    }

    /**
     * @param array<string,string> $nodeRow
     */
    public function mapNodeRowToNode(
        array $nodeRow,
        VisibilityConstraints $visibilityConstraints,
        ?DimensionSpacePoint $dimensionSpacePoint = null,
        ?ContentStreamId $contentStreamId = null
    ): Node {
        return Node::create(
            $this->contentRepositoryId,
            // todo use actual workspace name
            WorkspaceName::fromString('missing'),
            $dimensionSpacePoint ?: DimensionSpacePoint::fromJsonString($nodeRow['dimensionspacepoint']),
            NodeAggregateId::fromString($nodeRow['nodeaggregateid']),
            OriginDimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']),
            NodeAggregateClassification::from($nodeRow['classification']),
            NodeTypeName::fromString($nodeRow['nodetypename']),
            new PropertyCollection(
                SerializedPropertyValues::fromJsonString($nodeRow['properties']),
                $this->propertyConverter
            ),
            $nodeRow['nodename'] ? NodeName::fromString($nodeRow['nodename']) : null,
            // TODO implement {@see \Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory::mapNodeRowToNode()}
            NodeTags::createEmpty(),
            Timestamps::create(
                // TODO replace with $nodeRow['created'] and $nodeRow['originalcreated'] once projection has implemented support
                self::parseDateTimeString('2023-03-17 12:00:00'),
                self::parseDateTimeString('2023-03-17 12:00:00'),
                isset($nodeRow['lastmodified']) ? self::parseDateTimeString($nodeRow['lastmodified']) : null,
                isset($nodeRow['originallastmodified']) ? self::parseDateTimeString($nodeRow['originallastmodified']) : null,
            ),
            $visibilityConstraints,
            $contentStreamId ?: ContentStreamId::fromString($nodeRow['contentstreamid']),
        );
    }

    /**
     * @param array<int,array<string,string>> $nodeRows
     */
    public function mapNodeRowsToNodes(
        array $nodeRows,
        VisibilityConstraints $visibilityConstraints,
        ContentStreamId $contentStreamId = null
    ): Nodes {
        $nodes = [];
        foreach ($nodeRows as $nodeRow) {
            $nodes[] = $this->mapNodeRowToNode(
                $nodeRow,
                $visibilityConstraints,
                null,
                $contentStreamId
            );
        }

        return Nodes::fromArray($nodes);
    }

    /**
     * @param array<int,array<string,string>> $referenceRows
     */
    public function mapReferenceRowsToReferences(
        array $referenceRows,
        VisibilityConstraints $visibilityConstraints,
        ContentStreamId $contentStreamId = null
    ): References {
        $references = [];
        foreach ($referenceRows as $referenceRow) {
            $references[] = new Reference(
                $this->mapNodeRowToNode(
                    $referenceRow,
                    $visibilityConstraints,
                    null,
                    $contentStreamId
                ),
                ReferenceName::fromString($referenceRow['referencename']),
                $referenceRow['referenceproperties']
                    ? new PropertyCollection(
                        SerializedPropertyValues::fromJsonString($referenceRow['referenceproperties']),
                        $this->propertyConverter
                    )
                    : null
            );
        }

        return References::fromArray($references);
    }

    /**
     * @param array<int,array<string,string>> $nodeRows
     */
    public function mapNodeRowsToSubtree(
        array $nodeRows,
        VisibilityConstraints $visibilityConstraints
    ): ?Subtree {
        /** @var array<string, Subtree[]> $subtreesByParentNodeId */
        $subtreesByParentNodeId = [];
        foreach (array_reverse($nodeRows) as $nodeRow) {
            $nodeAggregateId = $nodeRow['nodeaggregateid'];
            $parentNodeAggregateId = $nodeRow['parentnodeaggregateid'];
            $node = $this->mapNodeRowToNode($nodeRow, $visibilityConstraints);
            $subtree = new Subtree((int)$nodeRow['level'], $node, array_key_exists($nodeAggregateId, $subtreesByParentNodeId) ? array_reverse($subtreesByParentNodeId[$nodeAggregateId]) : []);
            if ($subtree->level === 0) {
                return $subtree;
            }
            if (!array_key_exists($parentNodeAggregateId, $subtreesByParentNodeId)) {
                $subtreesByParentNodeId[$parentNodeAggregateId] = [];
            }
            $subtreesByParentNodeId[$parentNodeAggregateId][] = $subtree;
        }
        return null;
    }

    /**
     * @param array<int,array<string,string>> $nodeRows
     */
    public function mapNodeRowsToNodeAggregate(
        array $nodeRows,
        VisibilityConstraints $visibilityConstraints
    ): ?NodeAggregate {
        if (empty($nodeRows)) {
            return null;
        }

        $contentStreamId = null;
        $nodeAggregateId = null;
        $nodeAggregateClassification = null;
        $nodeTypeName = null;
        $nodeName = null;
        /** @var OriginDimensionSpacePoint[] $occupiedDimensionSpacePoints */
        $occupiedDimensionSpacePoints = [];
        $nodesByOccupiedDimensionSpacePoint = [];
        /** @var DimensionSpacePoint[][] $coverageByOccupant */
        $coverageByOccupant = [];
        /** @var DimensionSpacePoint[] $coveredDimensionSpacePoints */
        $coveredDimensionSpacePoints = [];
        $nodesByCoveredDimensionSpacePoint = [];
        $occupationByCovered = [];
        /** @var DimensionSpacePoint[] $disabledDimensionSpacePoints */
        $disabledDimensionSpacePoints = [];
        foreach ($nodeRows as $nodeRow) {
            $contentStreamId = $contentStreamId
                ?: ContentStreamId::fromString($nodeRow['contentstreamid']);
            $node = $this->mapNodeRowToNode(
                $nodeRow,
                $visibilityConstraints,
                null,
                $contentStreamId
            );
            $nodeAggregateId = $nodeAggregateId
                ?: NodeAggregateId::fromString($nodeRow['nodeaggregateid']);
            $nodeAggregateClassification = $nodeAggregateClassification
                ?: NodeAggregateClassification::from($nodeRow['classification']);
            $nodeTypeName = $nodeTypeName ?: NodeTypeName::fromString($nodeRow['nodetypename']);
            if (!empty($nodeRow['nodename']) && is_null($nodeName)) {
                $nodeName = NodeName::fromString($nodeRow['nodename']);
            }
            $occupiedDimensionSpacePoints[$node->originDimensionSpacePoint->hash]
                = $node->originDimensionSpacePoint;
            $nodesByOccupiedDimensionSpacePoint[$node->originDimensionSpacePoint->hash] = $node;

            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString($nodeRow['dimensionspacepoint']);
            $coverageByOccupant[$node->originDimensionSpacePoint->hash][$coveredDimensionSpacePoint->hash]
                = $coveredDimensionSpacePoint;
            $coveredDimensionSpacePoints[$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePoint[$coveredDimensionSpacePoint->hash] = $node;
            $occupationByCovered[$coveredDimensionSpacePoint->hash] = $node->originDimensionSpacePoint;
            if (isset($nodeRow['disableddimensionspacepointhash']) && $nodeRow['disableddimensionspacepointhash']) {
                $disabledDimensionSpacePoints[$nodeRow['disableddimensionspacepointhash']]
                    = $coveredDimensionSpacePoints[$nodeRow['disableddimensionspacepointhash']];
            }
        }

        return NodeAggregate::create(
            $this->contentRepositoryId,
            WorkspaceName::fromString('missing'), // todo
            $nodeAggregateId,
            $nodeAggregateClassification,
            $nodeTypeName,
            $nodeName,
            new OriginDimensionSpacePointSet($occupiedDimensionSpacePoints),
            $nodesByOccupiedDimensionSpacePoint,
            CoverageByOrigin::fromArray($coverageByOccupant),
            new DimensionSpacePointSet($coveredDimensionSpacePoints),
            $nodesByCoveredDimensionSpacePoint,
            OriginByCoverage::fromArray($occupationByCovered),
            // TODO implement (see \Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory::mapNodeRowsToNodeAggregate())
            DimensionSpacePointsBySubtreeTags::create(),
            $contentStreamId,
        );
    }

    /**
     * @param array<int,array<string,mixed>> $nodeRows
     */
    public function mapNodeRowsToNodeAggregates(array $nodeRows, VisibilityConstraints $visibilityConstraints): NodeAggregates
    {
        if (empty($nodeRows)) {
            return NodeAggregates::createEmpty();
        }

        $nodeAggregates = [];

        $contentStreamId = null;
        /** @var NodeAggregateId[] $nodeAggregateIds */
        $nodeAggregateIds = [];
        /** @var NodeAggregateClassification[] $nodeAggregateClassifications */
        $nodeAggregateClassifications = [];
        /** @var NodeTypeName[] $nodeTypeNames */
        $nodeTypeNames = [];
        /** @var NodeName[] $nodeNames */
        $nodeNames = [];
        /** @var OriginDimensionSpacePoint[][] $occupiedDimensionSpacePoints */
        $occupiedDimensionSpacePoints = [];
        /** @var Node[][] $nodesByOccupiedDimensionSpacePoint */
        $nodesByOccupiedDimensionSpacePoint = [];
        /** @var DimensionSpacePoint[][][] $coverageByOccupant */
        $coverageByOccupant = [];
        /** @var DimensionSpacePoint[][] $coveredDimensionSpacePoints */
        $coveredDimensionSpacePoints = [];
        /** @var Node[][] $nodesByCoveredDimensionSpacePoint */
        $nodesByCoveredDimensionSpacePoint = [];
        /** @var OriginDimensionSpacePoint[][] $occupationByCovered */
        $occupationByCovered = [];
        /** @var DimensionSpacePoint[][] $disabledDimensionSpacePoints */
        $disabledDimensionSpacePoints = [];
        foreach ($nodeRows as $nodeRow) {
            $key = $nodeRow['nodeaggregateid'];
            $contentStreamId = $contentStreamId
                ?: ContentStreamId::fromString($nodeRow['contentstreamid']);
            $node = $this->mapNodeRowToNode(
                $nodeRow,
                $visibilityConstraints,
                null,
                $contentStreamId
            );
            $nodeAggregateIds[$key] = NodeAggregateId::fromString(
                $nodeRow['nodeaggregateid']
            );
            if (!isset($nodeAggregateClassifications[$key])) {
                $nodeAggregateClassifications[$key] = NodeAggregateClassification::from(
                    $nodeRow['classification']
                );
            }
            if (!isset($nodeTypeNames[$key])) {
                $nodeTypeNames[$key] = NodeTypeName::fromString($nodeRow['nodetypename']);
            }
            if (!empty($nodeRow['nodename']) && !isset($nodeNames[$nodeRow['nodename']])) {
                $nodeNames[$key] = NodeName::fromString($nodeRow['nodename']);
            } else {
                $nodeNames[$key] = null;
            }
            $occupiedDimensionSpacePoints[$key][$node->originDimensionSpacePoint->hash]
                = $node->originDimensionSpacePoint;
            $nodesByOccupiedDimensionSpacePoint[$key][$node->originDimensionSpacePoint->hash] = $node;

            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString($nodeRow['dimensionspacepoint']);
            $coveredDimensionSpacePoints[$key][$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            $coverageByOccupant[$key][$node->originDimensionSpacePoint->hash][$coveredDimensionSpacePoint->hash]
                = $coveredDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePoint[$key][$coveredDimensionSpacePoint->hash] = $node;
            $occupationByCovered[$key][$coveredDimensionSpacePoint->hash] = $node->originDimensionSpacePoint;
            if (!isset($disabledDimensionSpacePoints[$key])) {
                $disabledDimensionSpacePoints[$key] = [];
            }
            if (isset($nodeRow['disableddimensionspacepointhash']) && $nodeRow['disableddimensionspacepointhash']) {
                $disabledDimensionSpacePoints[$key][$nodeRow['disableddimensionspacepointhash']]
                    = $coveredDimensionSpacePoints[$key][$nodeRow['disableddimensionspacepointhash']];
            }
        }

        foreach ($nodeAggregateIds as $key => $nodeAggregateId) {
            $nodeAggregates[] = NodeAggregate::create(
                $this->contentRepositoryId,
                WorkspaceName::fromString('missing'), // todo
                $nodeAggregateId,
                $nodeAggregateClassifications[$key],
                $nodeTypeNames[$key],
                $nodeNames[$key],
                new OriginDimensionSpacePointSet($occupiedDimensionSpacePoints[$key]),
                $nodesByOccupiedDimensionSpacePoint[$key],
                CoverageByOrigin::fromArray($coverageByOccupant[$key]),
                new DimensionSpacePointSet($coveredDimensionSpacePoints[$key]),
                $nodesByCoveredDimensionSpacePoint[$key],
                OriginByCoverage::fromArray($occupationByCovered[$key]),
                // TODO implement (see \Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory::mapNodeRowsToNodeAggregates())
                DimensionSpacePointsBySubtreeTags::create(),
                $contentStreamId,
            );
        }

        return NodeAggregates::fromArray($nodeAggregates);
    }

    private static function parseDateTimeString(string $string): \DateTimeImmutable
    {
        $result = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $string);
        if ($result === false) {
            throw new \RuntimeException(sprintf('Failed to parse "%s" into a valid DateTime', $string), 1678902055);
        }
        return $result;
    }
}
