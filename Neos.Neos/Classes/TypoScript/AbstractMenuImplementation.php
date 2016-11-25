<?php
namespace Neos\Neos\TypoScript;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use TYPO3\TypoScript\Exception as TypoScriptException;
use TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation;

/**
 * Base class for Menu and DimensionsMenu
 *
 * Main Options:
 *  - renderHiddenInIndex: if TRUE, hidden-in-index nodes will be shown in the menu. FALSE by default.
 */
abstract class AbstractMenuImplementation extends TemplateImplementation
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
            $this->renderHiddenInIndex = (boolean)$this->tsValue('renderHiddenInIndex');
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
            $typoScriptContext = $this->tsRuntime->getCurrentContext();
            $this->currentNode = isset($typoScriptContext['activeNode']) ? $typoScriptContext['activeNode'] : $typoScriptContext['documentNode'];
            $this->currentLevel = 1;
            $this->items = $this->buildItems();
        }

        return $this->items;
    }

    /**
     * Builds the array of menu items containing those items which match the
     * configuration set for this Menu object.
     *
     * Must be overridden in subclasses.
     *
     * @throws TypoScriptException
     * @return array An array of menu items and further information
     */
    abstract protected function buildItems();

    /**
     * Helper Method: Calculates the state of the given menu item (node) depending on the currentNode.
     *
     * This method needs to be called inside buildItems() in the subclasses.
     *
     * @param NodeInterface $node
     * @return string
     */
    protected function calculateItemState(NodeInterface $node = null)
    {
        if ($node === null) {
            return self::STATE_ABSENT;
        }

        if ($node === $this->currentNode) {
            return self::STATE_CURRENT;
        }

        if ($node !== $this->currentNode->getContext()->getCurrentSiteNode() && in_array($node, $this->getCurrentNodeRootline(), true)) {
            return self::STATE_ACTIVE;
        }

        return self::STATE_NORMAL;
    }

    /**
     * Return TRUE/FALSE if the node is currently hidden or not in the menu; taking the "renderHiddenInIndex" configuration
     * of the Menu TypoScript object into account.
     *
     * This method needs to be called inside buildItems() in the subclasses.
     *
     * @param NodeInterface $node
     * @return boolean
     */
    protected function isNodeHidden(NodeInterface $node)
    {
        return ($node->isVisible() === false || ($this->getRenderHiddenInIndex() === false && $node->isHiddenInIndex() === true) || $node->isAccessible() === false);
    }

    /**
     * Get the rootline from the current node up to the site node.
     *
     * @return array
     */
    protected function getCurrentNodeRootline()
    {
        if ($this->currentNodeRootline === null) {
            $nodeRootline = $this->currentNode->getContext()->getNodesOnPath($this->currentNode->getContext()->getCurrentSiteNode()->getPath(), $this->currentNode->getPath());
            $this->currentNodeRootline = array();

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
