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

namespace Neos\Neos\Service;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraints;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeNames;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context;
use Neos\Neos;
use Neos\Neos\Domain\Model\PluginViewDefinition;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\Neos\Domain\Service\SiteNodeUtility;

/**
 * Central authority for interactions with plugins.
 * Whenever details about Plugins or PluginViews are needed this service should be used.
 *
 * For some methods the ContentContext has to be specified.
 * This is required in order for the ContentRepository to fetch nodes of the current workspace.
 * The context can be retrieved from any node of the correct workspace & tree.
 * If no node is available (e.g. for CLI requests) the ContentContextFactory can be used to create a context instance.
 *
 * @Flow\Scope("singleton")
 */
class PluginService
{
    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    #[Flow\Inject]
    protected NodeAccessorManager $nodeAccessorManager;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected SiteNodeUtility $siteNodeUtility;


    /**
     * Returns an array of all plugin nodes with View Definitions
     *
     * @return Nodes all plugin nodes with View Definitions in the current site
     */
    public function getPluginNodesWithViewDefinitions(
        WorkspaceName $workspaceName,
        DimensionSpacePoint $dimensionSpacePoint,
        ContentRepositoryIdentifier $contentRepositoryIdentifier
    ): Nodes {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);

        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if (is_null($workspace)) {
            throw new \InvalidArgumentException('Could not find workspace "' . $workspaceName . '"');
        }

        $siteNode = $this->siteNodeUtility->findCurrentSiteNode(
            $contentRepositoryIdentifier,
            $workspace->getCurrentContentStreamIdentifier(),
            $dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );

        $pluginNodeTypes = $contentRepository->getNodeTypeManager()->getSubNodeTypes('Neos.Neos:Plugin', false);

        return $this->getNodes(
            $siteNode,
            NodeTypeNames::fromStringArray(array_keys($pluginNodeTypes))
        );
    }

    /**
     * Find all nodes of a specific node type
     *
     * @param NodeInterface $siteNode The site node to fetch the nodes beneath
     * @return Nodes All nodes matching the node type names in the given site
     */
    protected function getNodes(NodeInterface $siteNode, NodeTypeNames $nodeTypeNames): Nodes
    {
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $siteNode->getSubgraphIdentity()
        );

        return $nodeAccessor->findDescendants(
            [$siteNode],
            new NodeTypeConstraints(false, $nodeTypeNames),
            null
        );
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
        foreach (
            $this->getPluginViewConfigurationsByPluginNodeType(
                $pluginNodeType
            ) as $pluginViewName => $pluginViewConfiguration
        ) {
            $viewDefinitions[] = new PluginViewDefinition($pluginNodeType, $pluginViewName, $pluginViewConfiguration);
        }
        return $viewDefinitions;
    }

    /**
     * @param NodeType $pluginNodeType
     * @return array<string,mixed>
     */
    protected function getPluginViewConfigurationsByPluginNodeType(NodeType $pluginNodeType)
    {
        $pluginNodeTypeOptions = $pluginNodeType->getOptions();
        return $pluginNodeTypeOptions['pluginViews'] ?? [];
    }

    /**
     * returns a plugin node or one of it's view nodes
     * if an view has been configured for that specific
     * controller and action combination
     */
    public function getPluginNodeByAction(
        NodeInterface $currentNode,
        string $controllerObjectName,
        string $actionName
    ): ?NodeInterface {
        $viewDefinition = $this->getPluginViewDefinitionByAction($currentNode->getSubgraphIdentity()->contentRepositoryIdentifier, $controllerObjectName, $actionName);

        if ($currentNode->getNodeType()->isOfType('Neos.Neos:PluginView') && $viewDefinition) {
            $masterPluginNode = $this->getPluginViewNodeByMasterPlugin($currentNode, $viewDefinition->getName());
        } else {
            $masterPluginNode = $currentNode;
        }

        if ($viewDefinition !== null) {
            $viewNode = $this->getPluginViewNodeByMasterPlugin($currentNode, $viewDefinition->getName());
            if ($viewNode instanceof NodeInterface) {
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
     * @throws Neos\Exception if more than one PluginView matches the given controller/action pair
     */
    public function getPluginViewDefinitionByAction(ContentRepositoryIdentifier $contentRepositoryIdentifier, $controllerObjectName, $actionName): ?PluginViewDefinition
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryIdentifier);
        $pluginNodeTypes = $contentRepository->getNodeTypeManager()->getSubNodeTypes('Neos.Neos:Plugin', false);

        $matchingPluginViewDefinitions = [];
        foreach ($pluginNodeTypes as $pluginNodeType) {
            foreach ($this->getPluginViewDefinitionsByPluginNodeType($pluginNodeType) as $pluginViewDefinition) {
                if ($pluginViewDefinition->matchesControllerActionPair($controllerObjectName, $actionName) !== true) {
                    continue;
                }
                $matchingPluginViewDefinitions[] = $pluginViewDefinition;
            }
        }
        if (count($matchingPluginViewDefinitions) > 1) {
            throw new Neos\Exception(sprintf(
                'More than one PluginViewDefinition found for controller "%s", action "%s":%s',
                $controllerObjectName,
                $actionName,
                chr(10) . implode(chr(10), $matchingPluginViewDefinitions)
            ), 1377597671);
        }

        return count($matchingPluginViewDefinitions) > 0 ? current($matchingPluginViewDefinitions) : null;
    }

    /**
     * returns a specific view node of an master plugin
     * or NULL if it does not exist
     */
    public function getPluginViewNodeByMasterPlugin(NodeInterface $node, string $viewName): ?NodeInterface
    {
        $siteNode = $this->siteNodeUtility->findSiteNode($node);
        foreach (
            $this->getNodes($siteNode, NodeTypeNames::fromArray([
                NodeTypeName::fromString('Neos.Neos:PluginView')
            ])) as $pluginViewNode
        ) {
            if (
                $pluginViewNode->getProperty('plugin') === (string)$node->getNodeAggregateIdentifier()
                && $pluginViewNode->getProperty('view') === $viewName
            ) {
                return $pluginViewNode;
            }
        }

        return null;
    }
}
