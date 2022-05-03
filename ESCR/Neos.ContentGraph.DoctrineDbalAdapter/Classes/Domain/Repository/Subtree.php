<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
class Subtree implements SubtreeInterface
{
    protected int $level;

    protected ?NodeInterface $node;

    /**
     * @var array<int,SubtreeInterface>
     */
    protected array $children = [];

    public function __construct(int $level, NodeInterface $node = null)
    {
        $this->level = $level;
        $this->node = $node;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getNode(): ?NodeInterface
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
