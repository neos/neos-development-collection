<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedFrontController;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;
use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\ContentSubgraph\SubtreeInterface;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\InMemoryCache;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeSiteResolvingService;
use Neos\EventSourcedNeosAdjustments\Domain\Service\NodeShortcutResolver;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Exception\InvalidShortcutException;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\NodeUriBuilder;
use Neos\EventSourcedNeosAdjustments\View\FusionView;
use Neos\Flow\Annotations as Flow;

use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Utility\Now;
use Neos\Neos\Controller\Exception\NodeNotFoundException;

/**
 * Event Sourced Node Controller; as Replacement of NodeController
 */
class EventSourcedNodeController extends ActionController
{
    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * @Flow\Inject(lazy=false)
     * @var Now
     */
    protected $now;

    /**
     * @Flow\Inject
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var SessionInterface
     */
    protected $session;

    /**
     * @Flow\Inject
     * @var NodeShortcutResolver
     */
    protected $nodeShortcutResolver;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @var string
     */
    protected $defaultViewObjectName = FusionView::class;

    /**
     * @var FusionView
     */
    protected $view;

    /**
     * @Flow\Inject
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

    /**
     * @Flow\Inject
     * @var NodeSiteResolvingService
     */
    protected $nodeSiteResolvingService;

    /**
     * @param NodeAddress $node Legacy name for backwards compatibility of route components
     * @throws NodeNotFoundException
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     * @throws \Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Flow\Session\Exception\SessionNotStartedException
     * @throws \Neos\Neos\Exception
     * @Flow\SkipCsrfProtection We need to skip CSRF protection here because this action could be called
     * with unsafe requests from widgets or plugins that are rendered on the node
     * - For those the CSRF token is validated on the sub-request, so it is safe to be skipped here
     */
    public function previewAction(NodeAddress $node)
    {
        $nodeAddress = $node;

        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
            $nodeAddress->contentStreamIdentifier,
            $nodeAddress->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        if ($subgraph === null) {
            throw new NodeNotFoundException("TODO: SUBGRAPH NOT FOUND; should not happen (for address " . $nodeAddress);
        }

        $site = $this->nodeSiteResolvingService->findSiteNodeForNodeAddress($nodeAddress);
        if ($site === null) {
            throw new NodeNotFoundException("TODO: SITE NOT FOUND; should not happen (for address " . $nodeAddress);
        }

        $this->fillCacheWithContentNodes($subgraph, $nodeAddress);

        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $nodeAddress->contentStreamIdentifier,
            $nodeAddress->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        $nodeInstance = $nodeAccessor->findByIdentifier($nodeAddress->nodeAggregateIdentifier);

        if (is_null($nodeInstance)) {
            throw new NodeNotFoundException(
                'The requested node does not exist or isn\'t accessible to the current user',
                1430218623
            );
        }


        $this->view->assignMultiple([
            'value' => $nodeInstance,
            'site' => $site,
        ]);

        $this->overrideViewVariablesFromInternalArguments();
        $this->response->setHttpHeader('Cache-Control', 'no-cache');
        if (!$this->view->canRenderWithNodeAndPath()) {
            $this->view->setFusionPath('rawContent');
        }

        if ($this->session->isStarted()) {
            $this->session->putData('lastVisitedNode', $nodeAddress);
        }
    }

    /**
     * Initializes the view with the necessary parameters encoded in the given NodeAddress
     *
     * @param NodeAddress $node Legacy name for backwards compatibility of route components
     * @throws NodeNotFoundException
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     * @throws \Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Flow\Session\Exception\SessionNotStartedException
     * @throws \Neos\Neos\Exception
     * @Flow\SkipCsrfProtection We need to skip CSRF protection here because this action could be called
     * with unsafe requests from widgets or plugins that are rendered on the node
     * - For those the CSRF token is validated on the sub-request, so it is safe to be skipped here
     */
    public function showAction(NodeAddress $node)
    {
        $nodeAddress = $node;
        if (!$nodeAddress->isInLiveWorkspace()) {
            throw new NodeNotFoundException('The requested node isn\'t accessible to the current user', 1430218623);
        }

        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
            $nodeAddress->contentStreamIdentifier,
            $nodeAddress->dimensionSpacePoint,
            VisibilityConstraints::frontend()
        );
        if ($subgraph === null) {
            throw new NodeNotFoundException("TODO: SUBGRAPH NOT FOUND; should not happen (for address " . $nodeAddress);
        }

        $site = $this->nodeSiteResolvingService->findSiteNodeForNodeAddress($nodeAddress);
        if ($site === null) {
            throw new NodeNotFoundException("TODO: SITE NOT FOUND; should not happen (for address " . $nodeAddress);
        }

        $this->fillCacheWithContentNodes($subgraph, $nodeAddress);

        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $nodeAddress->contentStreamIdentifier,
            $nodeAddress->dimensionSpacePoint,
            VisibilityConstraints::frontend()
        );
        $nodeInstance = $nodeAccessor->findByIdentifier($nodeAddress->nodeAggregateIdentifier);

        if (is_null($nodeInstance)) {
            throw new NodeNotFoundException('The requested node does not exist', 1596191460);
        }

        if ($nodeInstance->getNodeType()->isOfType('Neos.Neos:Shortcut')) {
            $this->handleShortcutNode($nodeAddress);
        }

        $this->view->assignMultiple([
            'value' => $nodeInstance,
            'site' => $site,
        ]);
    }

    /**
     * Checks if the optionally given node context path, affected node context path and Fusion path are set
     * and overrides the rendering to use those. Will also add a "X-Neos-AffectedNodePath" header in case the
     * actually affected node is different from the one routing resolved.
     * This is used in out of band rendering for the backend.
     *
     * @return void
     * @throws NodeNotFoundException
     */
    protected function overrideViewVariablesFromInternalArguments()
    {
        if (($nodeContextPath = $this->request->getInternalArgument('__nodeContextPath')) !== null) {
            $node = $this->propertyMapper->convert($nodeContextPath, NodeInterface::class);
            if (!$node instanceof NodeInterface) {
                throw new NodeNotFoundException(sprintf(
                    'The node with context path "%s" could not be resolved',
                    $nodeContextPath
                ), 1437051934);
            }
            $this->view->assign('value', $node);
        }

        if (($affectedNodeContextPath = $this->request->getInternalArgument('__affectedNodeContextPath')) !== null) {
            $this->response->setHttpHeader('X-Neos-AffectedNodePath', $affectedNodeContextPath);
        }

        if (($fusionPath = $this->request->getInternalArgument('__fusionPath')) !== null) {
            $this->view->setFusionPath($fusionPath);
        }
    }

    /**
     * Handles redirects to shortcut targets in live rendering.
     *
     * @param NodeAddress $nodeAddress
     * @throws NodeNotFoundException
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     * @throws \Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException
     */
    protected function handleShortcutNode(NodeAddress $nodeAddress): void
    {
        try {
            $resolvedTarget = $this->nodeShortcutResolver->resolveShortcutTarget($nodeAddress);
        } catch (InvalidShortcutException $e) {
            throw new NodeNotFoundException(sprintf(
                'The shortcut node target of node "%s" could not be resolved: %s',
                $nodeAddress,
                $e->getMessage()
            ), 1430218730, $e);
        }
        if ($resolvedTarget instanceof NodeAddress) {
            if ($resolvedTarget === $nodeAddress) {
                return;
            }
            try {
                $resolvedUri = NodeUriBuilder::fromRequest($this->request)->uriFor($nodeAddress);
            } catch (NoMatchingRouteException $e) {
                throw new NodeNotFoundException(sprintf(
                    'The shortcut node target of node "%s" could not be resolved: %s',
                    $nodeAddress,
                    $e->getMessage()
                ), 1599670695, $e);
            }
        } else {
            $resolvedUri = $resolvedTarget;
        }
        $this->redirectToUri($resolvedUri);
    }

    private function fillCacheWithContentNodes(ContentSubgraphInterface $subgraph, NodeAddress $nodeAddress)
    {
        $subtree = $subgraph->findSubtrees(
            [$nodeAddress->nodeAggregateIdentifier],
            10,
            $this->nodeTypeConstraintFactory->parseFilterString('!Neos.Neos:Document')
        );
        $subtree = $subtree->getChildren()[0];

        $nodePathCache = $subgraph->getInMemoryCache()->getNodePathCache();

        $currentDocumentNode = $subtree->getNode();
        $nodePathOfDocumentNode = $subgraph->findNodePath($currentDocumentNode->getNodeAggregateIdentifier());

        $nodePathCache->add($currentDocumentNode->getNodeAggregateIdentifier(), $nodePathOfDocumentNode);

        foreach ($subtree->getChildren() as $childSubtree) {
            self::fillCacheInternal($childSubtree, $currentDocumentNode, $nodePathOfDocumentNode, $subgraph->getInMemoryCache());
        }
    }

    private static function fillCacheInternal(
        SubtreeInterface $subtree,
        NodeInterface $parentNode,
        NodePath $parentNodePath,
        InMemoryCache $inMemoryCache
    ) {
        $parentNodeIdentifierByChildNodeIdentifierCache
            = $inMemoryCache->getParentNodeIdentifierByChildNodeIdentifierCache();
        $namedChildNodeByNodeIdentifierCache = $inMemoryCache->getNamedChildNodeByNodeIdentifierCache();
        $allChildNodesByNodeIdentifierCache = $inMemoryCache->getAllChildNodesByNodeIdentifierCache();
        $nodePathCache = $inMemoryCache->getNodePathCache();

        $node = $subtree->getNode();
        if ($node->getNodeName() !== null) {
            $nodePath = $parentNodePath->appendPathSegment($node->getNodeName());
            $nodePathCache->add($node->getNodeAggregateIdentifier(), $nodePath);
            $namedChildNodeByNodeIdentifierCache->add(
                $parentNode->getNodeAggregateIdentifier(),
                $node->getNodeName(),
                $node
            );
        }

        $parentNodeIdentifierByChildNodeIdentifierCache->add(
            $node->getNodeAggregateIdentifier(),
            $parentNode->getNodeAggregateIdentifier()
        );

        $allChildNodes = [];
        foreach ($subtree->getChildren() as $childSubtree) {
            self::fillCacheInternal($childSubtree, $node, $nodePath, $inMemoryCache);
            $allChildNodes[] = $childSubtree->getNode();
        }

        // TODO Explain why this is safe (Content can not contain other documents)
        $allChildNodesByNodeIdentifierCache->add($node->getNodeAggregateIdentifier(), null, $allChildNodes);
    }
}
