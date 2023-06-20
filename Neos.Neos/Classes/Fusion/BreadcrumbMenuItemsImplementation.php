<?php
declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * A Fusion BreadcrumbMenuItems object
 */
class BreadcrumbMenuItemsImplementation extends MenuItemsImplementation
{
    /**
     * Internal cache for the includeSubItems value.
     *
     * @var bool|null
     */
    protected $includeSubItems;

    public function getIncludeSubItems()
    {
        if ($this->includeSubItems === null) {
            $this->includeSubItems = $this->fusionValue('includeSubItems');
        }

        return $this->includeSubItems;
    }

    /**
     * Prepare the menu item with state and label (and ignore sub items,
     * unless includeSubItems is set to true).
     *
     * @param NodeInterface $currentNode
     * @return array
     * @TODO Remove this with Neos 9.0 and leave out the sub-items in any case.
     * @see https://github.com/neos/neos-development-collection/issues/1438
     */
    protected function buildMenuItemRecursive(NodeInterface $currentNode)
    {
        if ($this->isNodeHidden($currentNode)) {
            $item = null;
        } else {
            $item = [
                'node' => $currentNode,
                'state' => $this->calculateItemState($currentNode),
                'label' => $currentNode->getLabel(),
                'menuLevel' => $this->currentLevel,
                'linkTitle' => $currentNode->getLabel()
            ];

            if ($this->getIncludeSubItems() && !$this->isOnLastLevelOfMenu($currentNode)) {
                $this->currentLevel++;
                $item['subItems'] = $this->buildMenuLevelRecursive($currentNode->getChildNodes($this->getFilter()));
                $this->currentLevel--;
            }
        }

        return $item;
    }
}
