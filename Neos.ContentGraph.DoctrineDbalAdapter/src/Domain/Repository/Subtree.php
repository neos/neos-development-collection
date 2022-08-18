<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

use Neos\ContentRepository\Feature\SubtreeInterface;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class Subtree implements SubtreeInterface
{
    protected int $level;

    protected ?Node $node;

    /**
     * @var array<int,SubtreeInterface>
     */
    protected array $children = [];

    public function __construct(int $level, Node $node = null)
    {
        $this->level = $level;
        $this->node = $node;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getNode(): ?Node
    {
        return $this->node;
    }

    public function add(SubtreeInterface $subtree): void
    {
        $this->children[] = $subtree;
    }

    /**
     * @return array<int,SubtreeInterface>
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
