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
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Neos\Domain\Exception;

/**
 * Eel helper for ContentRepository Nodes
 */
class NodeHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * Check if the given node is already a collection, find collection by nodePath otherwise, throw exception
     * if no content collection could be found
     *
     * @throws Exception
     */
    public function nearestContentCollection(NodeInterface $node, string $nodePath): NodeInterface
    {
        $contentCollectionType = 'Neos.Neos:ContentCollection';
        if ($node->getNodeType()->isOfType($contentCollectionType)) {
            return $node;
        } else {
            if ($nodePath === '') {
                throw new Exception(sprintf(
                    'No content collection of type %s could be found in the current node and no node path was provided.'
                    . ' You might want to configure the nodePath property'
                    . ' with a relative path to the content collection.',
                    $contentCollectionType
                ), 1409300545);
            }
            $subNode = $this->findNodeByNodePath(
                $node,
                NodePath::fromString($nodePath)
            );

            if ($subNode !== null && $subNode->getNodeType()->isOfType($contentCollectionType)) {
                return $subNode;
            } else {
                throw new Exception(sprintf(
                    'No content collection of type %s could be found in the current node (%s) or at the path "%s".'
                    . ' You might want to adjust your node type configuration and create the missing child node'
                    . ' through the "flow node:repair --node-type %s" command.',
                    $contentCollectionType,
                    $this->findNodePath($node),
                    $nodePath,
                    $node->getNodeType()
                ), 1389352984);
            }
        }
    }

    /**
     * Generate a label for a node with a chaining mechanism. To be used in nodetype definitions.
     */
    public function labelForNode(NodeInterface $node): NodeLabelToken
    {
        return new NodeLabelToken($node);
    }

    /**
     * If this node type or any of the direct or indirect super types
     * has the given name.
     *
     * @param NodeInterface $node
     * @param string $nodeType
     * @return bool
     */
    public function isOfType(NodeInterface $node, string $nodeType): bool
    {
        return $node->getNodeType()->isOfType($nodeType);
    }


    public function nodeAddressToString(NodeInterface $node): string
    {
        return $this->nodeAddressFactory->createFromNode($node)->serializeForUri();
    }

    private function findNodeByNodePath(NodeInterface $node, NodePath $nodePath): ?NodeInterface
    {
        if ($nodePath->isAbsolute()) {
            $node = $this->findRootNode($node);
        }


        return $this->findNodeByPath($node, $nodePath);
    }



    private function findRootNode(NodeInterface $node): NodeInterface
    {
        while (true) {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $node->getContentStreamIdentifier(),
                $node->getDimensionSpacePoint(),
                $node->getVisibilityConstraints()
            );
            $parentNode = $nodeAccessor->findParentNode($node);
            if ($parentNode === null) {
                // there is no parent, so the root node was the node before
                return $node;
            } else {
                $node = $parentNode;
            }
        }
    }

    private function findNodePath(NodeInterface $node): NodePath
    {
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $node->getContentStreamIdentifier(),
            $node->getDimensionSpacePoint(),
            $node->getVisibilityConstraints()
        );

        return $nodeAccessor->findNodePath($node);
    }

    private function findNodeByPath(NodeInterface $node, NodePath $nodePath): ?NodeInterface
    {
        foreach ($nodePath->getParts() as $nodeName) {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $node->getContentStreamIdentifier(),
                $node->getDimensionSpacePoint(),
                $node->getVisibilityConstraints()
            );
            $childNode = $nodeAccessor->findChildNodeConnectedThroughEdgeName($node, $nodeName);
            if ($childNode === null) {
                // we cannot find the child node, so there is no node on this path
                return null;
            }
            $node = $childNode;
        }

        return $node;
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
