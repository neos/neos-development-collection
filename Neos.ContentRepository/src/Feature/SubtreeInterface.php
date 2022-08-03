<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature;

use Neos\ContentRepository\Projection\Content\NodeInterface;

interface SubtreeInterface
{
    public function getLevel(): int;

    public function getNode(): ?NodeInterface;

    /**
     * @return SubtreeInterface[]
     */
    public function getChildren(): array;
}
