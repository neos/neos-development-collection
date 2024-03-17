<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Exception as FusionException;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * Base class for Menu and DimensionsMenu
 *
 * Main Options:
 *  - renderHiddenInIndex: if TRUE, hidden-in-index nodes will be shown in the menu. FALSE by default.
 */
abstract class AbstractMenuItemsImplementation extends AbstractFusionObject
{
    public const STATE_NORMAL = 'normal';
    public const STATE_CURRENT = 'current';
    public const STATE_ACTIVE = 'active';
    public const STATE_ABSENT = 'absent';

    /**
     * An internal cache for the built menu items array.
     *
     * @var array<int,MenuItem>
     */
    protected $items;

    /**
     * @var Node
     */
    protected $currentNode;

    /**
     * Internal cache for the renderHiddenInIndex property.
     *
     * @var boolean
     */
    protected $renderHiddenInIndex;

    /**
     * Internal cache for the calculateItemStates property.
     *
     * @var boolean
     */
    protected $calculateItemStates;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * Whether the active/current state of menu items is calculated on the server side.
     * This has an effect on performance and caching
     */
    public function isCalculateItemStatesEnabled(): bool
    {
        if ($this->calculateItemStates === null) {
            $this->calculateItemStates = (bool)$this->fusionValue('calculateItemStates');
        }

        return $this->calculateItemStates;
    }

    /**
     * Should nodes that have "hiddenInIndex" set still be visible in this menu.
     *
     * @return boolean
     */
    public function getRenderHiddenInIndex()
    {
        if ($this->renderHiddenInIndex === null) {
            $this->renderHiddenInIndex = (bool)$this->fusionValue('renderHiddenInIndex');
        }

        return $this->renderHiddenInIndex;
    }

    /**
     * The node the menu is built from, all relative specifications will
     * use this as a base
     */
    public function getCurrentNode(): Node
    {
        if ($this->currentNode === null) {
            $this->currentNode = $this->fusionValue('node');
        }

        return $this->currentNode;
    }

    /**
     * Main API method which sends the to-be-rendered data to Fluid
     *
     * @return array<int,MenuItem>
     */
    public function getItems(): array
    {
        if ($this->items === null) {
            $this->items = $this->buildItems();
        }

        return $this->items;
    }

    /**
     * Returns the items as result of the fusion object.
     *
     * @return array<int,MenuItem>
     */
    public function evaluate()
    {
        return $this->getItems();
    }

    /**
     * Builds the array of menu items containing those items which match the
     * configuration set for this Menu object.
     *
     * Must be overridden in subclasses.
     *
     * @throws FusionException
     * @return array<int,mixed> An array of menu items and further information
     */
    abstract protected function buildItems(): array;

    /**
     * Return TRUE/FALSE if the node is currently hidden or not in the menu;
     * taking the "renderHiddenInIndex" configuration of the Menu Fusion object into account.
     *
     * This method needs to be called inside buildItems() in the subclasses.
     *
     * @param Node $node
     * @return boolean
     */
    protected function isNodeHidden(Node $node)
    {
        if ($this->getRenderHiddenInIndex() === true) {
            // Please show hiddenInIndex nodes
            // -> node is *never* hidden!
            return false;
        }

        // Node is hidden depending on the _hiddenInIndex property
        return $node->getProperty('_hiddenInIndex');
    }

    protected function buildUri(Node $node): string
    {
        $this->runtime->pushContextArray([
            'itemNode' => $node,
            'documentNode' => $node,
        ]);
        $uri = $this->runtime->render($this->path . '/itemUriRenderer');
        $this->runtime->popContext();
        return $uri;
    }
}
