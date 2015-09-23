<?php
namespace TYPO3\TYPO3CR\Domain\Service\Cache;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * A first level cache for the NodeDataRepository. It is used to keep
 * Nodes in memory (indexed by identifier and path) and allows to fetch
 * them by path or identifier as single node.
 *
 * The caching of multiple nodes below a certain path is possible as well,
 * using the *ChildNodesByPathAndNodeTypeFilter() methods.
 */
class FirstLevelNodeCache
{
    /**
     * @var array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
     */
    protected $nodesByPath = array();

    /**
     * @var array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
     */
    protected $nodesByIdentifier = array();

    /**
     * @var array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
     */
    protected $childNodesByPathAndNodeTypeFilter = array();

    /**
     * If the cache contains a node for the given path, it is returned.
     *
     * Otherwise FALSE is returned.
     *
     * @param string $path
     * @return NodeInterface
     */
    public function getByPath($path)
    {
        if (isset($this->nodesByPath[$path])) {
            return $this->nodesByPath[$path];
        }

        return false;
    }

    /**
     * Adds the given node to the cache for the given path. The node
     * will also be added under it's identifier.
     *
     * @param string $path
     * @param NodeInterface $node
     * @return void
     */
    public function setByPath($path, NodeInterface $node = null)
    {
        $this->nodesByPath[$path] = $node;
        if ($node !== null) {
            $this->nodesByIdentifier[$node->getIdentifier()] = $node;
        }
    }

    /**
     * If the cache contains a node with the given identifier, it is returned.
     *
     * Otherwise FALSE is returned.
     *
     * @param string $identifier
     * @return NodeInterface|boolean
     */
    public function getByIdentifier($identifier)
    {
        if (isset($this->nodesByIdentifier[$identifier])) {
            return $this->nodesByIdentifier[$identifier];
        }

        return false;
    }

    /**
     * Adds the given node to the cache for the given identifier. The node
     * will also be added with is's path.
     *
     * @param string $identifier
     * @param NodeInterface $node
     * @return void
     */
    public function setByIdentifier($identifier, NodeInterface $node = null)
    {
        $this->nodesByIdentifier[$identifier] = $node;
        if ($node !== null) {
            $this->nodesByPath[$node->getPath()] = $node;
        }
    }

    /**
     * Returns the cached child nodes for the given path and node type filter.
     *
     * @param string $path
     * @param string $nodeTypeFilter
     * @return boolean
     */
    public function getChildNodesByPathAndNodeTypeFilter($path, $nodeTypeFilter)
    {
        if (isset($this->childNodesByPathAndNodeTypeFilter[$path][$nodeTypeFilter])) {
            return $this->childNodesByPathAndNodeTypeFilter[$path][$nodeTypeFilter];
        }

        return false;
    }

    /**
     * Sets the given nodes as child nodes for the given path and node type filter.
     * The nodes will each be added with their path and identifier as well.
     *
     * @param string $path
     * @param string $nodeTypeFilter
     * @param array $nodes
     * @return void
     */
    public function setChildNodesByPathAndNodeTypeFilter($path, $nodeTypeFilter, array $nodes)
    {
        if (!isset($this->childNodesByPathAndNodeTypeFilter[$path])) {
            $this->childNodesByPathAndNodeTypeFilter[$path] = array();
        }

        foreach ($nodes as $node) {
            /** @var NodeInterface $node */
            $this->nodesByPath[$node->getPath()] = $node;
            $this->nodesByIdentifier[$node->getIdentifier()] = $node;
        }

        $this->childNodesByPathAndNodeTypeFilter[$path][$nodeTypeFilter] = $nodes;
    }

    /**
     * Flush the cache.
     *
     * @return void
     */
    public function flush()
    {
        $this->nodesByPath = array();
        $this->nodesByIdentifier = array();
        $this->childNodesByPathAndNodeTypeFilter = array();
    }
}
