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

namespace Neos\Neos\Fusion\Helper;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\Neos\Domain\Model\NodeCacheEntryIdentifier;
use Neos\Neos\Exception;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;

/**
 * Caching helper to make cache tag generation easier.
 */
class CachingHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

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

        if (!is_array($nodes) && ($nodes instanceof Node)) {
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
            if ($node instanceof Node) {
                $prefixedNodeIdentifiers[] = $prefix . '_'
                    . $this->renderContentStreamIdentifierTag($node->subgraphIdentity->contentStreamIdentifier)
                    . '_' . $node->nodeAggregateIdentifier;
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

    public function entryIdentifierForNode(Node $node): NodeCacheEntryIdentifier
    {
        return NodeCacheEntryIdentifier::fromNode($node);
    }

    /**
     * Generate a `@cache` entry tag for a single node identifier. If a Node $contextNode is given the
     * entry tag will respect the workspace hash.
     *
     * @param string $identifier
     * @param ?Node $contextNode
     * @return string
     */
    public function nodeTagForIdentifier(string $identifier, Node $contextNode = null): string
    {
        $contentStreamTag = '';
        if ($contextNode instanceof Node) {
            $contentStreamTag = $this->renderContentStreamIdentifierTag(
                $contextNode->subgraphIdentity->contentStreamIdentifier
            ) . '_';
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
     * @param Node|null $contextNode
     * @return string|string[]
     */
    public function nodeTypeTag($nodeType, Node $contextNode = null)
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
     * @param ?Node $contextNode
     * @return string
     */
    protected function getNodeTypeTagFor($nodeType, Node $contextNode = null)
    {
        $nodeTypeName = '';
        $contentStreamTag = '';

        if ($contextNode instanceof Node) {
            $contentStreamTag = $this->renderContentStreamIdentifierTag(
                $contextNode->subgraphIdentity->contentStreamIdentifier
            ) . '_';
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
     * @param Node $node
     * @return array<string,Workspace>
     */
    public function getWorkspaceChain(?Node $node): array
    {
        if ($node === null) {
            return [];
        }

        $contentRepository = $this->contentRepositoryRegistry->get(
            $node->subgraphIdentity->contentRepositoryIdentifier
        );


        /** @var Workspace $currentWorkspace */
        $currentWorkspace = $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamIdentifier(
            $node->subgraphIdentity->contentStreamIdentifier
        );
        $workspaceChain = [];
        // TODO: Maybe write CTE here
        while ($currentWorkspace instanceof Workspace) {
            $workspaceChain[(string)$currentWorkspace->getWorkspaceName()] = $currentWorkspace;
            $currentWorkspace = $currentWorkspace->getBaseWorkspaceName()
                ? $contentRepository->getWorkspaceFinder()->findOneByName($currentWorkspace->getBaseWorkspaceName())
                : null;
        }

        return $workspaceChain;
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
