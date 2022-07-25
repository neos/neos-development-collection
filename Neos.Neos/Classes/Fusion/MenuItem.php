<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;

/**
 * A menu item
 */
final class MenuItem
{
    protected NodeInterface $node;

    protected ?MenuItemState $state;

    protected ?string $label;

    protected int $menuLevel;

    /**
     * @var array<int,MenuItem>
     */
    protected array $children;

    /**
     * @param array<int,MenuItem> $children
     */
    public function __construct(
        NodeInterface $node,
        ?MenuItemState $state = null,
        ?string $label = null,
        int $menuLevel = 1,
        array $children = []
    ) {
        $this->node = $node;
        $this->state = $state;
        $this->label = $label;
        $this->menuLevel = $menuLevel;
        $this->children = $children;
    }

    public function getNode(): NodeInterface
    {
        return $this->node;
    }

    public function getState(): ?MenuItemState
    {
        return $this->state;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getMenuLevel(): int
    {
        return $this->menuLevel;
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
     * @deprecated Use getChildren instead
     */
    public function getSubItems(): array
    {
        return $this->getChildren();
    }
}
