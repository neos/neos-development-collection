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

namespace Neos\ContentRepositoryRegistry\SubgraphCachingInMemory\InMemoryCache;

use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * Node ID -> Node Path cache
 *
 * @internal
 */
final class NodePathCache
{
    /**
     * @var array<string,AbsoluteNodePath>
     */
    private array $nodePaths = [];

    public function add(NodeAggregateId $nodeAggregateId, AbsoluteNodePath $nodePath): void
    {
        $this->nodePaths[$nodeAggregateId->value] = $nodePath;
    }

    public function get(NodeAggregateId $nodeAggregateId): ?AbsoluteNodePath
    {
        return $this->nodePaths[$nodeAggregateId->value] ?? null;
    }
}
