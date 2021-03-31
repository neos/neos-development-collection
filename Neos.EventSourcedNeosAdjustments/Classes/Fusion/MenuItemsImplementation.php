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

use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Intermediary\Domain\ReadModelFactory;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeTreeTraversalHelper;
use Neos\Fusion\Exception as FusionException;
use Neos\Flow\Annotations as Flow;

/**
 * A Fusion Menu object
 */
class MenuItemsImplementation extends AbstractMenuItemsImplementation
{
    /**
     * @Flow\Inject
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

    /**
     * Hard limit for the maximum number of levels supported by this menu
     */
    const MAXIMUM_LEVELS_LIMIT = 100;

    /**
     * Internal cache for the startingPoint tsValue.
     *
     * @var NodeInterface
     */
    protected $startingPoint;

    /**
     * Internal cache for the lastLevel value.
     *
     * @var integer
     */
    protected $lastLevel;

    /**
     * Internal cache for the maximumLevels tsValue.
     *
     * @var integer
     */
    protected $maximumLevels;

    /**
     * Runtime cache for the node type constraints to be applied
     *
     * @var NodeTypeConstraints
     */
    protected $nodeTypeConstraints;

    /**
     * @Flow\Inject
     * @var ReadModelFactory
     */
    protected $readModelFactory;

    /**
     * The last navigation level which should be rendered.
     *
     * 1 = first level of the site
     * 2 = second level of the site
     * ...
     * 0  = same level as the current page
     * -1 = one level above the current page
     * -2 = two levels above the current page
     * ...
     *
     * @return integer
     */
    public function getEntryLevel()
    {
        return $this->fusionValue('entryLevel');
    }

    /**
     * NodeType filter for nodes displayed in menu
     *
     * @return string
     */
    public function getFilter()
    {
        $filter = $this->fusionValue('filter');
        if ($filter === null) {
            $filter = 'Neos.Neos:Document';
        }

        return $filter;
    }

    /**
     * Maximum number of levels which should be rendered in this menu.
     *
     * @return integer
     */
    public function getMaximumLevels()
    {
        if ($this->maximumLevels === null) {
            $this->maximumLevels = $this->fusionValue('maximumLevels');
            if ($this->maximumLevels > self::MAXIMUM_LEVELS_LIMIT) {
                $this->maximumLevels = self::MAXIMUM_LEVELS_LIMIT;
            }
        }

        return $this->maximumLevels;
    }

    /**
     * Return evaluated lastLevel value.
     *
     * @return integer
     */
    public function getLastLevel(): int
    {
        if ($this->lastLevel === null) {
            $this->lastLevel = $this->fusionValue('lastLevel');
            if ($this->lastLevel > self::MAXIMUM_LEVELS_LIMIT) {
                $this->lastLevel = self::MAXIMUM_LEVELS_LIMIT;
            }
        }

        return $this->lastLevel;
    }

    /**
     * @return NodeInterface
     */
    public function getStartingPoint()
    {
        if ($this->startingPoint === null) {
            $this->startingPoint = $this->fusionValue('startingPoint');
        }

        return $this->startingPoint;
    }

    /**
     * @return array|NodeInterface[]
     */
    public function getItemCollection()
    {
        return $this->fusionValue('itemCollection');
    }

    /**
     * Builds the array of menu items containing those items which match the
     * configuration set for this Menu object.
     *
     * @throws FusionException
     * @return array An array of menu items and further information
     */
    protected function buildItems()
    {
        if (!is_null($this->getItemCollection())) {
            $menuLevelCollection = $this->getItemCollection();
            $entryNodeAggregateIdentifiers = array_map(function (NodeInterface $node) {
                return $node->getNodeAggregateIdentifier();
            }, $menuLevelCollection);

            $childSubtree = $this->getSubgraph()->findSubtrees($entryNodeAggregateIdentifiers, $this->getMaximumLevels(), $this->getNodeTypeConstraints());
        } else {
            $entryParentNode = $this->findMenuStartingPoint();
            if (!$entryParentNode) {
                return [];
            }

            $entryNodeAggregateIdentifiers = [$entryParentNode->getNodeAggregateIdentifier()];

            $childSubtree = $this->getSubgraph()->findSubtrees($entryNodeAggregateIdentifiers, $this->getMaximumLevels(), $this->getNodeTypeConstraints());
            $childSubtree = $childSubtree->getChildren()[0];
        }

        $items = [];
        foreach ($childSubtree->getChildren() as $childSubtree) {
            if (!$this->isNodeHidden($childSubtree->getNode())) {
                $items[] = $this->traverseChildren($childSubtree);
            }
        }

        return $items;
    }

    /**
     * @param SubtreeInterface $subtree
     * @return MenuItem
     */
    protected function traverseChildren(SubtreeInterface $subtree): MenuItem
    {
        $children = [];

        foreach ($subtree->getChildren() as $childSubtree) {
            if (!$this->isNodeHidden($childSubtree->getNode())) {
                $children[] = $this->traverseChildren($childSubtree);
            }
        }

        $traversableNode = $this->readModelFactory->createReadModel($subtree->getNode(), $this->getSubgraph());
        return new MenuItem($traversableNode, MenuItemState::normal(), $subtree->getNode()->getLabel(), $subtree->getLevel(), $children);
    }


    /**
     * Find the starting point for this menu. depending on given startingPoint
     * If startingPoint is given, this is taken as starting point for this menu level,
     * as a fallback the Fusion context variable node is used.
     *
     * If entryLevel is configured this will be taken into account as well.
     *
     * @return NodeInterface|null
     * @throws FusionException
     */
    protected function findMenuStartingPoint(): ?NodeInterface
    {
        $fusionContext = $this->runtime->getCurrentContext();
        $startingPoint = $this->getStartingPoint();

        if (!isset($fusionContext['node']) && !$startingPoint) {
            throw new FusionException('You must either set a "startingPoint" for the menu or "node" must be set in the Fusion context.', 1369596980);
        }
        /** @var NodeInterface $traversalStartingPoint */
        $traversalStartingPoint = $startingPoint ?: $fusionContext['node'];
        if ($this->getEntryLevel() === 0) {
            $entryParentNode = $traversalStartingPoint;
        } elseif ($this->getEntryLevel() < 0) {
            $remainingIterations = abs($this->getEntryLevel());
            $entryParentNode = null;
            NodeTreeTraversalHelper::traverseUpUntilCondition($this->getSubgraph(), $traversalStartingPoint, function (NodeInterface $node) use (&$remainingIterations, &$entryParentNode) {
                if (!$this->getNodeTypeConstraints()->matches($node->getNodeTypeName())) {
                    return false;
                }

                if ($remainingIterations > 0) {
                    $remainingIterations--;

                    return true;
                } else {
                    $entryParentNode = $node;

                    return false;
                }
            });
        } else {
            $traversedHierarchy = [];
            $constraints = $this->getNodeTypeConstraints()->withExplicitlyDisallowedNodeType(NodeTypeName::fromString('Neos.Neos:Sites'));
            NodeTreeTraversalHelper::traverseUpUntilCondition($this->getSubgraph(), $traversalStartingPoint, function (NodeInterface $traversedNode) use (&$traversedHierarchy, $constraints) {
                if (!$constraints->matches($traversedNode->getNodeTypeName())) {
                    return false;
                }
                $traversedHierarchy[] = $traversedNode;
                return true;
            });
            $traversedHierarchy = array_reverse($traversedHierarchy);

            $entryParentNode = $traversedHierarchy[$this->getEntryLevel() - 1] ?? null;
        }

        return $entryParentNode;
    }

    /**
     * @return ContentSubgraphInterface
     */
    protected function getSubgraph(): ContentSubgraphInterface
    {
        return $this->fusionValue('subgraph');
    }

    /**
     * @return NodeTypeConstraints
     */
    protected function getNodeTypeConstraints(): NodeTypeConstraints
    {
        if (!$this->nodeTypeConstraints) {
            $this->nodeTypeConstraints = $this->nodeTypeConstraintFactory->parseFilterString($this->getFilter());
        }

        return $this->nodeTypeConstraints;
    }
}
