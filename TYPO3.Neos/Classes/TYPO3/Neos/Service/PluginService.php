<?php
namespace TYPO3\Neos\Service;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Context;
use TYPO3\Neos;
use TYPO3\Neos\Domain\Model\PluginViewDefinition;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Central authority for interactions with plugins.
 * Whenever details about Plugins or PluginViews are needed this service should be used.
 *
 * For some methods the ContentContext has to be specified. This is required in order for the TYPO3CR to fetch nodes
 * of the current workspace. The context can be retrieved from any node of the correct workspace & tree. If no node
 * is available (e.g. for CLI requests) the ContentContextFactory can be used to create a context instance.
 *
 * @Flow\Scope("singleton")
 */
class PluginService
{
    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var ContentContextFactory
     */
    protected $contentContextFactory;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * Returns an array of all available plugin nodes
     *
     * @param ContentContext $context current content context, see class doc comment for details
     * @return array<NodeInterface> all plugin nodes in the current $context
     */
    public function getPluginNodes(ContentContext $context)
    {
        $pluginNodeTypes = $this->nodeTypeManager->getSubNodeTypes('TYPO3.Neos:Plugin', false);
        return $this->getNodes(array_keys($pluginNodeTypes), $context);
    }

    /**
     * Returns an array of all plugin nodes with View Definitions
     *
     * @param ContentContext $context
     * @return array<NodeInterface> all plugin nodes with View Definitions in the current $context
     */
    public function getPluginNodesWithViewDefinitions(ContentContext $context)
    {
        $pluginNodes = [];
        foreach ($this->getPluginNodes($context) as $pluginNode) {
            /** @var NodeInterface $pluginNode */
            if ($this->getPluginViewDefinitionsByPluginNodeType($pluginNode->getNodeType()) !== []) {
                $pluginNodes[] = $pluginNode;
            }
        }
        return $pluginNodes;
    }

    /**
     * Find all nodes of a specific node type
     *
     * @param array $nodeTypes
     * @param ContentContext $context current content context, see class doc comment for details
     * @return array<NodeInterface> all nodes of type $nodeType in the current $context
     */
    protected function getNodes(array $nodeTypes, ContentContext $context)
    {
        $nodes = [];
        $siteNode = $context->getCurrentSiteNode();
        foreach ($this->nodeDataRepository->findByParentAndNodeTypeRecursively($siteNode->getPath(), implode(',', $nodeTypes), $context->getWorkspace()) as $nodeData) {
            $nodes[] = $this->nodeFactory->createFromNodeData($nodeData, $context);
        }
        return $nodes;
    }

    /**
     * Get all configured PluginView definitions for a specific $pluginNodeType
     *
     * @param NodeType $pluginNodeType node type name of the master plugin
     * @return array<PluginViewDefinition> list of PluginViewDefinition instances for the given $pluginNodeName
     */
    public function getPluginViewDefinitionsByPluginNodeType(NodeType $pluginNodeType)
    {
        $viewDefinitions = [];
        foreach ($this->getPluginViewConfigurationsByPluginNodeType($pluginNodeType) as $pluginViewName => $pluginViewConfiguration) {
            $viewDefinitions[] = new PluginViewDefinition($pluginNodeType, $pluginViewName, $pluginViewConfiguration);
        }
        return $viewDefinitions;
    }

    /**
     * @param NodeType $pluginNodeType
     * @return array
     */
    protected function getPluginViewConfigurationsByPluginNodeType(NodeType $pluginNodeType)
    {
        $pluginNodeTypeOptions = $pluginNodeType->getOptions();
        return isset($pluginNodeTypeOptions['pluginViews']) ? $pluginNodeTypeOptions['pluginViews'] : [];
    }

    /**
     * returns a plugin node or one of it's view nodes
     * if an view has been configured for that specific
     * controller and action combination
     *
     * @param NodeInterface $currentNode
     * @param string $controllerObjectName
     * @param string $actionName
     * @return NodeInterface
     */
    public function getPluginNodeByAction(NodeInterface $currentNode, $controllerObjectName, $actionName)
    {
        $viewDefinition = $this->getPluginViewDefinitionByAction($controllerObjectName, $actionName);

        if ($currentNode->getNodeType()->isOfType('TYPO3.Neos:PluginView')) {
            $masterPluginNode = $this->getPluginViewNodeByMasterPlugin($currentNode, $viewDefinition->getName());
        } else {
            $masterPluginNode = $currentNode;
        }

        if ($viewDefinition !== null) {
            $viewNode = $this->getPluginViewNodeByMasterPlugin($currentNode, $viewDefinition->getName());
            if ($viewNode instanceof Node) {
                return $viewNode;
            }
        }

        return $masterPluginNode;
    }

    /**
     * Fetch a PluginView definition that matches the specified controller and action combination
     *
     * @param string $controllerObjectName
     * @param string $actionName
     * @return PluginViewDefinition
     * @throws Neos\Exception if more than one PluginView matches the given controller/action pair
     */
    public function getPluginViewDefinitionByAction($controllerObjectName, $actionName)
    {
        $pluginNodeTypes = $this->nodeTypeManager->getSubNodeTypes('TYPO3.Neos:Plugin', false);

        $matchingPluginViewDefinitions = [];
        foreach ($pluginNodeTypes as $pluginNodeType) {
            /** @var $pluginViewDefinition PluginViewDefinition */
            foreach ($this->getPluginViewDefinitionsByPluginNodeType($pluginNodeType) as $pluginViewDefinition) {
                if ($pluginViewDefinition->matchesControllerActionPair($controllerObjectName, $actionName) !== true) {
                    continue;
                }
                $matchingPluginViewDefinitions[] = $pluginViewDefinition;
            }
        }
        if (count($matchingPluginViewDefinitions) > 1) {
            throw new Neos\Exception(sprintf('More than one PluginViewDefinition found for controller "%s", action "%s":%s', $controllerObjectName, $actionName, chr(10) . implode(chr(10), $matchingPluginViewDefinitions)), 1377597671);
        }

        return count($matchingPluginViewDefinitions) > 0 ? current($matchingPluginViewDefinitions) : null;
    }

    /**
     * returns a specific view node of an master plugin
     * or NULL if it does not exist
     *
     * @param NodeInterface $node
     * @param string $viewName
     * @return NodeInterface
     */
    public function getPluginViewNodeByMasterPlugin(NodeInterface $node, $viewName)
    {
        /** @var $context ContentContext */
        $context = $node->getContext();
        foreach ($this->getNodes(['TYPO3.Neos:PluginView'], $context) as $pluginViewNode) {
            /** @var NodeInterface $pluginViewNode */
            if ($pluginViewNode->isRemoved()) {
                continue;
            }
            if ($pluginViewNode->getProperty('plugin') === $node->getIdentifier()
                && $pluginViewNode->getProperty('view') === $viewName) {
                return $pluginViewNode;
            }
        }

        return null;
    }
}
