<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

/**
 * A menu item
 */
final readonly class MenuItem
{
    /**
     * @param array<int,MenuItem> $children
     */
    public function __construct(
        public Node $node,
        public ?MenuItemState $state = null,
        public ?string $label = null,
        public int $menuLevel = 1,
        public array $children = [],
        public ?string $uri = null
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
