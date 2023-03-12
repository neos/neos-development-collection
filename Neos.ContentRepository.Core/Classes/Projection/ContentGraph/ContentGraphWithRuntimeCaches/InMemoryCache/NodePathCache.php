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

namespace Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphWithRuntimeCaches\InMemoryCache;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;

/**
 * Node ID -> Node Path cache
 *
 * @internal
 */
final class NodePathCache
{
    /**
     * @var array<string,NodePath>
     */
    protected array $nodePaths = [];

    protected bool $isEnabled;

    public function __construct(bool $isEnabled)
    {
        $this->isEnabled = $isEnabled;
    }

    public function add(NodeAggregateId $nodeAggregateId, NodePath $nodePath): void
    {
        if ($this->isEnabled === false) {
            return;
        }
        $this->nodePaths[$nodeAggregateId->value] = $nodePath;
    }

    public function get(NodeAggregateId $nodeAggregateId): ?NodePath
    {
        if ($this->isEnabled === false) {
            return null;
        }
        return $this->nodePaths[$nodeAggregateId->value] ?? null;
    }
}
