<?php
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
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodePath;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeConstraints;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain;
use Neos\EventSourcedContentRepository\Domain\Context\Node\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\Flow\Annotations as Flow;

/**
 * The interface to be implemented by content subgraphs
 */
interface ContentSubgraphInterface extends \JsonSerializable
{
    /**
     * @param NodeInterface $startNode
     * @param HierarchyTraversalDirection $direction
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param callable $callback
     */
    public function traverseHierarchy(NodeInterface $startNode, HierarchyTraversalDirection $direction, NodeTypeConstraints $nodeTypeConstraints, callable $callback): void;

    /**
     * @param NodeIdentifier $nodeIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByIdentifier(NodeIdentifier $nodeIdentifier): ?NodeInterface;

    /**
     * @param NodeIdentifier $parentNodeIdentifier
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     */
    public function findChildNodes(NodeIdentifier $parentNodeIdentifier, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * @param NodeIdentifier $nodeIdentifier
     * @param PropertyName|null $name
     * @return NodeInterface[]
     */
    public function findReferencedNodes(NodeIdentifier $nodeIdentifier, PropertyName $name = null): array;

    /**
     * @param NodeIdentifier $nodeIdentifier
     * @param PropertyName $name
     * @return NodeInterface[]
     */
    public function findReferencingNodes(NodeIdentifier $nodeIdentifier, PropertyName $name = null): array;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface;

    /**
     * @param NodeIdentifier $parentIdentifier
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @return int
     */
    public function countChildNodes(NodeIdentifier $parentIdentifier, NodeTypeConstraints $nodeTypeConstraints = null): int;

    /**
     * @param NodeIdentifier $childIdentifier
     * @return NodeInterface|null
     */
    public function findParentNode(NodeIdentifier $childIdentifier): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $childNodeAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findParentNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $childNodeAggregateIdentifier): ?NodeInterface;

    /**
     * @param NodeIdentifier $parentIdentifier
     * @return NodeInterface|null
     */
    public function findFirstChildNode(NodeIdentifier $parentIdentifier): ?NodeInterface;

    /**
     * @param string $path
     * @param NodeIdentifier $startingNodeIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByPath(string $path, NodeIdentifier $startingNodeIdentifier): ?NodeInterface;

    /**
     * @param NodeIdentifier $parentIdentifier
     * @param NodeName $edgeName
     * @return NodeInterface|null
     */
    public function findChildNodeConnectedThroughEdgeName(NodeIdentifier $parentIdentifier, NodeName $edgeName): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $parentAggregateIdentifier
     * @param NodeName $edgeName
     * @return NodeInterface|null
     */
    public function findChildNodeByNodeAggregateIdentifierConnectedThroughEdgeName(NodeAggregateIdentifier $parentAggregateIdentifier, NodeName $edgeName): ?NodeInterface;

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     */
    public function findSiblings(NodeAggregateIdentifier $sibling, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     */
    public function findSucceedingSiblings(NodeAggregateIdentifier $sibling, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     */
    public function findPrecedingSiblings(NodeAggregateIdentifier $sibling, NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * @param NodeTypeName $nodeTypeName
     * @return array|NodeInterface[]
     */
    public function findNodesByType(NodeTypeName $nodeTypeName): array;

    /**
     * @param NodeIdentifier $nodeIdentifier
     * @return NodePath
     */
    public function findNodePath(NodeIdentifier $nodeIdentifier): NodePath;

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
     * @param Domain\Context\Parameters\ContextParameters $contextParameters
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @return mixed
     */
    public function findSubtrees(array $entryNodeAggregateIdentifiers, int $maximumLevels, Domain\Context\Parameters\ContextParameters $contextParameters, NodeTypeConstraints $nodeTypeConstraints): SubtreeInterface;

    public function getInMemoryCache(): InMemoryCache;
}
