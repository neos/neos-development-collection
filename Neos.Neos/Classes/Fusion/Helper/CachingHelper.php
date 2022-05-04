<?php
namespace Neos\Neos\Fusion\Helper;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Neos\Exception;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;

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

        if (!is_array($nodes) && ($nodes instanceof NodeInterface || $nodes instanceof TraversableNodeInterface)) {
            $nodes = [$nodes];
        }

        if (!is_array($nodes) && !$nodes instanceof \Traversable) {
            throw new Exception(sprintf('FlowQuery result, Array or Traversable expected by this helper, given: "%s".', gettype($nodes)), 1437169992);
        }

        $prefixedNodeIdentifiers = [];
        foreach ($nodes as $node) {
            if ($node instanceof NodeInterface) {
                /* @var $node NodeInterface */
                $prefixedNodeIdentifiers[] = $prefix . '_' . $this->renderWorkspaceTagForContextNode($node->getContext()->getWorkspace()->getName()) . '_' . $node->getIdentifier();
            } elseif ($node instanceof TraversableNodeInterface) {
                /* @var $node TraversableNodeInterface */
                $prefixedNodeIdentifiers[] = $prefix . '_' . $this->renderWorkspaceTagForContextNode((string)$node->getContentStreamIdentifier()) . '_' . $node->getNodeAggregateIdentifier();
            } else {
                throw new Exception(sprintf('One of the elements in array passed to this helper was not a Node, but of type: "%s".', gettype($node)), 1437169991);
            }
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
     * @throws Exception
     */
    public function nodeTag($nodes)
    {
        return $this->convertArrayOfNodesToArrayOfNodeIdentifiersWithPrefix($nodes, 'Node');
    }

    /**
     * Generate a `@cache` entry tag for a single node identifier. If a NodeInterface $contextNode is given the
     * entry tag will respect the workspace hash.
     *
     * @param string $identifier
     * @param NodeInterface|null $contextNode
     * @return string
     *
     */
    public function nodeTagForIdentifier(string $identifier, NodeInterface $contextNode = null): string
    {
        $workspaceTag = '';
        if ($contextNode instanceof NodeInterface) {
            $workspaceTag = $this->renderWorkspaceTagForContextNode($contextNode->getContext()->getWorkspace()->getName()) .'_';
        }

        return 'Node_' . $workspaceTag . $identifier;
    }

    /**
     * Generate an `@cache` entry tag for a node type
     * A cache entry with this tag will be flushed whenever a node
     * (for any variant) that is of the given node type(s)
     * (including inheritance) is updated.
     *
     * @param string|NodeType|string[]|NodeType[] $nodeType
     * @param NodeInterface|null $contextNode
     * @return string|string[]
     */
    public function nodeTypeTag($nodeType, $contextNode = null)
    {
        if (!is_array($nodeType) && !($nodeType instanceof \Traversable)) {
            return $this->getNodeTypeTagFor($nodeType, $contextNode);
        }

        $result = [];
        foreach ($nodeType as $singleNodeType) {
            $result[] = $this->getNodeTypeTagFor($singleNodeType, $contextNode);
        }

        return array_filter($result);
    }

    /**
     * @param string|NodeType $nodeType
     * @param NodeInterface $contextNode|null
     * @return string
     */
    protected function getNodeTypeTagFor($nodeType, $contextNode = null)
    {
        $nodeTypeName = '';
        $workspaceTag = '';

        if ($contextNode instanceof NodeInterface) {
            $workspaceTag = $this->renderWorkspaceTagForContextNode($contextNode->getContext()->getWorkspace()->getName()) .'_';
        }

        if (is_string($nodeType)) {
            $nodeTypeName .= $nodeType;
        }
        if ($nodeType instanceof NodeType) {
            $nodeTypeName .= $nodeType->getName();
        }

        if ($nodeTypeName === '') {
            return '';
        }

        return 'NodeType_' . $workspaceTag . $nodeTypeName;
    }

    /**
     * Generate a `@cache` entry tag for descendants of a node, an array of nodes or a FlowQuery result
     * A cache entry with this tag will be flushed whenever a node
     * (for any variant) that is a descendant (child on any level) of one of
     * the given nodes is updated.
     *
     * @param mixed $nodes (A single Node or array or \Traversable of Nodes)
     * @return array
     * @throws Exception
     */
    public function descendantOfTag($nodes)
    {
        return $this->convertArrayOfNodesToArrayOfNodeIdentifiersWithPrefix($nodes, 'DescendantOf');
    }

    /**
     * @param string $workspaceName
     * @return string
     */
    public function renderWorkspaceTagForContextNode(string $workspaceName)
    {
        return '%' . md5($workspaceName) . '%';
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
