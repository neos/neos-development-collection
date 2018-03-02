<?php

namespace Neos\ContentRepository\Domain\Projection\Content\InMemoryCache;

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
use Neos\ContentRepository\Domain\ValueObject\NodeTypeConstraints;


/**
 * NOTE: we do NOT directly cache the Parent Node; but only the Parent Node Identifier; as then, the NodeByNodeIdentifierCache can be used properly - thus
 * it might increase the cache hit rate to split this apart.
 */
final class ParentNodeIdentifierByChildNodeIdentifierCache
{
    protected $parentNodeIdentifiers = [];

    public function add(NodeIdentifier $childNodeIdentifier, NodeIdentifier $parentNodeIdentifier): void
    {
        $key = (string)$childNodeIdentifier;
        $this->parentNodeIdentifiers[$key] = $parentNodeIdentifier;
    }

    public function contains(NodeIdentifier $childNodeIdentifier): bool
    {
        $key = (string)$childNodeIdentifier;
        return isset($this->parentNodeIdentifiers[$key]);
    }

    public function get(NodeIdentifier $childNodeIdentifier): ?NodeIdentifier
    {
        $key = (string)$childNodeIdentifier;
        return $this->parentNodeIdentifiers[$key] ?? null;
    }
}
