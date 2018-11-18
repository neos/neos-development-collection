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

/**
 * NodeIdentifier -> Node cache
 *
 * also contains a *blacklist* of unknown NodeIdentifiers.
 */
final class NodeByNodeIdentifierCache
{
    protected $nodes = [];
    protected $nonExistingNodeIdentifiers = [];

    /**
     * basically like "contains"
     */
    public function knowsAbout(NodeIdentifier $nodeIdentifier): bool
    {
        $key = (string)$nodeIdentifier;
        return isset($this->nodes[$key]) || isset($this->nonExistingNodeIdentifiers[$key]);
    }

    public function add(NodeIdentifier $nodeIdentifier, NodeInterface $node): void
    {
        $key = (string)$nodeIdentifier;
        $this->nodes[$key] = $node;
    }

    public function rememberNonExistingNodeIdentifier(NodeIdentifier $nodeIdentifier): void
    {
        $key = (string)$nodeIdentifier;
        $this->nonExistingNodeIdentifiers[$key] = true;
    }

    public function get(NodeIdentifier $nodeIdentifier): ?NodeInterface
    {
        $key = (string)$nodeIdentifier;
        return $this->nodes[$key] ?? null;
    }
}
