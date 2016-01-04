<?php
namespace TYPO3\Neos\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Redirection\RedirectionService;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Mvc\Exception\NoMatchingRouteException;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

/**
 * Service that creates redirections for moved / deleted nodes.
 * Note: This is usually invoked by a signal emitted by Workspace::publishNode()
 *
 * @Flow\Scope("singleton")
 */
class NodeRedirectionService
{
    const PATTERN_NODEURIPATH = '/^(?P<NodePath>(?:\/?[a-z0-9\-]+)(?:\/[a-z0-9\-]+)*)?(?:@(?P<WorkspaceName>[a-z0-9\-]+))?(?P<NodePathSuffix>\.[a-z0-9\-]*)?$/i';

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
     * @var RedirectionService
     */
    protected $redirectionService;

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
     * Creates a redirection for the node if it is a 'TYPO3.Neos:Document' node and its URI has changed
     *
     * @param NodeInterface $node The node that is about to be published
     * @param Workspace $targetWorkspace
     * @return void
     */
    public function createRedirectionsForPublishedNode(NodeInterface $node, Workspace $targetWorkspace)
    {
        // We only care about "live" workspace
        if ($targetWorkspace->getName() !== 'live') {
            return;
        }
        // skip nodes that are not of type "document" (pages)
        $nodeType = $node->getNodeType();
        if (!$nodeType->isOfType('TYPO3.Neos:Document')) {
            return;
        }

        $liveWorkspace = $node->getWorkspace()->getBaseWorkspace();
        $targetNodeData = $this->nodeDataRepository->findOneByIdentifier($node->getIdentifier(), $liveWorkspace);
        // The page has been added
        if ($targetNodeData === null) {
            return;
        }

        $targetNodeUriPath = $this->buildUriPathForNodeContextPath($targetNodeData->getContextPath());
        // The target URI path of the node could not be resolved (this should never happen, if routes are correct)
        if ($targetNodeUriPath === null) {
            return;
        }

        // The page has been removed
        if ($node->isRemoved()) {
            $this->flushRoutingCacheForNodeData($targetNodeData);
            $this->redirectionService->addRedirection($targetNodeUriPath, '', 410);
            return;
        }

        // compare the "old" node URI to the new one
        $nodeUriPath = $this->buildUriPathForNodeContextPath($node->getContextPath());
        if ($nodeUriPath === null || $nodeUriPath === $targetNodeUriPath) {
            // The page node path has not been changed
            return;
        }

        $this->flushRoutingCacheForNodeData($targetNodeData);
        $this->redirectionService->addRedirection($targetNodeUriPath, $nodeUriPath);
    }

    /**
     * Removes all routing cache entries for the given $nodeData
     *
     * @param NodeData $nodeData
     * @return void
     */
    protected function flushRoutingCacheForNodeData(NodeData $nodeData)
    {
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
            $uri = $this->getUriBuilder()
                ->uriFor('show', ['node' => $nodeContextPath], 'Frontend\\Node', 'TYPO3.Neos');
        } catch (NoMatchingRouteException $exception) {
            return null;
        }
        return preg_replace(self::PATTERN_NODEURIPATH, '$1$3', $uri);
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
            $actionRequest = $httpRequest->createActionRequest();
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
