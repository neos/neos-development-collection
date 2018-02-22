<?php
namespace Neos\Neos\Routing;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Domain\Projection\Content\HierarchyTraversalDirection;
use Neos\ContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeConstraints;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Mvc\Routing\DynamicRoutePart;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\ParameterAwareRoutePartInterface;
use Neos\Flow\Security\Context;
use Neos\Neos\Domain\Context\Content\ContentQuery;
use Neos\Neos\Domain\Context\Content\Exception\InvalidContentQuerySerializationException;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Neos\Http\ContentSubgraphUriProcessor;

/**
 * A route part handler for finding nodes specifically in the website's frontend.
 */
class FrontendNodeRoutePartHandler extends DynamicRoutePart implements FrontendNodeRoutePartHandlerInterface, ParameterAwareRoutePartInterface
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
     * @var ContentSubgraphUriProcessor
     */
    protected $contentSubgraphUriProcessor;


    const DIMENSION_REQUEST_PATH_MATCHER = '|^
        (?<firstUriPart>[^/@]+)                    # the first part of the URI, before the first slash, may contain the encoded dimension preset
        (?:                                        # start of non-capturing submatch for the remaining URL
            /?                                     # a "/"; optional. it must also match en@user-admin
            (?<remainingRequestPath>.*)            # the remaining request path
        )?                                         # ... and this whole remaining URL is optional
        $                                          # make sure we consume the full string
    |x';

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
        /** @var Node $matchingNode */
        $matchingNode = null;
        /** @var Node $matchingSite */
        $matchingSite = null;
        $tagArray = [];

        $this->securityContext->withoutAuthorizationChecks(function () use (&$matchingNode, &$matchingSite, $requestPath, &$tagArray) {
            // fetch subgraph explicitly without authorization checks because the security context isn't available yet
            // anyway and any Entity Privilege targeted on Workspace would fail at this point:
            $matchingSubgraph = $this->fetchSubgraphForParameters($requestPath);

            /** @var Node $matchingSite */
            $matchingSite = $this->fetchSiteFromRequest($matchingSubgraph, $requestPath);
            $tagArray[] = (string) $matchingSite->identifier;
            if ($requestPath === '') {
                $matchingNode = $matchingSite;

                return;
            }

            $matchingNode = $this->fetchNodeForRequestPath($matchingSubgraph, $matchingSite, $requestPath, $tagArray);
        });
        if (!$matchingNode) {
            return false;
        }
        if ($this->onlyMatchSiteNodes() && !$matchingNode->getNodeType()->isOfType('Neos.Neos:Site')) {
            throw new Exception\NoHomepageException('Homepage could not be loaded. Probably you haven\'t imported a site yet', 1346950755);
        }

        return new MatchResult((string) new ContentQuery(
            $matchingNode->aggregateIdentifier,
            $this->getWorkspaceNameFromParameters(),
            $this->getDimensionSpacePointFromParameters(),
            $matchingSite->aggregateIdentifier
        ), RouteTags::createFromArray($tagArray));
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
     * @throws Exception\NoSuchNodeException
     */
    protected function fetchNodeForRequestPath(ContentSubgraphInterface $subgraph, Node $site, string $requestPath, array &$tagArray): NodeInterface
    {
        $remainingUriPathSegments = explode('/', $requestPath);

        $subgraph->traverseHierarchy($site, HierarchyTraversalDirection::down(), new NodeTypeConstraints(['includeNodeTypes' => ['Neos.Neos:Document']]), function (Node $node) use (&$remainingUriPathSegments, &$matchingNode, &$tagArray) {
            $currentPathSegment = array_shift($remainingUriPathSegments);
            $continueTraversal = false;
            if ($node->getProperty('uriPathSegment') === $currentPathSegment) {
                $tagArray[] = (string) $node->identifier;
                if (empty($remainingUriPathSegments)) {
                    $matchingNode = $node;
                } else {
                    $continueTraversal = true;
                }
            }

            return $continueTraversal;
        });

        if (!$matchingNode instanceof NodeInterface) {
            throw new Exception\NoSuchNodeException(sprintf('No node found on request path "%s"', $requestPath), 1346949857);
        }

        return $matchingNode;
    }

    /**
     * @param string $requestPath
     * @return ContentSubgraphInterface
     * @throws Exception\NoWorkspaceException
     */
    protected function fetchSubgraphForParameters(string $requestPath): ContentSubgraphInterface
    {
        $workspace = $this->workspaceFinder->findOneByName($this->getWorkspaceNameFromParameters());
        if (!$workspace) {
            throw new Exception\NoWorkspaceException(sprintf('No workspace found for request path "%s"', $requestPath), 1346949318);
        }

        return $this->contentGraph->getSubgraphByIdentifier(
            $workspace->getCurrentContentStreamIdentifier(),
            $this->getDimensionSpacePointFromParameters()
        );
    }

    /**
     * @param ContentSubgraphInterface $contentSubgraph
     * @param string $requestPath
     * @return NodeInterface
     * @throws Exception\NoSiteException
     */
    protected function fetchSiteFromRequest(ContentSubgraphInterface $contentSubgraph, string $requestPath): NodeInterface
    {
        /** @var Node $sites */
        $sites = $this->contentGraph->findRootNodeByType(new NodeTypeName('Neos.Neos:Sites'), new NodeName('sites'));
        /** @var Node $site */
        $domain = $this->domainRepository->findOneByActiveRequest();
        if ($domain) {
            $site = $contentSubgraph->findChildNodeConnectedThroughEdgeName(
                $sites->identifier,
                new NodeName($domain->getSite()->getNodeName())
            );
        } else {
            $site = $contentSubgraph->findChildNodes($sites->identifier, null, 1)[0] ?? null;
        }

        if (!$site) {
            throw new Exception\NoSiteException(sprintf('No site found for request path "%s"', $requestPath), 1346949693);
        }

        return $site;
    }

    /**
     * @return WorkspaceName
     */
    protected function getWorkspaceNameFromParameters(): WorkspaceName
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
     * Checks, whether given value is a ContentQuery object and if so, sets $this->value to the respective route path.
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
     * @throws \Neos\Neos\Http\Exception\InvalidContentDimensionValueUriProcessorException
     * @throws \Exception
     */
    protected function resolveValue($node)
    {
        $contentQuery = $node;
        if (!$contentQuery instanceof ContentQuery && !is_string($contentQuery)) {
            return false;
        }

        if (is_string($contentQuery)) {
            try {
                $contentQuery = ContentQuery::fromJson($contentQuery);
            } catch (InvalidContentQuerySerializationException $exception) {
                $this->exceptionLogger->logThrowable($exception);
                return false;
            }
        }
        /** @var ContentQuery $contentQuery */

        $workspace = $this->workspaceFinder->findOneByName($contentQuery->getWorkspaceName());

        $subgraph = $this->contentGraph->getSubgraphByIdentifier($workspace->getCurrentContentStreamIdentifier(), $contentQuery->getDimensionSpacePoint());
        $node = $subgraph->findNodeByNodeAggregateIdentifier($contentQuery->getNodeAggregateIdentifier());

        if (!$node->getNodeType()->isOfType('Neos.Neos:Document')) {
            return false;
        }

        $isSiteNode = $node->getNodeType()->isOfType('Neos.Neos:Site');
        if ($this->onlyMatchSiteNodes() && !$isSiteNode) {
            return false;
        }

        $routePath = $isSiteNode ? '/' : $this->getRequestPathByNode($subgraph, $node);
        if (!$contentQuery->getWorkspaceName()->isLive()) {
            $routePath .= ContentSubgraphBackendRouteSuffix::fromWorkspaceAndDimensionSpacePoint($workspace->getWorkspaceName(), $subgraph->getDimensionSpacePoint());
        }
        $uriConstraints = $this->contentSubgraphUriProcessor->resolveDimensionUriConstraints($contentQuery, $isSiteNode);

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
        if ($node->getNodeType()->isOfType('Neos.Neos:Site')) {
            return '';
        }

        // To allow building of paths to non-hidden nodes beneath hidden nodes, we assume
        // the input node is allowed to be seen and we must generate the full path here.
        // To disallow showing a node actually hidden itself has to be ensured in matching
        // a request path, not in building one.
        $requestPathSegments = [];
        $this->securityContext->withoutAuthorizationChecks(function() use($contentSubgraph, $node, &$requestPathSegments) {
            $contentSubgraph->traverseHierarchy($node, HierarchyTraversalDirection::up(), new NodeTypeConstraints(['includeNodeTypes' => ['Neos.Neos:Document']]), function(NodeInterface $node) {
                if (!$node->hasProperty('uriPathSegment')) {
                    throw new Exception\MissingNodePropertyException(sprintf('Missing "uriPathSegment" property for node "%s". Nodes can be migrated with the "flow node:repair" command.', $node->getPath()), 1415020326);
                }
                $requestPathSegments[] = $node->getProperty('uriPathSegment');
                return true;
            });
        });

        return implode('/', array_reverse($requestPathSegments));
    }
}
