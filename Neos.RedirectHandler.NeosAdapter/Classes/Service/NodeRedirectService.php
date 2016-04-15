<?php
namespace Neos\RedirectHandler\NeosAdapter\Service;

/*
 * This file is part of the Neos.RedirectHandler.NeosAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\RedirectHandler\Storage\RedirectStorageInterface;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Exception\NoMatchingRouteException;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Neos\Domain\Model\Domain;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Routing\Exception;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\ContextFactory;

/**
 * Service that creates redirects for moved / deleted nodes.
 *
 * Note: This is usually invoked by a signal emitted by Workspace::publishNode()
 *
 * @Flow\Scope("singleton")
 */
class NodeRedirectService implements NodeRedirectServiceInterface
{
    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @Flow\Inject
     * @var RedirectStorageInterface
     */
    protected $redirectStorage;

    /**
     * @Flow\Inject
     * @var RouterCachingService
     */
    protected $routerCachingService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var ContextFactory
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\InjectConfiguration(path="statusCode", package="Neos.RedirectHandler")
     * @var array
     */
    protected $defaultStatusCode;

    /**
     * {@inheritdoc}
     */
    public function createRedirectionsForPublishedNode(NodeInterface $node, Workspace $targetWorkspace)
    {
        $nodeType = $node->getNodeType();
        if ($targetWorkspace->getName() !== 'live' || !$nodeType->isOfType('TYPO3.Neos:Document')) {
            return;
        }

        $context = $this->contextFactory->create([
            'workspaceName' => 'live',
            'invisibleContentShown' => true,
            'dimensions' => $node->getDimensions()
        ]);
        $targetNode = $context->getNodeByIdentifier($node->getIdentifier());
        if ($targetNode === null) {
            // The page has been added
            return;
        }

        $targetNodeUriPath = $this->buildUriPathForNodeContextPath($targetNode->getContextPath());
        if ($targetNodeUriPath === null) {
            throw new Exception('The target URI path of the node could not be resolved', 1451945358);
        }

        $hosts = $this->getHostPatterns($node->getContext());

        // The page has been removed
        if ($node->isRemoved()) {
            $this->flushRoutingCacheForNode($targetNode);
            $statusCode = (integer)$this->defaultStatusCode['gone'];
            $this->redirectStorage->addRedirection($targetNodeUriPath, '', $statusCode, $hosts);
            return;
        }

        // compare the "old" node URI to the new one
        $nodeUriPath = $this->buildUriPathForNodeContextPath($node->getContextPath());
        // use the same regexp than the ContentContextBar Ember View
        $nodeUriPath = preg_replace('/@[A-Za-z0-9;&,\-_=]+/', '', $nodeUriPath);
        if ($nodeUriPath === null || $nodeUriPath === $targetNodeUriPath) {
            // The page node path has not been changed
            return;
        }


        $this->flushRoutingCacheForNode($targetNode);
        $statusCode = (integer)$this->defaultStatusCode['redirect'];
        $this->redirectStorage->addRedirection($targetNodeUriPath, $nodeUriPath, $statusCode, $hosts);
        /** @var ContentContext $contentContext */


        $q = new FlowQuery([$node]);
        foreach ($q->children('[instanceof TYPO3.Neos:Document]') as $childrenNode) {
            $this->createRedirectionsForPublishedNode($childrenNode, $targetWorkspace);
        }
    }

    /**
     * @param ContentContext $contentContext
     * @return array
     */
    protected function getHostPatterns(ContentContext $contentContext)
    {
        $site = $contentContext->getCurrentSite();
        $domains = [];
        if ($site !== null) {
            foreach ($site->getDomains() as $domain) {
                /** @var Domain $domain */
                $domains[] = $domain->getHostPattern();
            }
        }
        return $domains;
    }

    /**
     * Removes all routing cache entries for the given $nodeData
     *
     * @param NodeInterface $node
     * @return void
     */
    protected function flushRoutingCacheForNode(NodeInterface $node)
    {
        $nodeData = $node->getNodeData();
        $nodeDataIdentifier = $this->persistenceManager->getIdentifierByObject($nodeData);
        if ($nodeDataIdentifier === null) {
            return;
        }
        $this->routerCachingService->flushCachesByTag($nodeDataIdentifier);
    }

    /**
     * Creates a (relative) URI for the given $nodeContextPath removing the "@workspace-name" from the result
     *
     * @param string $nodeContextPath
     * @return string the resulting (relative) URI or NULL if no route could be resolved
     */
    protected function buildUriPathForNodeContextPath($nodeContextPath)
    {
        try {
            return $this->getUriBuilder()
                ->uriFor('show', ['node' => $nodeContextPath], 'Frontend\\Node', 'TYPO3.Neos');
        } catch (NoMatchingRouteException $exception) {
            return null;
        }
    }

    /**
     * Creates an UriBuilder instance for the current request
     *
     * @return UriBuilder
     */
    protected function getUriBuilder()
    {
        if ($this->uriBuilder === null) {
            $httpRequest = Request::createFromEnvironment();
            $actionRequest = new ActionRequest($httpRequest);
            $this->uriBuilder = new UriBuilder();
            $this->uriBuilder
                ->setRequest($actionRequest);
            $this->uriBuilder
                ->setFormat('html')
                ->setCreateAbsoluteUri(false);
        }
        return $this->uriBuilder;
    }
}
