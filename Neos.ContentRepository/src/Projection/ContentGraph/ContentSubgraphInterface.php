<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Projection\ContentGraph;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\Feature\SubtreeInterface;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\ContentRepository\SharedModel\Node\PropertyName;

/**
 * This is the most important read model of a content repository.
 *
 * It is a "view" to the content graph, only showing a single dimension
 * (e.g. "language=de,country=ch") - so this means this is effectively
 * **a tree of nodes**.
 *
 * ## Accessing the Content Subgraph
 *
 * From the central Content Repository instance, you can fetch the singleton
 * {@see ContentGraphInterface}. There, you can call
 * {@see ContentGraphInterface::getSubgraph()} and pass in
 * the {@see ContentStreamIdentifier}, {@see DimensionSpacePoint} and
 * {@see VisibilityConstraints} you want to have.
 *
 *
 * ## Why is this called "Subgraph" and not Tree?
 *
 * This is because a tree can have only a single root node, but the ContentSubgraph
 * supports multiple root nodes. So the totally correct term would be a "Forest",
 * but this is unknown terminology outside academia. This is why we go for "Subgraph"
 * to show that this is a part of the Content Graph.
 *
 * @api
 */
interface ContentSubgraphInterface extends \JsonSerializable
{
    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     */
    public function findChildNodes(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null, // non nullable; NodeTypeConstraints::all()
        int $limit = null, // TODO REMOVE??
        int $offset = null // TODO REMOVE??
    ): Nodes;

    public function findReferencedNodes(
        NodeAggregateIdentifier $nodeAggregateAggregateIdentifier,
        PropertyName $name = null // non nullable; not PropertyName DTO; but ReferenceNameFilter::all()
    ): References;

    public function findReferencingNodes(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        PropertyName $name = null // non nullable; not PropertyName DTO; but ReferenceNameFilter::all()
    ): References;

    /**
     * TODO: RENAME: findById? or findByNodeAggregateId?
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return Node|null
     */
    public function findNodeByNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?Node;

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @return int
     *
     * TODO: REMOVE
     */
    public function countChildNodes(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeTypeConstraints $nodeTypeConstraints = null
    ): int;

    /**
     * @param NodeAggregateIdentifier $childNodeAggregateIdentifier
     * @return Node|null
     */
    public function findParentNode(NodeAggregateIdentifier $childNodeAggregateIdentifier): ?Node;

    /**
     * @param NodePath $path
     * @param NodeAggregateIdentifier $startingNodeAggregateIdentifier
     * @return Node|null
     *
     * TODO: findDescendantNodeByPath
     * TODO: DEPRECATE
     */
    public function findNodeByPath(
        NodePath $path,
        NodeAggregateIdentifier $startingNodeAggregateIdentifier
    ): ?Node;

    /**
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeName $edgeName
     * @return Node|null
     */
    public function findChildNodeConnectedThroughEdgeName(
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeName $edgeName
    ): ?Node;

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit // TODO REMOVE
     * @param int|null $offset // TODO REMOVE
     * @return Nodes
     *
     * TODO: REMOVE
     */
    public function findSiblings(
        NodeAggregateIdentifier $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): Nodes;

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     */
    public function findSucceedingSiblings(
        NodeAggregateIdentifier $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null, // TODO REMOVE
        int $offset = null // TODO REMOVE
    ): Nodes;

    /**
     * @param NodeAggregateIdentifier $sibling
     * @param NodeTypeConstraints|null $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @return Nodes
     */
    public function findPrecedingSiblings(
        NodeAggregateIdentifier $sibling,
        ?NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null, // TODO REMOVE
        int $offset = null // TODO REMOVE
    ): Nodes;

    /**
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodePath
     */
    public function findNodePath(NodeAggregateIdentifier $nodeAggregateIdentifier): NodePath;

    /**
     * @return ContentStreamIdentifier
     *
     * TODO REMOVE
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier;

    /**
     * @return DimensionSpacePoint
     *
     * TODO REMOVE
     */
    public function getDimensionSpacePoint(): DimensionSpacePoint;

    /**
     * TODO: ADJUST WITH findDescendants
     *
     * @internal
     * @deprecated
     */
    public function findSubtrees(
        NodeAggregateIdentifiers $entryNodeAggregateIdentifiers,
        int $maximumLevels,
        NodeTypeConstraints $nodeTypeConstraints
    ): SubtreeInterface;

    /**
     * Recursively find all nodes underneath the $entryNodeAggregateIdentifiers,
     * which match the node type constraints specified by NodeTypeConstraints.
     *
     * If a Search Term is specified, the properties are searched for this search term.
     *
     * TODO: ADJUST WITH findSubtrees
     *
     * @internal
     * @deprecated
     *
     * @param array<int,NodeAggregateIdentifier> $entryNodeAggregateIdentifiers
     * @param NodeTypeConstraints $nodeTypeConstraints
     * @param SearchTerm|null $searchTerm
     * @return Nodes
     */
    public function findDescendants(
        array $entryNodeAggregateIdentifiers,
        NodeTypeConstraints $nodeTypeConstraints,
        ?SearchTerm $searchTerm
    ): Nodes;

    /**
     * @return int
     * @internal
     * @deprecated do not rely on using this
     * TODO: rename to containsNodes()
     */
    public function countNodes(): int;
}
