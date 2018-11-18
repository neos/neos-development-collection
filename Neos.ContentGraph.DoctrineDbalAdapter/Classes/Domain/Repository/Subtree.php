<?php

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

use Neos\EventSourcedContentRepository\Domain\Context\Node\SubtreeInterface;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * Class Subtree
 */
class Subtree implements SubtreeInterface
{

    /**
     * @var int
     */
    protected $level;

    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @var SubtreeInterface[]
     */
    protected $children = [];

    public function __construct(int $level, NodeInterface $node = null)
    {
        $this->level = $level;
        $this->node = $node;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }


    public function getNode(): ?NodeInterface
    {
        return $this->node;
    }

    public function add(SubtreeInterface $subtree)
    {
        $this->children[] = $subtree;
    }

    /**
     * @return SubtreeInterface[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
