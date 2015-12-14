<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Exception\NodeException;
use TYPO3\TYPO3CR\Exception\NodeExistsException;

/**
 * Provide method to manage node
 *
 * @Flow\Scope("singleton")
 * @api
 */
class NodeService
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * Set default node property base on the target node type configuration
     *
     * @param NodeInterface $node
     * @param NodeType $targetNodeType
     * @return void
     */
    public function setDefaultValues(NodeInterface $node, NodeType $targetNodeType = null)
    {
        $nodeType = $targetNodeType ?: $node->getNodeType();
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $defaultValue) {
            if (trim($node->getProperty($propertyName)) === '') {
                $node->setProperty($propertyName, $defaultValue);
            }
        }
    }

    /**
     * Create missing child nodes based on target node type configuration
     *
     * @param NodeInterface $node
     * @param NodeType $targetNodeType
     * @return void
     */
    public function createChildNodes(NodeInterface $node, NodeType $targetNodeType = null)
    {
        $nodeType = $targetNodeType ?: $node->getNodeType();
        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeName => $childNodeType) {
            try {
                $node->createNode($childNodeName, $childNodeType);
            } catch (NodeExistsException $exception) {
            }
        }
    }

    /**
     * Remove all properties not configured in the current Node Type.
     * This will not do anything on Nodes marked as removed as those could be queued up for deletion
     * which contradicts updates (that would be necessary to remove the properties).
     *
     * @param NodeInterface $node
     * @return void
     */
    public function cleanUpProperties(NodeInterface $node)
    {
        if ($node->isRemoved() === false) {
            $nodeData = $node->getNodeData();
            $nodeTypeProperties = $node->getNodeType()->getProperties();
            foreach ($node->getProperties() as $name => $value) {
                if (!isset($nodeTypeProperties[$name])) {
                    $nodeData->removeProperty($name);
                }
            }
        }
    }

    /**
     * @param NodeInterface $node
     * @param NodeType $nodeType
     * @return boolean
     */
    public function isNodeOfType(NodeInterface $node, NodeType $nodeType)
    {
        if ($node->getNodeType()->getName() === $nodeType->getName()) {
            return true;
        }
        $subNodeTypes = $this->nodeTypeManager->getSubNodeTypes($nodeType->getName());
        return isset($subNodeTypes[$node->getNodeType()->getName()]);
    }

    /**
     * Checks if the given node path exists in any possible context already.
     *
     * @param string $nodePath
     * @return boolean
     */
    public function nodePathExistsInAnyContext($nodePath)
    {
        return $this->nodeDataRepository->pathExists($nodePath);
    }

    /**
     * Checks if the given node path can be used for the given node.
     *
     * @param string $nodePath
     * @param NodeInterface $node
     * @return boolean
     */
    public function nodePathAvailableForNode($nodePath, NodeInterface $node)
    {
        /** @var NodeData $existingNodeData */
        $existingNodeDataObjects = $this->nodeDataRepository->findByPathWithoutReduce($nodePath, $node->getWorkspace(), true);
        foreach ($existingNodeDataObjects as $existingNodeData) {
            if ($existingNodeData->getMovedTo() !== null && $existingNodeData->getMovedTo() === $node->getNodeData()) {
                return true;
            }
        }
        return !$this->nodePathExistsInAnyContext($nodePath);
    }
}
