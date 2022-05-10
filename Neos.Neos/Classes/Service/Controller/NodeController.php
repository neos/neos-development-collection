<?php
namespace Neos\Neos\Service\Controller;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\NodeDisabling\Command\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Helper\SecurityHelper;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Service\NodeSearchServiceInterface;
use Neos\Neos\Service\View\NodeView;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;

/**
 * Service Controller for managing Nodes
 *
 * Note: This controller should be, step-by-step, transformed into a clean REST controller (see NEOS-190 and NEOS-199).
 *       Since this is a rather big endeavor, we slice the elephant and move methods in a clean way from here to the
 *       new NodesController (\Neos\Neos\Controller\Service\NodesController)
 */
class NodeController extends AbstractServiceController
{
    /**
     * @var NodeView
     */
    protected $view;

    /**
     * @var array<string,class-string>
     */
    protected $viewFormatToObjectNameMap = [
        'html' => NodeView::class,
        'json' => NodeView::class
    ];

    /**
     * @var array<int,string>
     */
    protected $supportedMediaTypes = [
        'text/html',
        'application/json'
    ];

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeSearchServiceInterface
     */
    protected $nodeSearchService;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    #[Flow\Inject]
    protected NodeAddressFactory $nodeAddressFactory;

    #[Flow\Inject]
    protected NodeAccessorManager $nodeAccessorManager;

    #[Flow\Inject]
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    #
    # Actions which are not yet refactored to REST below (see NEOS-199):
    #

    /**
     * Return child nodes of specified node for usage in a TreeLoader
     *
     * @param NodeInterface $node The node to find child nodes for
     * @param string $nodeTypeFilter A node type filter
     * @param integer $depth levels of childNodes (0 = unlimited)
     * @param NodeInterface $untilNode expand the child nodes until $untilNode is reached, independent of $depth
     * @return void
     * @todo define how this is to be handled
     */
    public function getChildNodesForTreeAction(NodeInterface $node, $nodeTypeFilter, $depth, NodeInterface $untilNode)
    {
        /*
        $this->view->assignChildNodes($node, $nodeTypeFilter, NodeView::STYLE_TREE, $depth, $untilNode);
        */
    }

    /**
     * Return child nodes of specified node for usage in a TreeLoader based on filter
     *
     * @param NodeInterface $node The node to find child nodes for
     * @param string $term
     * @param string $nodeType
     * @return void
     * @todo define how this is to be handled
     */
    public function filterChildNodesForTreeAction(NodeInterface $node, $term, $nodeType)
    {
        /*
        $nodeTypes = strlen($nodeType) > 0
            ? [$nodeType]
            : array_keys($this->nodeTypeManager->getSubNodeTypes('Neos.Neos:Document', false));
        $context = $node->getContext();
        if ($term !== '') {
            $nodes = $this->nodeSearchService->findByProperties($term, $nodeTypes, $context, $node);
        } else {
            $nodes = [];
            $nodeDataRecords = $this->nodeDataRepository->findByParentAndNodeTypeRecursively(
                $node->getPath(),
                implode(',', $nodeTypes),
                $context->getWorkspace(),
                $context->getDimensions()
            );
            foreach ($nodeDataRecords as $nodeData) {
                $matchedNode = $this->nodeFactory->createFromNodeData($nodeData, $context);
                if ($matchedNode !== null) {
                    $nodes[$matchedNode->getPath()] = $matchedNode;
                }
            }
        }
        $this->view->assignFilteredChildNodes(
            $node,
            $nodes
        );
        */
    }

    /**
     * Creates a new node
     *
     * We need to call persistAll() in order to return the nextUri.
     * We can't persist only the nodes in NodeDataRepository
     * because they might be connected to images / resources which need to be updated at the same time.
     *
     * @param NodeInterface $referenceNode
     * @param array<mixed> $nodeData
     * @param string $position where the node should be added (allowed: before, into, after)
     * @return void
     * @todo define how this is to be handled
     */
    public function createAction(NodeInterface $referenceNode, array $nodeData, $position)
    {
        /*
        $newNode = $this->nodeOperations->create($referenceNode, $nodeData, $position);

        if (SecurityHelper::hasSafeMethod($this->request->getHttpRequest()) === false) {
            $this->persistenceManager->persistAll();
        }

        $nextUri = $this->getNodeUri($newNode);
        $this->view->assign('value', ['data' => ['nextUri' => $nextUri], 'success' => true]);
        */
    }

    /**
     * Creates a new node and renders the node inside the containing content collection.
     *
     * @param NodeInterface $referenceNode
     * @param string $fusionPath The Fusion path of the collection
     * @param array<mixed> $nodeData
     * @param string $position where the node should be added (allowed: before, into, after)
     * @todo define how this is to be handled
     */
    public function createAndRenderAction(NodeInterface $referenceNode, $fusionPath, array $nodeData, $position): void
    {
        /*
        $newNode = $this->nodeOperations->create($referenceNode, $nodeData, $position);
        $this->redirectToRenderNode($newNode, $fusionPath);
        */
    }

    /**
     * Creates a new node and returns tree structure
     *
     * @param NodeInterface $referenceNode
     * @param array<mixed> $nodeData
     * @param string $position where the node should be added, -1 is before, 0 is in, 1 is after
     * @param string $nodeTypeFilter
     * @return void
     * @todo define how this is to be handled
     */
    public function createNodeForTheTreeAction(
        NodeInterface $referenceNode,
        array $nodeData,
        $position,
        $nodeTypeFilter = ''
    ) {
        /*
        $newNode = $this->nodeOperations->create($referenceNode, $nodeData, $position);
        $this->view->assignNodeAndChildNodes($newNode, $nodeTypeFilter);
        */
    }

    /**
     * Move $node before, into or after $targetNode
     *
     * We need to call persistAll() in order to return the nextUri.
     * We can't persist only the nodes in NodeDataRepository
     * because they might be connected to images / resources which need to be updated at the same time.
     *
     * @param NodeInterface $node The node to be moved
     * @param NodeInterface $targetNode The target node to be moved "to", see $position
     * @param string $position where the node should be added (allowed: before, into, after)
     * @return void
     * @todo define how this is to be handled
     */
    public function moveAction(NodeInterface $node, NodeInterface $targetNode, $position)
    {
        /*
        $node = $this->nodeOperations->move($node, $targetNode, $position);

        if (SecurityHelper::hasSafeMethod($this->request->getHttpRequest()) === false) {
            $this->persistenceManager->persistAll();
        }

        $data = ['newNodePath' => $node->getContextPath()];
        if ($node->getNodeType()->isOfType('Neos.Neos:Document')) {
            $data['nextUri'] = $this->getNodeUri($node);
        }
        $this->view->assign('value', ['data' => $data, 'success' => true]);
        */
    }

    /**
     * Move the given node before, into or after the target node
     * depending on the given position and renders it's content collection.
     *
     * @param NodeInterface $node The node to be moved
     * @param NodeInterface $targetNode The target node to be moved "to", see $position
     * @param string $position Where the node should be added in relation to $targetNode (allowed: before, into, after)
     * @param string $fusionPath The Fusion path of the collection
     * @return void
     * @todo define how this is to be handled
     */
    public function moveAndRenderAction(NodeInterface $node, NodeInterface $targetNode, $position, $fusionPath)
    {
        /*
        $this->nodeOperations->move($node, $targetNode, $position);
        $this->redirectToRenderNode($node, $fusionPath);
        */
    }

    /**
     * Copy $node before, into or after $targetNode
     *
     * We need to call persistAll() in order to return the nextUri.
     * We can't persist only the nodes in NodeDataRepository
     * because they might be connected to images / resources which need to be updated at the same time.
     *
     * @param NodeInterface $node The node to be copied
     * @param NodeInterface $targetNode The target node to be copied "to", see $position
     * @param string $position Where the node should be added in relation to $targetNode (allowed: before, into, after)
     * @param string $nodeName Optional node name (if empty random node name will be generated)
     * @return void
     * @todo define how this is to be handled
     */
    public function copyAction(NodeInterface $node, NodeInterface $targetNode, $position, $nodeName = null)
    {
        /*
        $copiedNode = $this->nodeOperations->copy($node, $targetNode, $position, $nodeName);

        if (SecurityHelper::hasSafeMethod($this->request->getHttpRequest()) === false) {
            $this->persistenceManager->persistAll();
        }

        $q = new FlowQuery([$copiedNode]);
        $closestDocumentNode = $q->closest('[instanceof Neos.Neos:Document]')->get(0);

        $requestData = [
            'nextUri' => $this->getNodeUri($closestDocumentNode),
            'newNodePath' => $copiedNode->getContextPath()
        ];

        if ($node->getNodeType()->isOfType('Neos.Neos:Document')) {
            $requestData['nodeUri'] = $this->getNodeUri($copiedNode);
        }

        $this->view->assign('value', ['data' => $requestData, 'success' => true]);
        */
    }

    /**
     * Copies the given node before, into or after the target node depending on the given position
     * and renders it's content collection.
     *
     * @param NodeInterface $node The node to be copied
     * @param NodeInterface $targetNode The target node to be copied "to", see $position
     * @param string $position Where the node should be added in relation to $targetNode (allowed: before, into, after)
     * @param string $nodeName Optional node name (if empty random node name will be generated)
     * @param string $fusionPath The Fusion path of the collection
     * @return void
     * @todo define how this is to be handled
     */
    public function copyAndRenderAction(
        NodeInterface $node,
        NodeInterface $targetNode,
        $position,
        $fusionPath,
        $nodeName = null
    ) {
        /*
        $copiedNode = $this->nodeOperations->copy($node, $targetNode, $position, $nodeName);
        $this->redirectToRenderNode($copiedNode, $fusionPath);
        */
    }

    /**
     * Updates the specified node.
     *
     * Returns the following data:
     *
     * - the (possibly changed) workspace name of the node
     * - the URI of the closest document node. If $node is a document node (f.e. a Page), the own URI is returned.
     *   This is important to handle renames of nodes correctly.
     *
     * Note: We do not call $nodeDataRepository->update() here, as ContentRepository has a stateful API for now.
     *       We need to call persistAll() in order to return the nextUri.
     *       We can't persist only the nodes in NodeDataRepository
     *       because they might be connected to images / resources which need to be updated at the same time.
     *
     * @param NodeInterface $node The node to be updated
     * @return void
     * @todo define how this is to be handled
     */
    public function updateAction(NodeInterface $node)
    {
        /*
        if (SecurityHelper::hasSafeMethod($this->request->getHttpRequest()) === false) {
            $this->persistenceManager->persistAll();
        }

        $q = new FlowQuery([$node]);
        $closestDocumentNode = $q->closest('[instanceof Neos.Neos:Document]')->get(0);
        $nextUri = $this->getNodeUri($closestDocumentNode);
        $this->view->assign('value', [
            'data' => [
                'workspaceNameOfNode' => $node->getWorkspace()->getName(),
                'labelOfNode' => $node->getLabel(),
                'nextUri' => $nextUri
            ],
            'success' => true
        ]);*/
    }

    /**
     * Updates the specified node and renders it's content collection.
     *
     * @param NodeInterface $node The node to be updated
     * @param string $fusionPath The Fusion path of the collection
     */
    public function updateAndRenderAction(NodeInterface $node, string $fusionPath): void
    {
        $this->redirectToRenderNode($node, $fusionPath);
    }

    /**
     * Deletes the specified node and all of its descendants
     */
    public function deleteAction(NodeInterface $node): void
    {
        $userIdentifier = $this->getCurrentUserIdentifier();
        if (!$userIdentifier instanceof UserIdentifier) {
            $this->throwStatus(400, 'Missing initiating user');
        }

        if (SecurityHelper::hasSafeMethod($this->request->getHttpRequest()) === false) {
            $this->persistenceManager->persistAll();
        }

        $closestDocumentNode = $this->findClosestDocumentNode($node);

        $this->nodeAggregateCommandHandler->handleRemoveNodeAggregate(new RemoveNodeAggregate(
            $node->getContentStreamIdentifier(),
            $node->getNodeAggregateIdentifier(),
            $node->getDimensionSpacePoint(),
            NodeVariantSelectionStrategy::STRATEGY_VIRTUAL_SPECIALIZATIONS,
            $userIdentifier
        ));

        $nextUri = $closestDocumentNode ? $this->getNodeUri($closestDocumentNode) : null;

        $this->view->assign('value', ['data' => ['nextUri' => $nextUri], 'success' => true]);
    }

    /**
     * Takes care of creating a redirect to properly render the collection the given node is in.
     */
    protected function redirectToRenderNode(NodeInterface $node, string $fusionPath): void
    {
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $node->getContentStreamIdentifier(),
            $node->getDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );

        $ancestor = $node;
        $closestContentCollection = null;
        $closestDocumentNode = null;
        while ($ancestor) {
            if ($ancestor->getNodeType()->isOfType('Neos.Neos:ContentCollection')) {
                $closestContentCollection = $ancestor;
            } elseif ($ancestor->getNodeType()->isOfType('Neos.Neos:Document')) {
                $closestDocumentNode = $ancestor;
            }
            $ancestor = $nodeAccessor->findParentNode($ancestor);
        }

        $this->redirect('show', 'Frontend\\Node', 'Neos.Neos', [
            'node' => $closestDocumentNode,
            '__nodeContextPath' => $closestContentCollection
                ? $this->nodeAddressFactory->createFromNode($closestContentCollection)->serializeForUri()
                : null,
            '__affectedNodeContextPath' => $this->nodeAddressFactory->createFromNode($node)->serializeForUri(),
            '__fusionPath' => $fusionPath
        ], 0, 303, 'html');
    }

    /**
     * Returns an array with the data needed by for example the frontend editing
     * link plugins to represent the passed Node instance.
     *
     * @param NodeInterface $node
     * @return array<string,mixed>
     */
    protected function processNodeForEditorPlugins(NodeInterface $node)
    {
        $nodeAddress = $this->nodeAddressFactory->createFromNode($node);

        return [
            'id' => $nodeAddress->serializeForUri(),
            'name' => $node->getLabel(),
            'url' => $this->getNodeUri($node),
            'type' => 'neos/internal-link'
        ];
    }

    private function getNodeUri(NodeInterface $node): string
    {
        return $this->uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(true)->uriFor(
            'show',
            ['node' => $node],
            'Frontend\Node',
            'Neos.Neos'
        );
    }

    private function findClosestDocumentNode(NodeInterface $node): ?NodeInterface
    {
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $node->getContentStreamIdentifier(),
            $node->getDimensionSpacePoint(),
            $node->getVisibilityConstraints()
        );

        $ancestor = $node;
        while ($ancestor instanceof NodeInterface) {
            if ($ancestor->getNodeType()->isOfType('Neos.Neos:Document')) {
                return $ancestor;
            }
            $ancestor = $nodeAccessor->findParentNode($ancestor);
        }

        return null;
    }
}
