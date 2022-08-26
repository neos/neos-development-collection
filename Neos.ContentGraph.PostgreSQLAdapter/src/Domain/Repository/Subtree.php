<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository;

use Neos\ContentRepository\Feature\SubtreeInterface;
use Neos\ContentRepository\Projection\ContentGraph\Node;

/**
 * @internal
 */
final class Subtree implements SubtreeInterface
{
    protected int $level;

    protected Node $node;

    /**
     * @var array<int,SubtreeInterface>
     */
    protected array $children = [];

    /**
     * @param array<int,SubtreeInterface> $children
     */
    public function __construct(int $level, Node $node, array $children = [])
    {
        $this->level = $level;
        $this->node = $node;
        $this->children = $children;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getNode(): Node
    {
        return $this->node;
    }

    /**
     * @return array<int,SubtreeInterface>
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
