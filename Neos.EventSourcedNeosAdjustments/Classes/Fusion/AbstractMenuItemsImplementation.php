<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Fusion;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
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
    const STATE_NORMAL = 'normal';
    const STATE_CURRENT = 'current';
    const STATE_ACTIVE = 'active';
    const STATE_ABSENT = 'absent';

    /**
     * An internal cache for the built menu items array.
     *
     * @var array
     */
    protected $items;

    /**
     * @var NodeInterface
     */
    protected $currentNode;

    /**
     * Internal cache for the currentLevel tsValue.
     *
     * @var integer
     */
    protected $currentLevel;

    /**
     * Internal cache for the renderHiddenInIndex property.
     *
     * @var boolean
     */
    protected $renderHiddenInIndex;

    /**
     * Rootline of all nodes from the current node to the site root node, keys are depth of nodes.
     *
     * @var array<NodeInterface>
     */
    protected $currentNodeRootline;

    /**
     * Should nodes that have "hiddenInIndex" set still be visible in this menu.
     *
     * @return boolean
     */
    public function getRenderHiddenInIndex()
    {
        if ($this->renderHiddenInIndex === null) {
            $this->renderHiddenInIndex = (boolean)$this->fusionValue('renderHiddenInIndex');
        }

        return $this->renderHiddenInIndex;
    }

    /**
     * Main API method which sends the to-be-rendered data to Fluid
     *
     * @return array
     */
    public function getItems()
    {
        if ($this->items === null) {
            $fusionContext = $this->runtime->getCurrentContext();
            $this->currentNode = isset($fusionContext['activeNode']) ? $fusionContext['activeNode'] : $fusionContext['documentNode'];
            $this->currentLevel = 1;
            $this->items = $this->buildItems();
        }

        return $this->items;
    }

    /**
     * Returns the items as result of the fusion object.
     *
     * @return array
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
     * @return array An array of menu items and further information
     */
    abstract protected function buildItems();

    /**
     * Return TRUE/FALSE if the node is currently hidden or not in the menu; taking the "renderHiddenInIndex" configuration
     * of the Menu Fusion object into account.
     *
     * This method needs to be called inside buildItems() in the subclasses.
     *
     * @param NodeInterface $node
     * @return boolean
     */
    protected function isNodeHidden(NodeInterface $node)
    {
        if ($this->getRenderHiddenInIndex() === true) {
            // Please show hiddenInIndex nodes
            // -> node is *never* hidden!
            return false;
        }

        // Node is hidden depending on the _hiddenInIndex property
        return $node->getProperty('_hiddenInIndex');
    }

    /**
     * Get the rootline from the current node up to the site node.
     *
     * @return array
     */
    protected function getCurrentNodeRootline()
    {
        if ($this->currentNodeRootline === null) {
            /** @todo replace this */
            $nodeRootline = $this->currentNode->getContext()->getNodesOnPath($this->runtime->getCurrentContext()['site']->getPath(), $this->currentNode->getPath());
            $this->currentNodeRootline = [];

            foreach ($nodeRootline as $rootlineElement) {
                $this->currentNodeRootline[$this->getNodeLevelInSite($rootlineElement)] = $rootlineElement;
            }
        }

        return $this->currentNodeRootline;
    }

    /**
     * Node Level relative to site root node.
     * 0 = Site root node
     *
     * @param NodeInterface $node
     * @return integer
     */
    protected function getNodeLevelInSite(NodeInterface $node)
    {
        $siteNode = $this->currentNode->getContext()->getCurrentSiteNode();
        return $node->getDepth() - $siteNode->getDepth();
    }
}
