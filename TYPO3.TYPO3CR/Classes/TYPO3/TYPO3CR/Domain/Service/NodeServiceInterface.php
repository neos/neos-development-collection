<?php
namespace Neos\ContentRepository\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;

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
