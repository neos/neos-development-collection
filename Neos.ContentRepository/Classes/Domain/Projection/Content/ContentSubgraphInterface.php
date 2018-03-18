<?php
namespace Neos\ContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain;
use Neos\ContentRepository\Domain\Context\Node\SubtreeInterface;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * The interface to be implemented by content subgraphs
 */
interface ContentSubgraphInterface extends \JsonSerializable
{
    /**
     * @param NodeInterface $startNode
     * @param HierarchyTraversalDirection $direction
     * @param Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints
     * @param callable $callback
     */
    public function traverseHierarchy(NodeInterface $startNode, HierarchyTraversalDirection $direction, Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints, callable $callback): void;

    /**
     * @param Domain\ValueObject\NodeIdentifier $nodeIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByIdentifier(Domain\ValueObject\NodeIdentifier $nodeIdentifier): ?NodeInterface;

    /**
     * @param Domain\ValueObject\NodeIdentifier $parentNodeIdentifier
     * @param Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return array|NodeInterface[]
     */
    public function findChildNodes(Domain\ValueObject\NodeIdentifier $parentNodeIdentifier, Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): array;

    /**
     * @param NodeIdentifier $nodeIdentifier
     * @param Domain\ValueObject\PropertyName|null $name
     * @return NodeInterface[]
     */
    public function findReferencedNodes(Domain\ValueObject\NodeIdentifier $nodeIdentifier, Domain\ValueObject\PropertyName $name = null): array;

    /**
     * @param \Neos\ContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByNodeAggregateIdentifier(Domain\Context\NodeAggregate\NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface;

    /**
     * @param Domain\ValueObject\NodeIdentifier $parentIdentifier
     * @param Domain\ValueObject\NodeTypeConstraints|null $nodeTypeConstraints
     * @return int
     */
    public function countChildNodes(Domain\ValueObject\NodeIdentifier $parentIdentifier, Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null): int;

    /**
     * @param Domain\ValueObject\NodeIdentifier $childIdentifier
     * @return NodeInterface|null
     */
    public function findParentNode(Domain\ValueObject\NodeIdentifier $childIdentifier): ?NodeInterface;

    /**
     * @param Domain\Context\NodeAggregate\NodeAggregateIdentifier $childNodeAggregateIdentifier
     * @return NodeInterface|null
     */
    public function findParentNodeByNodeAggregateIdentifier(Domain\Context\NodeAggregate\NodeAggregateIdentifier $childNodeAggregateIdentifier): ?NodeInterface;

    /**
     * @param Domain\ValueObject\NodeIdentifier $parentIdentifier
     * @return NodeInterface|null
     */
    public function findFirstChildNode(Domain\ValueObject\NodeIdentifier $parentIdentifier): ?NodeInterface;

    /**
     * @param string $path
     * @param NodeIdentifier $startingNodeIdentifier
     * @return NodeInterface|null
     */
    public function findNodeByPath(string $path, NodeIdentifier $startingNodeIdentifier): ?NodeInterface;

    /**
     * @param Domain\ValueObject\NodeIdentifier $parentIdentifier
     * @param Domain\ValueObject\NodeName $edgeName
     * @return NodeInterface|null
     */
    public function findChildNodeConnectedThroughEdgeName(Domain\ValueObject\NodeIdentifier $parentIdentifier, Domain\ValueObject\NodeName $edgeName): ?NodeInterface;

    /**
     * @param \Neos\ContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifier $parentAggregateIdentifier
     * @param Domain\ValueObject\NodeName $edgeName
     * @return NodeInterface|null
     */
    public function findChildNodeByNodeAggregateIdentifierConnectedThroughEdgeName(Domain\Context\NodeAggregate\NodeAggregateIdentifier $parentAggregateIdentifier, Domain\ValueObject\NodeName $edgeName): ?NodeInterface;

    /**
     * @param Domain\ValueObject\NodeIdentifier $sibling
     * @return Domain\Model\NodeInterface|null
     */
    public function findSucceedingSibling(Domain\ValueObject\NodeIdentifier $sibling): ?NodeInterface;

    /**
     * @param NodeIdentifier $sibling
     * @return Domain\Model\NodeInterface|null
     */
    public function findPrecedingSibling(Domain\ValueObject\NodeIdentifier $sibling): ?NodeInterface;

    /**
     * @param Domain\ValueObject\NodeTypeName $nodeTypeName
     * @return array|NodeInterface[]
     */
    public function findNodesByType(Domain\ValueObject\NodeTypeName $nodeTypeName): array;

    /**
     * @param NodeIdentifier $nodeIdentifier
     * @return Domain\ValueObject\NodePath
     */
    public function findNodePath(Domain\ValueObject\NodeIdentifier $nodeIdentifier): Domain\ValueObject\NodePath;

    /**
     * @return \Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): Domain\Context\ContentStream\ContentStreamIdentifier;

    /**
     * @return Domain\ValueObject\DimensionSpacePoint
     */
    public function getDimensionSpacePoint(): Domain\ValueObject\DimensionSpacePoint;

    /**
     * @param NodeAggregateIdentifier[] $entryNodeAggregateIdentifiers
     * @param int $maximumLevels
     * @param Domain\Context\Parameters\ContextParameters $contextParameters
     * @param Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints
     * @return mixed
     */
    public function findSubtrees(array $entryNodeAggregateIdentifiers, int $maximumLevels, Domain\Context\Parameters\ContextParameters $contextParameters, Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints): SubtreeInterface;

    public function getInMemoryCache(): InMemoryCache;
}
