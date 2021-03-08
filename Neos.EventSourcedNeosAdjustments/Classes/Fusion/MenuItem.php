<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Fusion;

use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;

/**
 * A menu item
 */
final class MenuItem
{
    protected NodeBasedReadModelInterface $node;

    protected ?MenuItemState $state;

    protected ?string $label;

    protected int $menuLevel;

    protected array $children;

    public function __construct(NodeBasedReadModelInterface $node, MenuItemState $state = null, string $label = null, int $menuLevel = 1, array $children = [])
    {
        $this->node = $node;
        $this->state = $state;
        $this->label = $label;
        $this->menuLevel = $menuLevel;
        $this->children = $children;
    }

    public function getNode(): NodeBasedReadModelInterface
    {
        return $this->node;
    }

    public function getState(): MenuItemState
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
     * @return array|MenuItem[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return array|MenuItem[]
     * @deprecated Use getChildren instead
     */
    public function getSubItems(): array
    {
        return $this->getChildren();
    }
}
