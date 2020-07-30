<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\EventSourcedRouting;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeTreeTraversalHelper;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Http\ContentSubgraphUriProcessor;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\Routing\WorkspaceNameAndDimensionSpacePointForUriSerialization;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Mvc\Routing\DynamicRoutePart;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\ParameterAwareRoutePartInterface;
use Neos\Flow\Security\Context;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Routing\Exception\MissingNodePropertyException;
use Neos\Neos\Routing\Exception\NoSiteException;
use Neos\Neos\Routing\Exception\NoSuchNodeException;
use Neos\Neos\Routing\Exception\NoWorkspaceException;
use Neos\Neos\Routing\FrontendNodeRoutePartHandlerInterface;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 */
class EventSourcedFrontendNodeRoutePartHandler extends DynamicRoutePart implements FrontendNodeRoutePartHandlerInterface, ParameterAwareRoutePartInterface
{
    /**
     * @Flow\Inject
     * @var ThrowableStorageInterface
     */
    protected $exceptionLogger;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

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
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var ContentSubgraphUriProcessor
     */
    protected $contentSubgraphUriProcessor;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

    /**
     * Extracts the node path from the request path.
     *
     * @param string $requestPath The request path to be matched
     * @return string value to match, or an empty string if $requestPath is empty or split string was not found
     */
    protected function findValueToMatch($requestPath)
    {
        if ($this->splitString !== '') {
            $splitStringPosition = strpos($requestPath, $this->splitString);
            if ($splitStringPosition !== false) {
                return substr($requestPath, 0, $splitStringPosition);
            }
        }

        return $requestPath;
    }

    /**
     * Matches a frontend URI pointing to a node (for example a page).
     *
     * This function tries to find a matching node by the given request path. If one was found, its
     * absolute context node path is set in $this->value and true is returned.
     *
     * Note that this matcher does not check if access to the resolved workspace or node is allowed because at the point
     * in time the route part handler is invoked, the security framework is not yet fully initialized.
     *
     * @param string $requestPath The request path (without leading "/", relative to the current Site Node)
     * @return bool|MatchResult An instance of MatchResult if value could be matched successfully, otherwise false.
     * @throws \Exception
     * @throws Exception\NoHomepageException if no node could be found on the homepage (empty $requestPath)
     */
    protected function matchValue($requestPath)
    {
        if ($this->onlyMatchSiteNodes() && \mb_substr_count($requestPath, '/') > $this->getUriPathSegmentOffset()) {
            return false;
        }
        /** @var NodeInterface $matchingRootNode */
        $matchingRootNode = null;
        /** @var NodeInterface $matchingNode */
        $matchingNode = null;
        /** @var NodeInterface $matchingSite */
        $matchingSite = null;
        /** @var ContentSubgraphInterface $matchingSubgraph */
        $matchingSubgraph = null;
        $tagArray = [];

        $this->securityContext->withoutAuthorizationChecks(function () use (&$matchingRootNode, &$matchingNode, &$matchingSite, &$matchingSubgraph, $requestPath, &$tagArray) {
            // fetch subgraph explicitly without authorization checks because the security context isn't available yet
            // anyway and any Entity Privilege targeted on Workspace would fail at this point:
            $matchingSubgraph = $this->fetchSubgraphForParameters($requestPath);
            $sitesNodeAggregate = $this->contentGraph->findRootNodeAggregateByType($matchingSubgraph->getContentStreamIdentifier(), NodeTypeName::fromString('Neos.Neos:Sites'));
            $matchingRootNode = $matchingSubgraph->findNodeByNodeAggregateIdentifier($sitesNodeAggregate->getIdentifier());
            if (!$matchingRootNode) {
                return;
            }

            $matchingSite = $this->fetchSiteFromRequest($matchingRootNode, $matchingSubgraph, $requestPath);
            $tagArray[] = (string)$matchingSite->getNodeAggregateIdentifier();

            if ($requestPath === '' || substr($requestPath, 0, 1) === '@') {
                // if the request path is:
                // - "" (empty string): we are at the homepage in the live WS
                // - "@...": we are at the homepage in a user workspace
                $matchingNode = $matchingSite;
                return;
            }

            $matchingNode = $this->fetchNodeForRequestPath($matchingSubgraph, $matchingSite, $requestPath, $tagArray);
        });
        if (!$matchingNode) {
            return false;
        }
        if ($this->onlyMatchSiteNodes()) {
            // if we only want to match Site nodes, we need to go one level up and need to find the "Neos.Neos:Sites" node.
            // Note this operation is usually fast because it is served from the ContentSubgraph's in-memory Cache
            $parentOfMatchingNode = $matchingSubgraph->findParentNode($matchingNode->getNodeAggregateIdentifier());
            $parentOfMatchingNodeIsSitesNode = $parentOfMatchingNode !== null && $parentOfMatchingNode->getNodeType()->isOfType('Neos.Neos:Sites');
            if (!$parentOfMatchingNodeIsSitesNode) {
                return false;
            }
        }

        $nodeAddress = new NodeAddress(
            $matchingSubgraph->getContentStreamIdentifier(),
            $matchingSubgraph->getDimensionSpacePoint(),
            $matchingNode->getNodeAggregateIdentifier(),
            $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($matchingSubgraph->getContentStreamIdentifier())->getWorkspaceName()
        );

        return new MatchResult($nodeAddress->serializeForUri(), RouteTags::createFromArray($tagArray));
    }

    /**
     * Fetches the node from the given subgraph matching the given request path segments via the traversed uriPathSegment properties.
     * In the process, the tag array is populated with the traversed nodes' (the fetched node and its parents) identifiers.
     *
     * @param ContentSubgraphInterface $subgraph
     * @param Node $site
     * @param string $requestPath
     * @param array $tagArray
     * @return NodeInterface
     * @throws NoSuchNodeException
     */
    protected function fetchNodeForRequestPath(ContentSubgraphInterface $subgraph, NodeInterface $site, string $requestPath, array &$tagArray): ?NodeInterface
    {
        $remainingUriPathSegments = explode('/', $requestPath);
        $remainingUriPathSegments = array_slice($remainingUriPathSegments, $this->getUriPathSegmentOffset());
        $matchingNode = null;
        $documentNodeTypeFilter = $this->nodeTypeConstraintFactory->parseFilterString('Neos.Neos:Document');

        $currentNode = $site;
        foreach ($remainingUriPathSegments as $currentPathSegment) {
            $pivot = \mb_strpos($currentPathSegment, '@');
            if ($pivot !== false) {
                $currentPathSegment = \mb_substr($currentPathSegment, 0, $pivot);
            }

            $childNodes = $subgraph->findChildNodes($currentNode->getNodeAggregateIdentifier(), $documentNodeTypeFilter);
            $currentNode = self::findChildNodeWithMatchingPathSegment($childNodes, $currentPathSegment);
            if ($currentNode === null) {
                return null;
            }
            $tagArray[] = (string)$currentNode->getNodeAggregateIdentifier();
        }

        return $currentNode;
    }

    /**
     * @param NodeInterface[] $childNodes
     * @param string $currentPathSegment
     * @return NodeInterface
     */
    protected static function findChildNodeWithMatchingPathSegment(array $childNodes, string $currentPathSegment): ?NodeInterface
    {
        foreach ($childNodes as $childNode) {
            if ($childNode->getProperty('uriPathSegment') === $currentPathSegment) {
                return $childNode;
            }
        }
        return null;
    }

    /**
     * @param string $requestPath
     * @return ContentSubgraphInterface
     * @throws NoWorkspaceException
     */
    protected function fetchSubgraphForParameters(string $requestPath): ContentSubgraphInterface
    {
        $workspace = $this->workspaceFinder->findOneByName($this->getWorkspaceNameFromParameters() ?: WorkspaceName::forLive());
        if (!$workspace) {
            throw new NoWorkspaceException(sprintf('No workspace found for request path "%s"', $requestPath), 1346949318);
        }

        // when we are in live workspace, we use the "frontend" visibility constraints,
        // to ensure we re-use the same subgraph as the frontend rendering; and also re-use
        // their in-memory runtime caches.
        // This is safe because in live workspace, only "visible" nodes can be routed to.
        $visibilityConstraints = $workspace->getWorkspaceName()->isLive() ? VisibilityConstraints::frontend() : VisibilityConstraints::withoutRestrictions();

        return $this->contentGraph->getSubgraphByIdentifier(
            $workspace->getCurrentContentStreamIdentifier(),
            $this->getDimensionSpacePointFromParameters(),
            $visibilityConstraints
        );
    }

    /**
     * @param NodeInterface $rootNode
     * @param ContentSubgraphInterface $contentSubgraph
     * @param string $requestPath
     * @return NodeInterface
     * @throws NoSiteException
     */
    protected function fetchSiteFromRequest(NodeInterface $rootNode, ContentSubgraphInterface $contentSubgraph, string $requestPath): NodeInterface
    {
        $domain = $this->domainRepository->findOneByActiveRequest();
        if ($domain) {
            $site = $contentSubgraph->findChildNodeConnectedThroughEdgeName(
                $rootNode->getNodeAggregateIdentifier(),
                NodeName::fromString($domain->getSite()->getNodeName())
            );
        } else {
            $site = $contentSubgraph->findChildNodes($rootNode->getNodeAggregateIdentifier())[0] ?? null;
        }

        if (!$site) {
            throw new NoSiteException(sprintf('No site found for request path "%s"', $requestPath), 1346949693);
        }

        return $site;
    }

    /**
     * @return WorkspaceName
     */
    protected function getWorkspaceNameFromParameters(): ?WorkspaceName
    {
        return $this->parameters->getValue('workspaceName');
    }

    /**
     * @return DimensionSpacePoint
     */
    protected function getDimensionSpacePointFromParameters(): DimensionSpacePoint
    {
        return $this->parameters->getValue('dimensionSpacePoint');
    }

    /**
     * @return int
     */
    protected function getUriPathSegmentOffset(): ?int
    {
        return $this->parameters->getValue('uriPathSegmentOffset');
    }

    /**
     * Checks, whether given value is a NodeAddress object and if so, sets $this->value to the respective route path.
     *
     * In order to render a suitable frontend URI, this function strips off the path to the site node and only keeps
     * the actual node path relative to that site node. In practice this function would set $this->value as follows:
     *
     * absolute node path: /sites/neostypo3org/homepage/about
     * $this->value:       homepage/about
     *
     * absolute node path: /sites/neostypo3org/homepage/about@user-admin
     * $this->value:       homepage/about@user-admin
     *
     * @param $node
     * @return boolean|ResolveResult if value could be resolved successfully, otherwise false.
     * @throws \Exception
     */
    protected function resolveValue($node)
    {
        $nodeAddress = $node;
        $nodeAddressIsCorrectType = $nodeAddress instanceof NodeAddress || is_string($nodeAddress);
        if (!$nodeAddressIsCorrectType) {
            return false;
        }

        if (is_string($nodeAddress)) {
            try {
                $nodeAddress = $this->nodeAddressFactory->createFromUriString($nodeAddress);
            } catch (\Throwable $exception) {
                $this->exceptionLogger->logThrowable($exception);

                return false;
            }
        }
        /** @var NodeAddress $nodeAddress */
        // when we are in live workspace, we use the "frontend" visibility constraints,
        // to ensure we re-use the same subgraph as the frontend rendering; and also re-use
        // their in-memory runtime caches.
        // This is safe because in live workspace, only "visible" nodes can be routed to.
        $visibilityConstraints = $nodeAddress->isInLiveWorkspace() ? VisibilityConstraints::frontend() : VisibilityConstraints::withoutRestrictions();


        $subgraph = $this->contentGraph->getSubgraphByIdentifier($nodeAddress->getContentStreamIdentifier(), $nodeAddress->getDimensionSpacePoint(), $visibilityConstraints);
        $node = $subgraph->findNodeByNodeAggregateIdentifier($nodeAddress->getNodeAggregateIdentifier());

        if ($node === null) {
            return false;
        }

        if (!$node->getNodeType()->isOfType('Neos.Neos:Document')) {
            return false;
        }

        $parentNode = $subgraph->findParentNode($node->getNodeAggregateIdentifier());
        // a node is a site node if the node above it it Neos.Neos:Sites
        $isSiteNode = $parentNode && $parentNode->getNodeType()->isOfType('Neos.Neos:Sites');
        if ($this->onlyMatchSiteNodes() && !$isSiteNode) {
            return false;
        }

        $routePath = $isSiteNode ? '' : $this->getRequestPathByNode($subgraph, $node);

        if (!$nodeAddress->isInLiveWorkspace()) {
            $workspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($nodeAddress->getContentStreamIdentifier());
            if ($workspace) {
                $routePath .= WorkspaceNameAndDimensionSpacePointForUriSerialization::fromWorkspaceAndDimensionSpacePoint($workspace->getWorkspaceName(), $subgraph->getDimensionSpacePoint())->toBackendUriSuffix();
            } else {
                throw new \Exception("TODO: Workspace not found for CS " . $nodeAddress->getContentStreamIdentifier());
            }
        }
        $uriConstraints = $this->contentSubgraphUriProcessor->resolveDimensionUriConstraints($nodeAddress, $isSiteNode);

        return new ResolveResult($routePath, $uriConstraints);
    }

    /**
     * Whether the current route part should only match/resolve site nodes (e.g. the homepage)
     *
     * @return boolean
     */
    protected function onlyMatchSiteNodes(): bool
    {
        return $this->options['onlyMatchSiteNodes'] ?? false;
    }

    /**
     * Renders a request path based on the "uriPathSegment" properties of the nodes leading to the given node.
     *
     * @param ContentSubgraphInterface $contentSubgraph
     * @param NodeInterface $node The node where the generated path should lead to
     * @return string A relative request path
     * @throws \Exception
     */
    protected function getRequestPathByNode(ContentSubgraphInterface $contentSubgraph, NodeInterface $node)
    {
        // if the parent is the "sites" node, we return the empty URL.
        $parentNode = $contentSubgraph->findParentNode($node->getNodeAggregateIdentifier());
        if ($parentNode->getNodeType()->isOfType('Neos.Neos:Sites')) {
            return '';
        }

        // To allow building of paths to non-hidden nodes beneath hidden nodes, we assume
        // the input node is allowed to be seen and we must generate the full path here.
        // To disallow showing a node actually hidden itself has to be ensured in matching
        // a request path, not in building one.
        $requestPathSegments = [];
        $this->securityContext->withoutAuthorizationChecks(function () use ($contentSubgraph, $node, &$requestPathSegments) {
            NodeTreeTraversalHelper::traverseUpUntilCondition(
                $contentSubgraph,
                $node,
                function (NodeInterface $node) use (&$requestPathSegments, $contentSubgraph) {
                    if (!$node->hasProperty('uriPathSegment')) {
                        throw new MissingNodePropertyException(sprintf(
                            'Missing "uriPathSegment" property for node "%s". Nodes can be migrated with the "flow node:repair" command.',
                            $node->getNodeAggregateIdentifier()
                        ), 1415020326);
                    }

                    $parentNode = $contentSubgraph->findParentNode($node->getNodeAggregateIdentifier());
                    if ($parentNode->getNodeType()->isOfType('Neos.Neos:Sites')) {
                        // do not traverse further up than the Site node (which is one level beneath the "Sites" node.
                        return false;
                    }

                    $requestPathSegments[] = $node->getProperty('uriPathSegment');
                    return true;
                }
            );
        });

        return implode('/', array_reverse($requestPathSegments));
    }
}
