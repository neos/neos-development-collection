<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Fusion\Helper;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeTreeTraversalHelper;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
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
     * Check if the given node is already a collection, find collection by nodePath otherwise, throw exception
     * if no content collection could be found
     *
     * @param TraversableNodeInterface $node
     * @param string $nodePath
     * @return TraversableNodeInterface
     * @throws Exception
     */
    public function nearestContentCollection(TraversableNodeInterface $node, $nodePath, ContentSubgraphInterface $subgraph)
    {
        $contentCollectionType = 'Neos.Neos:ContentCollection';
        if ($node->getNodeType()->isOfType($contentCollectionType)) {
            return $node;
        } else {
            if ((string)$nodePath === '') {
                throw new Exception(sprintf('No content collection of type %s could be found in the current node and no node path was provided. You might want to configure the nodePath property with a relative path to the content collection.', $contentCollectionType), 1409300545);
            }
            $subNode = NodeTreeTraversalHelper::findNodeByNodePath(
                $subgraph,
                $node,
                NodePath::fromString($nodePath)
            );

            if ($subNode !== null && $subNode->getNodeType()->isOfType($contentCollectionType)) {
                return $subNode;
            } else {
                throw new Exception(sprintf('No content collection of type %s could be found in the current node (%s) or at the path "%s". You might want to adjust your node type configuration and create the missing child node through the "flow node:repair --node-type %s" command.', $contentCollectionType, $node->findNodePath(), $nodePath, (string)$node->getNodeType()), 1389352984);
            }
        }
    }

    public function nodeAddressToString(TraversableNodeInterface $node): string
    {
        return $this->nodeAddressFactory->createFromTraversableNode($node)->serializeForUri();
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
