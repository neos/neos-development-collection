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

use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Exception as FusionException;
use Neos\Neos\Domain\NodeLabel\NodeLabelRenderer;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * A Fusion Menu object
 */
class MenuItemsImplementation extends AbstractMenuItemsImplementation
{
    /**
     * Hard limit for the maximum number of levels supported by this menu
     */
    public const MAXIMUM_LEVELS_LIMIT = 100;

    /**
     * Internal cache for the startingPoint tsValue.
     */
    protected ?Node $startingPoint = null;

    /**
     * Internal cache for the lastLevel value.
     */
    protected ?int $lastLevel = null;

    /**
     * Internal cache for the maximumLevels tsValue.
     */
    protected ?int $maximumLevels = null;

    /**
     * Internal cache for the ancestors aggregate ids of the currentNode.
     */
    protected ?NodeAggregateIds $currentNodeRootlineAggregateIds = null;

    /**
     * Runtime cache for the node type criteria to be applied
     */
    protected ?NodeTypeCriteria $nodeTypeCriteria = null;

    #[Flow\Inject()]
    protected NodeLabelRenderer $nodeLabelRenderer;

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
     */
    protected function getEntryLevel(): int
    {
        return $this->fusionValue('entryLevel');
    }

    /**
     * NodeType filter for nodes displayed in menu
     */
    protected function getFilter(): string
    {
        $filter = $this->fusionValue('filter');
        if ($filter === null) {
            $filter = NodeTypeNameFactory::NAME_DOCUMENT;
        }

        return $filter;
    }

    /**
     * Maximum number of levels which should be rendered in this menu.
     */
    protected function getMaximumLevels(): int
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
     */
    protected function getLastLevel(): ?int
    {
        if ($this->lastLevel === null) {
            $this->lastLevel = $this->fusionValue('lastLevel');
            if ($this->lastLevel > self::MAXIMUM_LEVELS_LIMIT) {
                $this->lastLevel = self::MAXIMUM_LEVELS_LIMIT;
            }
        }

        return $this->lastLevel;
    }

    protected function getStartingPoint(): ?Node
    {
        if ($this->startingPoint === null) {
            $this->startingPoint = $this->fusionValue('startingPoint');
        }

        return $this->startingPoint;
    }

    /**
     * @return array<int,Node>|Nodes|null
     */
    protected function getItemCollection(): array|Nodes|null
    {
        return $this->fusionValue('itemCollection');
    }

    /**
     * Builds the array of menu items containing those items which match the
     * configuration set for this Menu object.
     *
     * @return array<int,MenuItem> An array of menu items and further information
     * @throws FusionException
     */
    protected function buildItems(): array
    {
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($this->getCurrentNode());
        if (!is_null($this->getItemCollection())) {
            $items = [];
            foreach ($this->getItemCollection() as $node) {
                if ($this->getMaximumLevels() > 0) {
                    $childSubtree = $subgraph->findSubtree(
                        $node->nodeAggregateId,
                        FindSubtreeFilter::create(nodeTypes: $this->getNodeTypeCriteria(), maximumLevels: $this->getMaximumLevels() - 1)
                    );
                    if ($childSubtree === null) {
                        continue;
                    }
                    $items[] = $this->buildMenuItemFromSubtree($childSubtree, 1);
                } else {
                    $items[] = $this->buildMenuItemFromNode($node);
                }
            }
            return $items;
        }

        $entryParentNodeAggregateId = $this->findMenuStartingPointAggregateId();
        if (!$entryParentNodeAggregateId) {
            return [];
        }

        $maximumLevels = $this->getMaximumLevels();
        $lastLevels = $this->getLastLevel();
        if ($lastLevels !== null) {
            $depthOfEntryParentNodeAggregateId = $subgraph->countAncestorNodes(
                $entryParentNodeAggregateId,
                CountAncestorNodesFilter::create(
                    NodeTypeCriteria::createWithAllowedNodeTypeNames(
                        NodeTypeNames::with(
                            NodeTypeNameFactory::forDocument()
                        )
                    )
                )
            );

            if ($lastLevels > 0) {
                $maxLevelsBasedOnLastLevel = max($lastLevels - $depthOfEntryParentNodeAggregateId, 0);
                $maximumLevels = min($maximumLevels, $maxLevelsBasedOnLastLevel);
            } elseif ($lastLevels < 0) {
                $currentNodeAncestorAggregateIds = $this->getCurrentNodeRootlineAggregateIds();
                $depthOfCurrentDocument = count(iterator_to_array($currentNodeAncestorAggregateIds)) - 1;
                $maxLevelsBasedOnLastLevel = max($depthOfCurrentDocument + $lastLevels - $depthOfEntryParentNodeAggregateId + 1, 0);
                $maximumLevels = min($maximumLevels, $maxLevelsBasedOnLastLevel);
            }
        }

        $childSubtree = $subgraph->findSubtree(
            $entryParentNodeAggregateId,
            FindSubtreeFilter::create(
                nodeTypes: $this->getNodeTypeCriteria(),
                maximumLevels: $maximumLevels
            )
        );
        if ($childSubtree === null) {
            return [];
        }
        return $this->buildMenuItemFromSubtree($childSubtree)->getChildren();
    }

    protected function buildMenuItemFromNode(Node $node): MenuItem
    {
        return new MenuItem(
            $node,
            $this->isCalculateItemStatesEnabled() ? $this->calculateItemState($node) : null,
            $this->nodeLabelRenderer->renderNodeLabel($node),
            0,
            [],
            $this->buildUri($node)
        );
    }

    protected function buildMenuItemFromSubtree(Subtree $subtree, int $startLevel = 0): MenuItem
    {
        $children = [];

        foreach ($subtree->children as $childSubtree) {
            $node = $childSubtree->node;
            if (!$this->isNodeHidden($node)) {
                $childNode = $this->buildMenuItemFromSubtree($childSubtree, $startLevel);
                $children[] = $childNode;
            }
        }

        $node = $subtree->node;

        return new MenuItem(
            $node,
            $this->isCalculateItemStatesEnabled() ? $this->calculateItemState($node) : null,
            $this->nodeLabelRenderer->renderNodeLabel($node),
            $subtree->level + $startLevel,
            $children,
            $this->buildUri($node)
        );
    }

    /**
     * Find the starting point for this menu. depending on given startingPoint
     * If startingPoint is given, this is taken as starting point for this menu level,
     * as a fallback the Fusion context variable node is used.
     *
     * If entryLevel is configured this will be taken into account as well.
     *
     * @return NodeAggregateId|null
     * @throws FusionException
     */
    protected function findMenuStartingPointAggregateId(): ?NodeAggregateId
    {
        $traversalStartingPoint = $this->getStartingPoint() ?: $this->getCurrentNode();

        if (!$traversalStartingPoint instanceof Node) {
            throw new FusionException(
                'You must either set a "startingPoint" for the menu or "node" must be set in the Fusion context.',
                1369596980
            );
        }

        if ($this->getEntryLevel() === 0) {
            return $traversalStartingPoint->nodeAggregateId;
        } elseif ($this->getEntryLevel() < 0) {
            $ancestorNodeAggregateIds = $this->getCurrentNodeRootlineAggregateIds();
            $ancestorNodeAggregateIdArray = array_values(iterator_to_array($ancestorNodeAggregateIds));
            return $ancestorNodeAggregateIdArray[$this->getEntryLevel() * -1] ?? null;
        } else {
            $ancestorNodeAggregateIds = $this->getCurrentNodeRootlineAggregateIds();
            $ancestorNodeAggregateIdArray = array_reverse(array_values(iterator_to_array($ancestorNodeAggregateIds)));
            return $ancestorNodeAggregateIdArray[$this->getEntryLevel() - 1] ?? null;
        }
    }

    protected function getNodeTypeCriteria(): NodeTypeCriteria
    {
        if (!$this->nodeTypeCriteria) {
            $this->nodeTypeCriteria = NodeTypeCriteria::fromFilterString($this->getFilter());
        }
        return $this->nodeTypeCriteria;
    }

    protected function getCurrentNodeRootlineAggregateIds(): NodeAggregateIds
    {
        if ($this->currentNodeRootlineAggregateIds instanceof NodeAggregateIds) {
            return $this->currentNodeRootlineAggregateIds;
        }
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($this->getCurrentNode());
        $currentNodeAncestors = $subgraph->findAncestorNodes(
            $this->currentNode->nodeAggregateId,
            FindAncestorNodesFilter::create(
                NodeTypeCriteria::createWithAllowedNodeTypeNames(
                    NodeTypeNames::with(
                        NodeTypeNameFactory::forDocument()
                    )
                )
            )
        );

        $this->currentNodeRootlineAggregateIds = NodeAggregateIds::create($this->currentNode->nodeAggregateId)
            ->merge(NodeAggregateIds::fromNodes($currentNodeAncestors));

        return $this->currentNodeRootlineAggregateIds;
    }

    protected function calculateItemState(Node $node): MenuItemState
    {
        if ($node->nodeAggregateId->equals($this->getCurrentNode()->nodeAggregateId)) {
            return MenuItemState::CURRENT;
        }
        if ($this->getCurrentNodeRootlineAggregateIds()->contain($node->nodeAggregateId)) {
            return MenuItemState::ACTIVE;
        }
        return MenuItemState::NORMAL;
    }
}
