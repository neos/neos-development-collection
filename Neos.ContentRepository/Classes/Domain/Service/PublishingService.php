<?php
namespace Neos\ContentRepository\Domain\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Exception\WorkspaceException;
use Neos\ContentRepository\Service\Utility\NodePublishingDependencySolver;

/**
 * A generic ContentRepository Publishing Service
 *
 * @api
 * @Flow\Scope("singleton")
 */
class PublishingService implements PublishingServiceInterface
{
    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * Returns a list of nodes contained in the given workspace which are not yet published
     *
     * @param Workspace $workspace
     * @return array<\Neos\ContentRepository\Domain\Model\NodeInterface>
     * @api
     */
    public function getUnpublishedNodes(Workspace $workspace)
    {
        if ($workspace->getBaseWorkspace() === null) {
            return [];
        }

        $nodeData = $this->nodeDataRepository->findByWorkspace($workspace);
        $unpublishedNodes = [];
        foreach ($nodeData as $singleNodeData) {
            /** @var NodeData $singleNodeData */
            // Skip the root entry from the workspace as it can't be published
            if ($singleNodeData->getPath() === '/') {
                continue;
            }
            $node = $this->nodeFactory->createFromNodeData($singleNodeData, $this->createContext($workspace, $singleNodeData->getDimensionValues()));
            if ($node !== null) {
                $unpublishedNodes[] = $node;
            }
        }

        $unpublishedNodes = $this->sortNodesForPublishing($unpublishedNodes);

        return $unpublishedNodes;
    }

    /**
     * Returns the number of unpublished nodes contained in the given workspace
     *
     * @param Workspace $workspace
     * @return integer
     * @api
     */
    public function getUnpublishedNodesCount(Workspace $workspace)
    {
        return $workspace->getNodeCount() - 1;
    }

    /**
     * Publishes the given node to the specified target workspace. If no workspace is specified, the source workspace's
     * base workspace is assumed.
     *
     * @param NodeInterface $node
     * @param Workspace $targetWorkspace If not set the base workspace is assumed to be the publishing target
     * @return void
     * @api
     */
    public function publishNode(NodeInterface $node, Workspace $targetWorkspace = null)
    {
        if ($targetWorkspace === null) {
            $targetWorkspace = $node->getWorkspace()->getBaseWorkspace();
        }
        if ($targetWorkspace instanceof Workspace) {
            $node->getWorkspace()->publishNode($node, $targetWorkspace);
            $this->emitNodePublished($node, $targetWorkspace);
        }
    }

    /**
     * Publishes the given nodes to the specified target workspace. If no workspace is specified, the source workspace's
     * base workspace is assumed.
     *
     * @param array<\Neos\ContentRepository\Domain\Model\NodeInterface> $nodes The nodes to publish
     * @param Workspace $targetWorkspace If not set the base workspace is assumed to be the publishing target
     * @return void
     * @api
     */
    public function publishNodes(array $nodes, Workspace $targetWorkspace = null)
    {
        $nodes = $this->sortNodesForPublishing($nodes);
        foreach ($nodes as $node) {
            $this->publishNode($node, $targetWorkspace);
        }
    }

    /**
     * Discards the given node.
     *
     * If the node has been moved, this method will also discard all changes of child nodes of the given node.
     *
     * @param NodeInterface $node The node to discard
     * @return void
     * @throws WorkspaceException
     * @api
     */
    public function discardNode(NodeInterface $node)
    {
        $this->doDiscardNode($node);
    }

    /**
     * Method which does the actual work of discarding, includes a protection against endless recursions and
     * multiple discarding of the same node.
     *
     * @param NodeInterface $node The node to discard
     * @param array &$alreadyDiscardedNodeIdentifiers List of node identifiers which already have been discarded during one discardNode() run
     * @return void
     * @throws \Neos\ContentRepository\Exception\WorkspaceException
     */
    protected function doDiscardNode(NodeInterface $node, array &$alreadyDiscardedNodeIdentifiers = [])
    {
        if ($node->getWorkspace()->getBaseWorkspace() === null) {
            throw new WorkspaceException('Nodes in a workspace without a base workspace cannot be discarded.', 1395841899);
        }
        if ($node->getPath() === '/') {
            return;
        }
        if (array_search($node->getIdentifier(), $alreadyDiscardedNodeIdentifiers) !== false) {
            return;
        }

        $alreadyDiscardedNodeIdentifiers[] = $node->getIdentifier();

        $possibleShadowNodeData = $this->nodeDataRepository->findOneByMovedTo($node->getNodeData());
        if ($possibleShadowNodeData instanceof NodeData) {
            if ($possibleShadowNodeData->getMovedTo() !== null) {
                $parentBasePath = $node->getPath();
                $affectedChildNodeDataInSameWorkspace = $this->nodeDataRepository->findByParentAndNodeType($parentBasePath, null, $node->getWorkspace(), null, false, true);
                foreach ($affectedChildNodeDataInSameWorkspace as $affectedChildNodeData) {
                    /** @var NodeData $affectedChildNodeData */
                    $affectedChildNode = $this->nodeFactory->createFromNodeData($affectedChildNodeData, $node->getContext());
                    $this->doDiscardNode($affectedChildNode, $alreadyDiscardedNodeIdentifiers);
                }
            }

            $this->nodeDataRepository->remove($possibleShadowNodeData);
        }

        $this->nodeDataRepository->remove($node);
        $this->emitNodeDiscarded($node);
    }

    /**
     * Discards the given nodes.
     *
     * @param array<\Neos\ContentRepository\Domain\Model\NodeInterface> $nodes The nodes to discard
     * @return void
     * @api
     */
    public function discardNodes(array $nodes)
    {
        $discardedNodeIdentifiers = [];
        foreach ($nodes as $node) {
            $this->doDiscardNode($node, $discardedNodeIdentifiers);
        }
    }

    /**
     * Discards all unpublished nodes of the given workspace.
     *
     * TODO: This method needs to be optimized / implemented in collaboration with a DQL-based method in NodeDataRepository
     *
     * @param Workspace $workspace The workspace to flush, can't be the live workspace
     * @return void
     * @throws WorkspaceException
     * @api
     */
    public function discardAllNodes(Workspace $workspace)
    {
        if ($workspace->getName() === 'live') {
            throw new WorkspaceException('Nodes in the live workspace cannot be discarded.', 1428937112);
        }

        foreach ($this->getUnpublishedNodes($workspace) as $node) {
            /** @var NodeInterface $node */
            if ($node->getPath() !== '/') {
                $this->discardNode($node);
            }
        }
    }

    /**
     * Sort an unsorted list of nodes in a publishable order
     *
     * @param array $nodes Unsorted list of nodes (unpublished nodes)
     * @return array Sorted list of nodes for publishing
     * @throws WorkspaceException
     */
    protected function sortNodesForPublishing(array $nodes)
    {
        $sorter = new NodePublishingDependencySolver();
        return $sorter->sort($nodes);
    }

    /**
     * Signals that a node has been published.
     *
     * The signal emits the source node and target workspace, i.e. the node contains its source
     * workspace.
     *
     * @param NodeInterface $node
     * @param Workspace $targetWorkspace
     * @return void
     * @Flow\Signal
     * @api
     */
    public function emitNodePublished(NodeInterface $node, Workspace $targetWorkspace = null)
    {
    }

    /**
     * Signals that a node has been discarded.
     *
     * The signal emits the node that has been discarded.
     *
     * @param NodeInterface $node
     * @return void
     * @Flow\Signal
     * @api
     */
    public function emitNodeDiscarded(NodeInterface $node)
    {
    }

    /**
     * Creates a new content context based on the given workspace and the NodeData object.
     *
     * @param Workspace $workspace Workspace for the new context
     * @param array $dimensionValues The dimension values for the new context
     * @param array $contextProperties Additional pre-defined context properties
     * @return Context
     */
    protected function createContext(Workspace $workspace, array $dimensionValues, array $contextProperties = [])
    {
        $presetsMatchingDimensionValues = $this->contentDimensionPresetSource->findPresetsByTargetValues($dimensionValues);
        $dimensions = array_map(function ($preset) {
            return $preset['values'];
        }, $presetsMatchingDimensionValues);

        $contextProperties += [
            'workspaceName' => $workspace->getName(),
            'inaccessibleContentShown' => true,
            'invisibleContentShown' => true,
            'removedContentShown' => true,
            'dimensions' => $dimensions
        ];

        return $this->contextFactory->create($contextProperties);
    }
}
