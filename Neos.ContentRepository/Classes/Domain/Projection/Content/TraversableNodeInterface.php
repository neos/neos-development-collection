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

use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodePath;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeConstraints;

// This should be the only place where we import something from the other package
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\ContextParameters;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;

/**
 * A convenience wrapper.
 *
 * Immutable. Read-only. With traversal operations.
 *
 * !! Reference resolving happens HERE!
 */
interface TraversableNodeInterface extends NodeInterface
{
    /**
     * @return ContentSubgraphInterface
     */
    public function getSubgraph(): ContentSubgraphInterface;

    /**
     * @return ContextParameters
     */
    public function getContextParameters(): ContextParameters;

    /**
     * @return TraversableNodeInterface|null
     */
    public function findParentNode(): ?TraversableNodeInterface;

    /**
     * @return NodePath
     */
    public function findNodePath(): NodePath;

    /**
     * @param NodeName $nodeName
     * @return TraversableNodeInterface|null
     */
    public function findNamedChildNode(NodeName $nodeName): ?TraversableNodeInterface;

    /**
     * Returns all direct child nodes of this node.
     * If a node type is specified, only nodes of that type are returned.
     *
     * @param NodeTypeConstraints $nodeTypeConstraints If specified, only nodes with that node type are considered
     * @param int $limit An optional limit for the number of nodes to find. Added or removed nodes can still change the number nodes!
     * @param int $offset An optional offset for the query
     * @return TraversableNodeInterface[]|array<TraversableNodeInterface>| An array of nodes or an empty array if no child nodes matched
     * @api
     */
    public function findChildNodes(NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null);
}
