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

use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Neos\Domain\Exception;

/**
 * Eel helper for TYPO3CR Nodes
 */
class NodeHelper implements ProtectedContextAwareInterface
{
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
        $contentCollectionType = 'TYPO3.Neos:ContentCollection';
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
                throw new Exception(sprintf('No content collection of type %s could be found in the current node (%s) or at the path "%s". You might want to adjust your node type configuration and create the missing child node through the "flow node:repair --node-type %s" command.', $contentCollectionType, $node->getPath(), $nodePath, (string)$node->getNodeType()), 1389352984);
            }
        }
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
