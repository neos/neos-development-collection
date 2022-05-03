<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 28.02.18
 * Time: 15:04
 */

namespace Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph;

use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;

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
