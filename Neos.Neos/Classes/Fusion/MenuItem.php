<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * A menu item
 */
final class MenuItem
{
    /**
     * @param array<int,MenuItem> $children
     */
    public function __construct(
        public readonly Node $node,
        public readonly ?MenuItemState $state = null,
        public readonly ?string $label = null,
        public readonly int $menuLevel = 1,
        public readonly array $children = [],
        public readonly ?string $uri = null
    ) {
    }

    /**
     * @return array<int,MenuItem>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return array<int,MenuItem>
     * @deprecated Use children instead
     */
    public function getSubItems(): array
    {
        return $this->getChildren();
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }
}
