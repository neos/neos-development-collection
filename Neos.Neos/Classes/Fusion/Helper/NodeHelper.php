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

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Exception;

/**
 * Eel helper for ContentRepository Nodes
 */
class NodeHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * Renders the actual node label based on the NodeType definition in Fusion.
     */
    public function label(Node $node): string
    {
        return $node->getLabel();
    }

    /**
     * @deprecated do not rely on this, as it is rather expensive to calculate
     */
    public function depth(Node $node): int
    {
        return $node->getDepth();
    }

    /**
     * @deprecated do not rely on this, as it is rather expensive to calculate
     */
    public function path(Node $node): string
    {
        return $node->getPath();
    }

    /**
     * Retrieving the NodeType of the given Node.
     *
     * If the NodeType schema changed and the NodeType does not exist anymore, NULL is returned.
     */
    public function nodeType(Node $node): ?NodeType
    {
        $realNodeTypeName = $node->getNodeData()->getNodeTypeNameWithoutFallback();

        if (!$this->nodeTypeManager->hasNodeType($realNodeTypeName)) {
            return null;
        }

        return $this->nodeTypeManager->getNodeType($realNodeTypeName);
    }

    /**
     * If this node type or any of the direct or indirect super types
     * has the given name.
     */
    public function isOfType(NodeInterface $node, string $nodeType): bool
    {
        return $node->getNodeType()->isOfType($nodeType);
    }

    public function isDisabled(Node $node): bool
    {
        return $node->isHidden();
    }

    /**
     * In Neos 8.4 it will just return the context path while in Neos 9 the actual json representation of the NodeAddress is returned.
     * Thus, the code must be likely adjusted still in Neos 9.0
     *
     * @internal experimental API without documentation and clear use-case
     */
    public function serializedNodeAddress(Node $node): string
    {
        return $node->getContextPath();
    }

    /**
     * Check if the given node is already a collection, find collection by nodePath otherwise, throw exception
     * if no content collection could be found
     *
     * @param NodeInterface $node
     * @param string $nodePath
     * @return NodeInterface
     * @throws Exception
     */
    public function nearestContentCollection(NodeInterface $node, $nodePath)
    {
        $contentCollectionType = 'Neos.Neos:ContentCollection';
        if ($node->getNodeType()->isOfType($contentCollectionType)) {
            return $node;
        } else {
            if ((string)$nodePath === '') {
                throw new Exception(sprintf('No content collection of type %s could be found in the current node and no node path was provided. You might want to configure the nodePath property with a relative path to the content collection.', $contentCollectionType), 1409300545);
            }
            $subNode = $node->getNode($nodePath);
            if ($subNode !== null && $subNode->getNodeType()->isOfType($contentCollectionType)) {
                return $subNode;
            } else {
                throw new Exception(sprintf('No content collection of type %s could be found in the current node (%s) or at the path "%s". You might want to adjust your node type configuration and create the missing child node through the "./flow node:repair --node-type %s" command.', $contentCollectionType, $node->getPath(), $nodePath, (string)$node->getNodeType()), 1389352984);
            }
        }
    }

    /**
     * Generate a label for a node with a chaining mechanism. To be used in nodetype definitions.
     *
     * @param NodeInterface|null $node
     * @return NodeLabelToken
     */
    public function labelForNode(?NodeInterface $node = null): NodeLabelToken
    {
        return new NodeLabelToken($node);
    }

    /**
     * Compares nodes for equality
     *
     * @param Node|null $nodeA
     * @param Node|null $nodeB
     * @return bool
     */
    public function equals(?Node $nodeA, ?Node $nodeB): bool
    {
        if ($nodeB === null || $nodeA === null) {
            return $nodeA === null && $nodeB === null;
        }

        return $nodeA->equals($nodeB);
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
