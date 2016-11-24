<?php
namespace TYPO3\Neos\TypoScript\Helper;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use TYPO3\Neos\Exception;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;

/**
 * Caching helper to make cache tag generation easier.
 */
class CachingHelper implements ProtectedContextAwareInterface
{
    /**
     * Render a caching configuration for array of Nodes
     *
     * @param mixed $nodes
     * @param string $prefix
     * @return array
     * @throws Exception
     */
    protected function convertArrayOfNodesToArrayOfNodeIdentifiersWithPrefix($nodes, $prefix)
    {
        if ($nodes === null) {
            $nodes = [];
        }

        if ($nodes instanceof NodeInterface) {
            $nodes = [$nodes];
        }

        if (!is_array($nodes) && !$nodes instanceof \Traversable) {
            throw new Exception(sprintf('FlowQuery result, Array or Traversable expected by this helper, given: "%s".', gettype($nodes)), 1437169992);
        }

        $prefixedNodeIdentifiers = array();
        foreach ($nodes as $node) {
            if (!$node instanceof NodeInterface) {
                throw new Exception(sprintf('One of the elements in array passed to this helper was not a Node, but of type: "%s".', gettype($node)), 1437169991);
            }
            $prefixedNodeIdentifiers[] = $prefix . '_' . $node->getIdentifier();
        }
        return $prefixedNodeIdentifiers;
    }

    /**
     * Generate a `@cache` entry tag for a single node, array of nodes or a FlowQuery result
     * A cache entry with this tag will be flushed whenever one of the
     * given nodes (for any variant) is updated.
     *
     * @param mixed $nodes (A single Node or array or \Traversable of Nodes)
     * @return array
     */
    public function nodeTag($nodes)
    {
        return $this->convertArrayOfNodesToArrayOfNodeIdentifiersWithPrefix($nodes, 'Node');
    }

    /**
     * Generate an `@cache` entry tag for a node type
     * A cache entry with this tag will be flushed whenever a node
     * (for any variant) that is of the given node type (including inheritance)
     * is updated.
     *
     * @param NodeType $nodeType
     * @return string
     */
    public function nodeTypeTag(NodeType $nodeType)
    {
        return 'NodeType_' . $nodeType->getName();
    }

    /**
     * Generate a `@cache` entry tag for descendants of a node, an array of nodes or a FlowQuery result
     * A cache entry with this tag will be flushed whenever a node
     * (for any variant) that is a descendant (child on any level) of one of
     * the given nodes is updated.
     *
     * @param mixed $nodes (A single Node or array or \Traversable of Nodes)
     * @return array
     */
    public function descendantOfTag($nodes)
    {
        return $this->convertArrayOfNodesToArrayOfNodeIdentifiersWithPrefix($nodes, 'DescendantOf');
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
