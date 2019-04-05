<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeConstraints;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\EventSourcedContentRepository\Domain\Context\Node\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;

/**
 * The interface to be implemented by content subgraphs
 */
interface ContentSubgraphInterface extends \JsonSerializable
{
    /**
     * TODO: TraverseHierarchy refactoring!
     *
     * @param NodeInterface $startNode
     * @param HierarchyTraversalDirection $direction
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param callable $callback
     */
    public function traverseHierarchy(NodeInterface $startNode, HierarchyTraversalDirection $direction, NodeTypeConstraints $nodeTypeConstraints, callable $callback): void;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     */
    public function findChildNodes(NodeAggregateIdentifier $nodeAggregateIdentifier, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateAggregateIdentifier
     * @param PropertyName|null $name
     * @return NodeInterface[]
     */
    public function findReferencedNodes(NodeAggregateIdentifier $nodeAggregateAggregateIdentifier, PropertyName $name = null): array;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param PropertyName $name
     * @return NodeInterface[]
     */
    public function findReferencingNodes(NodeAggregateIdentifier $nodeAggregateIdentifier, PropertyName $name = null): array;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @return int
     */
    public function countChildNodes(NodeAggregateIdentifier $parentNodeAggregateIdentifier, NodeTypeConstraints $nodeTypeConstraints = null): int;

    /**
     * @param NodeAggregateIdentifier $childAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findParentNode(NodeAggregateIdentifier $childAggregateIdentifier): ?NodeInterface;

    /**
     * @param string $path
     * @param NodeAggregateIdentifier $startingNodeAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByPath(string $path, NodeAggregateIdentifier $startingNodeAggregateIdentifier): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $edgeName
     * @return NodeInterface|null
     */
    public function findChildNodeConnectedThroughEdgeName(NodeAggregateIdentifier $parentNodeAggregateIdentifier, NodeName $edgeName): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $parentAggregateIdentifier
     * @param NodeName $edgeName
     * @return NodeInterface|null
     */
    public function findChildNodeByNodeAggregateIdentifierConnectedThroughEdgeName(NodeAggregateIdentifier $parentAggregateIdentifier, NodeName $edgeName): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodePath
     */
    public function findNodePath(NodeAggregateIdentifier $nodeAggregateIdentifier): NodePath;

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier;

    /**
     * @return DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint;

    /**
     * @param NodeAggregateIdentifier[] $entryNodeAggregateIdentifiers
     * @param int $maximumLevels
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @return mixed
     */
    public function findSubtrees(array $entryNodeAggregateIdentifiers, int $maximumLevels, NodeTypeConstraints $nodeTypeConstraints): SubtreeInterface;

    public function countNodes(): int;

    public function getInMemoryCache(): InMemoryCache;
}
