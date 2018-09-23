<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 28.02.18
 * Time: 15:04
 */

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;


use Neos\EventSourcedContentRepository\Domain\Context\Node\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;


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

    public function __construct(int $level, NodeInterface $node = null) {
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
