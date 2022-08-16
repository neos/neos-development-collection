<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature;

use Neos\ContentRepository\Projection\ContentGraph\Node;

interface SubtreeInterface
{
    public function getLevel(): int;

    public function getNode(): ?Node;

    /**
     * @return SubtreeInterface[]
     */
    public function getChildren(): array;
}
