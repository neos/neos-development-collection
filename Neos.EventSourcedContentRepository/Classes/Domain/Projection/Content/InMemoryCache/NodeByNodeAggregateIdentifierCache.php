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

use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * NodeAggregateIdentifier -> Node cache
 *
 * also contains a *blacklist* of unknown NodeAggregateIdentifiers.
 */
final class NodeByNodeAggregateIdentifierCache
{
    protected $nodes = [];
    protected $nonExistingNodeAggregateIdentifiers = [];

    /**
     * basically like "contains"
     */
    public function knowsAbout(NodeAggregateIdentifier $nodeAggregateIdentifier): bool
    {
        $key = (string)$nodeAggregateIdentifier;
        return isset($this->nodes[$key]) || isset($this->nonExistingNodeAggregateIdentifiers[$key]);
    }

    public function add(NodeAggregateIdentifier $nodeAggregateIdentifier, NodeInterface $node): void
    {
        $key = (string)$nodeAggregateIdentifier;
        $this->nodes[$key] = $node;
    }

    public function rememberNonExistingNodeAggregateIdentifier(NodeAggregateIdentifier $nodeAggregateIdentifier): void
    {
        $key = (string)$nodeAggregateIdentifier;
        $this->nonExistingNodeAggregateIdentifiers[$key] = true;
    }

    public function get(NodeAggregateIdentifier $nodeAggregateIdentifier): ?NodeInterface
    {
        $key = (string)$nodeAggregateIdentifier;
        return $this->nodes[$key] ?? null;
    }
}
