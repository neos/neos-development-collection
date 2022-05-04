<?php
declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\Feature\SubtreeInterface;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\Content\Nodes;
use Neos\ContentRepository\Projection\Content\SearchTerm;
use Neos\ContentRepository\SharedModel\Node\PropertyName;

/**
 * Base abstract class which implements delegation. Use this as basis to build custom NodeAccessors.
 * See {@see NodeAccessorInterface} for a full usage description.
 */
abstract class AbstractDelegatingNodeAccessor
{
    protected NodeAccessorInterface $nextAccessor;

    public function __construct(NodeAccessorInterface $nextAccessor)
    {
        $this->nextAccessor = $nextAccessor;
    }

    public function findByIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface
    {
        return $this->nextAccessor->findByIdentifier($nodeAggregateIdentifier);
    }

    /**
     * @return iterable<int,NodeInterface>
     */
    public function findChildNodes(
        NodeInterface $parentNode,
        NodeTypeConstraints $nodeTypeConstraints = null,
        int $limit = null,
        int $offset = null
    ): iterable {
        return $this->nextAccessor->findChildNodes($parentNode, $nodeTypeConstraints, $limit, $offset);
    }

    /**
     * @return iterable<int,NodeInterface>
     */
    public function findReferencedNodes(NodeInterface $node, PropertyName $name = null): iterable
    {
        return $this->nextAccessor->findReferencedNodes($node, $name);
    }

    /**
     * @return iterable<int,NodeInterface>
     */
    public function findReferencingNodes(NodeInterface $node, PropertyName $name = null): iterable
    {
        return $this->nextAccessor->findReferencingNodes($node, $name);
    }

    public function findParentNode(NodeInterface $childNode): ?NodeInterface
    {
        return $this->nextAccessor->findParentNode($childNode);
    }

    public function findNodeByPath(NodePath $path, NodeInterface $startingNode): ?NodeInterface
    {
        return $this->nextAccessor->findNodeByPath($path, $startingNode);
    }

    public function findChildNodeConnectedThroughEdgeName(NodeInterface $parentNode, NodeName $edgeName): ?NodeInterface
    {
        return $this->nextAccessor->findChildNodeConnectedThroughEdgeName($parentNode, $edgeName);
    }

    public function findNodePath(NodeInterface $node): NodePath
    {
        return $this->nextAccessor->findNodePath($node);
    }

    /**
     * @param array<int,NodeInterface> $entryNodes
     */
    public function findSubtrees(
        array $entryNodes,
        int $maximumLevels,
        NodeTypeConstraints $nodeTypeConstraints
    ): SubtreeInterface {
        return $this->nextAccessor->findSubtrees($entryNodes, $maximumLevels, $nodeTypeConstraints);
    }

    /**
     * @param array<int,NodeInterface> $entryNodes
     */
    public function findDescendants(
        array $entryNodes,
        NodeTypeConstraints $nodeTypeConstraints,
        ?SearchTerm $searchTerm
    ): Nodes {
        return $this->nextAccessor->findDescendants($entryNodes, $nodeTypeConstraints, $searchTerm);
    }
}
