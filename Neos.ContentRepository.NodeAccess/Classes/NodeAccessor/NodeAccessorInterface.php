<?php
declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\NodeAccessor;

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
use Neos\ContentRepository\Projection\ContentGraph\References;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\Feature\SubtreeInterface;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Projection\ContentGraph\SearchTerm;
use Neos\ContentRepository\SharedModel\Node\PropertyName;

/**
 * This is the main "node access" API which the upper layers of Neos, the Neos UI, Fusion should always use
 * to fetch Nodes and traverse the Node Tree.
 *
 * You can retrieve instances of NodeAccessor by injecting {@see NodeAccessorManager}
 * and calling {@see NodeAccessorManager::accessorFor()).
 *
 * ## Extensibility
 *
 * Because we want to make the fetching and traversal of Nodes independent of an actual storage implementation
 * (implemented by {@see ContentSubgraphInterface}), we instead use this interface as central access point.
 *
 * Internally, a NodeAccessor might *delegate* to another NodeAccessor; so effectively a *Chain* of NodeAccessors
 * is built up. This can be used to e.g. implement "Virtual Nodes" not tied to the Content Graph, or many kinds
 * of other features.
 *
 * To build an own NodeAccessor, the following things need to be done:
 *
 * 1) Create a custom implementation of {@see NodeAccessorInterface}.
 *    We recommend to use the {@see AbstractDelegatingNodeAccessor} for a base implementation
 *    of the full {@see NodeAccessorInterface} which delegates every call to the next accessor,
 *    and then overriding the methods selectively where you want to hook in.
 *
 * 2) Create a custom factory for your NodeAccessor, by implementing {@see NodeAccessorFactoryInterface}.
 *    Ensure that you pass on $nextAccessor to your custom factory.
 *
 * 3) Register your custom factory in `Settings.yaml` underneath `nodeAccessorFactories`.
 */
interface NodeAccessorInterface
{
    // Find by ID
    public function findByIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface;

    // Traversal
    public function findChildNodes(
        NodeInterface $parentNode,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes;

    // Nodes implements IteratorAggregate oÄ

    public function findReferencedNodes(NodeInterface $node, PropertyName $name = null): References;

    public function findReferencingNodes(NodeInterface $node, PropertyName $name = null): References;

    public function findParentNode(NodeInterface $childNode): ?NodeInterface;

    public function findNodeByPath(NodePath $path, NodeInterface $startingNode): ?NodeInterface;

    public function findChildNodeConnectedThroughEdgeName(
        NodeInterface $parentNode,
        NodeName $edgeName
    ): ?NodeInterface;

    // NO SIBLING methods - as we do not use them except in constraint checks

    public function findNodePath(NodeInterface $node): NodePath;

    /**
     * @param NodeInterface[] $entryNodes
     */
    public function findSubtrees(
        array $entryNodes,
        int $maximumLevels,
        NodeTypeConstraints $nodeTypeConstraints
    ): SubtreeInterface;

    /**
     * Recursively find all nodes underneath the $entryNodeAggregateIdentifiers,
     * which match the node type constraints specified by NodeTypeConstraints.
     *
     * If a Search Term is specified, the properties are searched for this search term.
     *
     * @param NodeInterface[] $entryNodes
     */
    public function findDescendants(
        array $entryNodes,
        NodeTypeConstraints $nodeTypeConstraints,
        ?SearchTerm $searchTerm
    ): Nodes;

    /**
     * Returns a single root node for the given node type name.
     *
     * Will throw an exception, if no such node exists
     *
     * @throws \InvalidArgumentException
     */
    public function findRootNodeByType(NodeTypeName $nodeTypeName): NodeInterface;
}
