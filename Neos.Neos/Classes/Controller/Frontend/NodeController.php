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
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintParser;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifiers;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\Feature\SubtreeInterface;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\Projection\ContentGraph\ContentSubgraphInterface;
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
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

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
        $contentRepository = $this->contentRepositoryRegistry->get($siteDetectionResult->contentRepositoryIdentifier);

        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromUriString($node);

        $subgraph = $contentRepository->getContentGraph()->getSubgraphByIdentifier(
            $nodeAddress->contentStreamIdentifier,
            $nodeAddress->dimensionSpacePoint,
            $visibilityConstraints
        );

        $site = $this->nodeSiteResolvingService->findSiteNodeForNodeAddress(
            $nodeAddress,
            $siteDetectionResult->contentRepositoryIdentifier
        );
        if ($site === null) {
            throw new NodeNotFoundException("TODO: SITE NOT FOUND; should not happen (for address " . $nodeAddress);
        }

        $this->fillCacheWithContentNodes($nodeAddress->nodeAggregateIdentifier, $subgraph, $contentRepository);

        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            new ContentSubgraphIdentity(
                $siteDetectionResult->contentRepositoryIdentifier,
                $nodeAddress->contentStreamIdentifier,
                $nodeAddress->dimensionSpacePoint,
                $visibilityConstraints
            )
        );
        $nodeInstance = $nodeAccessor->findByIdentifier($nodeAddress->nodeAggregateIdentifier);

        if (is_null($nodeInstance)) {
            throw new NodeNotFoundException(
                'The requested node does not exist or isn\'t accessible to the current user',
                1430218623
            );
        }

        if ($nodeInstance->getNodeType()->isOfType('Neos.Neos:Shortcut') && $nodeAddress->isInLiveWorkspace()) {
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
        $contentRepository = $this->contentRepositoryRegistry->get($siteDetectionResult->contentRepositoryIdentifier);

        $nodeAddress = NodeAddressFactory::create($contentRepository)->createFromUriString($node);
        if (!$nodeAddress->isInLiveWorkspace()) {
            throw new NodeNotFoundException('The requested node isn\'t accessible to the current user', 1430218623);
        }

        $visibilityConstraints = VisibilityConstraints::frontend();
        if ($showInvisible && $this->privilegeManager->isPrivilegeTargetGranted('Neos.Neos:Backend.GeneralAccess')) {
            $visibilityConstraints = VisibilityConstraints::withoutRestrictions();
        }

        $subgraph = $contentRepository->getContentGraph()->getSubgraphByIdentifier(
            $nodeAddress->contentStreamIdentifier,
            $nodeAddress->dimensionSpacePoint,
            $visibilityConstraints
        );

        $site = $this->nodeSiteResolvingService->findSiteNodeForNodeAddress(
            $nodeAddress,
            $siteDetectionResult->contentRepositoryIdentifier
        );
        if ($site === null) {
            throw new NodeNotFoundException("TODO: SITE NOT FOUND; should not happen (for address " . $nodeAddress);
        }

        $this->fillCacheWithContentNodes($nodeAddress->nodeAggregateIdentifier, $subgraph, $contentRepository);

        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            new ContentSubgraphIdentity(
                $siteDetectionResult->contentRepositoryIdentifier,
                $nodeAddress->contentStreamIdentifier,
                $nodeAddress->dimensionSpacePoint,
                $visibilityConstraints
            )
        );
        $nodeInstance = $nodeAccessor->findByIdentifier($nodeAddress->nodeAggregateIdentifier);

        if (is_null($nodeInstance)) {
            throw new NodeNotFoundException('The requested node does not exist', 1596191460);
        }

        if ($nodeInstance->getNodeType()->isOfType('Neos.Neos:Shortcut')) {
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
            $node = $this->propertyMapper->convert((string)$nodeContextPath, NodeInterface::class);
            if (!$node instanceof NodeInterface) {
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
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ContentSubgraphInterface $subgraph,
        ContentRepository $contentRepository
    ): void {
        if (!$subgraph instanceof ContentSubgraph) {
            // wrong subgraph implementation
            return;
        }
        $inMemoryCache = $subgraph->inMemoryCache;

        $subtree = $subgraph->findSubtrees(
            NodeAggregateIdentifiers::fromArray([$nodeAggregateIdentifier]),
            10,
            NodeTypeConstraintParser::create($contentRepository->getNodeTypeManager())
                ->parseFilterString('!Neos.Neos:Document')
        );
        $subtree = $subtree->getChildren()[0];

        $nodePathCache = $inMemoryCache->getNodePathCache();

        $currentDocumentNode = $subtree->getNode();
        if (is_null($currentDocumentNode)) {
            return;
        }
        $nodePathOfDocumentNode = $subgraph->findNodePath($currentDocumentNode->getNodeAggregateIdentifier());

        $nodePathCache->add($currentDocumentNode->getNodeAggregateIdentifier(), $nodePathOfDocumentNode);

        foreach ($subtree->getChildren() as $childSubtree) {
            self::fillCacheInternal(
                $childSubtree,
                $currentDocumentNode,
                $nodePathOfDocumentNode,
                $inMemoryCache
            );
        }
    }

    private static function fillCacheInternal(
        SubtreeInterface $subtree,
        NodeInterface $parentNode,
        NodePath $parentNodePath,
        InMemoryCache $inMemoryCache
    ): void {
        $node = $subtree->getNode();
        if (is_null($node)) {
            return;
        }

        $parentNodeIdentifierByChildNodeIdentifierCache
            = $inMemoryCache->getParentNodeIdentifierByChildNodeIdentifierCache();
        $namedChildNodeByNodeIdentifierCache = $inMemoryCache->getNamedChildNodeByNodeIdentifierCache();
        $allChildNodesByNodeIdentifierCache = $inMemoryCache->getAllChildNodesByNodeIdentifierCache();
        $nodePathCache = $inMemoryCache->getNodePathCache();
        if ($node->getNodeName() !== null) {
            $nodePath = $parentNodePath->appendPathSegment($node->getNodeName());
            $nodePathCache->add($node->getNodeAggregateIdentifier(), $nodePath);
            $namedChildNodeByNodeIdentifierCache->add(
                $parentNode->getNodeAggregateIdentifier(),
                $node->getNodeName(),
                $node
            );
        } else {
            // @todo use node aggregate identifier instead?
        }

        $parentNodeIdentifierByChildNodeIdentifierCache->add(
            $node->getNodeAggregateIdentifier(),
            $parentNode->getNodeAggregateIdentifier()
        );

        $allChildNodes = [];
        foreach ($subtree->getChildren() as $childSubtree) {
            if (isset($nodePath)) {
                self::fillCacheInternal($childSubtree, $node, $nodePath, $inMemoryCache);
            }
            $childNode = $childSubtree->getNode();
            if (!is_null($childNode)) {
                $allChildNodes[] = $childNode;
            }
        }

        // TODO Explain why this is safe (Content can not contain other documents)
        $allChildNodesByNodeIdentifierCache->add(
            $node->getNodeAggregateIdentifier(),
            null,
            $allChildNodes
        );
    }
}
