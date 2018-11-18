<?php

namespace Neos\EventSourcedContentRepository\Domain\Projection\Content\InMemoryCache;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;

/**
 * Parent Node Identifier + Node Name => Child Node
 */
final class NamedChildNodeByNodeIdentifierCache
{

    /**
     * first level: Parent Node Identifier
     * Second Level: Node Name
     * Value: Node
     * @var array
     */
    protected $nodes = [];

    public function add(NodeIdentifier $parentNodeIdentifier, NodeName $nodeName, NodeInterface $node): void
    {
        $this->nodes[(string)$parentNodeIdentifier][(string)$nodeName] = $node;
    }

    public function contains(NodeIdentifier $parentNodeIdentifier, NodeName $nodeName): bool
    {
        return isset($this->nodes[(string)$parentNodeIdentifier][(string)$nodeName]);
    }

    public function get(NodeIdentifier $parentNodeIdentifier, NodeName $nodeName): ?NodeInterface
    {
        return $this->nodes[(string)$parentNodeIdentifier][(string)$nodeName] ?? null;
    }
}
