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

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\Neos\Exception;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;

/**
 * Caching helper to make cache tag generation easier.
 */
class CachingHelper implements ProtectedContextAwareInterface
{
    /**
     * Render a caching configuration for array of Nodes
     *
     * @return array<int,string>
     * @throws Exception
     */
    protected function convertArrayOfNodesToArrayOfNodeIdentifiersWithPrefix(mixed $nodes, string $prefix): array
    {
        if ($nodes === null) {
            $nodes = [];
        }

        if (!is_array($nodes) && ($nodes instanceof NodeInterface)) {
            $nodes = [$nodes];
        }

        if (!is_array($nodes) && !$nodes instanceof \Traversable) {
            throw new Exception(sprintf(
                'FlowQuery result, Array or Traversable expected by this helper, given: "%s".',
                gettype($nodes)
            ), 1437169992);
        }

        $prefixedNodeIdentifiers = [];
        foreach ($nodes as $node) {
            if ($node instanceof NodeInterface) {
                $prefixedNodeIdentifiers[] = $prefix . '_'
                    . $this->renderContentStreamIdentifierTag($node->getContentStreamIdentifier())
                    . '_' . $node->getNodeAggregateIdentifier();
            } else {
                throw new Exception(sprintf(
                    'One of the elements in array passed to this helper was not a Node, but of type: "%s".',
                    gettype($node)
                ), 1437169991);
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
     * @return array<int,string>
     * @throws Exception
     */
    public function nodeTag(mixed $nodes): array
    {
        return $this->convertArrayOfNodesToArrayOfNodeIdentifiersWithPrefix($nodes, 'Node');
    }

    /**
     * Generate a `@cache` entry tag for a single node identifier. If a NodeInterface $contextNode is given the
     * entry tag will respect the workspace hash.
     *
     * @param string $identifier
     * @param ?NodeInterface $contextNode
     * @return string
     */
    public function nodeTagForIdentifier(string $identifier, NodeInterface $contextNode = null): string
    {
        $contentStreamTag = '';
        if ($contextNode instanceof NodeInterface) {
            $contentStreamTag = $this->renderContentStreamIdentifierTag(
                    $contextNode->getContentStreamIdentifier()
                ) .'_';
        }

        return 'Node_' . $contentStreamTag . $identifier;
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
    public function nodeTypeTag($nodeType, NodeInterface $contextNode = null)
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
     * @param ?NodeInterface $contextNode
     * @return string
     */
    protected function getNodeTypeTagFor($nodeType, NodeInterface $contextNode = null)
    {
        $nodeTypeName = '';
        $contentStreamTag = '';

        if ($contextNode instanceof NodeInterface) {
            $contentStreamTag = $this->renderContentStreamIdentifierTag(
                    $contextNode->getContentStreamIdentifier()
                ) .'_';
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

        return 'NodeType_' . $contentStreamTag . $nodeTypeName;
    }

    /**
     * Generate a `@cache` entry tag for descendants of a node, an array of nodes or a FlowQuery result
     * A cache entry with this tag will be flushed whenever a node
     * (for any variant) that is a descendant (child on any level) of one of
     * the given nodes is updated.
     *
     * @param mixed $nodes (A single Node or array or \Traversable of Nodes)
     * @return array<int,string>
     * @throws Exception
     */
    public function descendantOfTag(mixed $nodes): array
    {
        return $this->convertArrayOfNodesToArrayOfNodeIdentifiersWithPrefix($nodes, 'DescendantOf');
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return string
     */
    private function renderContentStreamIdentifierTag(ContentStreamIdentifier $contentStreamIdentifier)
    {
        return '%' . $contentStreamIdentifier . '%';
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
