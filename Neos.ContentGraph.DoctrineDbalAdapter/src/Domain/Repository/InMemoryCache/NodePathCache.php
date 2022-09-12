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

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\InMemoryCache;

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

    public function contains(NodeAggregateId $nodeAggregateId): bool
    {
        if ($this->isEnabled === false) {
            return false;
        }
        $key = (string)$nodeAggregateId;
        return isset($this->nodePaths[$key]);
    }

    public function add(NodeAggregateId $nodeAggregateId, NodePath $nodePath): void
    {
        if ($this->isEnabled === false) {
            return;
        }
        $key = (string)$nodeAggregateId;
        $this->nodePaths[$key] = $nodePath;
    }

    public function get(NodeAggregateId $nodeAggregateId): ?NodePath
    {
        if ($this->isEnabled === false) {
            return null;
        }
        $key = (string)$nodeAggregateId;

        return $this->nodePaths[$key] ?? null;
    }
}
