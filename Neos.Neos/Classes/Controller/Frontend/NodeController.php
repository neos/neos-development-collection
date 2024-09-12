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

namespace Neos\Neos\Controller\Frontend;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\SubgraphCachingInMemory\SubgraphCachePool;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Utility\Now;
use Neos\Neos\Domain\Model\RenderingMode;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\RenderingModeService;
use Neos\Neos\FrontendRouting\Exception\InvalidShortcutException;
use Neos\Neos\FrontendRouting\Exception\NodeNotFoundException;
use Neos\Neos\FrontendRouting\NodeShortcutResolver;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\Utility\NodeTypeWithFallbackProvider;
use Neos\Neos\View\FusionView;

class NodeController extends ActionController
{
    use NodeTypeWithFallbackProvider;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected SubgraphCachePool $subgraphCachePool;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

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

    #[Flow\Inject]
    protected RenderingModeService $renderingModeService;

    #[Flow\InjectConfiguration(path: "frontend.shortcutRedirectHttpStatusCode", package: "Neos.Neos")]
    protected int $shortcutRedirectHttpStatusCode;

    #[Flow\Inject]
    protected NodeUriBuilderFactory $nodeUriBuilderFactory;

    /**
     * @param string $node
     * @throws NodeNotFoundException
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Flow\Session\Exception\SessionNotStartedException
     * @throws \Neos\Neos\Exception
     * @Flow\SkipCsrfProtection We need to skip CSRF protection here because this action could be called
     * with unsafe requests from widgets or plugins that are rendered on the node
     * - For those the CSRF token is validated on the sub-request, so it is safe to be skipped here
     */
    public function previewAction(string $node): void
    {
        // @todo add $renderingModeName as parameter and append it for successive links again as get parameter to node uris
        $renderingMode = $this->renderingModeService->findByCurrentUser();

        $visibilityConstraints = VisibilityConstraints::frontend();
        if ($this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')) {
            $visibilityConstraints = VisibilityConstraints::withoutRestrictions();
        }

        $siteDetectionResult = SiteDetectionResult::fromRequest($this->request->getHttpRequest());
        $contentRepository = $this->contentRepositoryRegistry->get($siteDetectionResult->contentRepositoryId);

        $nodeAddress = NodeAddress::fromJsonString($node);

        $subgraph = $contentRepository->getContentGraph($nodeAddress->workspaceName)->getSubgraph(
            $nodeAddress->dimensionSpacePoint,
            $visibilityConstraints
        );

        $nodeInstance = $subgraph->findNodeById($nodeAddress->aggregateId);

        if (is_null($nodeInstance)) {
            throw new NodeNotFoundException(
                'The requested node does not exist or isn\'t accessible to the current user',
                1430218623
            );
        }

        $site = $subgraph->findClosestNode($nodeAddress->aggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE));
        if ($site === null) {
            throw new NodeNotFoundException("TODO: SITE NOT FOUND; should not happen (for identity " . $nodeAddress->toJson());
        }

        $this->fillCacheWithContentNodes($nodeAddress->aggregateId, $subgraph);

        if (
            $this->getNodeType($nodeInstance)->isOfType(NodeTypeNameFactory::NAME_SHORTCUT)
            && !$renderingMode->isEdit
            && $nodeAddress->workspaceName->isLive() // shortcuts are only resolvable for the live workspace
        ) {
            $this->handleShortcutNode($nodeAddress);
        }

        $this->view->setOption('renderingModeName', $renderingMode->name);

        $this->view->assignMultiple([
            'value' => $nodeInstance,
            'site' => $site,
        ]);

        if ($renderingMode->isEdit) {
            $this->overrideViewVariablesFromInternalArguments();
            $this->response->setHttpHeader('Cache-Control', 'no-cache');
            if (!$this->view->canRenderWithNodeAndPath()) {
                $this->view->setFusionPath('rawContent');
            }
        }
    }

    /**
     * Initializes the view with the necessary parameters encoded in the given NodeAddress
     *
     * @param string $node Legacy name for backwards compatibility of route components
     * @throws NodeNotFoundException
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Flow\Session\Exception\SessionNotStartedException
     * @throws \Neos\Neos\Exception
     * @Flow\SkipCsrfProtection We need to skip CSRF protection here because this action could be called
     * with unsafe requests from widgets or plugins that are rendered on the node
     * - For those the CSRF token is validated on the sub-request, so it is safe to be skipped here
     */
    public function showAction(string $node): void
    {
        $nodeAddress = NodeAddress::fromJsonString($node);
        unset($node);

        if (!$nodeAddress->workspaceName->isLive()) {
            throw new NodeNotFoundException('The requested node isn\'t accessible to the current user', 1430218623);
        }

        $contentRepository = $this->contentRepositoryRegistry->get($nodeAddress->contentRepositoryId);
        $subgraph = $contentRepository->getContentGraph($nodeAddress->workspaceName)->getSubgraph(
            $nodeAddress->dimensionSpacePoint,
            VisibilityConstraints::frontend()
        );

        $nodeInstance = $subgraph->findNodeById($nodeAddress->aggregateId);
        if ($nodeInstance === null) {
            throw new NodeNotFoundException(sprintf('The cached node address for this uri could not be resolved. Possibly you have to flush the "Flow_Mvc_Routing_Route" cache. %s', $nodeAddress->toJson()), 1707300738);
        }

        $site = $subgraph->findClosestNode($nodeAddress->aggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE));
        if ($site === null) {
            throw new NodeNotFoundException(sprintf('The site node of %s could not be resolved.', $nodeAddress->toJson()), 1707300861);
        }

        $this->fillCacheWithContentNodes($nodeAddress->aggregateId, $subgraph);

        if ($this->getNodeType($nodeInstance)->isOfType(NodeTypeNameFactory::NAME_SHORTCUT)) {
            $this->handleShortcutNode($nodeAddress);
        }

        $this->view->setOption('renderingModeName', RenderingMode::FRONTEND);

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
            assert(is_string($nodeContextPath));
            $node = $this->propertyMapper->convert($nodeContextPath, Node::class);
            if (!$node instanceof Node) {
                throw new NodeNotFoundException(sprintf(
                    'The node with context path "%s" could not be resolved',
                    $nodeContextPath
                ), 1437051934);
            }
            $this->view->assign('value', $node);
        }

        if (($affectedNodeContextPath = $this->request->getInternalArgument('__affectedNodeContextPath')) !== null) {
            assert(is_string($affectedNodeContextPath));
            $this->response->setHttpHeader('X-Neos-AffectedNodePath', $affectedNodeContextPath);
        }

        if (($fusionPath = $this->request->getInternalArgument('__fusionPath')) !== null) {
            assert(is_string($fusionPath));
            $this->view->setFusionPath($fusionPath);
        }
    }

    /**
     * Handles redirects to shortcut targets of nodes in the live workspace.
     *
     * @throws NodeNotFoundException
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     */
    protected function handleShortcutNode(NodeAddress $nodeAddress): void
    {
        try {
            $resolvedTarget = $this->nodeShortcutResolver->resolveShortcutTarget($nodeAddress);
        } catch (InvalidShortcutException $e) {
            throw new NodeNotFoundException(sprintf(
                'The shortcut node target of node %s could not be resolved: %s',
                $nodeAddress->toJson(),
                $e->getMessage()
            ), 1430218730, $e);
        }
        if ($resolvedTarget instanceof NodeAddress) {
            if ($nodeAddress->equals($resolvedTarget)) {
                return;
            }
            try {
                $resolvedUri = $this->nodeUriBuilderFactory->forActionRequest($this->request)
                    ->uriFor($nodeAddress);
            } catch (NoMatchingRouteException $e) {
                throw new NodeNotFoundException(sprintf(
                    'The shortcut node target of node %s could not be resolved: %s',
                    $nodeAddress->toJson(),
                    $e->getMessage()
                ), 1599670695, $e);
            }
        } else {
            $resolvedUri = $resolvedTarget;
        }

        $this->redirectToUri($resolvedUri, statusCode: $this->shortcutRedirectHttpStatusCode);
    }

    private function fillCacheWithContentNodes(
        NodeAggregateId $nodeAggregateId,
        ContentSubgraphInterface $subgraph,
    ): void {
        $subtree = $subgraph->findSubtree(
            $nodeAggregateId,
            FindSubtreeFilter::create(nodeTypes: '!' . NodeTypeNameFactory::NAME_DOCUMENT, maximumLevels: 20)
        );
        if ($subtree === null) {
            return;
        }

        $currentDocumentNode = $subtree->node;

        foreach ($subtree->children as $childSubtree) {
            self::fillCacheInternal(
                $childSubtree,
                $currentDocumentNode,
                $this->subgraphCachePool,
                $subgraph
            );
        }
    }

    private static function fillCacheInternal(
        Subtree $subtree,
        Node $parentNode,
        SubgraphCachePool $subgraphCachePool,
        ContentSubgraphInterface $subgraph,
    ): void {
        $node = $subtree->node;

        $parentNodeIdentifierByChildNodeIdentifierCache
            = $subgraphCachePool->getParentNodeIdByChildNodeIdCache($subgraph);
        $namedChildNodeByNodeIdentifierCache = $subgraphCachePool->getNamedChildNodeByNodeIdCache($subgraph);
        $allChildNodesByNodeIdentifierCache = $subgraphCachePool->getAllChildNodesByNodeIdCache($subgraph);
        if ($node->name !== null) {
            $namedChildNodeByNodeIdentifierCache->add(
                $parentNode->aggregateId,
                $node->name,
                $node
            );
        } else {
            // @todo use node aggregate identifier instead?
        }

        $parentNodeIdentifierByChildNodeIdentifierCache->add(
            $node->aggregateId,
            $parentNode->aggregateId
        );

        $allChildNodes = [];
        foreach ($subtree->children as $childSubtree) {
            self::fillCacheInternal($childSubtree, $node, $subgraphCachePool, $subgraph);
            $childNode = $childSubtree->node;
            $allChildNodes[] = $childNode;
        }
        // TODO Explain why this is safe (Content can not contain other documents)
        $allChildNodesByNodeIdentifierCache->add(
            $node->aggregateId,
            null,
            Nodes::fromArray($allChildNodes)
        );
    }
}
