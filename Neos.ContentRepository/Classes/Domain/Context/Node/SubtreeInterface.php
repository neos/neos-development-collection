<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 28.02.18
 * Time: 15:04
 */

namespace Neos\ContentRepository\Domain\Context\Node;


use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * Interface SubtreeInterface
 *
 * @package Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository
 */
interface SubtreeInterface
{
    public function getLevel(): int;

    public function getNode(): ?NodeInterface;


    /**
     * @return SubtreeInterface[]
     */
    public function getChildren(): array;
}
