<?php
namespace Neos\Neos\Controller\Frontend;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Parameters\ContextParameters;
use Neos\ContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Utility\Now;
use Neos\Neos\Controller\Exception\NodeNotFoundException;
use Neos\Neos\Controller\Exception\UnresolvableShortcutException;
use Neos\Neos\Domain\Context\Content\ContentQuery;
use Neos\Neos\Domain\Projection\Site\Site;
use Neos\Neos\Domain\Projection\Site\SiteFinder;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\NodeShortcutResolver;
use Neos\Neos\Domain\ValueObject\NodeName;
use Neos\Neos\View\FusionView;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * Controller for displaying nodes in the frontend
 *
 * @Flow\Scope("singleton")
 */
class NodeController extends ActionController
{
    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var SiteFinder
     */
    protected $siteFinder;

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
     * Initializes the view with the necessary parameters encoded in the given ContentQuery
     *
     * @param ContentQuery $node Legacy name for backwards compatibility of route components
     * @throws NodeNotFoundException
     * @throws UnresolvableShortcutException
     * @throws \Neos\Flow\Session\Exception\SessionNotStartedException
     * @throws \Neos\Neos\Exception
     * @Flow\SkipCsrfProtection We need to skip CSRF protection here because this action could be called with unsafe requests from widgets or plugins that are rendered on the node - For those the CSRF token is validated on the sub-request, so it is safe to be skipped here
     */
    public function showAction(ContentQuery $node)
    {
        $contentQuery = $node;
        $workspace = $this->workspaceFinder->findOneByName($contentQuery->getWorkspaceName());
        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
            $workspace->getCurrentContentStreamIdentifier(),
            $contentQuery->getDimensionSpacePoint()
        );
        $inBackend = !$workspace->getWorkspaceName()->isLive();

        $contextParameters = $this->createContextParameters($inBackend);

        $siteNode = $subgraph->findNodeByNodeAggregateIdentifier($contentQuery->getSiteIdentifier());
        // TODO CACHE
        $site = $this->siteFinder->findOneByNodeName(new NodeName($siteNode->getName()));

        $contentContext = $this->createContentContext($contentQuery, $subgraph, $contextParameters, $site);

        $siteNode = $subgraph->findNodeByNodeAggregateIdentifier($contentQuery->getSiteIdentifier(), $contentContext);
        $node = $subgraph->findNodeByNodeAggregateIdentifier($contentQuery->getNodeAggregateIdentifier(), $contentContext);
        if (is_null($node)) {
            throw new NodeNotFoundException('The requested node does not exist or isn\'t accessible to the current user', 1430218623);
        }

        if ($node->getNodeType()->isOfType('Neos.Neos:Shortcut') && !$inBackend) {
            $this->handleShortcutNode($node);
        }

        $this->view->assignMultiple([
            'value' => $node,
            'subgraph' => $subgraph,
            'site' => $siteNode,
            'contextParameters' => $contextParameters,
            'workspaceName' => $workspace->getWorkspaceName()
        ]);

        if ($inBackend) {
            $this->overrideViewVariablesFromInternalArguments();
            $this->response->setHeader('Cache-Control', 'no-cache');
            if (!$this->view->canRenderWithNodeAndPath()) {
                $this->view->setFusionPath('rawContent');
            }
        }

        if ($this->session->isStarted() && $inBackend) {
            $this->session->putData('lastVisitedNode', $node->getContextPath());
        }
    }

    /**
     * @param bool $inBackend
     * @return ContextParameters
     */
    protected function createContextParameters(bool $inBackend): ContextParameters
    {
        return new ContextParameters($this->now, $this->securityContext->getRoles(), $inBackend, $inBackend, $inBackend);
    }

    /**
     * @param ContentQuery $contentQuery
     * @param ContentSubgraphInterface $subgraph
     * @param ContextParameters $contextParameters
     * @return ContentContext
     */
    protected function createContentContext(ContentQuery $contentQuery, ContentSubgraphInterface $subgraph, ContextParameters $contextParameters, Site $site): ContentContext
    {
        return new ContentContext(
            (string)$contentQuery->getWorkspaceName(),
            $contextParameters->getCurrentDateTime(),
            $subgraph->getDimensionSpacePoint()->toLegacyDimensionArray(),
            $subgraph->getDimensionSpacePoint()->getCoordinates(),
            $contextParameters->isInvisibleContentShown(),
            $contextParameters->isRemovedContentShown(),
            $contextParameters->isInaccessibleContentShown(),
            $contentQuery->getRootNodeIdentifier(),
            $subgraph,
            $contextParameters,
            $site,
            null
        );
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
                throw new NodeNotFoundException(sprintf('The node with context path "%s" could not be resolved', $nodeContextPath), 1437051934);
            }
            $this->view->assign('value', $node);
        }

        if (($affectedNodeContextPath = $this->request->getInternalArgument('__affectedNodeContextPath')) !== null) {
            $this->response->setHeader('X-Neos-AffectedNodePath', $affectedNodeContextPath);
        }

        if (($fusionPath = $this->request->getInternalArgument('__fusionPath')) !== null) {
            $this->view->setFusionPath($fusionPath);
        }
    }

    /**
     * Handles redirects to shortcut targets in live rendering.
     *
     * @param NodeInterface $node
     * @return void
     * @throws NodeNotFoundException|UnresolvableShortcutException
     */
    protected function handleShortcutNode(NodeInterface $node)
    {
        $resolvedNode = $this->nodeShortcutResolver->resolveShortcutTarget($node);
        if ($resolvedNode === null) {
            throw new NodeNotFoundException(sprintf('The shortcut node target of node "%s" could not be resolved', $node->getPath()), 1430218730);
        } elseif (is_string($resolvedNode)) {
            $this->redirectToUri($resolvedNode);
        } elseif ($resolvedNode instanceof NodeInterface && $resolvedNode === $node) {
            throw new NodeNotFoundException('The requested node does not exist or isn\'t accessible to the current user', 1502793585229);
        } elseif ($resolvedNode instanceof NodeInterface) {
            $this->redirect('show', null, null, ['node' => $resolvedNode]);
        } else {
            throw new UnresolvableShortcutException(sprintf('The shortcut node target of node "%s" resolves to an unsupported type "%s"', $node->getPath(),
                is_object($resolvedNode) ? get_class($resolvedNode) : gettype($resolvedNode)), 1430218738);
        }
    }
}
