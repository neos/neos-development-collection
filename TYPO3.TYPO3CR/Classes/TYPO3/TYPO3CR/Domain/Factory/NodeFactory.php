<?php
namespace TYPO3\TYPO3CR\Domain\Factory;

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
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Context as SecurityContext;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\Context;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Exception\NodeConfigurationException;

/**
 * This factory creates nodes based on node data. Its main purpose is to
 * assure that nodes created for a certain node data container and context
 * are unique in memory.
 *
 * @Flow\Scope("singleton")
 */
class NodeFactory
{
    /**
     * @var array<\TYPO3\TYPO3CR\Domain\Model\Node>
     */
    protected $nodes = array();

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;


    /**
     * Creates a node from the given NodeData container.
     *
     * If this factory has previously created a Node for the given $node and it's dimensions,
     * it will return the same node again.
     *
     * @param NodeData $nodeData
     * @param Context $context
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
     * @throws \TYPO3\TYPO3CR\Exception\NodeConfigurationException if a configured 'class' for a Node does not exist or does not inherit NodeInterface
     */
    public function createFromNodeData(NodeData $nodeData, Context $context)
    {
        if ($nodeData->isInternal()) {
            return null;
        }

        $internalNodeIdentifier = $nodeData->getIdentifier() . spl_object_hash($context);

        // In case there is a Node with an internal NodeData (because the NodeData was changed in the meantime) we need to flush it.
        if (isset($this->nodes[$internalNodeIdentifier]) && $this->nodes[$internalNodeIdentifier]->getNodeData()->isInternal()) {
            unset($this->nodes[$internalNodeIdentifier]);
        }

        if (!isset($this->nodes[$internalNodeIdentifier])) {
            // Warning: Alternative node implementations are considered internal for now, feature can change or be removed anytime. We want to be sure it works well and makes sense before declaring it public.
            $class = $nodeData->getNodeType()->getConfiguration('class') ?: $this->objectManager->getClassNameByObjectName('TYPO3\\TYPO3CR\\Domain\\Model\\NodeInterface');
            if (!in_array($class, static::getNodeInterfaceImplementations($this->objectManager))) {
                throw new NodeConfigurationException('The configured implementation class name "' . $class . '" for NodeType "' . $nodeData->getNodeType() . '" does not inherit from TYPO3\\TYPO3CR\\Domain\\Model\\NodeInterface.', 1406884014);
            }
            $this->nodes[$internalNodeIdentifier] = new $class($nodeData, $context);
        }
        $node = $this->nodes[$internalNodeIdentifier];

        return $this->filterNodeByContext($node, $context);
    }

    /**
     * Get all NodeInterface implementations to check if a configured node class is in there.
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @Flow\CompileStatic
     */
    public static function getNodeInterfaceImplementations($objectManager)
    {
        $reflectionService = $objectManager->get('TYPO3\Flow\Reflection\ReflectionService');
        $nodeImplementations = $reflectionService->getAllImplementationClassNamesForInterface('TYPO3\\TYPO3CR\\Domain\\Model\\NodeInterface');
        return $nodeImplementations;
    }

    /**
     * Filter a node by the current context.
     * Will either return the node or NULL if it is not permitted in current context.
     *
     * @param NodeInterface $node
     * @param Context $context
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface|NULL
     */
    protected function filterNodeByContext(NodeInterface $node, Context $context)
    {
        $this->securityContext->withoutAuthorizationChecks(function () use (&$node, $context) {
            if (!$context->isRemovedContentShown() && $node->isRemoved()) {
                $node = null;
                return;
            }
            if (!$context->isInvisibleContentShown() && !$node->isVisible()) {
                $node = null;
                return;
            }
            if (!$context->isInaccessibleContentShown() && !$node->isAccessible()) {
                $node = null;
            }
        });
        return $node;
    }

    /**
     * Generates a Context that exactly fits the given NodeData Workspace and Dimensions.
     *
     * TODO: We could get more specific about removed and invisible content by adding some more logic here that generates fitting values.
     *
     * @param NodeData $nodeData
     * @return Context
     */
    public function createContextMatchingNodeData(NodeData $nodeData)
    {
        return $this->contextFactory->create(array(
            'workspaceName' => $nodeData->getWorkspace()->getName(),
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true,
            'removedContentShown' => true,
            'dimensions' => $nodeData->getDimensionValues()
        ));
    }

    /**
     * Reset the node instances (for testing)
     *
     * @return void
     */
    public function reset()
    {
        $this->nodes = array();
    }
}
