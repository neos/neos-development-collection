<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Content\Node;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;

/**
 * The node factory for mapping database rows to nodes and node aggregates
 */
final class NodeFactory
{
    private NodeTypeManager $nodeTypeManager;

    public function __construct(NodeTypeManager $nodeTypeManager)
    {
        $this->nodeTypeManager = $nodeTypeManager;
    }

    public function mapNodeRowsToNodeAggregate(array $nodeRows): ?NodeAggregate
    {
        if (empty($nodeRows)) {
            return null;
        }

        $contentStreamIdentifier = null;
        $nodeAggregateIdentifier = null;
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
        foreach ($nodeRows as $nodeRow) {
            $node = $this->mapNodeRowToNode($nodeRow);
            $contentStreamIdentifier = $contentStreamIdentifier ?: ContentStreamIdentifier::fromString($nodeRow['contentstreamidentifier']);
            $nodeAggregateIdentifier = $nodeAggregateIdentifier ?: NodeAggregateIdentifier::fromString($nodeRow['nodeaggregateidentifier']);
            $nodeAggregateClassification = $nodeAggregateClassification ?: NodeAggregateClassification::fromString($nodeRow['classification']);
            $nodeTypeName = $nodeTypeName ?: NodeTypeName::fromString($nodeRow['nodetypename']);
            if (!empty($nodeRow['nodename']) && is_null($nodeName)) {
                $nodeName = NodeName::fromString($nodeRow['nodename']);
            }
            $occupiedDimensionSpacePoints[$node->getOriginDimensionSpacePoint()->getHash()] = $node->getOriginDimensionSpacePoint();
            $nodesByOccupiedDimensionSpacePoint[$node->getOriginDimensionSpacePoint()->getHash()] = $node;

            $coveredDimensionSpacePoint = DimensionSpacePoint::fromArray(\json_decode($nodeRow['dimensionspacepoint'], true));
            $coverageByOccupant[$node->getOriginDimensionSpacePoint()->getHash()][$coveredDimensionSpacePoint->getHash()] = $coveredDimensionSpacePoint;
            $coveredDimensionSpacePoints[$coveredDimensionSpacePoint->getHash()] = $coveredDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePoint[$coveredDimensionSpacePoint->getHash()] = $node;
            $occupationByCovered[$coveredDimensionSpacePoint->getHash()] = $node->getOriginDimensionSpacePoint();
        }

        return new NodeAggregate(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $nodeAggregateClassification,
            $nodeTypeName,
            $nodeName,
            new OriginDimensionSpacePointSet($occupiedDimensionSpacePoints),
            $nodesByOccupiedDimensionSpacePoint,
            $coverageByOccupant,
            new DimensionSpacePointSet($coveredDimensionSpacePoints),
            $nodesByCoveredDimensionSpacePoint,
            $occupationByCovered,
            new DimensionSpacePointSet([])
        );
    }

    /**
     * @param array $nodeRows
     * @return array|NodeAggregate[]
     */
    public function mapNodeRowsToNodeAggregates(array $nodeRows): array
    {
        $nodeAggregates = [];
        if (empty($nodeRows)) {
            return $nodeAggregates;
        }

        $contentStreamIdentifier = null;
        /** @var NodeAggregateIdentifier[] $nodeAggregateIdentifiers */
        $nodeAggregateIdentifiers = [];
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
        /** @var DimensionSpacePointSet[][] $coverageByOccupant */
        $coverageByOccupant = [];
        /** @var DimensionSpacePointSet[][] $coveredDimensionSpacePoints */
        $coveredDimensionSpacePoints = [];
        /** @var Node[][] $nodesByCoveredDimensionSpacePoint */
        $nodesByCoveredDimensionSpacePoint = [];
        /** @var OriginDimensionSpacePoint[][] $occupationByCovered */
        $occupationByCovered = [];
        foreach ($nodeRows as $nodeRow) {
            $key = $nodeRow['nodeaggregateidentifier'];
            $node = $this->mapNodeRowToNode($nodeRow);
            $contentStreamIdentifier = $contentStreamIdentifier ?: ContentStreamIdentifier::fromString($nodeRow['contentstreamidentifier']);
            $nodeAggregateIdentifier[$key] = NodeAggregateIdentifier::fromString($nodeRow['nodeaggregateidentifier']);
            if (!isset($nodeAggregateClassifications[$key])) {
                $nodeAggregateClassifications[$key] = NodeAggregateClassification::fromString($nodeRow['classification']);
            }
            if (!isset($nodeTypeNames[$key])) {
                $nodeTypeNames[$key] = NodeTypeName::fromString($nodeRow['nodetypename']);
            }
            if (!empty($nodeRow['nodename']) && !isset($nodeNames[$nodeRow['nodename']])) {
                $nodeNames[$key] = NodeName::fromString($nodeRow['nodename']);
            } else {
                $nodeNames[$key] = null;
            }
            $occupiedDimensionSpacePoints[$key][$node->getOriginDimensionSpacePoint()->getHash()] = $node->getOriginDimensionSpacePoint();
            $nodesByOccupiedDimensionSpacePoint[$key][$node->getOriginDimensionSpacePoint()->getHash()] = $node;

            $coveredDimensionSpacePoint = DimensionSpacePoint::fromArray(\json_decode($nodeRow['dimensionspacepoint'], true));
            $coveredDimensionSpacePoints[$key][$coveredDimensionSpacePoint->getHash()] = $coveredDimensionSpacePoint;
            $coverageByOccupant[$key][$node->getOriginDimensionSpacePoint()->getHash()][$coveredDimensionSpacePoint->getHash()] = $coveredDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePoint[$key][$coveredDimensionSpacePoint->getHash()] = $node;
            $occupationByCovered[$key][$coveredDimensionSpacePoint->getHash()] = $node->getOriginDimensionSpacePoint();
        }

        foreach ($nodeAggregateIdentifiers as $key => $nodeAggregateIdentifier) {
            $nodeAggregates[$key] = new NodeAggregate(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $nodeAggregateClassifications[$key],
                $nodeTypeNames[$key],
                $nodeNames[$key],
                new OriginDimensionSpacePointSet($occupiedDimensionSpacePoints[$key]),
                $nodesByOccupiedDimensionSpacePoint[$key],
                $coverageByOccupant[$key],
                new DimensionSpacePointSet($coveredDimensionSpacePoints[$key]),
                $nodesByCoveredDimensionSpacePoint[$key],
                $occupationByCovered[$key],
                new DimensionSpacePointSet([])
            );
        }

        return $nodeAggregates;
    }

    public function mapNodeRowToNode(array $nodeRow): Node
    {
        return new Node(
            ContentStreamIdentifier::fromString($nodeRow['contentstreamidentifier']),
            NodeAggregateIdentifier::fromString($nodeRow['nodeaggregateidentifier']),
            OriginDimensionSpacePoint::fromArray(\json_decode($nodeRow['origindimensionspacepoint'], true)),
            NodeTypeName::fromString($nodeRow['nodetypename']),
            $this->nodeTypeManager->getNodeType($nodeRow['nodetypename']),
            !empty($nodeRow['nodename']) ? NodeName::fromString($nodeRow['nodename']) : null,
            SerializedPropertyValues::fromArray(\json_decode($nodeRow['properties'], true)),
            NodeAggregateClassification::fromString($nodeRow['classification'])
        );
    }
}
