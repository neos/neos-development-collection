<?php
namespace Neos\EventSourcedNeosAdjustments\Fusion;

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;

/**
 * A menu item
 */
final class MenuItem
{
    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @var MenuItemState
     */
    protected $state;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var int
     */
    protected $menuLevel;

    /**
     * @var array
     */
    protected $children;


    /**
     * @param NodeInterface $node
     * @param MenuItemState|null $state
     * @param string|null $label
     * @param int $menuLevel
     * @param array $children
     */
    public function __construct(NodeInterface $node, MenuItemState $state = null, string $label = null, int $menuLevel = 1, array $children = [])
    {
        $this->node = $node;
        $this->state = $state;
        $this->label = $label;
        $this->menuLevel = $menuLevel;
        $this->children = $children;
    }


    /**
     * @return NodeInterface
     */
    public function getNode(): NodeInterface
    {
        return $this->node;
    }

    /**
     * @return MenuItemState
     */
    public function getState(): MenuItemState
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @return int
     */
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
