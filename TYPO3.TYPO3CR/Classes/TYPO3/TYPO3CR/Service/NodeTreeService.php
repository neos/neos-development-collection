<?php
namespace TYPO3\TYPO3CR\Service;

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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\ContentDimensionCombinator;
use TYPO3\TYPO3CR\Domain\Service\Context as ContentContext;
use TYPO3\TYPO3CR\Domain\Service\ContextFactory as ContentContextFactory;

/**
 * The node tree service
 * @Flow\Scope("singleton")
 */
class NodeTreeService
{

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var ContentContextFactory
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var ContentDimensionCombinator
     */
    protected $contentDimensionCombinator;


    /**
     * @param NodeInterface $node
     * @param callable $action
     * @return void
     */
    public function traverseTree(NodeInterface $node, callable $action)
    {
        $continue = $action($node);
        if ($continue !== false && $node->hasChildNodes()) {
            foreach ($node->getChildNodes() as $childNode) {
                $this->traverseTree($childNode, $action);
            }
        }
    }

    /**
     * Traverses the node tree in multiple workspaces
     *
     * @param NodeInterface $node The root node for the tree or branch to be traversed
     * @param callable $action The action to be performed on each node visited
     * @param callable|null $workspaceCallback The optional callback function called each time a workspace has been completely traversed
     * @param ContentContext|null $context The content context to be used during traversal. Will fall back to the node's context if omitted
     * @param array|null $workspaceNames The workspace names to be used. Will fall back to all workspaces if omitted
     */
    public function traverseTreeInWorkspaces(NodeInterface $node, callable $action, callable $workspaceCallback = null, ContentContext $context = null, array $workspaceNames = null)
    {
        if (is_null($context)) {
            $context = $node->getContext();
        }
        if (is_null($workspaceNames)) {
            $workspaceNames = $this->fetchAllWorkspaceNames();
        }

        foreach ($workspaceNames as $workspaceName) {
            $workspaceContext = $this->switchToWorkspace($context, $workspaceName);
            $nodeInWorkspace = $workspaceContext->getNodeByIdentifier($node->getIdentifier());

            $this->traverseTree($nodeInWorkspace, $action);
            if (!is_null($workspaceCallback)) {
                $workspaceCallback($workspaceContext);
            }
        }
    }

    /**
     * Traverses the node tree in multiple dimension combinations
     *
     * @param NodeInterface $node The root node for the tree or branch to be traversed
     * @param callable $action The action to be performed on each node visited
     * @param callable|null $dimensionCombinationCallback The optional callback function called each time a dimension combination has been completely traversed
     * @param ContentContext|null $context The content context to be used during traversal. Will fall back to the node's context if omitted
     * @param array|null $dimensionCombinations The dimension combinations to be used. Will fall back to all allowed combinations if omitted
     */
    public function traverseTreeInDimensionCombinations(
        NodeInterface $node,
        callable $action,
        callable $dimensionCombinationCallback = null,
        ContentContext $context = null,
        array $dimensionCombinations = null
    ) {
        if (is_null($context)) {
            $context = $node->getContext();
        }
        if (is_null($dimensionCombinations)) {
            $dimensionCombinations = $this->fetchAllDimensionCombinations();
        }

        foreach ($dimensionCombinations as $dimensionCombination) {
            $dimensionCombinationContext = $this->switchToDimensionCombination($context, $dimensionCombination);
            $nodeInDimensionCombination = $dimensionCombinationContext->getNodeByIdentifier($node->getIdentifier());

            $this->traverseTree($nodeInDimensionCombination, $action);
            if (!is_null($dimensionCombinationCallback)) {
                $dimensionCombinationCallback($dimensionCombinationContext);
            }
        }
    }

    /**
     * Traverses the node tree in multiple workspaces and dimension combinations
     *
     * @param NodeInterface $node The root node for the tree or branch to be traversed
     * @param callable $action The action to be performed on each node visited
     * @param callable|null $workspaceCallback The optional callback function called each time a workspace has been completely traversed
     * @param callable|null $dimensionCombinationCallback The optional callback function called each time a dimension combination has been completely traversed
     * @param ContentContext|null $context The content context to be used during traversal. Will fall back to the node's context if omitted
     * @param array|null $workspaceNames The workspace names to be used. Will fall back to all workspaces if omitted
     * @param array|null $dimensionCombinations The dimension combinations to be used. Will fall back to all allowed combinations if omitted
     */
    public function traverseTreeInWorkspacesAndDimensionCombinations(
        NodeInterface $node,
        callable $action,
        callable $workspaceCallback = null,
        callable $dimensionCombinationCallback = null,
        ContentContext $context = null,
        array $workspaceNames = null,
        array $dimensionCombinations = null
    ) {
        if (is_null($context)) {
            $context = $node->getContext();
        }
        if (is_null($workspaceNames)) {
            $workspaceNames = $this->fetchAllWorkspaceNames();
        }
        if (is_null($dimensionCombinations)) {
            $dimensionCombinations = $this->fetchAllDimensionCombinations();
        }

        foreach ($workspaceNames as $workspaceName) {
            $workspaceContext = $this->switchToWorkspace($context, $workspaceName);
            $nodeInWorkspace = $workspaceContext->getNodeByIdentifier($node->getIdentifier());

            $this->traverseTreeInDimensionCombinations($nodeInWorkspace, $action, $dimensionCombinationCallback, $context, $dimensionCombinations);
            if (!is_null($workspaceCallback)) {
                $workspaceCallback($workspaceContext);
            }
        }
    }


    /**
     * @param ContentContext $context
     * @param string $newWorkspaceName
     * @return ContentContext
     */
    protected function switchToWorkspace(ContentContext $context, $newWorkspaceName)
    {
        $contextProperties = $context->getProperties();
        $contextProperties['workspaceName'] = $newWorkspaceName;

        return $this->contextFactory->create($contextProperties);
    }

    /**
     * @param ContentContext $context
     * @param array $newDimensionConfiguration
     * @return ContentContext
     */
    protected function switchToDimensionCombination(ContentContext $context, array $newDimensionConfiguration)
    {
        $contextProperties = $context->getProperties();
        $contextProperties['dimensions'] = $newDimensionConfiguration;
        $contextProperties['targetDimensions'] = $newDimensionConfiguration;
        array_walk($contextProperties['targetDimensions'], function (&$item) {
            $item = reset($item);
        });

        return $this->contextFactory->create($contextProperties);
    }


    /**
     * @return array
     */
    protected function fetchAllWorkspaceNames()
    {
        $workspaceNames = [];
        foreach ($this->workspaceRepository->findAll() as $workspace) {
            /** @var Workspace $workspace */
            $workspaceNames[] = $workspace->getName();
        }

        return $workspaceNames;
    }

    /**
     * @return array
     */
    protected function fetchAllDimensionCombinations()
    {
        return $this->contentDimensionCombinator->getAllAllowedCombinations();
    }
}
