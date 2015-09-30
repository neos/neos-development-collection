<?php
namespace TYPO3\TYPO3CR\Domain\Service;

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
use TYPO3\TYPO3CR\Domain\Model\NodeType;

/**
 * Provides generic methods to manage and work with Nodes
 *
 * @api
 */
interface NodeServiceInterface
{
    /**
     * Sets default node property values on the given node.
     *
     * @param NodeInterface $node
     * @return void
     */
    public function setDefaultValues(NodeInterface $node);

    /**
     * Creates missing child nodes for the given node.
     *
     * @param NodeInterface $node
     * @return void
     */
    public function createChildNodes(NodeInterface $node);

    /**
     * Removes all properties not configured in the current Node Type.
     *
     * @param NodeInterface $node
     * @return void
     */
    public function cleanUpProperties(NodeInterface $node);

    /**
     * Removes all auto created child nodes that existed in the previous nodeType.
     *
     * @param NodeInterface $node
     * @param NodeType $oldNodeType
     * @return void
     */
    public function cleanUpAutoCreatedChildNodes(NodeInterface $node, NodeType $oldNodeType);

    /**
     * @param NodeInterface $node
     * @param NodeType $nodeType
     * @return boolean
     */
    public function isNodeOfType(NodeInterface $node, NodeType $nodeType);

    /**
     * Checks if the given node path exists in any possible context already.
     *
     * @param string $nodePath
     * @return boolean
     */
    public function nodePathExistsInAnyContext($nodePath);

    /**
     * Checks if the given node path can be used for the given node.
     *
     * @param string $nodePath
     * @param NodeInterface $node
     * @return boolean
     */
    public function nodePathAvailableForNode($nodePath, NodeInterface $node);

    /**
     * Normalizes the given node path to a reference path and returns an absolute path.
     *
     * @param string $path The non-normalized path
     * @param string $referencePath a reference path in case the given path is relative.
     * @return string The normalized absolute path
     * @throws \InvalidArgumentException if the node path was invalid.
     */
    public function normalizePath($path, $referencePath = null);

    /**
     * Generates a possible node name, optionally based on a suggested "ideal" name.
     *
     * @param string $parentPath
     * @param string $idealNodeName Can be any string, doesn't need to be a valid node name.
     * @return string valid node name that is possible as child of the given $parentNode
     */
    public function generateUniqueNodeName($parentPath, $idealNodeName = null);
}
