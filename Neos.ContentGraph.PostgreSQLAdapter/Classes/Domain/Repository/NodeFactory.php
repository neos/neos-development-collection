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
        $occupiedDimensionSpacePoints = new OriginDimensionSpacePointSet([]);
        $nodesByOccupiedDimensionSpacePoint = [];
        $coverageByOccupant = [];
        $coveredDimensionSpacePoints = new DimensionSpacePointSet([]);
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
            $originDimensionSpacePoint = OriginDimensionSpacePoint::fromArray(\json_decode($nodeRow['origindimensionspacepoint'], true));
            $occupiedDimensionSpacePoints = $occupiedDimensionSpacePoints->getUnion(new OriginDimensionSpacePointSet([$originDimensionSpacePoint]));
            $nodesByOccupiedDimensionSpacePoint[$originDimensionSpacePoint->getHash()] = $node;
            $coverageByNode = new DimensionSpacePointSet(\json_decode($nodeRow['dimensionspacepoints'], true));
            $coverageByOccupant[$originDimensionSpacePoint->getHash()] = $coverageByNode;
            $coveredDimensionSpacePoints = $coveredDimensionSpacePoints->getUnion($coverageByNode);
            foreach ($coverageByNode as $dimensionSpacePoint) {
                $nodesByCoveredDimensionSpacePoint[$dimensionSpacePoint->getHash()] = $node;
                $occupationByCovered[$dimensionSpacePoint->getHash()] = $originDimensionSpacePoint;
            }
        }

        return new NodeAggregate(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $nodeAggregateClassification,
            $nodeTypeName,
            $nodeName,
            $occupiedDimensionSpacePoints,
            $nodesByOccupiedDimensionSpacePoint,
            $coverageByOccupant,
            $coveredDimensionSpacePoints,
            $nodesByCoveredDimensionSpacePoint,
            $occupationByCovered,
            new DimensionSpacePointSet([])
        );
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
            SerializedPropertyValues::fromArray(\json_decode($nodeRow['properties'])),
            NodeAggregateClassification::fromString($nodeRow['classification'])
        );
    }
}
