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

use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodePath;

/**
 * Node Identifier -> Node Path cache
 */
final class NodePathCache
{
    protected $nodePaths = [];

    public function contains(NodeIdentifier $nodeIdentifier): bool
    {
        $key = (string)$nodeIdentifier;
        return isset($this->nodePaths[$key]);
    }

    public function add(NodeIdentifier $nodeIdentifier, NodePath $nodePath): void
    {
        $key = (string)$nodeIdentifier;
        $this->nodePaths[$key] = $nodePath;
    }

    public function get(NodeIdentifier $nodeIdentifier): ?NodePath
    {
        $key = (string)$nodeIdentifier;

        return $this->nodePaths[$key] ?? null;
    }
}
