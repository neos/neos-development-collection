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

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\ContentSubgraph;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreesFilter;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\NodeType\NodeTypeConstraintParser;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraintsWithSubNodeTypes;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\InMemoryCache;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Neos\FrontendRouting\Exception\NodeNotFoundException;
use Neos\Neos\FrontendRouting\NodeShortcutResolver;
use Neos\Neos\Domain\Service\NodeSiteResolvingService;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Neos\FrontendRouting\Exception\InvalidShortcutException;
use Neos\Neos\FrontendRouting\NodeUriBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Utility\Now;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\View\FusionView;

/**
 * Event Sourced Node Controller; as Replacement of NodeController
 */
class NodeController extends ActionController
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

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

    /**
     * @Flow\Inject
     * @var NodeSiteResolvingService
     */
    protected $nodeSiteResolvingService;

    /**
     * @param string $node Legacy name for backwards compatibility of route components
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
    public function previewAction(string $node): void
    {
        $visibilityConstraints = VisibilityConstraints::frontend();
        if ($this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')) {
            $visibilityConstraints = VisibilityConstraints::withoutRestrictions();
        }

        $siteDetectionResult = SiteDetectionResult::fromRequest($this->request->getHttpRequest());
        $contentRepository = $this->contentRepositoryRegistry->get($siteDetectionResult->contentRepositoryId);

        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromUriString($node);

        $subgraph = $contentRepository->getContentGraph()->getSubgraph(
            $nodeAddress->contentStreamId,
            $nodeAddress->dimensionSpacePoint,
            $visibilityConstraints
        );

        $site = $this->nodeSiteResolvingService->findSiteNodeForNodeAddress(
            $nodeAddress,
            $siteDetectionResult->contentRepositoryId
        );
        if ($site === null) {
            throw new NodeNotFoundException("TODO: SITE NOT FOUND; should not happen (for address " . $nodeAddress);
        }

        $this->fillCacheWithContentNodes($nodeAddress->nodeAggregateId, $subgraph, $contentRepository);

        $nodeInstance = $subgraph->findNodeById($nodeAddress->nodeAggregateId);

        if (is_null($nodeInstance)) {
            throw new NodeNotFoundException(
                'The requested node does not exist or isn\'t accessible to the current user',
                1430218623
            );
        }

        if ($nodeInstance->nodeType->isOfType('Neos.Neos:Shortcut') && $nodeAddress->isInLiveWorkspace()) {
            $this->handleShortcutNode($nodeAddress, $contentRepository);
        }

        $this->view->assignMultiple([
            'value' => $nodeInstance,
            'site' => $site,
        ]);

        if (!$nodeAddress->isInLiveWorkspace()) {
            $this->overrideViewVariablesFromInternalArguments();
            $this->response->setHttpHeader('Cache-Control', 'no-cache');
            if (!$this->view->canRenderWithNodeAndPath()) {
                $this->view->setFusionPath('rawContent');
            }

            if ($this->session->isStarted()) {
                $this->session->putData('lastVisitedNode', $nodeAddress);
            }
        }
    }

    /**
     * Initializes the view with the necessary parameters encoded in the given NodeAddress
     *
     * @param string $node Legacy name for backwards compatibility of route components
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
    public function showAction(string $node, bool $showInvisible = false): void
    {
        $siteDetectionResult = SiteDetectionResult::fromRequest($this->request->getHttpRequest());
        $contentRepository = $this->contentRepositoryRegistry->get($siteDetectionResult->contentRepositoryId);

        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromUriString($node);
        if (!$nodeAddress->isInLiveWorkspace()) {
            throw new NodeNotFoundException('The requested node isn\'t accessible to the current user', 1430218623);
        }

        $visibilityConstraints = VisibilityConstraints::frontend();
        if ($showInvisible && $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')) {
            $visibilityConstraints = VisibilityConstraints::withoutRestrictions();
        }

        $subgraph = $contentRepository->getContentGraph()->getSubgraph(
            $nodeAddress->contentStreamId,
            $nodeAddress->dimensionSpacePoint,
            $visibilityConstraints
        );

        $site = $this->nodeSiteResolvingService->findSiteNodeForNodeAddress(
            $nodeAddress,
            $siteDetectionResult->contentRepositoryId
        );
        if ($site === null) {
            throw new NodeNotFoundException("TODO: SITE NOT FOUND; should not happen (for address " . $nodeAddress);
        }

        $this->fillCacheWithContentNodes($nodeAddress->nodeAggregateId, $subgraph, $contentRepository);

        $nodeInstance = $subgraph->findNodeById($nodeAddress->nodeAggregateId);

        if (is_null($nodeInstance)) {
            throw new NodeNotFoundException('The requested node does not exist', 1596191460);
        }

        if ($nodeInstance->nodeType->isOfType('Neos.Neos:Shortcut')) {
            $this->handleShortcutNode($nodeAddress, $contentRepository);
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
            $node = $this->propertyMapper->convert((string)$nodeContextPath, Node::class);
            if (!$node instanceof Node) {
                throw new NodeNotFoundException(sprintf(
                    'The node with context path "%s" could not be resolved',
                    (string)$nodeContextPath
                ), 1437051934);
            }
            $this->view->assign('value', $node);
        }

        if (($affectedNodeContextPath = $this->request->getInternalArgument('__affectedNodeContextPath')) !== null) {
            $this->response->setHttpHeader('X-Neos-AffectedNodePath', (string)$affectedNodeContextPath);
        }

        if (($fusionPath = $this->request->getInternalArgument('__fusionPath')) !== null) {
            $this->view->setFusionPath((string)$fusionPath);
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
    protected function handleShortcutNode(NodeAddress $nodeAddress, ContentRepository $contentRepository): void
    {
        try {
            $resolvedTarget = $this->nodeShortcutResolver->resolveShortcutTarget($nodeAddress, $contentRepository);
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

    private function fillCacheWithContentNodes(
        NodeAggregateId $nodeAggregateIdentifier,
        ContentSubgraphInterface $subgraph,
        ContentRepository $contentRepository
    ): void {
        if (!$subgraph instanceof ContentSubgraph) {
            // wrong subgraph implementation
            return;
        }
        $inMemoryCache = $subgraph->inMemoryCache;

        $subtree = $subgraph->findSubtrees(
            NodeAggregateIds::fromArray([$nodeAggregateIdentifier]),
            FindSubtreesFilter::nodeTypeConstraints('!Neos.Neos:Document')
                ->withMaximumLevels(20)
        )->first();
        if (is_null($subtree)) {
            return;
        }

        $nodePathCache = $inMemoryCache->getNodePathCache();

        $currentDocumentNode = $subtree->node;

        $nodePathOfDocumentNode = $subgraph->findNodePath($currentDocumentNode->nodeAggregateId);

        $nodePathCache->add($currentDocumentNode->nodeAggregateId, $nodePathOfDocumentNode);

        foreach ($subtree->children as $childSubtree) {
            self::fillCacheInternal(
                $childSubtree,
                $currentDocumentNode,
                $nodePathOfDocumentNode,
                $inMemoryCache
            );
        }
    }

    private static function fillCacheInternal(
        Subtree $subtree,
        Node $parentNode,
        NodePath $parentNodePath,
        InMemoryCache $inMemoryCache
    ): void {
        $node = $subtree->node;

        $parentNodeIdentifierByChildNodeIdentifierCache
            = $inMemoryCache->getParentNodeIdByChildNodeIdCache();
        $namedChildNodeByNodeIdentifierCache = $inMemoryCache->getNamedChildNodeByNodeIdCache();
        $allChildNodesByNodeIdentifierCache = $inMemoryCache->getAllChildNodesByNodeIdCache();
        $nodePathCache = $inMemoryCache->getNodePathCache();
        if ($node->nodeName !== null) {
            $nodePath = $parentNodePath->appendPathSegment($node->nodeName);
            $nodePathCache->add($node->nodeAggregateId, $nodePath);
            $namedChildNodeByNodeIdentifierCache->add(
                $parentNode->nodeAggregateId,
                $node->nodeName,
                $node
            );
        } else {
            // @todo use node aggregate identifier instead?
        }

        $parentNodeIdentifierByChildNodeIdentifierCache->add(
            $node->nodeAggregateId,
            $parentNode->nodeAggregateId
        );

        $allChildNodes = [];
        foreach ($subtree->children as $childSubtree) {
            if (isset($nodePath)) {
                self::fillCacheInternal($childSubtree, $node, $nodePath, $inMemoryCache);
            }
            $childNode = $childSubtree->node;
            $allChildNodes[] = $childNode;
        }

        // TODO Explain why this is safe (Content can not contain other documents)
        $allChildNodesByNodeIdentifierCache->add(
            $node->nodeAggregateId,
            NodeTypeConstraintsWithSubNodeTypes::allowAll(),
            $allChildNodes
        );
    }
}
